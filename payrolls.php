<?php
// PHẦN 1: TOÀN BỘ LOGIC XỬ LÝ (ĐƯỢC ĐƯA LÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

// Kiểm tra vai trò trước khi xử lý bất cứ điều gì
check_login();
check_role(['admin','hr']);

// Xử lý thêm bảng lương (Không thay đổi)
if(isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO payrolls (employee_id, period_from, period_to, gross_salary, net_salary, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['period_from'],
        $_POST['period_to'],
        $_POST['gross_salary'],
        $_POST['net_salary'],
        $_POST['status']
    ]);
    header("Location: payrolls.php?msg=added");
    exit;
}
// Xử lý sửa bảng lương (Không thay đổi)
if(isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE payrolls SET employee_id=?, period_from=?, period_to=?, gross_salary=?, net_salary=?, status=? WHERE id=?");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['period_from'],
        $_POST['period_to'],
        $_POST['gross_salary'],
        $_POST['net_salary'],
        $_POST['status'],
        $_POST['id']
    ]);
    header("Location: payrolls.php?msg=updated");
    exit;
}

// Xử lý xóa (Không thay đổi)
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM payrolls WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: payrolls.php?msg=deleted");
    exit;
}

// Xử lý tạo bảng lương tự động
if (isset($_POST['generate_auto'])) {
    $period_from = $_POST['period_from_auto'] ?? null;
    $period_to = $_POST['period_to_auto'] ?? null;
    $overtime_multiplier = isset($_POST['overtime_multiplier']) ? (float) $_POST['overtime_multiplier'] : 1.5;

    unset($_SESSION['auto_error'], $_SESSION['auto_summary']);

    try {
        if (!$period_from || !$period_to) {
            throw new RuntimeException('Vui lòng chọn đầy đủ khoảng thời gian.');
        }

        if (new DateTime($period_from) > new DateTime($period_to)) {
            throw new RuntimeException('"Từ ngày" phải nhỏ hơn hoặc bằng "Đến ngày".');
        }

        $pdo->beginTransaction();

        $employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name")
                         ->fetchAll(PDO::FETCH_ASSOC);

        $summary = [];
        $generated = 0;

        $attendanceStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN clock_in IS NOT NULL AND clock_out IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, clock_in, clock_out) ELSE 0 END), 0) AS actual_minutes,
                COALESCE(SUM(COALESCE(s.hours, 0) * 60), 0) AS scheduled_minutes,
                COALESCE(SUM(CASE WHEN clock_in IS NOT NULL AND clock_out IS NOT NULL
                    THEN GREATEST(TIMESTAMPDIFF(MINUTE, clock_in, clock_out) - COALESCE(s.hours,0) * 60, 0) ELSE 0 END), 0) AS overtime_minutes
            FROM attendance a
            LEFT JOIN shifts s ON a.shift_id = s.id
            WHERE a.employee_id = ? AND a.date BETWEEN ? AND ?
        ");

        $allowanceStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM allowances WHERE employee_id = ? AND allowance_date BETWEEN ? AND ?");
        $rewardStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM hr_actions WHERE employee_id = ? AND type = 'reward' AND date BETWEEN ? AND ?");
        $disciplineStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM hr_actions WHERE employee_id = ? AND type = 'discipline' AND date BETWEEN ? AND ?");
        $contractStmt = $pdo->prepare("SELECT salary_base FROM contracts WHERE employee_id = ? AND (status = 'active' OR end_date IS NULL OR end_date >= ?) ORDER BY start_date DESC LIMIT 1");
        $existingStmt = $pdo->prepare("SELECT id FROM payrolls WHERE employee_id = ? AND period_from = ? AND period_to = ?");

        foreach ($employees as $emp) {
            $contractStmt->execute([$emp['id'], $period_from]);
            $contract = $contractStmt->fetch(PDO::FETCH_ASSOC);
            if (!$contract) {
                continue; // Bỏ qua nhân viên chưa có hợp đồng hợp lệ
            }

            $base_salary = (float) $contract['salary_base'];

            $attendanceStmt->execute([$emp['id'], $period_from, $period_to]);
            $attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

            $actual_minutes = (float) ($attendance['actual_minutes'] ?? 0);
            $scheduled_minutes = (float) ($attendance['scheduled_minutes'] ?? 0);
            $overtime_minutes = (float) ($attendance['overtime_minutes'] ?? 0);

            $working_hours = round($actual_minutes / 60, 2);
            $scheduled_hours = $scheduled_minutes > 0 ? $scheduled_minutes / 60 : 0;
            $overtime_hours = round($overtime_minutes / 60, 2);

            if ($scheduled_minutes > 0) {
                $work_ratio = min($actual_minutes / $scheduled_minutes, 1);
                $base_salary_earned = round($base_salary * $work_ratio, 0);
            } else {
                $base_salary_earned = round($base_salary, 0);
                $scheduled_hours = $scheduled_hours > 0 ? $scheduled_hours : 208;
            }

            $hours_for_rate = $scheduled_hours > 0 ? $scheduled_hours : 208;
            $hourly_rate = $hours_for_rate > 0 ? $base_salary / $hours_for_rate : 0;
            $overtime_pay = round($hourly_rate * $overtime_hours * $overtime_multiplier, 0);

            $allowanceStmt->execute([$emp['id'], $period_from, $period_to]);
            $allowance_total = (float) $allowanceStmt->fetchColumn();

            $rewardStmt->execute([$emp['id'], $period_from, $period_to]);
            $reward_total = (float) $rewardStmt->fetchColumn();

            $disciplineStmt->execute([$emp['id'], $period_from, $period_to]);
            $discipline_total = (float) $disciplineStmt->fetchColumn();

            $gross_salary = $base_salary_earned + $overtime_pay + $allowance_total + $reward_total;
            $net_salary = max($gross_salary - $discipline_total, 0);

            $notes = sprintf(
                'Tạo tự động %s | Hệ số tăng ca %.2f',
                date('Y-m-d H:i'),
                $overtime_multiplier
            );

            $existingStmt->execute([$emp['id'], $period_from, $period_to]);
            $existing_id = $existingStmt->fetchColumn();

            if ($existing_id) {
                $updateStmt = $pdo->prepare("UPDATE payrolls SET gross_salary=?, net_salary=?, status=?, base_salary_amount=?, working_hours=?, overtime_hours=?, overtime_pay=?, allowance_total=?, reward_total=?, discipline_total=?, notes=? WHERE id=?");
                $updateStmt->execute([
                    $gross_salary,
                    $net_salary,
                    'finalized',
                    $base_salary_earned,
                    $working_hours,
                    $overtime_hours,
                    $overtime_pay,
                    $allowance_total,
                    $reward_total,
                    $discipline_total,
                    $notes,
                    $existing_id
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO payrolls (employee_id, period_from, period_to, gross_salary, net_salary, status, base_salary_amount, working_hours, overtime_hours, overtime_pay, allowance_total, reward_total, discipline_total, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $insertStmt->execute([
                    $emp['id'],
                    $period_from,
                    $period_to,
                    $gross_salary,
                    $net_salary,
                    'finalized',
                    $base_salary_earned,
                    $working_hours,
                    $overtime_hours,
                    $overtime_pay,
                    $allowance_total,
                    $reward_total,
                    $discipline_total,
                    $notes
                ]);
            }

            $generated++;
            $summary[] = [
                'employee' => $emp['full_name'],
                'net' => $net_salary,
                'hours' => $working_hours,
                'overtime_hours' => $overtime_hours
            ];
        }

        $pdo->commit();

        $_SESSION['auto_summary'] = [
            'count' => $generated,
            'period_from' => $period_from,
            'period_to' => $period_to,
            'details' => $summary
        ];

        header("Location: payrolls.php?msg=auto_generated");
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['auto_error'] = $e->getMessage();
        header("Location: payrolls.php?msg=auto_error");
        exit;
    }
}

// PHẦN 2: CHỈ HIỂN THỊ HTML SAU KHI LOGIC ĐÃ XONG
include "inc/header.php";

// PHẦN 3: LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$auto_summary = $_SESSION['auto_summary'] ?? null;
$auto_error = $_SESSION['auto_error'] ?? null;
unset($_SESSION['auto_summary'], $_SESSION['auto_error']);

$employees = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);

// *** THAY ĐỔI 1: Xử lý tìm kiếm và sắp xếp động ***
$search_term = $_GET['search'] ?? '';
$params = [];

// Bắt đầu câu SQL
$sql = "
    SELECT p.*, e.full_name, e.join_date 
    FROM payrolls p 
    LEFT JOIN employees e ON p.employee_id = e.id
";

// Thêm điều kiện TÌM KIẾM nếu có
if (!empty($search_term)) {
    $sql .= " WHERE e.full_name LIKE ?";
    $params[] = '%' . $search_term . '%';
}

// Thêm logic SẮP XẾP:
// 1. Ưu tiên ngày vào làm sớm nhất (ASC)
// 2. Nếu cùng ngày vào, ưu tiên lương thực lĩnh cao nhất (DESC)
$sql .= " ORDER BY e.join_date ASC, p.net_salary DESC";

// Thực thi câu lệnh
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý bảng lương</h2>

    <?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg']=='added'): ?>
    <div class="alert alert-success"> Thêm bảng lương thành công!</div>
    <?php elseif($_GET['msg']=='updated'): ?>
    <div class="alert alert-info"> Cập nhật bảng lương thành công!</div>
    <?php elseif($_GET['msg']=='deleted'): ?>
    <div class="alert alert-success"> Xóa bảng lương thành công!</div>
    <?php elseif($_GET['msg']=='auto_generated' && $auto_summary): ?>
    <div class="alert alert-success">
        Đã tạo tự động <strong><?= $auto_summary['count'] ?></strong> bảng lương cho kỳ
        <strong><?= htmlspecialchars($auto_summary['period_from']) ?></strong> -
        <strong><?= htmlspecialchars($auto_summary['period_to']) ?></strong>.
    </div>
    <?php elseif($_GET['msg']=='auto_error' && $auto_error): ?>
    <div class="alert alert-danger">Không thể tạo tự động: <?= htmlspecialchars($auto_error) ?></div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if(is_array($auto_summary) && !empty($auto_summary['details'])): ?>
    <div class="card mb-3 border-success">
        <div class="card-header bg-success text-white">Tóm tắt sinh bảng lương tự động</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Giờ làm</th>
                            <th>Giờ tăng ca</th>
                            <th class="text-end">Lương thực lĩnh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auto_summary['details'] as $detail): ?>
                        <tr>
                            <td><?= htmlspecialchars($detail['employee']) ?></td>
                            <td><?= number_format($detail['hours'], 2) ?></td>
                            <td><?= number_format($detail['overtime_hours'], 2) ?></td>
                            <td class="text-end"><?= number_format($detail['net'], 0, ',', '.') ?> VNĐ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6 d-flex gap-2 mb-2 mb-md-0">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Thêm bảng lương
            </button>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#autoModal">
                <i class="bi bi-robot"></i> Tạo tự động
            </button>
            <a href="export_payroll_simple.php" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Xuất Excel
            </a>
        </div>

        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Tìm theo tên nhân viên..."
                    value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm glass">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="50">ID</th>
                            <th>Nhân viên</th>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
                            <th>Lương gộp</th>
                            <th>Lương thực lĩnh</th>
                            <th>Chi tiết</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; ?>
                        <?php foreach($payrolls as $p): ?>
                        <tr>
                            <td class="text-center"><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($p['full_name']) ?></td>
                            <td class="text-center"><?= $p['period_from'] ?></td>
                            <td class="text-center"><?= $p['period_to'] ?></td>
                            <td class="text-end"><?= number_format($p['gross_salary'],0,',','.') ?></td>
                            <td class="text-end"><?= number_format($p['net_salary'],0,',','.') ?></td>
                            <td class="text-center">
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#detailModal<?= $p['id'] ?>" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <?php if($p['status']==='paid'): ?>
                                    <span class="badge bg-success">Đã trả</span>
                                <?php elseif($p['status']==='finalized'): ?>
                                    <span class="badge bg-primary">Chốt</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nháp</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?= $p['id'] ?>" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Xóa bảng lương này?')" title="Xóa">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </td>
                        </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="autoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">Tạo bảng lương tự động</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="generate_auto" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="period_from_auto" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="period_to_auto" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hệ số tăng ca</label>
                            <input type="number" name="overtime_multiplier" class="form-control" step="0.1" min="1" value="1.5">
                            <div class="form-text">Mặc định 1.5 giờ/giờ.</div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        Hệ thống sẽ lấy dữ liệu chấm công, phụ cấp, thưởng/phạt trong khoảng ngày đã chọn và cập nhật vào bảng lương hiện có.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary">Tạo tự động</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach($payrolls as $p): ?>
<div class="modal fade" id="detailModal<?= $p['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Chi tiết bảng lương - <?= htmlspecialchars($p['full_name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-uppercase text-muted">Thông tin kỳ</h6>
                            <p class="mb-1"><strong>Từ ngày:</strong> <?= htmlspecialchars($p['period_from']) ?></p>
                            <p class="mb-1"><strong>Đến ngày:</strong> <?= htmlspecialchars($p['period_to']) ?></p>
                            <p class="mb-1"><strong>Giờ làm:</strong> <?= number_format($p['working_hours'], 2) ?> giờ</p>
                            <p class="mb-1"><strong>Giờ tăng ca:</strong> <?= number_format($p['overtime_hours'], 2) ?> giờ</p>
                            <p class="mb-0"><strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($p['notes'] ?? '')) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-uppercase text-muted">Chi tiết tính toán</h6>
                            <ul class="list-unstyled mb-0">
                                <li><strong>Lương cơ bản:</strong> <?= number_format($p['base_salary_amount'],0,',','.') ?> VNĐ</li>
                                <li><strong>Tiền tăng ca:</strong> <?= number_format($p['overtime_pay'],0,',','.') ?> VNĐ</li>
                                <li><strong>Tổng phụ cấp:</strong> <?= number_format($p['allowance_total'],0,',','.') ?> VNĐ</li>
                                <li><strong>Thưởng:</strong> <?= number_format($p['reward_total'],0,',','.') ?> VNĐ</li>
                                <li><strong>Phạt/Kỷ luật:</strong> <?= number_format($p['discipline_total'],0,',','.') ?> VNĐ</li>
                                <li class="mt-2"><strong>Lương gộp:</strong> <?= number_format($p['gross_salary'],0,',','.') ?> VNĐ</li>
                                <li><strong>Lương thực lĩnh:</strong> <?= number_format($p['net_salary'],0,',','.') ?> VNĐ</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Sửa bảng lương</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <?php foreach($employees as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $e['id']==$p['employee_id']?'selected':'' ?>>
                                    <?= htmlspecialchars($e['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Từ ngày</label>
                            <input type="date" name="period_from" class="form-control" value="<?= $p['period_from'] ?>"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label>Đến ngày</label>
                            <input type="date" name="period_to" class="form-control" value="<?= $p['period_to'] ?>"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương gộp</label>
                            <input type="number" name="gross_salary" class="form-control"
                                value="<?= $p['gross_salary'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương thực lĩnh</label>
                            <input type="number" name="net_salary" class="form-control" value="<?= $p['net_salary'] ?>"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="draft" <?= $p['status']=='draft'?'selected':'' ?>>Nháp
                                </option>
                                <option value="finalized" <?= $p['status']=='finalized'?'selected':'' ?>>
                                    Chốt</option>
                                <option value="paid" <?= $p['status']=='paid'?'selected':'' ?>>Đã trả
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit" class="btn btn-warning">Lưu</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>


<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm bảng lương</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <?php foreach($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Từ ngày</label>
                            <input type="date" name="period_from" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Đến ngày</label>
                            <input type="date" name="period_to" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương gộp</label>
                            <input type="number" name="gross_salary" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương thực lĩnh</label>
                            <input type="number" name="net_salary" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="draft">Nháp</option>
                                <option value="finalized">Chốt</option>
                                <option value="paid">Đã trả</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-primary">Thêm</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "inc/footer.php"; ?>
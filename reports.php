<?php
// PHẦN 1: TOÀN BỘ LOGIC XỬ LÝ (ĐƯỢC ĐƯA LÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
check_role(['admin','hr']);

// Xử lý thêm (Không thay đổi)
if(isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO reports (employee_id, date, hours_worked, tasks_completed, overtime_hours, notes) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['date'],
        $_POST['hours_worked'] ?: 0,
        $_POST['tasks_completed'] ?: 0,
        $_POST['overtime_hours'] ?: 0,
        $_POST['notes']
    ]);
    header("Location: reports.php?msg=added");
    exit;
}
// Xử lý sửa (Không thay đổi)
if(isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE reports SET employee_id=?, date=?, hours_worked=?, tasks_completed=?, overtime_hours=?, notes=? WHERE id=?");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['date'],
        $_POST['hours_worked'] ?: 0,
        $_POST['tasks_completed'] ?: 0,
        $_POST['overtime_hours'] ?: 0,
        $_POST['notes'],
        $_POST['id']
    ]);
    header("Location: reports.php?msg=updated");
    exit;
}

// Xử lý xóa (Không thay đổi)
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: reports.php?msg=deleted");
    exit;
}

// PHẦN 2: HIỂN THỊ HTML (SAU KHI LOGIC ĐÃ CHẠY XONG)
include "inc/header.php";

// Lấy dữ liệu
$employees = $pdo->query("SELECT * FROM employees ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// *** THAY ĐỔI 1: XỬ LÝ TÌM KIẾM VÀ CẬP NHẬT TRUY VẤN SQL ***
$search_term = $_GET['search'] ?? '';
$params = [];

$sql = "
    SELECT r.*, e.full_name, d.name AS department_name
    FROM reports r
    LEFT JOIN employees e ON r.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
";

if (!empty($search_term)) {
    // Tìm kiếm theo tên nhân viên, phòng ban, hoặc ngày (YYYY-MM-DD)
    $sql .= " WHERE e.full_name LIKE ? OR d.name LIKE ? OR r.date LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = [$like_term, $like_term, $like_term];
}

// Giữ nguyên sắp xếp: Báo cáo mới nhất lên đầu
$sql .= " ORDER BY r.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4 fw-bold">Báo cáo tổng hợp nhân viên</h2>

    <?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg']=='added'): ?>
    <div class="alert alert-success"> Thêm báo cáo thành công!</div>
    <?php elseif($_GET['msg']=='updated'): ?>
    <div class="alert alert-info">Cập nhật báo cáo thành công!</div>
    <?php elseif($_GET['msg']=='deleted'): ?>
    <div class="alert alert-success">Xóa báo cáo thành công!</div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6 mb-2 mb-md-0">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Thêm báo cáo
            </button>
        </div>
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2"
                    placeholder="Tìm theo NV, phòng ban, ngày (YYYY-MM-DD)..."
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
                    <thead class="text-center">
                        <tr>
                            <th width="50">ID</th>
                            <th>Nhân viên</th>
                            <th>Phòng ban</th>
                            <th>Ngày</th>
                            <th>Giờ làm</th>
                            <th>Task hoàn thành</th>
                            <th>OT</th>
                            <th>Ghi chú</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; ?>
                        <?php foreach($reports as $r): ?>
                        <tr>
                            <td class="text-center"><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['department_name']) ?></td>
                            <td class="text-center"><?= $r['date'] ?></td>
                            <td class="text-center"><?= $r['hours_worked'] ?></td>
                            <td class="text-center"><?= $r['tasks_completed'] ?></td>
                            <td class="text-center"><?= $r['overtime_hours'] ?></td>
                            <td><?= htmlspecialchars($r['notes']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?= $r['id'] ?>" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Xóa báo cáo này?')" title="Xóa">
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

<?php foreach($reports as $r): ?>
<div class="modal fade" id="editModal<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <?php foreach($employees as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $r['employee_id']==$e['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($e['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label>Ngày</label><input type="date" name="date" class="form-control"
                                value="<?= $r['date'] ?>" required></div>
                        <div class="col-md-2"><label>Giờ làm</label><input type="number" step="0.01" name="hours_worked"
                                class="form-control" value="<?= $r['hours_worked'] ?>"></div>
                        <div class="col-md-2"><label>Task hoàn thành</label><input type="number" name="tasks_completed"
                                class="form-control" value="<?= $r['tasks_completed'] ?>"></div>
                        <div class="col-md-2"><label>OT</label><input type="number" step="0.01" name="overtime_hours"
                                class="form-control" value="<?= $r['overtime_hours'] ?>"></div>
                        <div class="col-md-6"><label>Ghi chú</label><textarea name="notes"
                                class="form-control"><?= htmlspecialchars($r['notes']) ?></textarea>
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
                <div class="modal-header">
                    <h5 class="modal-title">Thêm báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <?php foreach($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label>Ngày</label><input type="date" name="date" class="form-control"
                                value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-2"><label>Giờ làm</label><input type="number" step="0.01" name="hours_worked"
                                class="form-control" value="8"></div>
                        <div class="col-md-2"><label>Task hoàn thành</label><input type="number" name="tasks_completed"
                                class="form-control" value="0"></div>
                        <div class="col-md-2"><label>OT</label><input type="number" step="0.01" name="overtime_hours"
                                class="form-control" value="0"></div>
                        <div class="col-md-6"><label>Ghi chú</label><textarea name="notes"
                                class="form-control"></textarea></div>
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
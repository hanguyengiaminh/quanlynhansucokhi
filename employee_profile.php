<?php
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT employee_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['employee_id']) {
    echo "<div class='alert alert-danger'>Không tìm thấy thông tin nhân viên!</div>";
    include "inc/footer.php";
    exit;
}

$employee_id = $user['employee_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = trim($_POST['dob']);

    $update = $pdo->prepare("UPDATE employees SET full_name = ?, phone = ?, address = ?, dob = ? WHERE id = ?");
    $update->execute([$full_name, $phone, $address, $dob, $employee_id]);

    echo "<div class='alert alert-success'>Cập nhật thông tin cá nhân thành công!</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        echo "<div class='alert alert-danger'> Không tìm thấy người dùng!</div>";
    } elseif (hash('sha256', $old_pass) !== $user_data['password']) {
        echo "<div class='alert alert-danger'> Mật khẩu cũ không chính xác!</div>";
    } elseif ($new_pass !== $confirm_pass) {
        echo "<div class='alert alert-warning'> Mật khẩu mới không khớp nhau!</div>";
    } else {
        $hashed = hash('sha256', $new_pass);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$hashed, $user_id]);
        echo "<div class='alert alert-success'> Đổi mật khẩu thành công!</div>";
    }
}
$stmt = $pdo->prepare("
    SELECT e.*, d.name AS department_name, p.title AS position_title
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE e.id = ?
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "<div class='alert alert-warning'>Không tìm thấy thông tin nhân viên.</div>";
    include "inc/footer.php";
    exit;
}
$contracts = $pdo->prepare("SELECT * FROM contracts WHERE employee_id = ? ORDER BY start_date DESC");
$contracts->execute([$employee_id]);
$contracts = $contracts->fetchAll(PDO::FETCH_ASSOC);

$attendance = $pdo->prepare("
    SELECT a.*, s.name AS shift_name
    FROM attendance a
    LEFT JOIN shifts s ON a.shift_id = s.id
    WHERE a.employee_id = ?
    ORDER BY a.date DESC
");
$attendance->execute([$employee_id]);
$attendance = $attendance->fetchAll(PDO::FETCH_ASSOC);

$payrolls = $pdo->prepare("SELECT * FROM payrolls WHERE employee_id = ? ORDER BY period_from DESC");
$payrolls->execute([$employee_id]);
$payrolls = $payrolls->fetchAll(PDO::FETCH_ASSOC);

$reports = $pdo->prepare("SELECT * FROM reports WHERE employee_id = ? ORDER BY date DESC");
$reports->execute([$employee_id]);
$reports = $reports->fetchAll(PDO::FETCH_ASSOC);

$actions = $pdo->prepare("SELECT * FROM hr_actions WHERE employee_id = ? ORDER BY date DESC");
$actions->execute([$employee_id]);
$actions = $actions->fetchAll(PDO::FETCH_ASSOC);

$leaves = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? ORDER BY from_date DESC");
$leaves->execute([$employee_id]);
$leaves = $leaves->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "inc/header.php"; ?>

<h2 class="text-dark mb-4">Thông tin của tôi</h2>

<!-- Thông tin cá nhân -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span>Thông tin cá nhân</span>
        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#editInfo">
             Chỉnh sửa
        </button>
    </div>
    <div class="card-body">
        <p><strong>Họ và tên:</strong> <?= htmlspecialchars($employee['full_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($employee['email']) ?></p>
        <p><strong>Điện thoại:</strong> <?= htmlspecialchars($employee['phone']) ?></p>
        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($employee['address']) ?></p>
        <p><strong>Ngày sinh:</strong> <?= htmlspecialchars($employee['dob']) ?></p>
        <p><strong>Phòng ban:</strong> <?= htmlspecialchars($employee['department_name']) ?></p>
        <p><strong>Chức vụ:</strong> <?= htmlspecialchars($employee['position_title']) ?></p>
        <p><strong>Ngày vào làm:</strong> <?= htmlspecialchars($employee['join_date']) ?></p>

        <div class="collapse mt-3" id="editInfo">
            <form method="post">
                <input type="hidden" name="update_info" value="1">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($employee['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($employee['dob']) ?>">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Điện thoại</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($employee['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($employee['address']) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-2"> Cập nhật</button>
            </form>
        </div>
    </div>
</div>

<!-- Đổi mật khẩu -->
<div class="card mb-3">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span>Đổi mật khẩu</span>
        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#changePassword">
             Mở form
        </button>
    </div>
    <div class="card-body collapse" id="changePassword">
        <form method="post">
            <input type="hidden" name="change_password" value="1">
            <div class="row mb-2">
                <div class="col-md-4">
                    <label class="form-label">Mật khẩu cũ</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-warning mt-2">🔄 Đổi mật khẩu</button>
        </form>
    </div>
</div>

<!-- Hợp đồng -->
<div class="card mb-3">
    <div class="card-header bg-success text-white">Hợp đồng</div>
    <div class="card-body">
        <?php if ($contracts): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Số hợp đồng</th>
                        <th>Loại</th>
                        <th>Ngày bắt đầu</th>
                        <th>Ngày kết thúc</th>
                        <th>Lương cơ bản</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['contract_no']) ?></td>
                            <td><?= htmlspecialchars($c['contract_type']) ?></td>
                            <td><?= htmlspecialchars($c['start_date']) ?></td>
                            <td><?= htmlspecialchars($c['end_date'] ?? '-') ?></td>
                            <td><?= number_format($c['salary_base'], 0, ',', '.') ?> VNĐ</td>
                            <td><?= ucfirst(htmlspecialchars($c['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có hợp đồng.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Chấm công -->
<div class="card mb-3">
    <div class="card-header bg-warning text-white">Chấm công</div>
    <div class="card-body">
        <?php if ($attendance): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Giờ vào</th>
                        <th>Giờ ra</th>
                        <th>Ca</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['date']) ?></td>
                            <td><?= htmlspecialchars($a['clock_in'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['clock_out'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['shift_name'] ?? '-') ?></td>
                            <td><?= ucfirst(htmlspecialchars($a['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có dữ liệu chấm công.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Bảng lương -->
<div class="card mb-3">
    <div class="card-header bg-info text-white">Bảng lương</div>
    <div class="card-body">
        <?php if ($payrolls): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Lương gộp</th>
                        <th>Lương thực lĩnh</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrolls as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['period_from']) ?></td>
                            <td><?= htmlspecialchars($p['period_to']) ?></td>
                            <td><?= number_format($p['gross_salary'], 0, ',', '.') ?> VNĐ</td>
                            <td><?= number_format($p['net_salary'], 0, ',', '.') ?> VNĐ</td>
                            <td><?= ucfirst(htmlspecialchars($p['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có bảng lương.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Báo cáo -->
<div class="card mb-3">
    <div class="card-header bg-secondary text-white">Báo cáo công việc</div>
    <div class="card-body">
        <?php if ($reports): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Giờ làm</th>
                        <th>Số công việc</th>
                        <th>Giờ OT</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['date']) ?></td>
                            <td><?= htmlspecialchars($r['hours_worked']) ?></td>
                            <td><?= htmlspecialchars($r['tasks_completed']) ?></td>
                            <td><?= htmlspecialchars($r['overtime_hours']) ?></td>
                            <td><?= htmlspecialchars($r['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có báo cáo.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Khen thưởng / Kỷ luật -->
<div class="card mb-3">
    <div class="card-header bg-dark text-white">Khen thưởng / Kỷ luật</div>
    <div class="card-body">
        <?php if ($actions): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Loại</th>
                        <th>Tiêu đề</th>
                        <th>Mô tả</th>
                        <th>Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actions as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['date']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($a['type'])) ?></td>
                            <td><?= htmlspecialchars($a['title']) ?></td>
                            <td><?= htmlspecialchars($a['description']) ?></td>
                            <td><?= number_format($a['amount'], 0, ',', '.') ?> VNĐ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có dữ liệu khen thưởng/kỷ luật.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Nghỉ phép -->
<div class="card mb-3">
    <div class="card-header bg-secondary text-white">Nghỉ phép</div>
    <div class="card-body">
        <?php if ($leaves): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Loại</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Số ngày</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaves as $l): ?>
                        <tr>
                            <td><?= ucfirst(htmlspecialchars($l['leave_type'])) ?></td>
                            <td><?= htmlspecialchars($l['from_date']) ?></td>
                            <td><?= htmlspecialchars($l['to_date']) ?></td>
                            <td><?= htmlspecialchars($l['total_days']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($l['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có dữ liệu nghỉ phép.</p>
        <?php endif; ?>
    </div>
</div>

<?php include "inc/footer.php"; ?>

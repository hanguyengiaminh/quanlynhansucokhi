<?php
session_start();
require_once "inc/db.php";
require_once "inc/auth.php";

check_login();
if ($_SESSION['role'] !== 'employee') {
    echo "<div class='alert alert-danger'>Bạn không có quyền truy cập!</div>";
    exit;
}
$employee_id = $_SESSION['employee_id'] ?? null;

if (!$employee_id) {
    echo "<div class='alert alert-danger'>Không tìm thấy thông tin nhân viên!</div>";
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "<div class='alert alert-danger'>Không tìm thấy thông tin nhân viên!</div>";
    exit;
}
$shifts = $pdo->query("SELECT * FROM shifts ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['shift_id'])) {
    $shift_id = $_POST['shift_id'];
    $date = date('Y-m-d');
    $check = $pdo->prepare("SELECT * FROM attendance WHERE employee_id=? AND date=?");
    $check->execute([$employee_id, $date]);
    if ($check->rowCount() > 0) {
        $message = "Bạn đã chấm công hôm nay!";
    } else {
        $stmtShift = $pdo->prepare("SELECT start_time FROM shifts WHERE id=?");
        $stmtShift->execute([$shift_id]);
        $shift = $stmtShift->fetch(PDO::FETCH_ASSOC);

        $clock_in = date('Y-m-d') . ' ' . $shift['start_time'];

        $stmtInsert = $pdo->prepare("INSERT INTO attendance (employee_id, date, clock_in, shift_id, status) VALUES (?,?,?,?,?)");
        $stmtInsert->execute([$employee_id, $date, $clock_in, $shift_id, 'present']);
        $message = "Chấm công thành công!";
    }
}
$attendance = $pdo->prepare("
    SELECT a.*, s.name as shift_name 
    FROM attendance a 
    LEFT JOIN shifts s ON a.shift_id=s.id 
    WHERE a.employee_id=? 
    ORDER BY a.date DESC
");
$attendance->execute([$employee_id]);
$records = $attendance->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "inc/header.php"; ?>

<h2>Chấm công của tôi</h2>

<?php if($message) echo "<div class='alert alert-info'>$message</div>"; ?>

<!-- Form chấm công -->
<form method="POST" class="mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label>Chọn ca làm việc</label>
            <select name="shift_id" class="form-control" required>
                <option value="">-- Chọn ca --</option>
                <?php foreach($shifts as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= $s['name'] ?> (<?= $s['start_time'] ?> - <?= $s['end_time'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Chấm công</button>
        </div>
    </div>
</form>

<!-- Bảng chấm công -->
<table class="table table-bordered table-hover">
    <thead class="table-dark text-center">
        <tr>
            <th>Ngày</th>
            <th>Ca</th>
            <th>Giờ vào</th>
            <th>Giờ ra</th>
            <th>Trạng thái</th>
        </tr>
    </thead>
    <tbody>
        <?php if($records): ?>
            <?php foreach($records as $r): ?>
                <tr class="text-center">
                    <td><?= $r['date'] ?></td>
                    <td><?= $r['shift_name'] ?></td>
                    <td><?= $r['clock_in'] ?? '-' ?></td>
                    <td><?= $r['clock_out'] ?? '-' ?></td>
                    <td><?= ucfirst($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">Chưa có dữ liệu</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include "inc/footer.php"; ?>

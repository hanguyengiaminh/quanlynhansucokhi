<?php
include "inc/header.php";
check_login();
if ($_SESSION['role'] == 'employee') {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $employee = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<h2>Thông tin nhân viên</h2>

<?php if ($_SESSION['role'] == 'employee'): ?>
<table class="table table-bordered">
<tr><th>Họ tên</th><td><?= $employee['full_name'] ?></td></tr>
<tr><th>Phòng ban</th><td><?= $employee['department_id'] ?></td></tr>
<tr><th>Chức vụ</th><td><?= $employee['position'] ?></td></tr>
<tr><th>Email</th><td><?= $employee['email'] ?></td></tr>
</table>
<?php else: ?>
<table class="table table-bordered table-hover">
<thead>
<tr><th>ID</th><th>Họ tên</th><th>Phòng ban</th><th>Chức vụ</th></tr>
</thead>
<tbody>
<?php foreach($employee as $e): ?>
<tr>
<td><?= $e['id'] ?></td>
<td><?= $e['full_name'] ?></td>
<td><?= $e['department_id'] ?></td>
<td><?= $e['position'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

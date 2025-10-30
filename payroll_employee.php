<?php
require_once "inc/auth.php";
require_once "inc/db.php";
check_login();

$stmt = $pdo->prepare("
    SELECT e.id AS employee_id, e.full_name
    FROM users u
    JOIN employees e ON u.employee_id = e.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-danger'>Không tìm thấy thông tin nhân viên!</div>";
    exit;
}

$employee_id = $user['employee_id'];
$payrolls_stmt = $pdo->prepare("
    SELECT p.*, e.full_name
    FROM payrolls p
    JOIN employees e ON p.employee_id = e.id
    WHERE p.employee_id = ?
    ORDER BY p.period_from DESC
");
$payrolls_stmt->execute([$employee_id]);
$payrolls = $payrolls_stmt->fetchAll(PDO::FETCH_ASSOC);

include "inc/header.php";
?>

<div class="container mt-4">
    <h2 class="text-dark mb-4">Bảng lương của <?= htmlspecialchars($user['full_name']) ?></h2>

    <?php if ($payrolls): ?>
        <table class="table table-bordered table-hover">
            <thead class="table-dark text-center">
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
                    <tr class="text-center">
                        <td><?= $p['period_from'] ?></td>
                        <td><?= $p['period_to'] ?></td>
                        <td class="text-end"><?= number_format($p['gross_salary'], 0, ',', '.') ?> VNĐ</td>
                        <td class="text-end"><?= number_format($p['net_salary'], 0, ',', '.') ?> VNĐ</td>
                        <td><?= ucfirst($p['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">Chưa có bảng lương nào được tạo cho bạn.</div>
    <?php endif; ?>
</div>

<?php include "inc/footer.php"; ?>

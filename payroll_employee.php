<?php
require_once "inc/auth.php";
require_once "inc/db.php";
check_login();

// Lấy thông tin nhân viên từ tài khoản đăng nhập
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

// Lấy danh sách bảng lương của nhân viên đó
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
    <div class="card shadow-sm glass">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
                            <th>Lương gộp</th>
                            <th>Lương thực lĩnh</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrolls as $p): ?>
                        <tr class="text-center align-middle">
                            <td><?= date('d/m/Y', strtotime($p['period_from'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['period_to'])) ?></td>
                            <td class="text-end"><?= number_format($p['gross_salary'], 0, ',', '.') ?> VNĐ</td>
                            <td class="text-end fw-bold text-success">
                                <?= number_format($p['net_salary'], 0, ',', '.') ?> VNĐ</td>
                            <td>
                                <?php if($p['status'] == 'paid'): ?>
                                <span class="badge bg-success">Đã thanh toán</span>
                                <?php elseif($p['status'] == 'finalized'): ?>
                                <span class="badge bg-primary">Đã chốt</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Nháp</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="download_payroll.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Tải phiếu
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">Chưa có bảng lương nào được tạo cho bạn.</div>
    <?php endif; ?>
</div>

<?php include "inc/footer.php"; ?>
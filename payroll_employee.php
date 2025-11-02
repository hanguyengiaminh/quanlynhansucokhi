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
                    <th>Chi tiết</th>
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
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#payrollDetail<?= $p['id'] ?>">
                                Xem
                            </button>
                        </td>
                        <td>
                            <?php if($p['status']==='paid'): ?>
                                <span class="badge bg-success">Đã trả</span>
                            <?php elseif($p['status']==='finalized'): ?>
                                <span class="badge bg-primary">Chốt</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nháp</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
<?php else: ?>
        <div class="alert alert-warning">Chưa có bảng lương nào được tạo cho bạn.</div>
    <?php endif; ?>
</div>

<?php if ($payrolls): ?>
    <?php foreach ($payrolls as $p): ?>
        <div class="modal fade" id="payrollDetail<?= $p['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content glass">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Chi tiết bảng lương kỳ <?= htmlspecialchars($p['period_from']) ?> - <?= htmlspecialchars($p['period_to']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-uppercase text-muted">Tổng quan</h6>
                                    <p class="mb-1"><strong>Lương gộp:</strong> <?= number_format($p['gross_salary'], 0, ',', '.') ?> VNĐ</p>
                                    <p class="mb-1"><strong>Lương thực lĩnh:</strong> <?= number_format($p['net_salary'], 0, ',', '.') ?> VNĐ</p>
                                    <p class="mb-1"><strong>Giờ làm:</strong> <?= number_format($p['working_hours'], 2) ?> giờ</p>
                                    <p class="mb-1"><strong>Giờ tăng ca:</strong> <?= number_format($p['overtime_hours'], 2) ?> giờ</p>
                                    <p class="mb-0"><strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($p['notes'] ?? '')) ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-uppercase text-muted">Chi tiết</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li><strong>Lương cơ bản:</strong> <?= number_format($p['base_salary_amount'], 0, ',', '.') ?> VNĐ</li>
                                        <li><strong>Tăng ca:</strong> <?= number_format($p['overtime_pay'], 0, ',', '.') ?> VNĐ</li>
                                        <li><strong>Phụ cấp:</strong> <?= number_format($p['allowance_total'], 0, ',', '.') ?> VNĐ</li>
                                        <li><strong>Thưởng:</strong> <?= number_format($p['reward_total'], 0, ',', '.') ?> VNĐ</li>
                                        <li><strong>Khấu trừ/Kỷ luật:</strong> <?= number_format($p['discipline_total'], 0, ',', '.') ?> VNĐ</li>
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
    <?php endforeach; ?>
<?php endif; ?>

<?php include "inc/footer.php"; ?>

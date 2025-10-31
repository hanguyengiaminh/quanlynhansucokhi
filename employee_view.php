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

<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h4 mb-0">Thông tin nhân viên</h2>
    </div>

    <?php if ($_SESSION['role'] == 'employee'): ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-2"
                            style="width:36px;height:36px;font-weight:700;">
                            <?php
                            $initial = '';
                            if (!empty($employee['full_name'])) {
                                // Get first letter (UTF-8 safe)
                                $initial = mb_strtoupper(mb_substr($employee['full_name'], 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            echo htmlspecialchars($initial);
                            ?>
                        </div>
                        <span>Hồ sơ của bạn</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width:56px;height:56px;font-weight:700;">
                            <?php echo htmlspecialchars($initial); ?>
                        </div>
                        <div>
                            <div class="h5 mb-1"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($employee['position']); ?></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted" style="width: 160px;">Phòng ban</th>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($employee['department_id']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Chức vụ</th>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Email</th>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>"
                                            class="text-decoration-none">
                                            <?php echo htmlspecialchars($employee['email']); ?>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
            <strong>Danh sách nhân viên</strong>
            <a href="employees.php" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Thêm nhân viên
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Họ tên</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($employee as $e): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($e['id']); ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($e['full_name']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($e['department_id']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($e['position']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
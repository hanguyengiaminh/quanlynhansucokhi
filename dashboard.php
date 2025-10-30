<?php 
include "inc/header.php"; 
require_once "inc/db.php"; // Thêm dòng này để kết nối CSDL

// 1. Lấy vai trò và tên người dùng
$role = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// 2. Định nghĩa các biến dựa trên vai trò
$welcome_message = "";
$role_description = "";
$card_class = ""; // Màu sắc viền card
$icon_class = ""; // Icon

if ($role == 'admin') {
    $welcome_message = "Chào mừng Quản trị viên!";
    $role_description = "Bạn có toàn quyền truy cập hệ thống, bao gồm quản lý nhân viên, phòng ban, và tài khoản người dùng.";
    $card_class = "border-primary"; // Màu xanh dương
    $icon_class = "bi-shield-lock-fill text-primary"; 
} elseif ($role == 'hr') {
    $welcome_message = "Chào mừng, Bộ phận Nhân sự!";
    $role_description = "Bạn có quyền quản lý hồ sơ nhân viên, chấm công, và tính lương.";
    $card_class = "border-info"; // Màu xanh lơ
    $icon_class = "bi-people-fill text-info"; 
} else {
    $welcome_message = "Chào mừng, $username!";
    $role_description = "Bạn có thể xem thông tin cá nhân, chấm công và xem bảng lương của mình tại đây.";
    $card_class = "border-success"; // Màu xanh lá
    $icon_class = "bi-person-fill text-success";
}

// 3. Lấy dữ liệu thống kê (chỉ dành cho admin/hr)
if ($role == 'admin' || $role == 'hr') {
    try {
        $total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
        $total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
        $total_reports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
    } catch (PDOException $e) {
        // Xử lý lỗi nếu không query được
        $total_employees = $total_departments = $total_reports = "N/A";
    }
}
?>

<div class="container-fluid">
    <h2 class="display-6 fw-bold mb-4">Bảng điều khiển</h2>

    <div class="card shadow-sm mb-4 <?= $card_class ?>" style="border-width: 4px;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <i class="bi <?= $icon_class ?>" style="font-size: 3rem; margin-right: 20px;"></i>
                <div>
                    <h3 class="card-title fw-bold mb-1"><?= $welcome_message ?></h3>
                    <p class="card-text text-muted mb-0"><?= $role_description ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($role == 'admin' || $role == 'hr'): ?>
    <h4 class="fw-bold mb-3">Thống kê hệ thống</h4>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h5 class="card-title text-muted fw-normal mb-1">Tổng Nhân viên</h5>
                        <span class="display-5 fw-bold"><?= $total_employees ?></span>
                    </div>
                    <i class="bi bi-people-fill text-primary" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h5 class="card-title text-muted fw-normal mb-1">Phòng ban</h5>
                        <span class="display-5 fw-bold"><?= $total_departments ?></span>
                    </div>
                    <i class="bi bi-building text-success" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h5 class="card-title text-muted fw-normal mb-1">Báo cáo</h5>
                        <span class="display-5 fw-bold"><?= $total_reports ?></span>
                    </div>
                    <i class="bi bi-clipboard-data-fill text-warning" style="font-size: 3rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($role == 'employee'): ?>
    <h4 class="fw-bold mb-3">Truy cập nhanh</h4>
    <div class="list-group shadow-sm">
        <a href="employee_profile.php"
            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
            <div>
                <i class="bi bi-person-fill me-2 text-primary" style="font-size: 1.2rem;"></i>
                <strong class="fs-5">Thông tin của tôi</strong>
                <br><small class="text-muted">Xem và cập nhật thông tin cá nhân, đổi mật khẩu.</small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
        <a href="attendance_employee.php"
            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
            <div>
                <i class="bi bi-calendar-check-fill me-2 text-success" style="font-size: 1.2rem;"></i>
                <strong class="fs-5">Chấm công</strong>
                <br><small class="text-muted">Thực hiện chấm công hàng ngày và xem lịch sử.</small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
        <a href="payroll_employee.php"
            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
            <div>
                <i class="bi bi-cash me-2 text-warning" style="font-size: 1.2rem;"></i>
                <strong class="fs-5">Bảng lương</strong>
                <br><small class="text-muted">Xem lịch sử lương và chi tiết các kỳ thanh toán.</small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
    </div>
    <?php endif; ?>

</div>
<?php include "inc/footer.php"; ?>
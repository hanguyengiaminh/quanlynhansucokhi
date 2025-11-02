<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
check_login();
$role = $_SESSION['role'];

$current_page = basename($_SERVER['PHP_SELF']);

function is_admin_or_hr() {
    global $role;
    return in_array($role, ['admin', 'hr']);
}

function is_employee() {
    global $role;
    return $role === 'employee';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>QLNS Cơ Khí</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .sidebar {
        width: 230px;
        min-width: 230px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background-color: #343a40;
        color: white;
        padding-top: 20px;
        z-index: 1000;
        overflow-y: auto;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border-radius: 10px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar .sidebar-brand {
        font-size: 1.4rem;
        font-weight: 600;
        text-align: center;
        display: block;
        margin-bottom: 10px;
        padding: 10px 0;
        text-decoration: none;
    }

    .sidebar .sidebar-brand .bi-gear-fill {
        font-size: 1.5rem;
        vertical-align: middle;
        color: white;
    }

    .sidebar .sidebar-brand span {
        color: #0d6efd;
    }

    .sidebar hr.sidebar-divider {
        margin: 0 1.5rem 1rem 1.5rem;
        border-top: 1px solid #495057;
    }

    .sidebar .user-panel {
        padding: 0 20px 15px 20px;
        text-align: center;
        color: #adb5bd;
    }

    .sidebar .user-panel .user-name {
        font-weight: 600;
        color: white;
        font-size: 1.1rem;
        display: block;
    }

    .sidebar .user-panel .user-role {
        font-size: 0.9rem;
    }

    .sidebar a.nav-link {
        color: #adb5bd;
        text-decoration: none;
        display: block;
        padding: 12px 20px;
        font-weight: 500;
        margin: 4px 15px;
        border-radius: 6px;
    }

    .sidebar a.nav-link:hover {
        background-color: #495057;
        color: white;
    }

    .sidebar a.nav-link.active {
        background-color: #0d6efd;
        color: white;
        font-weight: 600;
    }

    .sidebar a.nav-link .bi {
        margin-right: 12px;
        font-size: 1.1rem;
    }

    .main-wrapper {
        margin-left: 230px;
        width: calc(100% - 230px);
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    /* === 1. SỬA TOPBAR === */
    .topbar {
        background-color: #ffffff;
        padding: 0 30px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        /* THAY ĐỔI: Sử dụng space-between để căn đều 3 mục */
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        height: 80px;
    }

    .topbar-title {
        font-size: 1.6rem;
        font-weight: 600;
        color: #0d6efd;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        /* THÊM: Đặt kích thước cố định để căn giữa */
        flex-basis: 250px;
    }

    .sparkle-text {
        background: linear-gradient(90deg,
                #0d6efd,
                #454d55,
                #0d6efd);
        background-size: 300% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: sparkle-animation 5s linear infinite;
        font-weight: 700;
    }

    @keyframes sparkle-animation {
        0% {
            background-position: 150% 50%;
        }

        100% {
            background-position: -150% 50%;
        }
    }

    /* === 2. THÊM VÙNG ẢNH Ở GIỮA === */
    .topbar-center-image {
        flex-grow: 1;
        /* Chiếm hết không gian ở giữa */
        text-align: center;
        height: 100%;
    }

    .topbar-center-image img {
        height: 100%;
        width: auto;
        max-width: 100%;
        object-fit: contain;
    }


    /* === 3. SỬA VÙNG LOGO BÊN PHẢI === */
    .company-logo-top {
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        /* Đẩy logo về bên phải */
        /* THÊM: Đặt kích thước cố định để căn giữa */
        flex-basis: 250px;
    }

    .company-logo-top img {
        height: 80px;
        /* Giữ nguyên 80px */
        width: auto;
        object-fit: contain;
    }


    .content {
        margin-left: 0 !important;
        padding: 30px;
    }

    @media (max-width: 992px) {
        .topbar-title {
            font-size: 1.2rem;
            flex-basis: auto;
            /* Tắt kích thước cố định */
        }

        .topbar-center-image {
            display: none;
            /* Ẩn ảnh ở giữa trên màn hình nhỏ */
        }

        .topbar {
            padding: 0 15px;
            justify-content: space-between;
            /* Quay lại space-between */
        }

        .company-logo-top {
            flex-basis: auto;
            /* Tắt kích thước cố định */
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
            margin-bottom: 15px;
            overflow-y: hidden;
        }

        .main-wrapper {
            margin-left: 0;
            width: 100%;
        }

        .content {
            padding: 20px;
        }

        .topbar {
            padding: 12px 15px;
            height: auto;
            flex-direction: column;
            gap: 10px;
        }

        .company-logo-top {
            align-self: flex-end;
            height: 40px;
        }
    }
    </style>
</head>

<body>
    <div>
        <div class="sidebar flex-shrink-0">

            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-gear-fill me-2"></i>
                <span>QLNS Cơ Khí</span>
            </a>
            <hr class="sidebar-divider">
            <div class="user-panel">
                <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <span class="user-role">(Vai trò: <?= $role ?>)</span>
            </div>
            <hr class="sidebar-divider mt-0">

            <?php if(is_admin_or_hr()): ?>
            <a href="employees.php" class="nav-link <?php echo ($current_page == 'employees.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>Nhân viên
            </a>
            <a href="departments.php"
                class="nav-link <?php echo ($current_page == 'departments.php') ? 'active' : ''; ?>">
                <i class="bi bi-building"></i>Phòng ban
            </a>

            <a href="positions.php" class="nav-link <?php echo ($current_page == 'positions.php') ? 'active' : ''; ?>">
                <i class="bi bi-briefcase-fill"></i>Chức vụ
            </a>

            <a href="contracts.php" class="nav-link <?php echo ($current_page == 'contracts.php') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text-fill"></i>Hợp đồng
            </a>
            <a href="attendance.php"
                class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i>Chấm công
            </a>
            <a href="payrolls.php" class="nav-link <?php echo ($current_page == 'payrolls.php') ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack"></i>Tiền lương
            </a>
            <a href="reports.php" class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="bi bi-clipboard-data-fill"></i>Báo cáo
            </a>
            <a href="stats.php" class="nav-link <?php echo ($current_page == 'stats.php') ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i>Thống kê
            </a>
            <?php if ($role == 'admin'): ?>
            <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-lock-fill"></i>Quản lý Tài khoản
            </a>
            <?php endif; ?>

            <?php elseif(is_employee()): ?>
            <a href="employee_profile.php"
                class="nav-link <?php echo ($current_page == 'employee_profile.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-fill"></i>Thông tin của tôi
            </a>
            <a href="attendance_employee.php"
                class="nav-link <?php echo ($current_page == 'attendance_employee.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i>Chấm công
            </a>
            <a href="payroll_employee.php"
                class="nav-link <?php echo ($current_page == 'payroll_employee.php') ? 'active' : ''; ?>">
                <i class="bi bi-cash"></i>Bảng lương
            </a>
            <?php endif; ?>

            <a href="logout.php" class="nav-link">
                <i class="bi bi-box-arrow-left"></i>Đăng xuất
            </a>
        </div>

        <div class="main-wrapper">

            <div class="topbar">
                <h1 class="topbar-title sparkle-text"> CƠ KHÍ Ý TƯỞNG </h1>

                <div class="topbar-center-image">
                    <img src="assets/img/bieutuong.png" alt="Ảnh banner trung tâm">
                </div>
                <div class="company-logo-top">
                    <img src="assets/img/logo.png" alt="Logo Công ty">
                </div>
            </div>
            <div class="content">
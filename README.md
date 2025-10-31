🛠️ Quản lý Nhân sự (QLNS) – Công ty Cơ khí
Hệ thống Quản lý Nhân sự (HRM) được phát triển bằng PHP thuần, sử dụng MySQL, tập trung vào các nghiệp vụ cốt lõi của một công ty cơ khí. Giao diện hiện đại theo phong cách Glassmorphism, hỗ trợ phân quyền rõ ràng giữa các vai trò: Admin, HR, và Employee.

</div>

📂 Cấu trúc Thư mục
/quanlynhansucokhi/
├── index.php             (Trang đăng nhập)
├── dashboard.php         (Bảng điều khiển chính)
├── employees.php         (Quản lý nhân viên)
├── attendance.php        (Quản lý chấm công)
├── payrolls.php          (Quản lý lương)
├── reports.php           (Quản lý báo cáo)
├── inc/
│   ├── db.php            (Kết nối CSDL)
│   └── auth.php          (Xác thực & Phân quyền)
├── assets/
│   ├── css/              (Giao diện CSS tùy chỉnh)
│   ├── js/               (Javascript, jQuery, Chart.js)
│   └── images/           (Logo, biểu tượng)
└── qlns_cokhi.sql        (Tệp dữ liệu CSDL)
🚀 Công nghệ sử dụng
Backend: PHP thuần (script-based)

Frontend: Bootstrap 5, CSS tùy chỉnh (Glassmorphism), jQuery, Chart.js

Cơ sở dữ liệu: MySQL (MariaDB)

⚙️ Hướng dẫn Cài đặt
1. Cơ sở dữ liệu (Database)
Import file qlns_cokhi.sql bằng phpMyAdmin hoặc HeidiSQL.

Đảm bảo dịch vụ MySQL của bạn đang chạy trên port 3307.

Nếu bạn sử dụng cổng (port) khác, vui lòng mở tệp inc/db.php và chỉnh sửa lại:

PHP

<?php
$host = "localhost";
$dbname = "qlns_cokhi";
$user = "root";
$pass = ""; 
$port = 3307; // <-- SỬA CỔNG NÀY NẾU CẦN

try {
    // SỬA DÒNG NÀY: Thêm $port vào chuỗi kết nối
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
2. Máy chủ Web (Web Server)
Sử dụng một môi trường máy chủ web hỗ trợ PHP (như XAMPP, WAMP).

Đặt toàn bộ thư mục dự án vào thư mục gốc của máy chủ (ví dụ: htdocs trong XAMPP).

Truy cập dự án qua trình duyệt (ví dụ: http://localhost/ten_thu_muc_du_an/).

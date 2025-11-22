<?php
// inc/db.php
$host = "localhost";
$dbname = "qlns_cokhi";
$user = "root";
$pass = ""; 
$port = 3306; // Đã quay về cổng chuẩn

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    
    // Thiết lập chế độ báo lỗi để dễ debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
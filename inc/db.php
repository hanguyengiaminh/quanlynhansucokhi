<?php
$host = "localhost";
$dbname = "qlns_cokhi";
$user = "root";
$pass = ""; 
$port = 3307; // Cổng 3307 của bạn

try {
    // SỬA DÒNG NÀY: Thêm $port vào chuỗi kết nối
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
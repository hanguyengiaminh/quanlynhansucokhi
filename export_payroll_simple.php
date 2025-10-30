<?php
require_once "inc/db.php";
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','hr'])) {
    header("Location: login.php");
    exit;
}
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Bang_Luong_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
$stmt = $pdo->query("
    SELECT p.*, e.full_name 
    FROM payrolls p 
    LEFT JOIN employees e ON p.employee_id = e.id
    ORDER BY p.period_from DESC
");
echo "<html><head><meta charset='utf-8'></head><body style='font-family:Times New Roman;'>";
echo "<h2 style='text-align:center;font-weight:bold;color:#000;'>BẢNG LƯƠNG NHÂN VIÊN - CÔNG TY CƠ KHÍ</h2>";
echo "<table border='1' cellspacing='0' cellpadding='6' style='border-collapse:collapse;width:100%;font-family:Times New Roman;font-size:13px;'>";
echo "<tr style='font-weight:bold;text-align:center;'>
        <th>ID</th>
        <th>Họ tên</th>
        <th>Từ ngày</th>
        <th>Đến ngày</th>
        <th>Lương gộp (VNĐ)</th>
        <th>Lương thực lĩnh (VNĐ)</th>
        <th>Trạng thái</th>
      </tr>";

$totalGross = 0;
$totalNet = 0;

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr style='text-align:center;'>
            <td>{$r['id']}</td>
            <td style='text-align:left;'>{$r['full_name']}</td>
            <td>{$r['period_from']}</td>
            <td>{$r['period_to']}</td>
            <td style='text-align:right;'>".number_format($r['gross_salary'],0,',','.')."</td>
            <td style='text-align:right;'>".number_format($r['net_salary'],0,',','.')."</td>
            <td>{$r['status']}</td>
          </tr>";
    $totalGross += $r['gross_salary'];
    $totalNet += $r['net_salary'];
}
echo "<tr style='font-weight:bold;background-color:#f9f9f9;text-align:right;'>
        <td colspan='4' style='text-align:center;'>TỔNG CỘNG</td>
        <td>".number_format($totalGross,0,',','.')."</td>
        <td>".number_format($totalNet,0,',','.')."</td>
        <td></td>
      </tr>";

echo "</table>";
echo "<p style='text-align:right;margin-top:15px;font-style:italic;'>Ngày xuất: ".date('d/m/Y H:i')."</p>";
echo "</body></html>";
?>

<?php
// download_payroll.php - PHIÊN BẢN TP.HCM (608 LÊ HỒNG PHONG)
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Đặt múi giờ Việt Nam
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();

$id = $_GET['id'] ?? 0;

// 1. TRUY VẤN DỮ LIỆU
$sql = "
    SELECT p.*, 
           e.full_name, e.employee_code, e.department_id,
           d.name AS department_name, 
           pos.title AS position_title
    FROM payrolls p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions pos ON e.position_id = pos.id
    WHERE p.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) die("Không tìm thấy bảng lương!");
if ($_SESSION['role'] == 'employee' && $payroll['employee_id'] != $_SESSION['employee_id']) {
    die("Không có quyền truy cập!");
}

$emp_id = $payroll['employee_id'];
$start_date = $payroll['period_from'];
$end_date = $payroll['period_to'];

// 2. LẤY LƯƠNG CƠ BẢN TỪ HỢP ĐỒNG
$stmtContract = $pdo->prepare("SELECT salary_base FROM contracts WHERE employee_id = ? AND status = 'active' ORDER BY start_date DESC LIMIT 1");
$stmtContract->execute([$emp_id]);
$contract = $stmtContract->fetch(PDO::FETCH_ASSOC);
$base_salary = $contract['salary_base'] ?? 0;

// 3. LẤY DỮ LIỆU CHẤM CÔNG & OT & PHẠT/THƯỞNG
// Ngày công
$stmtAtt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND status = 'present' AND date BETWEEN ? AND ?");
$stmtAtt->execute([$emp_id, $start_date, $end_date]);
$work_days = $stmtAtt->fetchColumn();

// Giờ OT
$stmtOT = $pdo->prepare("SELECT SUM(overtime_hours) FROM reports WHERE employee_id = ? AND date BETWEEN ? AND ?");
$stmtOT->execute([$emp_id, $start_date, $end_date]);
$ot_hours = $stmtOT->fetchColumn() ?: 0;

// Thưởng/Phạt
$stmtAction = $pdo->prepare("SELECT type, amount, title FROM hr_actions WHERE employee_id = ? AND date BETWEEN ? AND ?");
$stmtAction->execute([$emp_id, $start_date, $end_date]);
$actions = $stmtAction->fetchAll(PDO::FETCH_ASSOC);

$total_reward = 0;
$total_discipline = 0;
$reward_list = [];
$discipline_list = [];

foreach ($actions as $act) {
    if ($act['type'] == 'reward') {
        $total_reward += $act['amount'];
        $reward_list[] = $act['title'];
    } else {
        $total_discipline += $act['amount'];
        $discipline_list[] = $act['title'];
    }
}

// 4. TÍNH TOÁN LOGIC
$standard_days = 26; // Công chuẩn
$salary_per_day = ($base_salary > 0) ? ($base_salary / $standard_days) : 0;
$salary_actual_work = $salary_per_day * $work_days;

// Tính bảo hiểm (10.5% lương cơ bản - Mức chung toàn quốc)
$bhxh = $base_salary * 0.08;   // 8%
$bhyt = $base_salary * 0.015;  // 1.5%
$bhtn = $base_salary * 0.01;   // 1%
$total_insurance = $bhxh + $bhyt + $bhtn;

// Cân đối số liệu với DB
$stored_gross = $payroll['gross_salary'];
$stored_net = $payroll['net_salary'];

// Thu nhập khác (Điều chỉnh để khớp Gross)
$other_income = $stored_gross - ($salary_actual_work + $total_reward);

// Tên file tải về
$filename = "PhieuLuong_" . $payroll['employee_code'] . "_" . date('m_Y', strtotime($end_date)) . ".pdf";
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Phiếu lương - <?= htmlspecialchars($payroll['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
    body {
        background: #e9ecef;
        font-family: 'Times New Roman', serif;
        font-size: 14px;
    }

    .container {
        max-width: 800px;
        margin-top: 20px;
        margin-bottom: 50px;
    }

    #invoice-content {
        background: white;
        padding: 40px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-radius: 0;
    }

    .company-name {
        font-weight: bold;
        font-size: 20px;
        text-transform: uppercase;
        color: #0d6efd;
        margin-bottom: 0;
    }

    .company-address {
        font-size: 13px;
        color: #555;
    }

    .invoice-header {
        text-align: center;
        margin-top: 20px;
        margin-bottom: 30px;
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
    }

    .invoice-title {
        font-size: 26px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .table-salary th {
        background-color: #f8f9fa;
        text-align: center;
        font-weight: bold;
        border-bottom: 2px solid #dee2e6;
    }

    .group-row {
        background-color: #e9ecef;
        font-weight: bold;
        font-style: italic;
    }

    .total-gross {
        font-weight: bold;
        color: #0d6efd;
    }

    .total-net {
        font-weight: bold;
        font-size: 18px;
        color: #198754;
        background-color: #d1e7dd;
    }

    .signature-section {
        margin-top: 50px;
    }
    </style>
</head>

<body>

    <div class="container">
        <div class="d-flex justify-content-between mb-3 no-print">
            <a href="javascript:history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
            <button id="downloadBtn" class="btn btn-primary btn-lg"><i class="bi bi-download"></i> Tải PDF</button>
        </div>

        <div id="invoice-content">
            <div class="row align-items-top">
                <div class="col-2 text-center">
                    <img src="assets/img/logo.png" style="width: 80px;">
                </div>
                <div class="col-10">
                    <div class="company-name">CÔNG TY TNHH CƠ KHÍ Ý TƯỞNG</div>
                    <div class="company-address">
                        <strong>Trụ sở:</strong> 608 Lê Hồng Phong, Phường Tân Bình, Thành phố Hồ Chí Minh, VN<br>
                        <strong>MST:</strong> 0312345678 | <strong>Hotline:</strong> 0909.999.888
                    </div>
                </div>
            </div>

            <div class="invoice-header">
                <div class="invoice-title">PHIẾU LƯƠNG</div>
                <div>Kỳ thanh toán: Tháng <?= date('m/Y', strtotime($end_date)) ?></div>
                <small class="fst-italic">(Ban hành tự động ngày <?= date('d/m/Y') ?>)</small>
            </div>

            <div class="row mb-4">
                <div class="col-6">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td style="width: 100px;">Họ tên:</td>
                            <td><strong><?= htmlspecialchars($payroll['full_name']) ?></strong></td>
                        </tr>
                        <tr>
                            <td>Mã NV:</td>
                            <td><?= htmlspecialchars($payroll['employee_code']) ?></td>
                        </tr>
                        <tr>
                            <td>Phòng ban:</td>
                            <td><?= htmlspecialchars($payroll['department_name']) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-6">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td style="width: 120px;">Chức vụ:</td>
                            <td><?= htmlspecialchars($payroll['position_title']) ?></td>
                        </tr>
                        <tr>
                            <td>Lương HĐ:</td>
                            <td><strong><?= number_format($base_salary, 0, ',', '.') ?> VNĐ</strong></td>
                        </tr>
                        <tr>
                            <td>Ngày công:</td>
                            <td><?= $work_days ?> / <?= $standard_days ?> ngày</td>
                        </tr>
                    </table>
                </div>
            </div>

            <table class="table table-bordered table-salary">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Nội dung</th>
                        <th style="width: 150px;">Chi tiết</th>
                        <th style="width: 150px;" class="text-end">Thành tiền (VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="group-row">
                        <td colspan="4">I. CÁC KHOẢN THU NHẬP</td>
                    </tr>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Lương theo ngày công</td>
                        <td class="text-center"><?= $work_days ?> ngày</td>
                        <td class="text-end"><?= number_format($salary_actual_work, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Phụ cấp / Thưởng
                            <?php if($reward_list) echo "<br><small class='text-success'>(" . implode(', ', $reward_list) . ")</small>"; ?>
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-end"><?= number_format($total_reward, 0, ',', '.') ?></td>
                    </tr>
                    <?php if($other_income != 0): ?>
                    <tr>
                        <td class="text-center">3</td>
                        <td>Làm thêm giờ (OT) / Khác</td>
                        <td class="text-center"><?= $ot_hours ?> giờ OT</td>
                        <td class="text-end"><?= number_format($other_income, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3" class="text-end total-gross">TỔNG THU NHẬP (GROSS):</td>
                        <td class="text-end total-gross"><?= number_format($stored_gross, 0, ',', '.') ?></td>
                    </tr>

                    <tr class="group-row">
                        <td colspan="4">II. CÁC KHOẢN KHẤU TRỪ</td>
                    </tr>
                    <tr>
                        <td class="text-center">1</td>
                        <td>BHXH (8%)</td>
                        <td class="text-center">8%</td>
                        <td class="text-end text-danger"><?= number_format($bhxh, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>BHYT (1.5%)</td>
                        <td class="text-center">1.5%</td>
                        <td class="text-end text-danger"><?= number_format($bhyt, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td>BHTN (1%)</td>
                        <td class="text-center">1%</td>
                        <td class="text-end text-danger"><?= number_format($bhtn, 0, ',', '.') ?></td>
                    </tr>
                    <?php 
                    $other_deduction = $stored_gross - $stored_net - $total_insurance;
                ?>
                    <?php if(abs($other_deduction) > 1000): ?>
                    <tr>
                        <td class="text-center">4</td>
                        <td>Thuế TNCN / Phạt / Khác
                            <?php if($discipline_list) echo "<br><small class='text-danger'>(" . implode(', ', $discipline_list) . ")</small>"; ?>
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-end text-danger"><?= number_format($other_deduction, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr class="total-net">
                        <td colspan="3" class="text-end">THỰC LĨNH (NET):</td>
                        <td class="text-end"><?= number_format($stored_net, 0, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-3 fst-italic small text-muted">
                * Các khoản bảo hiểm được tính trên mức lương hợp đồng theo quy định hiện hành tại TP.HCM (Vùng I).
            </div>

            <div class="row signature-section text-center">
                <div class="col-4">
                    <strong>Người lập biểu</strong><br>
                    <small>(Ký, họ tên)</small>
                </div>
                <div class="col-4">
                    <strong>Giám đốc</strong><br>
                    <small>(Ký, đóng dấu)</small>
                </div>
                <div class="col-4">
                    <strong>Người nhận</strong><br>
                    <small>(Ký, xác nhận)</small>
                    <br><br><br><br>
                    <strong><?= htmlspecialchars($payroll['full_name']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('downloadBtn').addEventListener('click', function() {
        const element = document.getElementById('invoice-content');
        const opt = {
            margin: 0.4,
            filename: '<?= $filename ?>',
            image: {
                type: 'jpeg',
                quality: 1
            },
            html2canvas: {
                scale: 2,
                useCORS: true
            },
            jsPDF: {
                unit: 'in',
                format: 'a4',
                orientation: 'portrait'
            }
        };
        html2pdf().set(opt).from(element).save();
    });
    </script>

</body>

</html>
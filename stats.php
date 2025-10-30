<?php
include "inc/header.php";
require_once "inc/db.php";
check_role(['admin','hr']);

// 1. Tổng lương theo nhân viên
$salaryByEmployee = $pdo->query("
    SELECT e.full_name, SUM(p.net_salary) AS total_salary
    FROM payrolls p
    LEFT JOIN employees e ON p.employee_id = e.id
    GROUP BY p.employee_id
    ORDER BY total_salary DESC
")->fetchAll(PDO::FETCH_ASSOC);

$labelsEmp = [];
$valuesEmp = [];
foreach($salaryByEmployee as $row){
    $labelsEmp[] = $row['full_name'];
    $valuesEmp[] = (float)$row['total_salary'];
}

// 2. Tổng lương theo phòng ban
$salaryByDept = $pdo->query("
    SELECT d.name AS department, SUM(p.net_salary) AS total_salary
    FROM payrolls p
    LEFT JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    GROUP BY d.id
")->fetchAll(PDO::FETCH_ASSOC);

$labelsDept = [];
$valuesDept = [];
foreach($salaryByDept as $row){
    $labelsDept[] = $row['department'] ?? 'Chưa có';
    $valuesDept[] = (float)$row['total_salary'];
}

// 3. Tổng số giờ làm của nhân viên (giả sử dựa vào bảng reports)
$hoursByEmployee = $pdo->query("
    SELECT e.full_name, SUM(r.hours_worked) AS total_hours
    FROM reports r
    LEFT JOIN employees e ON r.employee_id = e.id
    GROUP BY r.employee_id
")->fetchAll(PDO::FETCH_ASSOC);

$labelsHours = [];
$valuesHours = [];
foreach($hoursByEmployee as $row){
    $labelsHours[] = $row['full_name'];
    $valuesHours[] = (float)$row['total_hours'];
}

// 4. Số nhân viên theo trạng thái bảng lương
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) AS count
    FROM payrolls
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

$labelsStatus = [];
$valuesStatus = [];
$colorsStatus = ['#ffc107','#17a2b8','#28a745'];
foreach($statusCounts as $row){
    $labelsStatus[] = ucfirst($row['status']);
    $valuesStatus[] = (int)$row['count'];
}
?>

<h2 class="text-dark mb-4">Thống kê tổng hợp</h2>

<div class="row g-4">

    <!-- Biểu đồ 1: Tổng lương theo nhân viên -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Tổng lương theo nhân viên</h5>
                <canvas id="salaryEmpChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Biểu đồ 2: Tổng lương theo phòng ban -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Tổng lương theo phòng ban</h5>
                <canvas id="salaryDeptChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Biểu đồ 3: Tổng số giờ làm của nhân viên -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Tổng số giờ làm của nhân viên</h5>
                <canvas id="hoursChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Biểu đồ 4: Số nhân viên theo trạng thái bảng lương -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Số nhân viên theo trạng thái bảng lương</h5>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Biểu đồ 1: Tổng lương theo nhân viên
new Chart(document.getElementById('salaryEmpChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsEmp) ?>,
        datasets: [{
            label: 'Tổng lương (VNĐ)',
            data: <?= json_encode($valuesEmp) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive:true,
        scales:{ y:{ beginAtZero:true, ticks:{ callback: function(v){ return v.toLocaleString() + ' VNĐ'; } } } }
    }
});

// Biểu đồ 2: Tổng lương theo phòng ban
new Chart(document.getElementById('salaryDeptChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsDept) ?>,
        datasets: [{
            label: 'Tổng lương (VNĐ)',
            data: <?= json_encode($valuesDept) ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.7)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true, ticks:{ callback: function(v){ return v.toLocaleString() + ' VNĐ'; } } } } }
});

// Biểu đồ 3: Tổng số giờ làm
new Chart(document.getElementById('hoursChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsHours) ?>,
        datasets: [{
            label: 'Tổng số giờ',
            data: <?= json_encode($valuesHours) ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.4)',
            borderColor: 'rgba(255, 206, 86, 1)',
            fill:true,
            tension:0.3
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});

// Biểu đồ 4: Trạng thái bảng lương
new Chart(document.getElementById('statusChart').getContext('2d'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($labelsStatus) ?>,
        datasets:[{
            data: <?= json_encode($valuesStatus) ?>,
            backgroundColor: <?= json_encode($colorsStatus) ?>
        }]
    },
    options:{ responsive:true }
});
</script>

<?php include "inc/footer.php"; ?>

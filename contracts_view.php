<?php
include "inc/header.php";
check_login();

if ($_SESSION['role'] == 'employee') {
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE employee_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $contracts = $pdo->query("SELECT c.*, e.full_name FROM contracts c LEFT JOIN employees e ON c.employee_id=e.id")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="bi bi-file-earmark-text"></i> Danh sách hợp đồng</h2>
        <a href="add_contract.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Thêm hợp đồng</a>
    </div>

    <div class="table-responsive shadow-sm rounded">
        <table id="contractTable" class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th><i class="bi bi-person-badge"></i> Nhân viên</th>
                    <th><i class="bi bi-file-earmark"></i> Loại hợp đồng</th>
                    <th><i class="bi bi-calendar-check"></i> Ngày bắt đầu</th>
                    <th><i class="bi bi-calendar-x"></i> Ngày kết thúc</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($contracts as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= $c['full_name'] ?? '<span class="text-muted">Không xác định</span>' ?></td>
                    <td><span class="badge bg-info text-dark"><?= $c['type'] ?></span></td>
                    <td><?= date("d/m/Y", strtotime($c['start_date'])) ?></td>
                    <td><?= date("d/m/Y", strtotime($c['end_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#contractTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
        }
    });
});
</script>
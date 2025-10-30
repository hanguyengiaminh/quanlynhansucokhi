<?php
include "inc/header.php";
require_once "inc/db.php";
check_role(['admin','hr']);
// Xử lý thêm bảng lương
if(isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO payrolls (employee_id, period_from, period_to, gross_salary, net_salary, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['period_from'],
        $_POST['period_to'],
        $_POST['gross_salary'],
        $_POST['net_salary'],
        $_POST['status']
    ]);
    header("Location: payrolls.php?msg=added");
    exit;
}
// Xử lý sửa bảng lương
if(isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE payrolls SET employee_id=?, period_from=?, period_to=?, gross_salary=?, net_salary=?, status=? WHERE id=?");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['period_from'],
        $_POST['period_to'],
        $_POST['gross_salary'],
        $_POST['net_salary'],
        $_POST['status'],
        $_POST['id']
    ]);
    header("Location: payrolls.php?msg=updated");
    exit;
}

// ------------------------
// Xử lý xóa
// ------------------------
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM payrolls WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: payrolls.php?msg=deleted");
    exit;
}

// ------------------------
// Dữ liệu
// ------------------------
$employees = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
$payrolls = $pdo->query("
    SELECT p.*, e.full_name 
    FROM payrolls p 
    LEFT JOIN employees e ON p.employee_id = e.id
    ORDER BY p.period_from DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý bảng lương</h2>

    <!-- Thông báo -->
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg']=='added'): ?>
            <div class="alert alert-success"> Thêm bảng lương thành công!</div>
        <?php elseif($_GET['msg']=='updated'): ?>
            <div class="alert alert-info"> Cập nhật bảng lương thành công!</div>
        <?php elseif($_GET['msg']=='deleted'): ?>
            <div class="alert alert-success"> Xóa bảng lương thành công!</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="mb-3 d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Thêm bảng lương</button>
        <a href="export_payroll_simple.php" class="btn btn-success">Xuất Excel / CSV</a>
    </div>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th>ID</th>
                <th>Nhân viên</th>
                <th>Từ ngày</th>
                <th>Đến ngày</th>
                <th>Lương gộp</th>
                <th>Lương thực lĩnh</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($payrolls as $p): ?>
            <tr>
                <td class="text-center"><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td class="text-center"><?= $p['period_from'] ?></td>
                <td class="text-center"><?= $p['period_to'] ?></td>
                <td class="text-end"><?= number_format($p['gross_salary'],0,',','.') ?></td>
                <td class="text-end"><?= number_format($p['net_salary'],0,',','.') ?></td>
                <td class="text-center"><?= ucfirst($p['status']) ?></td>
                <td class="text-center">>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa bảng lương này?')" title="Xóa">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </td>
            </tr>

            <!-- Modal Sửa -->
            <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header bg-warning text-white">
                                <h5 class="modal-title">Sửa bảng lương</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label>Nhân viên</label>
                                        <select name="employee_id" class="form-control" required>
                                            <?php foreach($employees as $e): ?>
                                                <option value="<?= $e['id'] ?>" <?= $e['id']==$p['employee_id']?'selected':'' ?>>
                                                    <?= htmlspecialchars($e['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Từ ngày</label>
                                        <input type="date" name="period_from" class="form-control" value="<?= $p['period_from'] ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Đến ngày</label>
                                        <input type="date" name="period_to" class="form-control" value="<?= $p['period_to'] ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Lương gộp</label>
                                        <input type="number" name="gross_salary" class="form-control" value="<?= $p['gross_salary'] ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Lương thực lĩnh</label>
                                        <input type="number" name="net_salary" class="form-control" value="<?= $p['net_salary'] ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Trạng thái</label>
                                        <select name="status" class="form-control">
                                            <option value="draft" <?= $p['status']=='draft'?'selected':'' ?>>Nháp</option>
                                            <option value="finalized" <?= $p['status']=='finalized'?'selected':'' ?>>Chốt</option>
                                            <option value="paid" <?= $p['status']=='paid'?'selected':'' ?>>Đã trả</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="edit" class="btn btn-warning">Lưu</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Thêm -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm bảng lương</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <?php foreach($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Từ ngày</label>
                            <input type="date" name="period_from" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Đến ngày</label>
                            <input type="date" name="period_to" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương gộp</label>
                            <input type="number" name="gross_salary" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương thực lĩnh</label>
                            <input type="number" name="net_salary" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="draft">Nháp</option>
                                <option value="finalized">Chốt</option>
                                <option value="paid">Đã trả</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-primary">Thêm</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "inc/footer.php"; ?>

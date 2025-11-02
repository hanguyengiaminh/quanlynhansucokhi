<?php
// PHẦN 1: TOÀN BỘ LOGIC XỬ LÝ (ĐƯỢC ĐƯA LÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

// Kiểm tra vai trò trước khi xử lý bất cứ điều gì
check_login();
check_role(['admin','hr']);

// Xử lý thêm bảng lương (Không thay đổi)
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
// Xử lý sửa bảng lương (Không thay đổi)
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

// Xử lý xóa (Không thay đổi)
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM payrolls WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: payrolls.php?msg=deleted");
    exit;
}

// PHẦN 2: CHỈ HIỂN THỊ HTML SAU KHI LOGIC ĐÃ XONG
include "inc/header.php"; 

// PHẦN 3: LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$employees = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);

// *** THAY ĐỔI 1: Xử lý tìm kiếm và sắp xếp động ***
$search_term = $_GET['search'] ?? '';
$params = [];

// Bắt đầu câu SQL
$sql = "
    SELECT p.*, e.full_name, e.join_date 
    FROM payrolls p 
    LEFT JOIN employees e ON p.employee_id = e.id
";

// Thêm điều kiện TÌM KIẾM nếu có
if (!empty($search_term)) {
    $sql .= " WHERE e.full_name LIKE ?";
    $params[] = '%' . $search_term . '%';
}

// Thêm logic SẮP XẾP:
// 1. Ưu tiên ngày vào làm sớm nhất (ASC)
// 2. Nếu cùng ngày vào, ưu tiên lương thực lĩnh cao nhất (DESC)
$sql .= " ORDER BY e.join_date ASC, p.net_salary DESC";

// Thực thi câu lệnh
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý bảng lương</h2>

    <?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg']=='added'): ?>
    <div class="alert alert-success"> Thêm bảng lương thành công!</div>
    <?php elseif($_GET['msg']=='updated'): ?>
    <div class="alert alert-info"> Cập nhật bảng lương thành công!</div>
    <?php elseif($_GET['msg']=='deleted'): ?>
    <div class="alert alert-success"> Xóa bảng lương thành công!</div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6 d-flex gap-2 mb-2 mb-md-0">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Thêm bảng lương
            </button>
            <a href="export_payroll_simple.php" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Xuất Excel
            </a>
        </div>

        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Tìm theo tên nhân viên..."
                    value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm glass">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="50">ID</th>
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
                        <?php $stt = 1; ?>
                        <?php foreach($payrolls as $p): ?>
                        <tr>
                            <td class="text-center"><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($p['full_name']) ?></td>
                            <td class="text-center"><?= $p['period_from'] ?></td>
                            <td class="text-center"><?= $p['period_to'] ?></td>
                            <td class="text-end"><?= number_format($p['gross_salary'],0,',','.') ?></td>
                            <td class="text-end"><?= number_format($p['net_salary'],0,',','.') ?></td>
                            <td class="text-center"><?= ucfirst($p['status']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?= $p['id'] ?>" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Xóa bảng lương này?')" title="Xóa">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </td>
                        </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach($payrolls as $p): ?>
<div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
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
                            <input type="date" name="period_from" class="form-control" value="<?= $p['period_from'] ?>"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label>Đến ngày</label>
                            <input type="date" name="period_to" class="form-control" value="<?= $p['period_to'] ?>"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương gộp</label>
                            <input type="number" name="gross_salary" class="form-control"
                                value="<?= $p['gross_salary'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lương thực lĩnh</label>
                            <input type="number" name="net_salary" class="form-control" value="<?= $p['net_salary'] ?>"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="draft" <?= $p['status']=='draft'?'selected':'' ?>>Nháp
                                </option>
                                <option value="finalized" <?= $p['status']=='finalized'?'selected':'' ?>>
                                    Chốt</option>
                                <option value="paid" <?= $p['status']=='paid'?'selected':'' ?>>Đã trả
                                </option>
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


<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
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
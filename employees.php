<?php
// PHẦN 1: TOÀN BỘ LOGIC XỬ LÝ (ĐƯỢC ĐƯA LÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
check_role(['admin','hr']);

// Thêm nhân viên
if(isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO employees (full_name,dob,gender,address,phone,email,join_date,department_id,position_id) 
    VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['full_name'] ?? '',
        $_POST['dob'] ?? '',
        $_POST['gender'] ?? '',
        $_POST['address'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['email'] ?? '',
        $_POST['join_date'] ?? '',
        $_POST['department_id'] ?? '',
        $_POST['position_id'] ?? ''
    ]);

    $lastId = $pdo->lastInsertId();
    $employee_code = 'NV' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE employees SET employee_code=? WHERE id=?")->execute([$employee_code, $lastId]);

    header("Location: employees.php?msg=added");
    exit;
}

// Xóa nhân viên an toàn
if(isset($_GET['delete'])) {
    $employee_id = $_GET['delete'];

    // Xóa dữ liệu liên quan trước
    $pdo->prepare("DELETE FROM attendance WHERE employee_id=?")->execute([$employee_id]);
    $pdo->prepare("DELETE FROM payrolls WHERE employee_id=?")->execute([$employee_id]);
    $pdo->prepare("DELETE FROM contracts WHERE employee_id=?")->execute([$employee_id]);
    $pdo->prepare("DELETE FROM hr_actions WHERE employee_id=?")->execute([$employee_id]); 
    $pdo->prepare("DELETE FROM reports WHERE employee_id=?")->execute([$employee_id]); // Thêm xóa reports
    $pdo->prepare("DELETE FROM leaves WHERE employee_id=?")->execute([$employee_id]); // Thêm xóa leaves
    
    // Cuối cùng, xóa người dùng và nhân viên
    $pdo->prepare("DELETE FROM users WHERE employee_id=?")->execute([$employee_id]); // Xóa user liên kết
    $pdo->prepare("DELETE FROM employees WHERE id=?")->execute([$employee_id]); // Xóa nhân viên

    header("Location: employees.php?msg=deleted");
    exit;
}

// Sửa nhân viên
if(isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE employees SET full_name=?, dob=?, gender=?, address=?, phone=?, email=?, join_date=?, department_id=?, position_id=? WHERE id=?");
    $stmt->execute([
        $_POST['full_name'] ?? '',
        $_POST['dob'] ?? '',
        $_POST['gender'] ?? '',
        $_POST['address'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['email'] ?? '',
        $_POST['join_date'] ?? '',
        $_POST['department_id'] ?? '',
        $_POST['position_id'] ?? '',
        $_POST['id'] ?? 0
    ]);
    header("Location: employees.php?msg=updated");
    exit;
}

// PHẦN 2: HIỂN THỊ HTML (SAU KHI LOGIC ĐÃ CHẠY XONG)
include "inc/header.php"; 

// Lấy danh sách nhân viên, phòng ban, chức vụ
$employees = $pdo->query("SELECT e.*, d.name as department_name, p.title as position_title 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id
    ORDER BY e.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$positions = $pdo->query("SELECT * FROM positions ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="mb-4 fw-bold">Quản lý nhân viên</h2>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'added') { ?>
<div class="alert alert-success"> Thêm nhân viên thành công!</div>
<?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'updated') { ?>
<div class="alert alert-info"> Cập nhật nhân viên thành công!</div>
<?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
<div class="alert alert-success"> Xóa nhân viên thành công!</div>
<?php } ?>

<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-plus-circle me-1"></i> Thêm nhân viên
</button>

<div class="card shadow-sm glass">
    <div class="card-body">
        <div class="table-responsive">
            <table id="employeeTable" class="table table-bordered table-hover" style="width:100%">
                <thead class="text-center">
                    <tr>
                        <th>ID</th>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th>Chức vụ</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Ngày vào</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach($employees as $e) { ?>
                    <tr>
                        <td class="text-center"><?= $e['id'] ?></td>
                        <td class="text-center"><?= $e['employee_code'] ?></td>
                        <td><?= htmlspecialchars($e['full_name']) ?></td>
                        <td><?= htmlspecialchars($e['department_name']) ?></td>
                        <td><?= htmlspecialchars($e['position_title']) ?></td>
                        <td><?= htmlspecialchars($e['email']) ?></td>
                        <td><?= htmlspecialchars($e['phone']) ?></td>
                        <td class="text-center"><?= $e['join_date'] ?></td>
                        <td class="text-center">
                            <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $e['id'] ?>" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Xóa nhân viên này và tất cả dữ liệu liên quan?')" title="Xóa">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </td>
                    </tr>

                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm nhân viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label>Họ tên</label><input name="full_name" class="form-control"
                                required></div>
                        <div class="col-md-3"><label>Ngày sinh</label><input type="date" name="dob" class="form-control"
                                required></div>
                        <div class="col-md-3"><label>Giới tính</label>
                            <select name="gender" class="form-control" required>
                                <option value="M">Nam</option>
                                <option value="F">Nữ</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label>Địa chỉ</label><input name="address" class="form-control"></div>
                        <div class="col-md-3"><label>Điện thoại</label><input name="phone" class="form-control"></div>
                        <div class="col-md-3"><label>Email</label><input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-3"><label>Ngày vào làm</label><input type="date" name="join_date"
                                class="form-control"></div>
                        <div class="col-md-3"><label>Phòng ban</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <?php foreach($departments as $d) { ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label>Chức vụ</label>
                            <select name="position_id" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <?php foreach($positions as $p) { ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                                <?php } ?>
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

<?php foreach($employees as $e) { ?>
<div class="modal fade" id="editModal<?= $e['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa nhân viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label>Họ tên</label><input name="full_name" class="form-control"
                                value="<?= htmlspecialchars($e['full_name']) ?>" required></div>
                        <div class="col-md-3"><label>Ngày sinh</label><input type="date" name="dob" class="form-control"
                                value="<?= $e['dob'] ?>" required></div>
                        <div class="col-md-3"><label>Giới tính</label>
                            <select name="gender" class="form-control" required>
                                <option value="M" <?= $e['gender']=='M'?'selected':'' ?>>Nam
                                </option>
                                <option value="F" <?= $e['gender']=='F'?'selected':'' ?>>Nữ</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label>Địa chỉ</label><input name="address" class="form-control"
                                value="<?= htmlspecialchars($e['address']) ?>">
                        </div>
                        <div class="col-md-3"><label>Điện thoại</label><input name="phone" class="form-control"
                                value="<?= htmlspecialchars($e['phone']) ?>">
                        </div>
                        <div class="col-md-3"><label>Email</label><input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($e['email']) ?>">
                        </div>
                        <div class="col-md-3"><label>Ngày vào làm</label><input type="date" name="join_date"
                                class="form-control" value="<?= $e['join_date'] ?>"></div>
                        <div class="col-md-3"><label>Phòng ban</label>
                            <select name="department_id" class="form-control" required>
                                <?php foreach($departments as $d) { ?>
                                <option value="<?= $d['id'] ?>" <?= $e['department_id']==$d['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($d['name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label>Chức vụ</label>
                            <select name="position_id" class="form-control" required>
                                <?php foreach($positions as $p) { ?>
                                <option value="<?= $p['id'] ?>" <?= $e['position_id']==$p['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($p['title']) ?></option>
                                <?php } ?>
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
<?php } ?>


<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#employeeTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
        },
        order: [
            [0, 'desc']
        ] // Sắp xếp theo ID (cột 0) giảm dần
    });
});
</script>

<?php include "inc/footer.php"; ?>
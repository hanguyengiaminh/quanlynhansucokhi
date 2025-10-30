<?php
include "inc/header.php";
require_once "inc/db.php";
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

    header("Location: employees.php");
    exit;
}
// Xóa nhân viên an toàn
if(isset($_GET['delete'])) {
    $employee_id = $_GET['delete'];

    $pdo->prepare("DELETE FROM attendance WHERE employee_id=?")->execute([$employee_id]);
    $pdo->prepare("DELETE FROM payrolls WHERE employee_id=?")->execute([$employee_id]);
    $pdo->prepare("DELETE FROM contracts WHERE employee_id=?")->execute([$employee_id]);
    $pdo->prepare("DELETE FROM hr_actions WHERE employee_id=?")->execute([$employee_id]); 
    $pdo->prepare("DELETE FROM employees WHERE id=?")->execute([$employee_id]);

    header("Location: employees.php");
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
    header("Location: employees.php");
    exit;
}

// Lấy danh sách nhân viên
$employees = $pdo->query("SELECT e.*, d.name as department_name, p.title as position_title 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id=d.id
    LEFT JOIN positions p ON e.position_id=p.id")->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$positions = $pdo->query("SELECT * FROM positions")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Quản lý nhân viên</h2>
<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Thêm nhân viên</button>

<table class="table table-bordered table-hover">
    <thead class="table-dark text-center">
        <tr>
            <th>ID</th><th>Mã NV</th><th>Họ tên</th><th>Ngày sinh</th><th>Giới tính</th>
            <th>Phòng ban</th><th>Chức vụ</th><th>Email</th><th>Điện thoại</th><th>Ngày vào</th><th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($employees as $e) { ?>
        <tr>
            <td><?= $e['id'] ?></td>
            <td><?= $e['employee_code'] ?></td>
            <td><?= $e['full_name'] ?></td>
            <td><?= $e['dob'] ?></td>
            <td><?= $e['gender'] ?></td>
            <td><?= $e['department_name'] ?></td>
            <td><?= $e['position_title'] ?></td>
            <td><?= $e['email'] ?></td>
            <td><?= $e['phone'] ?></td>
            <td><?= $e['join_date'] ?></td>
            <td class="text-center">
                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $e['id'] ?>" title="Sửa">
                    <i class="bi bi-pencil"></i>
                </a>
              <a href="?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa nhân viên này?')" title="Xóa">
                    <i class="bi bi-x-lg"></i>
                </a>
            </td>
        </tr>

        <!-- Modal Sửa -->
        <div class="modal fade" id="editModal<?= $e['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="POST">
              <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Sửa nhân viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                <div class="row g-3">
                  <div class="col-md-6"><label>Họ tên</label><input name="full_name" class="form-control" value="<?= $e['full_name'] ?>" required></div>
                  <div class="col-md-3"><label>Ngày sinh</label><input type="date" name="dob" class="form-control" value="<?= $e['dob'] ?>" required></div>
                  <div class="col-md-3"><label>Giới tính</label>
                      <select name="gender" class="form-control" required>
                          <option value="M" <?= $e['gender']=='M'?'selected':'' ?>>Nam</option>
                          <option value="F" <?= $e['gender']=='F'?'selected':'' ?>>Nữ</option>
                      </select>
                  </div>
                  <div class="col-md-6"><label>Địa chỉ</label><input name="address" class="form-control" value="<?= $e['address'] ?>"></div>
                  <div class="col-md-3"><label>Điện thoại</label><input name="phone" class="form-control" value="<?= $e['phone'] ?>"></div>
                  <div class="col-md-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= $e['email'] ?>"></div>
                  <div class="col-md-3"><label>Ngày vào làm</label><input type="date" name="join_date" class="form-control" value="<?= $e['join_date'] ?>"></div>
                  <div class="col-md-3"><label>Phòng ban</label>
                      <select name="department_id" class="form-control" required>
                          <?php foreach($departments as $d) { ?>
                          <option value="<?= $d['id'] ?>" <?= $e['department_id']==$d['id']?'selected':'' ?>><?= $d['name'] ?></option>
                          <?php } ?>
                      </select>
                  </div>
                  <div class="col-md-3"><label>Chức vụ</label>
                      <select name="position_id" class="form-control" required>
                          <?php foreach($positions as $p) { ?>
                          <option value="<?= $p['id'] ?>" <?= $e['position_id']==$p['id']?'selected':'' ?>><?= $p['title'] ?></option>
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
    </tbody>
</table>

<!-- Modal Thêm nhân viên -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Thêm nhân viên</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label>Họ tên</label><input name="full_name" class="form-control" required></div>
          <div class="col-md-3"><label>Ngày sinh</label><input type="date" name="dob" class="form-control" required></div>
          <div class="col-md-3"><label>Giới tính</label>
              <select name="gender" class="form-control" required>
                  <option value="M">Nam</option>
                  <option value="F">Nữ</option>
              </select>
          </div>
          <div class="col-md-6"><label>Địa chỉ</label><input name="address" class="form-control"></div>
          <div class="col-md-3"><label>Điện thoại</label><input name="phone" class="form-control"></div>
          <div class="col-md-3"><label>Email</label><input type="email" name="email" class="form-control"></div>
          <div class="col-md-3"><label>Ngày vào làm</label><input type="date" name="join_date" class="form-control"></div>
          <div class="col-md-3"><label>Phòng ban</label>
              <select name="department_id" class="form-control" required>
                  <?php foreach($departments as $d) { ?>
                  <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                  <?php } ?>
              </select>
          </div>
          <div class="col-md-3"><label>Chức vụ</label>
              <select name="position_id" class="form-control" required>
                  <?php foreach($positions as $p) { ?>
                  <option value="<?= $p['id'] ?>"><?= $p['title'] ?></option>
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

<?php include "inc/footer.php"; ?>

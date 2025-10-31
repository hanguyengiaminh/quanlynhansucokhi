<?php
// PHẦN 1: XỬ LÝ LOGIC (ĐẶT LÊN TRÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

// 1. Kiểm tra đăng nhập và vai trò (CHỈ ADMIN)
check_login();
check_role(['admin']);

$message = "";
$error = "";

// 2. Xử lý Thêm tài khoản
if (isset($_POST['add'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $employee_id = $_POST['employee_id'] ?: null; // Dùng null nếu rỗng
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Mật khẩu không khớp!";
    } else {
        $hashed_pass = hash('sha256', $password);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, employee_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_pass, $email, $role, $employee_id]);
            header("Location: users.php?msg=added");
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi khi thêm: " . $e->getMessage();
        }
    }
}

// 3. Xử lý Sửa tài khoản (không bao gồm mật khẩu)
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $employee_id = $_POST['employee_id'] ?: null;

    try {
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=?, employee_id=? WHERE id=?");
        $stmt->execute([$username, $email, $role, $employee_id, $id]);
        header("Location: users.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $error = "Lỗi khi cập nhật: " . $e->getMessage();
    }
}

// 4. Xử lý Đặt lại mật khẩu
if (isset($_POST['reset_password'])) {
    $id = $_POST['id'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "Mật khẩu mới không khớp!";
    } else {
        $hashed_pass = hash('sha256', $new_pass);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hashed_pass, $id]);
        header("Location: users.php?msg=pw_reset");
        exit;
    }
}

// 5. Xử lý Xóa tài khoản
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];

    // Rất quan trọng: Ngăn Admin tự xóa tài khoản của mình
    if ($id_to_delete == $_SESSION['user_id']) {
        header("Location: users.php?error=self_delete");
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id_to_delete]);
        header("Location: users.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: users.php?error=delete_failed");
        exit;
    }
}

// PHẦN 2: HIỂN THỊ (HTML)
// Chỉ 'include' header sau khi tất cả logic đã chạy xong
include "inc/header.php";

// 6. Lấy dữ liệu cho trang
// Lấy danh sách tài khoản (JOIN với employees để lấy tên)
$users = $pdo->query("
    SELECT u.*, e.full_name 
    FROM users u 
    LEFT JOIN employees e ON u.employee_id = e.id 
    ORDER BY u.username
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách nhân viên (dùng cho dropdowns)
$employees = $pdo->query("SELECT id, full_name, employee_code FROM employees ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý Tài khoản</h2>

    <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] == 'added') echo '<div class="alert alert-success"> Thêm tài khoản thành công!</div>'; ?>
    <?php if ($_GET['msg'] == 'updated') echo '<div class="alert alert-info"> Cập nhật tài khoản thành công!</div>'; ?>
    <?php if ($_GET['msg'] == 'deleted') echo '<div class="alert alert-success"> Xóa tài khoản thành công!</div>'; ?>
    <?php if ($_GET['msg'] == 'pw_reset') echo '<div class="alert alert-success"> Đặt lại mật khẩu thành công!</div>'; ?>
    <?php elseif (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] == 'self_delete') echo '<div class="alert alert-danger"> Lỗi: Không thể tự xóa tài khoản của mình!</div>'; ?>
    <?php if ($_GET['error'] == 'delete_failed') echo '<div class="alert alert-danger"> Lỗi khi xóa tài khoản.</div>'; ?>
    <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i> Thêm tài khoản
    </button>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Nhân viên liên kết</th>
                <th width="200">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td class="text-center"><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td class="text-center">
                    <?php 
                            if($u['role'] == 'admin') echo '<span class="badge bg-danger">Admin</span>';
                            elseif($u['role'] == 'hr') echo '<span class="badge bg-info">HR</span>';
                            else echo '<span class="badge bg-secondary">Employee</span>';
                        ?>
                </td>
                <td><?= htmlspecialchars($u['full_name'] ?? 'N/A') ?></td>
                <td class="text-center">
                    <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                        data-bs-target="#editModal<?= $u['id'] ?>" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="#" class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#resetPassModal<?= $u['id'] ?>" title="Đặt lại Mật khẩu">
                        <i class="bi bi-key-fill"></i>
                    </a>
                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                        onclick="return confirm('Xóa tài khoản này?')" title="Xóa">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </td>
            </tr>

            <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header bg-warning text-white">
                                <h5 class="modal-title">Sửa tài khoản</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Tên đăng nhập</label>
                                    <input type="text" name="username" class="form-control"
                                        value="<?= htmlspecialchars($u['username']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?= htmlspecialchars($u['email']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Vai trò</label>
                                    <select name="role" class="form-control" required>
                                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                        <option value="hr" <?= $u['role']=='hr'?'selected':'' ?>>HR</option>
                                        <option value="employee" <?= $u['role']=='employee'?'selected':'' ?>>Employee
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nhân viên liên kết</label>
                                    <select name="employee_id" class="form-control">
                                        <option value="">-- Không liên kết --</option>
                                        <?php foreach ($employees as $e): ?>
                                        <option value="<?= $e['id'] ?>"
                                            <?= $u['employee_id']==$e['id']?'selected':'' ?>>
                                            <?= htmlspecialchars($e['full_name']) ?> (<?= $e['employee_code'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
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

            <div class="modal fade" id="resetPassModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header bg-secondary text-white">
                                <h5 class="modal-title">Đặt lại mật khẩu cho: <?= htmlspecialchars($u['username']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu mới</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="reset_password" class="btn btn-primary">Đặt lại</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên đăng nhập</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận Mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vai trò</label>
                        <select name="role" class="form-control" required>
                            <option value="employee">Employee</option>
                            <option value="hr">HR</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhân viên liên kết (Nếu có)</label>
                        <select name="employee_id" class="form-control">
                            <option value="">-- Không liên kết --</option>
                            <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>">
                                <?= htmlspecialchars($e['full_name']) ?> (<?= $e['employee_code'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
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
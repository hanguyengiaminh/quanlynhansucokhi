<?php
// PHẦN 1: TOÀN BỘ LOGIC XỬ LÝ (ĐƯỢC ĐƯA LÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
check_role(['admin','hr']);

// Xử lý Thêm phòng ban (Không thay đổi)
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
    $stmt->execute([$_POST['name'], $_POST['description']]);
    header("Location: departments.php?msg=added");
    exit;
}
// Xử lý Sửa phòng ban (Không thay đổi)
if (isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE departments SET name=?, description=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['id']]);
    header("Location: departments.php?msg=updated");
    exit;
}
// Xử lý Xóa phòng ban (Không thay đổi)
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        header("Location: departments.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: departments.php?error=foreignkey");
        exit;
    }
}

// PHẦN 2: HIỂN THỊ HTML (SAU KHI LOGIC ĐÃ CHẠY XONG)
include "inc/header.php";

// *** THAY ĐỔI 1: XỬ LÝ TÌM KIẾM VÀ CẬP NHẬT TRUY VẤN SQL ***
$search_term = $_GET['search'] ?? '';
$params = [];

$sql = "SELECT * FROM departments";

if (!empty($search_term)) {
    // Tìm kiếm ở cột 'name' hoặc 'description'
    $sql .= " WHERE name LIKE ? OR description LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = [$like_term, $like_term];
}

$sql .= " ORDER BY id ASC"; // Sắp xếp theo ID tăng dần

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="mb-4 fw-bold">Quản lý phòng ban</h2>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'added') { ?>
<div class="alert alert-success"> Thêm phòng ban thành công!</div>
<?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'updated') { ?>
<div class="alert alert-info"> Cập nhật phòng ban thành công!</div>
<?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
<div class="alert alert-success"> Xóa phòng ban thành công!</div>
<?php } elseif (isset($_GET['error']) && $_GET['error'] == 'foreignkey') { ?>
<div class="alert alert-danger"> Không thể xóa phòng ban này vì đang có nhân viên thuộc phòng ban!</div>
<?php } ?>

<div class="row mb-3">
    <div class="col-md-6">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm phòng ban
        </button>
    </div>
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Tìm theo tên hoặc mô tả..."
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
                <thead class="text-center">
                    <tr>
                        <th>ID</th>
                        <th>Tên phòng ban</th>
                        <th>Mô tả</th>
                        <th width="150">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $d): ?>
                    <tr>
                        <td class="text-center"><?= $d['id'] ?></td>
                        <td><?= htmlspecialchars($d['name']) ?></td>
                        <td><?= htmlspecialchars($d['description']) ?></td>
                        <td class="text-center">
                            <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $d['id'] ?>" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?delete=<?= $d['id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Xóa phòng ban này?')" title="Xóa">
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

<?php foreach ($departments as $d): ?>
<div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa phòng ban</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Tên phòng ban</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($d['name']) ?>"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control"
                            rows="3"><?= htmlspecialchars($d['description']) ?></textarea>
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
    <div class="modal-dialog">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm phòng ban</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên phòng ban</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
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
<?php
include "inc/header.php";
require_once "inc/db.php";
check_role(['admin','hr']);
// Xử lý Thêm phòng ban
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
    $stmt->execute([$_POST['name'], $_POST['description']]);
    header("Location: departments.php?msg=added");
    exit;
}
// Xử lý Sửa phòng ban
if (isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE departments SET name=?, description=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['id']]);
    header("Location: departments.php?msg=updated");
    exit;
}
// Xử lý Xóa phòng ban 
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

// Lấy danh sách phòng ban
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý phòng ban</h2>

    <!-- Hiển thị thông báo -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added') { ?>
        <div class="alert alert-success"> Thêm phòng ban thành công!</div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'updated') { ?>
        <div class="alert alert-info"> Cập nhật phòng ban thành công!</div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
        <div class="alert alert-success"> Xóa phòng ban thành công!</div>
    <?php } elseif (isset($_GET['error']) && $_GET['error'] == 'foreignkey') { ?>
        <div class="alert alert-danger"> Không thể xóa phòng ban này vì đang có nhân viên thuộc phòng ban!</div>
    <?php } ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
         Thêm phòng ban
    </button>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
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
                        <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $d['id'] ?>" title="Sửa">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="?delete=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa phòng ban này?')" title="Xóa">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </td>
                </tr>

                <!-- Modal Sửa -->
                <div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-warning text-white">
                                    <h5 class="modal-title">Sửa phòng ban</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Tên phòng ban</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($d['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($d['description']) ?></textarea>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
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

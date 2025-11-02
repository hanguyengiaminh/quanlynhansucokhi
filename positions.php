<?php
// BƯỚC 1: BẮT ĐẦU SESSION VÀ GỌI CÁC FILE LOGIC CẦN THIẾT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php"; // Cần cho check_login()
require_once "inc/db.php";   // Cần cho logic CSDL

// BƯỚC 2: KIỂM TRA ĐĂNG NHẬP VÀ VAI TRÒ NGAY LẬP TỨC
check_login();
check_role(['admin','hr']);

// BƯỚC 3: ĐẶT TẤT CẢ LOGIC XỬ LÝ (THÊM/SỬA/XÓA) LÊN TRÊN ĐẦU
// (Phần này giữ nguyên logic cũ của bạn)

// Xử lý Thêm chức vụ
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO positions (title, level, description) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['title'], 
        $_POST['level'], 
        $_POST['description']
    ]);
    header("Location: positions.php?msg=added");
    exit; // Dừng ngay lập tức
}

// Xử lý Sửa chức vụ
if (isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE positions SET title=?, level=?, description=? WHERE id=?");
    $stmt->execute([
        $_POST['title'], 
        $_POST['level'], 
        $_POST['description'], 
        $_POST['id']
    ]);
    header("Location: positions.php?msg=updated");
    exit; // Dừng ngay lập tức
}

// Xử lý Xóa chức vụ 
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM positions WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        header("Location: positions.php?msg=deleted");
        exit; // Dừng ngay lập tức
    } catch (PDOException $e) {
        // Bắt lỗi nếu không thể xóa do ràng buộc khóa ngoại
        header("Location: positions.php?error=foreignkey");
        exit; // Dừng ngay lập tức
    }
}

// BƯỚC 4: CHỈ SAU KHI LOGIC XỬ LÝ XONG (và không bị 'exit'), CHÚNG TA MỚI GỌI HEADER
// 'header.php' sẽ render phần đầu HTML
include "inc/header.php"; 

// BƯỚC 5: LẤY DỮ LIỆU ĐỂ HIỂN THỊ RA TRANG

// *** THAY ĐỔI 1: Sắp xếp theo cấp bậc từ cao xuống thấp bằng lệnh CASE ***
$positions = $pdo->query("
    SELECT * FROM positions 
    ORDER BY
      CASE
        WHEN level = 'Manager' THEN 10
        WHEN level = 'Expert'  THEN 20
        WHEN level = 'Senior'  THEN 30
        WHEN level = 'Mid'     THEN 40
        WHEN level = 'Junior'  THEN 50
        WHEN level = 'Intern'  THEN 60
        ELSE 99  -- Các cấp bậc 'Khác' hoặc rỗng sẽ ở cuối
      END ASC, 
      title ASC  -- Sắp xếp theo tên nếu cùng cấp bậc
")->fetchAll(PDO::FETCH_ASSOC);


// Danh sách cấp bậc cho dropdown
$levels_list = ['Junior', 'Mid', 'Senior', 'Intern', 'Expert', 'Manager', 'Khác'];
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý chức vụ</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added') { ?>
    <div class="alert alert-success"> Thêm chức vụ thành công!</div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'updated') { ?>
    <div class="alert alert-info"> Cập nhật chức vụ thành công!</div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
    <div class="alert alert-success"> Xóa chức vụ thành công!</div>
    <?php } elseif (isset($_GET['error']) && $_GET['error'] == 'foreignkey') { ?>
    <div class="alert alert-danger"> Không thể xóa chức vụ này vì đang có nhân viên thuộc chức vụ!</div>
    <?php } ?>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i> Thêm chức vụ
    </button>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
            <tr>
                <th>ID</th>
                <th>Tên chức vụ</th>
                <th>Cấp bậc</th>
                <th>Mô tả</th>
                <th width="150">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = 1; ?>
            <?php foreach ($positions as $p): ?>
            <tr>
                <td class="text-center"><?= $stt++ ?></td>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['level']) ?></td>
                <td><?= htmlspecialchars($p['description']) ?></td>
                <td class="text-center">
                    <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                        data-bs-target="#editModal<?= $p['id'] ?>" title="Sửa">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                        onclick="return confirm('Xóa chức vụ này?')" title="Xóa">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </td>
            </tr>

            <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header bg-warning text-white">
                                <h5 class="modal-title">Sửa chức vụ</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Tên chức vụ</label>
                                    <input type="text" name="title" class="form-control"
                                        value="<?= htmlspecialchars($p['title']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cấp bậc</label>
                                    <select name="level" class="form-control">
                                        <option value="">-- Chọn cấp bậc --</option>
                                        <?php foreach ($levels_list as $level): ?>
                                        <option value="<?= htmlspecialchars($level) ?>"
                                            <?php if (htmlspecialchars($p['level']) == $level) echo 'selected'; ?>>
                                            <?= htmlspecialchars($level) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mô tả</label>
                                    <textarea name="description" class="form-control"
                                        rows="3"><?= htmlspecialchars($p['description']) ?></textarea>
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

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm chức vụ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên chức vụ</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cấp bậc</label>
                        <select name="level" class="form-control">
                            <option value="">-- Chọn cấp bậc --</option>
                            <?php foreach ($levels_list as $level): ?>
                            <option value="<?= htmlspecialchars($level) ?>">
                                <?= htmlspecialchars($level) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
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
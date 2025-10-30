<?php
include "inc/header.php";
require_once "inc/db.php";
check_role(['admin','hr']);
// THÊM CHẤM CÔNG
if (isset($_POST['add'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $clock_in = $_POST['clock_in'] ?? null;
    $clock_out = $_POST['clock_out'] ?? null;
    $shift_id = $_POST['shift_id'] ?? null;
    $status = $_POST['status'] ?? 'present';

    $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, clock_in, clock_out, shift_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$employee_id, $date, $clock_in, $clock_out, $shift_id, $status]);
    header("Location: attendance.php?msg=added");
    exit;
}

// SỬA CHẤM CÔNG
if (isset($_POST['edit'])) {
    $id = $_POST['id'] ?? 0;
    $employee_id = $_POST['employee_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $clock_in = $_POST['clock_in'] ?? null;
    $clock_out = $_POST['clock_out'] ?? null;
    $shift_id = $_POST['shift_id'] ?? null;
    $status = $_POST['status'] ?? 'present';

    $stmt = $pdo->prepare("UPDATE attendance SET employee_id=?, date=?, clock_in=?, clock_out=?, shift_id=?, status=? WHERE id=?");
    $stmt->execute([$employee_id, $date, $clock_in, $clock_out, $shift_id, $status, $id]);
    header("Location: attendance.php?msg=updated");
    exit;
}

// XÓA CHẤM CÔNG
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        header("Location: attendance.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: attendance.php?error=delete");
        exit;
    }
}

// LẤY DỮ LIỆU
$attendance = $pdo->query("
    SELECT a.*, e.full_name, s.name AS shift_name 
    FROM attendance a
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN shifts s ON a.shift_id = s.id
    ORDER BY a.date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$shifts = $pdo->query("SELECT id, name FROM shifts ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý chấm công</h2>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'added') echo '<div class="alert alert-success"> Đã thêm bản ghi chấm công!</div>'; ?>
        <?php if ($_GET['msg'] == 'updated') echo '<div class="alert alert-info"> Đã cập nhật chấm công!</div>'; ?>
        <?php if ($_GET['msg'] == 'deleted') echo '<div class="alert alert-success"> Đã xóa bản ghi chấm công!</div>'; ?>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 'delete'): ?>
        <div class="alert alert-danger"> Không thể xóa bản ghi này!</div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Thêm chấm công</button>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>Nhân viên</th>
                    <th>Ngày làm việc</th>
                    <th>Ca làm</th>
                    <th>Giờ vào</th>
                    <th>Giờ ra</th>
                    <th>Trạng thái</th>
                    <th width="150">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $a): ?>
                <tr>
                    <td class="text-center"><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['full_name'] ?? '---') ?></td>
                    <td class="text-center"><?= $a['date'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($a['shift_name'] ?? '---') ?></td>
                    <td class="text-center"><?= $a['clock_in'] ?></td>
                    <td class="text-center"><?= $a['clock_out'] ?></td>
                    <td class="text-center">
                        <?php
                        switch($a['status']) {
                            case 'present': echo '<span class="badge bg-success">Đi làm</span>'; break;
                            case 'absent': echo '<span class="badge bg-danger">Vắng</span>'; break;
                            case 'leave': echo '<span class="badge bg-warning text-dark">Nghỉ phép</span>'; break;
                            case 'ot': echo '<span class="badge bg-info text-dark">Tăng ca</span>'; break;
                        }
                        ?>
                    </td>
                    <td class="text-center">
                      <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $a['id'] ?>" title="Sửa">
                            <i class="bi bi-pencil"></i>
                        </a>
                       <a href="?delete=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa bản ghi này?')" title="Xóa">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </td>
                </tr>

                <!-- Modal Sửa -->
                <div class="modal fade" id="editModal<?= $a['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-warning text-white">
                                    <h5 class="modal-title">Sửa chấm công</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Nhân viên</label>
                                        <select name="employee_id" class="form-control" required>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>" <?= $a['employee_id']==$emp['id']?'selected':'' ?>><?= htmlspecialchars($emp['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ngày</label>
                                        <input type="date" name="date" class="form-control" value="<?= $a['date'] ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ca làm</label>
                                        <select name="shift_id" class="form-control">
                                            <option value="">-- Chọn ca --</option>
                                            <?php foreach ($shifts as $s): ?>
                                                <option value="<?= $s['id'] ?>" <?= $a['shift_id']==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Giờ vào</label>
                                            <input type="datetime-local" name="clock_in" class="form-control" value="<?= str_replace(' ', 'T', $a['clock_in']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Giờ ra</label>
                                            <input type="datetime-local" name="clock_out" class="form-control" value="<?= str_replace(' ', 'T', $a['clock_out']) ?>">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select name="status" class="form-control">
                                            <option value="present" <?= $a['status']=='present'?'selected':'' ?>>Đi làm</option>
                                            <option value="absent" <?= $a['status']=='absent'?'selected':'' ?>>Vắng</option>
                                            <option value="leave" <?= $a['status']=='leave'?'selected':'' ?>>Nghỉ phép</option>
                                            <option value="ot" <?= $a['status']=='ot'?'selected':'' ?>>Tăng ca</option>
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

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm chấm công</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nhân viên</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">-- Chọn nhân viên --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngày</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ca làm</label>
                        <select name="shift_id" class="form-control">
                            <option value="">-- Chọn ca --</option>
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Giờ vào</label>
                            <input type="datetime-local" name="clock_in" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giờ ra</label>
                            <input type="datetime-local" name="clock_out" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="present">Đi làm</option>
                            <option value="absent">Vắng</option>
                            <option value="leave">Nghỉ phép</option>
                            <option value="ot">Tăng ca</option>
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

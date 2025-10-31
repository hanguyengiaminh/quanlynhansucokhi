<?php
// PHẦN 1: TOÀN BỘ LOGIC XỬ LÝ (ĐƯỢC ĐƯA LÊN ĐẦU)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
check_role(['admin','hr']);

// XỬ LÝ THÊM HỢP ĐỒNG (Không thay đổi)
if (isset($_POST['add'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $contract_no = $_POST['contract_no'] ?? '';
    $contract_type = $_POST['contract_type'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $salary_base = $_POST['salary_base'] ?? 0;

    $stmt = $pdo->prepare("INSERT INTO contracts (employee_id, contract_no, contract_type, start_date, end_date, salary_base) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $employee_id,
        $contract_no,
        $contract_type,
        $start_date ?: null,
        $end_date ?: null,
        $salary_base
    ]);

    header("Location: contracts.php?msg=added");
    exit;
}

// XỬ LÝ SỬA HỢP ĐỒNG (Không thay đổi)
if (isset($_POST['edit'])) {
    $id = $_POST['id'] ?? 0;
    $employee_id = $_POST['employee_id'] ?? null;
    $contract_no = $_POST['contract_no'] ?? '';
    $contract_type = $_POST['contract_type'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $salary_base = $_POST['salary_base'] ?? 0;
    $status = $_POST['status'] ?? 'active';

    $stmt = $pdo->prepare("UPDATE contracts SET employee_id=?, contract_no=?, contract_type=?, start_date=?, end_date=?, salary_base=?, status=? WHERE id=?");
    $stmt->execute([
        $employee_id,
        $contract_no,
        $contract_type,
        $start_date ?: null,
        $end_date ?: null,
        $salary_base,
        $status,
        $id
    ]);

    header("Location: contracts.php?msg=updated");
    exit;
}

// XỬ LÝ XÓA HỢP ĐỒNG (Không thay đổi)
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM contracts WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        header("Location: contracts.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: contracts.php?error=delete");
        exit;
    }
}

// PHẦN 2: HIỂN THỊ HTML (SAU KHI LOGIC ĐÃ CHẠY XONG)
include "inc/header.php";

// LẤY DỮ LIỆU HIỂN THỊ
// *** THAY ĐỔI 1: XỬ LÝ TÌM KIẾM VÀ CẬP NHẬT TRUY VẤN SQL ***
$search_term = $_GET['search'] ?? '';
$params = [];

$sql = "SELECT c.*, e.full_name 
        FROM contracts c 
        LEFT JOIN employees e ON c.employee_id = e.id";

if (!empty($search_term)) {
    // Tìm kiếm theo tên nhân viên hoặc số hợp đồng
    $sql .= " WHERE e.full_name LIKE ? OR c.contract_no LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = [$like_term, $like_term];
}

// Sắp xếp theo ngày bắt đầu (cũ nhất lên trước)
$sql .= " ORDER BY c.start_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách nhân viên cho modal
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4 fw-bold">Quản lý hợp đồng</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added') { ?>
    <div class="alert alert-success"> Thêm hợp đồng thành công!</div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'updated') { ?>
    <div class="alert alert-info">Cập nhật hợp đồng thành công!</div>
    <?php } elseif (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
    <div class="alert alert-success"> Xóa hợp đồng thành công!</div>
    <?php } elseif (isset($_GET['error']) && $_GET['error'] == 'delete') { ?>
    <div class="alert alert-danger"> Không thể xóa hợp đồng (có lỗi hệ thống).</div>
    <?php } ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Thêm hợp đồng
            </button>
        </div>
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Tìm theo tên NV hoặc số HĐ..."
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
                            <th width="50">ID</th>
                            <th>Nhân viên</th>
                            <th>Số hợp đồng</th>
                            <th>Loại</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th>Lương cơ bản</th>
                            <th>Trạng thái</th>
                            <th width="120">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; ?>
                        <?php foreach ($contracts as $c) { ?>
                        <tr>
                            <td class="text-center"><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($c['full_name'] ?? '---') ?></td>
                            <td><?= htmlspecialchars($c['contract_no']) ?></td>
                            <td><?= htmlspecialchars($c['contract_type']) ?></td>
                            <td class="text-center"><?= $c['start_date'] ?: '-' ?></td>
                            <td class="text-center"><?= $c['end_date'] ?: '-' ?></td>
                            <td class="text-end"><?= number_format($c['salary_base'], 0, ',', '.') ?> VNĐ</td>
                            <td class="text-center">
                                <?php if($c['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($c['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editModal<?= $c['id'] ?>" title="Sửa">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Xóa hợp đồng này?')" title="Xóa">
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
</div>

<?php foreach ($contracts as $c) { ?>
<div class="modal fade" id="editModal<?= $c['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa hợp đồng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">-- Chọn nhân viên --</option>
                                <?php foreach($employees as $emp) { ?>
                                <option value="<?= $emp['id'] ?>"
                                    <?= $emp['id']==$c['employee_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['full_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số hợp đồng</label>
                            <input type="text" name="contract_no" class="form-control"
                                value="<?= htmlspecialchars($c['contract_no']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Loại hợp đồng</label>
                            <input type="text" name="contract_type" class="form-control"
                                value="<?= htmlspecialchars($c['contract_type']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $c['start_date'] ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ngày kết thúc (để trống nếu vô thời
                                hạn)</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $c['end_date'] ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lương cơ bản</label>
                            <input type="number" name="salary_base" class="form-control" step="0.01"
                                value="<?= $c['salary_base'] ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= $c['status']=='active' ? 'selected' : '' ?>>
                                    Active</option>
                                <option value="expired" <?= $c['status']=='expired' ? 'selected' : '' ?>>Expired
                                </option>
                                <option value="terminated" <?= $c['status']=='terminated' ? 'selected' : '' ?>>
                                    Terminated</option>
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


<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm hợp đồng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">-- Chọn nhân viên --</option>
                                <?php foreach($employees as $emp) { ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số hợp đồng</label>
                            <input type="text" name="contract_no" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Loại hợp đồng</label>
                            <input type="text" name="contract_type" class="form-control"
                                placeholder="ví dụ: 1 năm, 3 năm, Không xác định">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ngày kết thúc (để trống nếu vô thời hạn)</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lương cơ bản</label>
                            <input type="number" name="salary_base" class="form-control" step="0.01" value="0">
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
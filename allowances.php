<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
check_role(['admin','hr']);

// Handle add
if (isset($_POST['add_allowance'])) {
    $stmt = $pdo->prepare("INSERT INTO allowances (employee_id, type, allowance_date, amount, note) VALUES (?,?,?,?,?)");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['type'],
        $_POST['allowance_date'],
        $_POST['amount'],
        $_POST['note'] ?: null
    ]);
    header("Location: allowances.php?msg=added");
    exit;
}

// Handle edit
if (isset($_POST['edit_allowance'])) {
    $stmt = $pdo->prepare("UPDATE allowances SET employee_id=?, type=?, allowance_date=?, amount=?, note=? WHERE id=?");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['type'],
        $_POST['allowance_date'],
        $_POST['amount'],
        $_POST['note'] ?: null,
        $_POST['id']
    ]);
    header("Location: allowances.php?msg=updated");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM allowances WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: allowances.php?msg=deleted");
    exit;
}

include "inc/header.php";

$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);

$filter_employee = $_GET['employee'] ?? '';
$filter_month = $_GET['month'] ?? '';

$sql = "SELECT a.*, e.full_name FROM allowances a JOIN employees e ON a.employee_id = e.id";
$conditions = [];
$params = [];

if ($filter_employee !== '') {
    $conditions[] = "a.employee_id = ?";
    $params[] = $filter_employee;
}

if ($filter_month !== '') {
    $conditions[] = "DATE_FORMAT(a.allowance_date, '%Y-%m') = ?";
    $params[] = $filter_month;
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= " ORDER BY a.allowance_date DESC";
$allowances_stmt = $pdo->prepare($sql);
$allowances_stmt->execute($params);
$allowances = $allowances_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Quản lý phụ cấp</h2>

    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">Đã thêm phụ cấp thành công.</div>
        <?php elseif($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-info">Đã cập nhật phụ cấp.</div>
        <?php elseif($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-warning">Đã xóa phụ cấp.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm glass mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAllowanceModal">
                    <i class="bi bi-plus-circle me-1"></i> Thêm phụ cấp
                </button>
                <form class="row g-2" method="get">
                    <div class="col-auto">
                        <select name="employee" class="form-select">
                            <option value="">-- Nhân viên --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $filter_employee == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filter_month) ?>">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i></button>
                    </div>
                    <div class="col-auto">
                        <a href="allowances.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm glass">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>#</th>
                            <th>Nhân viên</th>
                            <th>Ngày</th>
                            <th>Loại</th>
                            <th class="text-end">Số tiền</th>
                            <th>Ghi chú</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$allowances): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Chưa có phụ cấp nào.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($allowances as $a): ?>
                            <tr>
                                <td class="text-center"><?= $a['id'] ?></td>
                                <td><?= htmlspecialchars($a['full_name']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($a['allowance_date']) ?></td>
                                <td class="text-center">
                                    <?php
                                        switch ($a['type']) {
                                            case 'hazard':
                                                echo 'Độc hại';
                                                break;
                                            case 'overtime':
                                                echo 'Tăng ca (thêm)';
                                                break;
                                            default:
                                                echo 'Khác';
                                        }
                                    ?>
                                </td>
                                <td class="text-end"><?= number_format($a['amount'], 0, ',', '.') ?> VNĐ</td>
                                <td><?= htmlspecialchars($a['note'] ?? '') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editAllowanceModal<?= $a['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa phụ cấp này?')">
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

<div class="modal fade" id="addAllowanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm phụ cấp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_allowance" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <select name="employee_id" class="form-select" required>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ngày</label>
                            <input type="date" name="allowance_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Loại phụ cấp</label>
                            <select name="type" class="form-select">
                                <option value="hazard">Độc hại</option>
                                <option value="overtime">Tăng ca (thêm)</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Số tiền (VNĐ)</label>
                            <input type="number" name="amount" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" name="note" class="form-control" placeholder="Ví dụ: phụ cấp độc hại ca đêm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Lưu</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($allowances as $a): ?>
<div class="modal fade" id="editAllowanceModal<?= $a['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="post">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Cập nhật phụ cấp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_allowance" value="1">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <select name="employee_id" class="form-select" required>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $a['employee_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ngày</label>
                            <input type="date" name="allowance_date" class="form-control" value="<?= htmlspecialchars($a['allowance_date']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Loại phụ cấp</label>
                            <select name="type" class="form-select">
                                <option value="hazard" <?= $a['type'] === 'hazard' ? 'selected' : '' ?>>Độc hại</option>
                                <option value="overtime" <?= $a['type'] === 'overtime' ? 'selected' : '' ?>>Tăng ca (thêm)</option>
                                <option value="other" <?= $a['type'] === 'other' ? 'selected' : '' ?>>Khác</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Số tiền (VNĐ)</label>
                            <input type="number" name="amount" class="form-control" min="0" step="1000" value="<?= htmlspecialchars($a['amount']) ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" name="note" class="form-control" value="<?= htmlspecialchars($a['note'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Lưu thay đổi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include "inc/footer.php"; ?>

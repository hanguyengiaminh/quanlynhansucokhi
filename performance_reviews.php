<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "inc/auth.php";
require_once "inc/db.php";

check_login();
check_role(['admin','hr']);

if (isset($_POST['add_review'])) {
    $stmt = $pdo->prepare("INSERT INTO performance_reviews (employee_id, review_period, reviewer, score, strengths, improvements, goals, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['review_period'],
        $_POST['reviewer'] ?: $_SESSION['username'],
        $_POST['score'],
        $_POST['strengths'] ?: null,
        $_POST['improvements'] ?: null,
        $_POST['goals'] ?: null,
        $_POST['status']
    ]);
    header("Location: performance_reviews.php?msg=added");
    exit;
}

if (isset($_POST['edit_review'])) {
    $stmt = $pdo->prepare("UPDATE performance_reviews SET employee_id=?, review_period=?, reviewer=?, score=?, strengths=?, improvements=?, goals=?, status=? WHERE id=?");
    $stmt->execute([
        $_POST['employee_id'],
        $_POST['review_period'],
        $_POST['reviewer'] ?: $_SESSION['username'],
        $_POST['score'],
        $_POST['strengths'] ?: null,
        $_POST['improvements'] ?: null,
        $_POST['goals'] ?: null,
        $_POST['status'],
        $_POST['id']
    ]);
    header("Location: performance_reviews.php?msg=updated");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM performance_reviews WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: performance_reviews.php?msg=deleted");
    exit;
}

include "inc/header.php";

$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);

$filter_employee = $_GET['employee'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_period = $_GET['period'] ?? '';

$sql = "SELECT r.*, e.full_name FROM performance_reviews r JOIN employees e ON r.employee_id = e.id";
$conditions = [];
$params = [];

if ($filter_employee !== '') {
    $conditions[] = "r.employee_id = ?";
    $params[] = $filter_employee;
}

if ($filter_status !== '') {
    $conditions[] = "r.status = ?";
    $params[] = $filter_status;
}

if ($filter_period !== '') {
    $conditions[] = "r.review_period = ?";
    $params[] = $filter_period;
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= " ORDER BY r.created_at DESC";
$reviews_stmt = $pdo->prepare($sql);
$reviews_stmt->execute($params);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold">Đánh giá hiệu suất</h2>

    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">Đã tạo phiếu đánh giá mới.</div>
        <?php elseif($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-info">Đã cập nhật phiếu đánh giá.</div>
        <?php elseif($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-warning">Đã xóa phiếu đánh giá.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm glass mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                    <i class="bi bi-plus-circle me-1"></i> Thêm đánh giá
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
                        <input type="text" name="period" class="form-control" placeholder="Ví dụ: Q4-2024" value="<?= htmlspecialchars($filter_period) ?>">
                    </div>
                    <div class="col-auto">
                        <select name="status" class="form-select">
                            <option value="">-- Trạng thái --</option>
                            <option value="draft" <?= $filter_status === 'draft' ? 'selected' : '' ?>>Nháp</option>
                            <option value="in_review" <?= $filter_status === 'in_review' ? 'selected' : '' ?>>Đang duyệt</option>
                            <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i></button>
                    </div>
                    <div class="col-auto">
                        <a href="performance_reviews.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i></a>
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
                            <th>Kỳ đánh giá</th>
                            <th>Người đánh giá</th>
                            <th>Điểm</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$reviews): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Chưa có phiếu đánh giá.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td class="text-center"><?= $r['id'] ?></td>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($r['review_period']) ?></td>
                                <td><?= htmlspecialchars($r['reviewer']) ?></td>
                                <td class="text-center"><span class="badge bg-primary"><?= htmlspecialchars($r['score']) ?>/5</span></td>
                                <td class="text-center">
                                    <?php
                                        switch ($r['status']) {
                                            case 'draft':
                                                echo '<span class="badge bg-secondary">Nháp</span>';
                                                break;
                                            case 'in_review':
                                                echo '<span class="badge bg-warning text-dark">Đang duyệt</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-success">Đã duyệt</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($r['created_at']) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewReviewModal<?= $r['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editReviewModal<?= $r['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa phiếu đánh giá này?')">
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

<div class="modal fade" id="addReviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm đánh giá hiệu suất</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_review" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <select name="employee_id" class="form-select" required>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kỳ đánh giá</label>
                            <input type="text" name="review_period" class="form-control" placeholder="Ví dụ: Q4-2024" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Người đánh giá</label>
                            <input type="text" name="reviewer" class="form-control" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" placeholder="Tên người đánh giá">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Điểm (1-5)</label>
                            <input type="number" name="score" class="form-control" min="1" max="5" step="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Thế mạnh</label>
                            <textarea name="strengths" class="form-control" rows="2" placeholder="Tổng kết điểm mạnh"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Cần cải thiện</label>
                            <textarea name="improvements" class="form-control" rows="2" placeholder="Những điểm cần cải thiện"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mục tiêu kỳ tới</label>
                            <textarea name="goals" class="form-control" rows="2" placeholder="Mục tiêu/KPI đề xuất"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="draft">Nháp</option>
                                <option value="in_review">Đang duyệt</option>
                                <option value="approved">Đã duyệt</option>
                            </select>
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

<?php foreach ($reviews as $r): ?>
<div class="modal fade" id="viewReviewModal<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Chi tiết đánh giá - <?= htmlspecialchars($r['full_name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Kỳ đánh giá:</strong> <?= htmlspecialchars($r['review_period']) ?></p>
                <p><strong>Điểm số:</strong> <?= htmlspecialchars($r['score']) ?>/5</p>
                <p><strong>Người đánh giá:</strong> <?= htmlspecialchars($r['reviewer']) ?></p>
                <p><strong>Trạng thái:</strong> <?= htmlspecialchars($r['status']) ?></p>
                <hr>
                <p><strong>Thế mạnh:</strong><br><?= nl2br(htmlspecialchars($r['strengths'] ?? '')) ?></p>
                <p><strong>Cần cải thiện:</strong><br><?= nl2br(htmlspecialchars($r['improvements'] ?? '')) ?></p>
                <p><strong>Mục tiêu kỳ tới:</strong><br><?= nl2br(htmlspecialchars($r['goals'] ?? '')) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editReviewModal<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass">
            <form method="post">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Cập nhật đánh giá</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_review" value="1">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nhân viên</label>
                            <select name="employee_id" class="form-select" required>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $r['employee_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kỳ đánh giá</label>
                            <input type="text" name="review_period" class="form-control" value="<?= htmlspecialchars($r['review_period']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Người đánh giá</label>
                            <input type="text" name="reviewer" class="form-control" value="<?= htmlspecialchars($r['reviewer']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Điểm (1-5)</label>
                            <input type="number" name="score" class="form-control" min="1" max="5" value="<?= htmlspecialchars($r['score']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Thế mạnh</label>
                            <textarea name="strengths" class="form-control" rows="2"><?= htmlspecialchars($r['strengths'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Cần cải thiện</label>
                            <textarea name="improvements" class="form-control" rows="2"><?= htmlspecialchars($r['improvements'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Mục tiêu kỳ tới</label>
                            <textarea name="goals" class="form-control" rows="2"><?= htmlspecialchars($r['goals'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= $r['status'] === 'draft' ? 'selected' : '' ?>>Nháp</option>
                                <option value="in_review" <?= $r['status'] === 'in_review' ? 'selected' : '' ?>>Đang duyệt</option>
                                <option value="approved" <?= $r['status'] === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                            </select>
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

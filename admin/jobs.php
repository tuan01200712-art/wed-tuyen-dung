<?php
/**
 * ============================================================
 * FILE: admin/jobs.php
 * MÔ TẢ: Kiểm duyệt và quản lý toàn bộ tin tuyển dụng
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Quản Lý Tin Tuyển Dụng';

// Xử lý POST (Duyệt, Ẩn, Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $job_id = (int)($_POST['job_id'] ?? 0);

    if ($job_id > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE jobs SET status = 'active' WHERE id = ?")->execute([$job_id]);
            $_SESSION['flash_message'] = 'Đã duyệt và công khai tin tuyển dụng.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'hide') {
            $pdo->prepare("UPDATE jobs SET status = 'inactive' WHERE id = ?")->execute([$job_id]);
            $_SESSION['flash_message'] = 'Đã ẩn/tạm dừng tin tuyển dụng.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$job_id]);
            $_SESSION['flash_message'] = 'Đã xóa tin tuyển dụng khỏi hệ thống.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: jobs.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT j.*, u.name as employer_name, u.email as employer_email,
           COUNT(a.id) as app_count
    FROM jobs j
    LEFT JOIN users u ON u.id = j.employer_id
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all' && in_array($status_filter, ['active', 'inactive', 'draft'])) {
    $sql .= " AND j.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (j.title LIKE :search OR j.company LIKE :search OR j.location LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " GROUP BY j.id ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
$total_count = count($jobs);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Nội dung tuyển dụng</span>
                <h1 class="section-title">Danh sách tin tuyển dụng</h1>
                <p class="section-desc">Tổng cộng <strong><?= $total_count ?></strong> tin tuyển dụng được ghi nhận trong cơ sở dữ liệu.</p>
            </div>
            <a href="job-edit.php?action=create" class="btn btn-primary">+ Tạo tin mới (Admin)</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm & lọc -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1.5fr 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm theo tiêu đề, công ty, địa điểm..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="status" class="search-select">
                        <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Tất cả trạng thái</option>
                        <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Đang tuyển (Active)</option>
                        <option value="inactive" <?= ($status_filter === 'inactive') ? 'selected' : '' ?>>Tạm dừng (Inactive)</option>
                        <option value="draft" <?= ($status_filter === 'draft') ? 'selected' : '' ?>>Bản nháp (Draft)</option>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc tin</button>
                    <?php if ($status_filter !== 'all' || !empty($search)): ?>
                        <a href="jobs.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách tin tuyển dụng -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Vị trí tuyển dụng & Doanh nghiệp</th>
                            <th>Địa điểm & Ngành</th>
                            <th>Mức lương</th>
                            <th>Hồ sơ</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $i => $j): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <a href="../job-detail.php?id=<?= (int)$j['id'] ?>" target="_blank" style="color:var(--ink);">
                                                <?= htmlspecialchars($j['title']) ?> ↗
                                            </a>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            🏢 <?= htmlspecialchars($j['company']) ?>
                                            <?= !empty($j['employer_name']) ? ' (' . htmlspecialchars($j['employer_name']) . ')' : '' ?>
                                        </div>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= htmlspecialchars($j['location']) ?> &middot; <?= htmlspecialchars($j['category']) ?>
                                    </td>
                                    <td>
                                        <span class="chip" style="background:var(--status-success-bg); color:var(--status-success-text); font-weight:700;">
                                            <?= htmlspecialchars($j['salary']) ?>
                                        </span>
                                    </td>
                                    <td style="font-weight:700;">
                                        <?= (int)$j['app_count'] ?> hồ sơ
                                    </td>
                                    <td>
                                        <?php if ($j['status'] === 'active'): ?>
                                            <span class="badge badge--active">Đang tuyển</span>
                                        <?php elseif ($j['status'] === 'draft'): ?>
                                            <span class="badge badge--pending">Bản nháp</span>
                                        <?php else: ?>
                                            <span class="badge badge--danger">Tạm dừng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y', strtotime($j['created_at'])) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <a href="job-edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-secondary btn-sm">Sửa</a>

                                            <?php if ($j['status'] !== 'active'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--status-success-text); font-weight:700;">Duyệt</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="hide">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm">Ẩn</button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin tuyển dụng này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy tin tuyển dụng nào theo điều kiện tìm kiếm.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>

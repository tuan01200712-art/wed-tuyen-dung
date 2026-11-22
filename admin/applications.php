<?php
/**
 * ============================================================
 * FILE: admin/applications.php
 * MÔ TẢ: Quản lý toàn bộ hồ sơ ứng tuyển trên hệ thống
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Quản Lý Hồ Sơ Ứng Tuyển';

// Xử lý POST (Cập nhật trạng thái hoặc Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $app_id = (int)($_POST['app_id'] ?? 0);

    if ($app_id > 0) {
        if ($action === 'update_status') {
            $new_status = trim($_POST['status'] ?? '');
            if (in_array($new_status, ['Đã nộp hồ sơ', 'Đang xét duyệt', 'Phù hợp', 'Từ chối'])) {
                $pdo->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$new_status, $app_id]);
                $_SESSION['flash_message'] = 'Đã cập nhật trạng thái hồ sơ #' . $app_id . ' thành "' . $new_status . '".';
                $_SESSION['flash_type'] = 'success';
            }
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$app_id]);
            $_SESSION['flash_message'] = 'Đã xóa hồ sơ ứng tuyển khỏi hệ thống.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: applications.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT a.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title, j.company, c.id as cv_id, c.title as cv_title
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR j.title LIKE :search OR j.company LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();
$total_count = count($applications);
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
                <span class="section-eyebrow">Dữ liệu ứng tuyển</span>
                <h1 class="section-title">Danh sách hồ sơ ứng tuyển</h1>
                <p class="section-desc">Toàn bộ <strong><?= $total_count ?></strong> hồ sơ do ứng viên nộp vào các vị trí việc làm.</p>
            </div>
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
                    <input type="text" name="search" class="search-input" placeholder="Tìm theo tên ứng viên, email, vị trí, công ty..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="status" class="search-select">
                        <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Tất cả trạng thái xét duyệt</option>
                        <option value="Đã nộp hồ sơ" <?= ($status_filter === 'Đã nộp hồ sơ') ? 'selected' : '' ?>>Đã nộp hồ sơ</option>
                        <option value="Đang xét duyệt" <?= ($status_filter === 'Đang xét duyệt') ? 'selected' : '' ?>>Đang xét duyệt</option>
                        <option value="Phù hợp" <?= ($status_filter === 'Phù hợp') ? 'selected' : '' ?>>Phù hợp</option>
                        <option value="Từ chối" <?= ($status_filter === 'Từ chối') ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <?php if ($status_filter !== 'all' || !empty($search)): ?>
                        <a href="applications.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách ứng tuyển -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Ứng viên</th>
                            <th>Vị trí ứng tuyển & Công ty</th>
                            <th>Hồ sơ CV</th>
                            <th>Ngày nộp</th>
                            <th>Trạng thái & Cập nhật nhanh</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($applications)): ?>
                            <?php foreach ($applications as $i => $app): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($app['candidate_name']) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($app['candidate_email']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:var(--ink);">
                                            <a href="../job-detail.php?id=<?= (int)$app['job_id'] ?>" target="_blank" style="color:var(--ink);">
                                                <?= htmlspecialchars($app['job_title']) ?> ↗
                                            </a>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">🏢 <?= htmlspecialchars($app['company']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_title'])): ?>
                                            <a href="../cv-preview.php?id=<?= (int)$app['cv_id'] ?>" target="_blank" style="font-weight:600; font-size:13px; text-decoration:underline;">
                                                <?= htmlspecialchars($app['cv_title']) ?> ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Thư giới thiệu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y H:i', strtotime($app['created_at'])) ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:flex; align-items:center; gap:6px;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                            <select name="status" class="form-control form-select" style="padding:4px 28px 4px 8px; font-size:12px; height:32px;" onchange="this.form.submit()">
                                                <option value="Đã nộp hồ sơ" <?= ($app['status'] === 'Đã nộp hồ sơ') ? 'selected' : '' ?>>Đã nộp hồ sơ</option>
                                                <option value="Đang xét duyệt" <?= ($app['status'] === 'Đang xét duyệt') ? 'selected' : '' ?>>Đang xét duyệt</option>
                                                <option value="Phù hợp" <?= ($app['status'] === 'Phù hợp') ? 'selected' : '' ?>>Phù hợp</option>
                                                <option value="Từ chối" <?= ($app['status'] === 'Từ chối') ? 'selected' : '' ?>>Từ chối</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bản ghi ứng tuyển này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy hồ sơ nào.
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

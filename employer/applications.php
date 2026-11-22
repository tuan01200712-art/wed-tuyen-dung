<?php
/**
 * ============================================================
 * FILE: employer/applications.php
 * MÔ TẢ: Danh sách hồ sơ ứng viên ứng tuyển vào công ty
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Hồ Sơ Ứng Tuyển';

$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = trim($_GET['status'] ?? '');

// Lấy danh sách jobs của employer để làm dropdown lọc
$job_list_stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
$job_list_stmt->execute([$employer_id]);
$employer_jobs = $job_list_stmt->fetchAll();

// Xây dựng câu truy vấn
$sql = "
    SELECT a.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title, c.title as cv_title
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    WHERE j.employer_id = :employer_id
";
$params = [':employer_id' => $employer_id];

if ($job_filter > 0) {
    $sql .= " AND j.id = :job_id";
    $params[':job_id'] = $job_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();
$total_apps = count($applications);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Doanh Nghiệp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Quản lý nhân tài</span>
                <h1 class="section-title">Hồ sơ ứng viên</h1>
                <p class="section-desc">Tổng số <strong><?= $total_apps ?></strong> lượt nộp hồ sơ ứng tuyển vào doanh nghiệp của bạn.</p>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Bộ lọc hồ sơ -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1fr 1fr auto;">
                <div class="search-field">
                    <select name="job_id" class="search-select">
                        <option value="0">Tất cả vị trí việc làm</option>
                        <?php foreach ($employer_jobs as $ej): ?>
                            <option value="<?= (int)$ej['id'] ?>" <?= ($job_filter === (int)$ej['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ej['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-field">
                    <select name="status" class="search-select">
                        <option value="">Tất cả trạng thái xét duyệt</option>
                        <option value="Đã nộp hồ sơ" <?= ($status_filter === 'Đã nộp hồ sơ') ? 'selected' : '' ?>>Đã nộp hồ sơ</option>
                        <option value="Đang xét duyệt" <?= ($status_filter === 'Đang xét duyệt') ? 'selected' : '' ?>>Đang xét duyệt</option>
                        <option value="Phù hợp" <?= ($status_filter === 'Phù hợp') ? 'selected' : '' ?>>Phù hợp</option>
                        <option value="Từ chối" <?= ($status_filter === 'Từ chối') ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc hồ sơ</button>
                    <?php if ($job_filter > 0 || !empty($status_filter)): ?>
                        <a href="applications.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách ứng viên -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ứng viên</th>
                            <th>Vị trí ứng tuyển</th>
                            <th>CV đính kèm</th>
                            <th>Ngày nộp</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($applications)): ?>
                            <?php foreach ($applications as $app): 
                                $status_badge_map = [
                                    'Đã nộp hồ sơ'  => 'badge--pending',
                                    'Đang xét duyệt' => 'badge--pending',
                                    'Phù hợp'        => 'badge--success',
                                    'Từ chối'        => 'badge--danger'
                                ];
                                $b_cls = $status_badge_map[$app['status']] ?? 'badge--pending';
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($app['candidate_name']) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            <?= htmlspecialchars($app['candidate_email']) ?>
                                            <?= !empty($app['candidate_phone']) ? ' &middot; ' . htmlspecialchars($app['candidate_phone']) : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:var(--ink);">
                                            <a href="../job-detail.php?id=<?= (int)$app['job_id'] ?>" target="_blank" style="color:var(--ink);">
                                                <?= htmlspecialchars($app['job_title']) ?> ↗
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_title'])): ?>
                                            <span style="font-weight:600; font-size:13px; color:var(--ink);">
                                                <?= htmlspecialchars($app['cv_title']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Chỉ có thư giới thiệu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y H:i', strtotime($app['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $b_cls ?>">
                                            <?= htmlspecialchars($app['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="application-view.php?id=<?= (int)$app['id'] ?>" class="btn btn-secondary btn-sm">
                                            Xem chi tiết & Duyệt →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:48px; color:var(--muted);">
                                    Chưa có hồ sơ ứng tuyển nào theo điều kiện tìm kiếm.
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

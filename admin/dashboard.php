<?php
/**
 * ============================================================
 * FILE: admin/dashboard.php
 * MÔ TẢ: Bảng điều khiển Quản trị viên — Chuẩn Apple Design
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Bảng Điều Khiển Quản Trị';

$stats = [
    'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'candidates'  => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='candidate'")->fetchColumn(),
    'employers'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='employer'")->fetchColumn(),
    'total_jobs'  => (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'active_jobs' => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active'")->fetchColumn(),
    'total_apps'  => (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'total_cvs'   => (int)$pdo->query("SELECT COUNT(*) FROM cvs")->fetchColumn(),
];

$recent_apps = $pdo->query("
    SELECT a.id, a.created_at, a.status,
           u.name AS candidate_name, u.email AS candidate_email,
           j.id AS job_id, j.title AS job_title, j.company
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs  j ON j.id = a.job_id
    ORDER BY a.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
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
                <span class="section-eyebrow">Hệ thống quản trị</span>
                <h1 class="section-title">Tổng quan hệ thống</h1>
                <p class="section-desc">Theo dõi các chỉ số hoạt động toàn diện của JobFinder theo thời gian thực.</p>
            </div>
            <a href="../index.php" target="_blank" class="btn btn-secondary btn-sm">Xem giao diện công khai ↗</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="stats-cards" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div class="stat-box">
                <div class="stat-box__label">Tổng người dùng</div>
                <div class="stat-box__value"><?= $stats['total_users'] ?></div>
                <div style="font-size:11px; color:var(--muted); margin-top:4px;"><?= $stats['candidates'] ?> ứng viên &middot; <?= $stats['employers'] ?> NTD</div>
            </div>
            <div class="stat-box">
                <div class="stat-box__label">Tin tuyển dụng</div>
                <div class="stat-box__value" style="color:var(--status-success-text);"><?= $stats['active_jobs'] ?> <span style="font-size:16px; font-weight:500; color:var(--muted);">/ <?= $stats['total_jobs'] ?></span></div>
                <div style="font-size:11px; color:var(--muted); margin-top:4px;">Đang tuyển công khai</div>
            </div>
            <div class="stat-box">
                <div class="stat-box__label">Hồ sơ đã nộp</div>
                <div class="stat-box__value"><?= $stats['total_apps'] ?></div>
                <div style="font-size:11px; color:var(--muted); margin-top:4px;">Lượt ứng tuyển việc làm</div>
            </div>
            <div class="stat-box">
                <div class="stat-box__label">Hồ sơ CV trực tuyến</div>
                <div class="stat-box__value"><?= $stats['total_cvs'] ?></div>
                <div style="font-size:11px; color:var(--muted); margin-top:4px;">CV đã tạo trên hệ thống</div>
            </div>
        </div>

        <!-- Bảng hoạt động ứng tuyển mới nhất -->
        <div class="table-card">
            <div style="padding:20px 24px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size:16px; font-weight:700;">Hồ sơ nộp gần đây nhất</h2>
                <a href="applications.php" class="btn btn-secondary btn-sm">Xem tất cả hồ sơ →</a>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ứng viên</th>
                            <th>Vị trí ứng tuyển</th>
                            <th>Doanh nghiệp</th>
                            <th>Ngày nộp</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Chi tiết tin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_apps)): ?>
                            <?php foreach ($recent_apps as $app): 
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
                                        <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($app['candidate_email']) ?></div>
                                    </td>
                                    <td style="font-weight:600;">
                                        <?= htmlspecialchars($app['job_title']) ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= htmlspecialchars($app['company']) ?>
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
                                        <a href="../job-detail.php?id=<?= (int)$app['job_id'] ?>" target="_blank" class="btn btn-secondary btn-sm">
                                            Xem tin ↗
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:48px; color:var(--muted);">
                                    Chưa có hoạt động nộp hồ sơ nào trong hệ thống.
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

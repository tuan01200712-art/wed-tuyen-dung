<?php
/**
 * ============================================================
 * FILE: employer/dashboard.php
 * MÔ TẢ: Bảng điều khiển Nhà tuyển dụng — Chuẩn Apple Design
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Bảng Điều Khiển Tuyển Dụng';

// Thống kê số liệu
$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?");
$stmt->execute([$employer_id]);
$total_jobs = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'active'");
$stmt->execute([$employer_id]);
$active_jobs = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(a.id) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ?");
$stmt->execute([$employer_id]);
$total_apps = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(a.id) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ? AND a.status = 'Đã nộp hồ sơ'");
$stmt->execute([$employer_id]);
$pending_apps = (int)$stmt->fetchColumn();

// 5 Tin tuyển dụng mới nhất
$stmt = $pdo->prepare("
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.employer_id = ?
    GROUP BY j.id
    ORDER BY j.created_at DESC
    LIMIT 5
");
$stmt->execute([$employer_id]);
$recent_jobs = $stmt->fetchAll();
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
                <span class="section-eyebrow">Kênh Doanh nghiệp</span>
                <h1 class="section-title">Tổng quan tuyển dụng</h1>
            </div>
            <a href="job-create.php" class="btn btn-primary">+ Đăng tin tuyển dụng mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="stats-cards">
            <div class="stat-box">
                <div class="stat-box__label">Tổng tin tuyển dụng</div>
                <div class="stat-box__value"><?= $total_jobs ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box__label">Đang mở tuyển dụng</div>
                <div class="stat-box__value" style="color:var(--status-success-text);"><?= $active_jobs ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box__label">Tổng hồ sơ ứng tuyển</div>
                <div class="stat-box__value"><?= $total_apps ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box__label">Hồ sơ chờ xem xét</div>
                <div class="stat-box__value" style="color:var(--status-pending-text);"><?= $pending_apps ?></div>
            </div>
        </div>

        <!-- Tin đăng gần đây -->
        <div class="table-card">
            <div style="padding:20px 24px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size:16px; font-weight:700;">Tin tuyển dụng gần đây</h2>
                <a href="jobs.php" class="btn btn-secondary btn-sm">Xem tất cả tin →</a>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vị trí công việc</th>
                            <th>Địa điểm & Ngành</th>
                            <th>Mức lương</th>
                            <th>Số hồ sơ</th>
                            <th>Trạng thái</th>
                            <th>Hạn nộp</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_jobs)): ?>
                            <?php foreach ($recent_jobs as $j): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <a href="../job-detail.php?id=<?= (int)$j['id'] ?>" target="_blank" style="color:var(--ink);">
                                                <?= htmlspecialchars($j['title']) ?> ↗
                                            </a>
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
                                    <td>
                                        <a href="applications.php?job_id=<?= (int)$j['id'] ?>" style="font-weight:700; text-decoration:underline;">
                                            <?= (int)$j['app_count'] ?> hồ sơ
                                        </a>
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
                                        <?= !empty($j['deadline']) ? date('d/m/Y', strtotime($j['deadline'])) : 'Không đặt' ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="applications.php?job_id=<?= (int)$j['id'] ?>" class="btn btn-secondary btn-sm">Xem hồ sơ</a>
                                        <a href="job-edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-ghost btn-sm">Sửa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Doanh nghiệp của bạn chưa đăng tải tin tuyển dụng nào.
                                    <br><br>
                                    <a href="job-create.php" class="btn btn-primary btn-sm">+ Đăng tin tuyển dụng đầu tiên</a>
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

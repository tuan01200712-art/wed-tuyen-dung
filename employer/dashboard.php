<?php
/**
 * ============================================================
 * FILE: employer/dashboard.php
 * MÔ TẢ: Bảng điều khiển Quản trị Tuyển dụng Doanh nghiệp — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Bảng Điều Khiển Tuyển Dụng';

// Lấy thông tin công ty và cờ xác thực
$ep = $pdo->query("SELECT company_name, is_verified, company_logo FROM employer_profiles WHERE user_id = {$employer_id}")->fetch(PDO::FETCH_ASSOC);
$company_name = $ep['company_name'] ?? $_SESSION['user_name'];
$is_verified = (int)($ep['is_verified'] ?? 0);

// Thống kê số liệu công việc
$total_jobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id}")->fetchColumn();
$active_jobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'active'")->fetchColumn();
$pending_jobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'pending'")->fetchColumn();
$expired_jobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND (status = 'expired' OR (deadline IS NOT NULL AND deadline < CURDATE()))")->fetchColumn();

// Thống kê hồ sơ CV nhận được
$total_apps = (int)$pdo->query("
    SELECT COUNT(a.id) 
    FROM applications a 
    JOIN jobs j ON j.id = a.job_id 
    WHERE j.employer_id = {$employer_id}
")->fetchColumn();

$new_apps = (int)$pdo->query("
    SELECT COUNT(a.id) 
    FROM applications a 
    JOIN jobs j ON j.id = a.job_id 
    WHERE j.employer_id = {$employer_id} AND a.status IN ('new', 'Đã nộp hồ sơ')
")->fetchColumn();

$reviewing_apps = (int)$pdo->query("
    SELECT COUNT(a.id) 
    FROM applications a 
    JOIN jobs j ON j.id = a.job_id 
    WHERE j.employer_id = {$employer_id} AND a.status IN ('viewed', 'suitable', 'contacted', 'interview_scheduled', 'interviewed', 'Đang xét duyệt', 'Phù hợp')
")->fetchColumn();

$hired_apps = (int)$pdo->query("
    SELECT COUNT(a.id) 
    FROM applications a 
    JOIN jobs j ON j.id = a.job_id 
    WHERE j.employer_id = {$employer_id} AND a.status IN ('offered', 'hired')
")->fetchColumn();

$rejected_apps = (int)$pdo->query("
    SELECT COUNT(a.id) 
    FROM applications a 
    JOIN jobs j ON j.id = a.job_id 
    WHERE j.employer_id = {$employer_id} AND a.status IN ('rejected', 'Từ chối')
")->fetchColumn();

// Lịch phỏng vấn sắp tới
$upcoming_interviews = $pdo->query("
    SELECT i.*, u.name as candidate_name, u.phone as candidate_phone, j.title as job_title
    FROM interview_schedules i
    JOIN users u ON u.id = i.candidate_id
    JOIN jobs j ON j.id = i.job_id
    WHERE i.employer_id = {$employer_id} AND i.interview_date >= CURDATE() AND i.status != 'cancelled'
    ORDER BY i.interview_date ASC, i.interview_time ASC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Ứng viên mới nộp gần đây nhất
$recent_applicants = $pdo->query("
    SELECT a.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title, c.id as cv_id, c.title as cv_title
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    WHERE j.employer_id = {$employer_id}
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Top tin tuyển dụng hiệu quả nhất (Nhiều CV nộp nhất)
$top_jobs = $pdo->query("
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.employer_id = {$employer_id}
    GROUP BY j.id
    ORDER BY app_count DESC, j.views DESC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Doanh Nghiệp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <?php if ($is_verified): ?>
                        <span class="badge badge--active" style="font-size:11px;">✓ Doanh nghiệp đã xác thực</span>
                    <?php else: ?>
                        <a href="profile.php" class="badge badge--pending" style="font-size:11px; text-decoration:none;">Chưa xác thực — Gửi hồ sơ ngay</a>
                    <?php endif; ?>
                </div>
                <h1 class="section-title">Trung tâm Tuyển dụng <?= htmlspecialchars($company_name) ?></h1>
                <p class="section-desc">Theo dõi tiến độ tuyển dụng, sàng lọc hồ sơ và quản lý tin đăng.</p>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a href="job-create.php" class="btn btn-primary">+ Đăng tin tuyển dụng mới</a>
                <a href="../logout.php" class="btn btn-secondary btn-sm">Đăng xuất</a>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- HÀNG 1: 4 THẺ THỐNG KÊ XẾP NGANG -->
        <div class="stats-cards" style="grid-template-columns: repeat(4, 1fr); margin-bottom:24px;">
            <div class="stat-box">
                <div class="stat-box__label">Tin đang hiển thị</div>
                <div class="stat-box__value" style="color:#059669;"><?= number_format($active_jobs) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                    Tổng <?= number_format($total_jobs) ?> tin &middot; <?= number_format($pending_jobs) ?> chờ duyệt
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-box__label">Tổng hồ sơ nhận được</div>
                <div class="stat-box__value"><?= number_format($total_apps) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">Tất cả tin tuyển dụng</div>
            </div>

            <div class="stat-box" style="<?= ($new_apps > 0) ? 'border-color:#3B82F6; background:#F8FAFC;' : '' ?>">
                <div class="stat-box__label">Hồ sơ mới chờ duyệt</div>
                <div class="stat-box__value" style="color:#2563EB;"><?= number_format($new_apps) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                    <a href="applications.php?status=new" style="color:#2563EB; font-weight:700; text-decoration:underline;">Xem ngay →</a>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-box__label">Đang phỏng vấn / Đã tuyển</div>
                <div class="stat-box__value" style="color:#16A34A;"><?= number_format($reviewing_apps + $hired_apps) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;"><?= number_format($hired_apps) ?> đã trúng tuyển</div>
            </div>
        </div>

        <!-- HÀNG 2: LỊCH PHỎNG VẤN SẮP TỚI & TIN HIỆU QUẢ CAO -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:24px;">
            <!-- Lịch phỏng vấn sắp tới -->
            <div class="form-card" style="padding:22px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h2 style="font-size:16px; font-weight:800; color:var(--ink);">Lịch phỏng vấn sắp tới</h2>
                        <p style="font-size:12px; color:var(--muted);">Lịch gặp ứng viên được xếp trong tuần</p>
                    </div>
                    <a href="interviews.php" class="btn btn-secondary btn-sm">+ Quản lý lịch</a>
                </div>

                <?php if (!empty($upcoming_interviews)): ?>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($upcoming_interviews as $iv): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:#F8FAFC; border:1px solid var(--line); border-radius:10px;">
                                <div>
                                    <div style="font-weight:700; color:var(--ink); font-size:14px;">
                                        <?= htmlspecialchars($iv['candidate_name']) ?>
                                        <span class="chip" style="font-size:11px; padding:1px 6px;"><?= ($iv['interview_type'] === 'online') ? 'Trực tuyến' : 'Trực tiếp' ?></span>
                                    </div>
                                    <div style="font-size:12px; color:var(--muted); margin-top:2px;">
                                        <?= htmlspecialchars($iv['job_title']) ?>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-weight:800; color:#2563EB; font-size:13px;">
                                        <?= date('H:i', strtotime($iv['interview_time'])) ?> - <?= date('d/m/Y', strtotime($iv['interview_date'])) ?>
                                    </div>
                                    <a href="<?= htmlspecialchars($iv['location_or_link']) ?>" target="_blank" style="font-size:11px; color:var(--ink); text-decoration:underline;">
                                        <?= ($iv['interview_type'] === 'online') ? 'Mở Google Meet ↗' : 'Địa điểm' ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:32px 0; color:var(--muted); font-size:13px;">
                        Chưa có lịch phỏng vấn nào sắp tới. Bạn có thể hẹn phỏng vấn ứng viên từ mục Quản lý CV.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top tin tuyển dụng hiệu quả nhất -->
            <div class="form-card" style="padding:22px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h2 style="font-size:16px; font-weight:800; color:var(--ink);">Tin tuyển dụng nổi bật nhất</h2>
                        <p style="font-size:12px; color:var(--muted);">Số lượng quan tâm và ứng tuyển</p>
                    </div>
                    <a href="jobs.php" class="btn btn-ghost btn-sm">Xem tất cả →</a>
                </div>

                <?php if (!empty($top_jobs)): ?>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($top_jobs as $tj): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-bottom:1px solid var(--line);">
                                <div style="max-width:280px;">
                                    <a href="../job-detail.php?id=<?= (int)$tj['id'] ?>" target="_blank" style="font-weight:700; color:var(--ink); font-size:13px; text-decoration:none;">
                                        <?= htmlspecialchars($tj['title']) ?> ↗
                                    </a>
                                    <div style="font-size:11px; color:var(--muted);">
                                        <?= htmlspecialchars($tj['category']) ?> &middot; <?= htmlspecialchars($tj['location']) ?>
                                    </div>
                                </div>
                                <div style="display:flex; gap:14px; align-items:center;">
                                    <span style="font-size:12px; color:var(--muted);"><?= number_format($tj['views']) ?> lượt xem</span>
                                    <a href="applications.php?job_id=<?= (int)$tj['id'] ?>" class="chip" style="font-weight:700; text-decoration:none; color:var(--ink);">
                                        <?= $tj['app_count'] ?> CV nộp →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:32px 0; color:var(--muted); font-size:13px;">
                        Chưa có tin tuyển dụng nào được tạo.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- HÀNG 3: BẢNG ỨNG VIÊN MỚI NỘP GẦN ĐÂY NHẤT -->
        <div class="table-card">
            <div style="padding:18px 24px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="font-size:16px; font-weight:800; color:var(--ink);">Ứng viên mới nộp gần đây</h2>
                    <p style="font-size:12px; color:var(--muted);">Sàng lọc, ghi chú nội bộ và xếp lịch phỏng vấn</p>
                </div>
                <a href="applications.php" class="btn btn-secondary btn-sm">Quản lý hồ sơ ứng tuyển →</a>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ứng viên</th>
                            <th>Vị trí ứng tuyển</th>
                            <th>Hồ sơ CV</th>
                            <th>Thời gian nộp</th>
                            <th>Vòng tuyển dụng</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_applicants)): ?>
                            <?php foreach ($recent_applicants as $app): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($app['candidate_name']) ?></div>
                                        <div style="font-size:11px; color:var(--muted);">
                                            Email: <?= htmlspecialchars($app['candidate_email']) ?>
                                            <?php if (!empty($app['candidate_phone'])): ?>
                                                &middot; SĐT: <?= htmlspecialchars($app['candidate_phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($app['job_title']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_id'])): ?>
                                            <a href="../cv-preview.php?id=<?= (int)$app['cv_id'] ?>" target="_blank" class="chip" style="font-weight:700; text-decoration:none; color:var(--ink);">
                                                Xem / In CV ↗
                                            </a>
                                        <?php else: ?>
                                            <a href="application-view.php?id=<?= (int)$app['id'] ?>" class="text-muted text-sm" style="text-decoration:underline;">Xem thư ứng tuyển →</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= time_ago($app['created_at']) ?>
                                    </td>
                                    <td>
                                        <?= get_application_stage_badge($app['status']) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="application-view.php?id=<?= (int)$app['id'] ?>" class="btn btn-secondary btn-sm">
                                            Sàng lọc & Ghi chú →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:36px; color:var(--muted);">
                                    Chưa có hồ sơ ứng tuyển mới nào được gửi đến.
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

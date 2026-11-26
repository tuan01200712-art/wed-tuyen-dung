<?php
/**
 * ============================================================
 * FILE: admin/dashboard.php
 * MÔ TẢ: Bảng điều khiển Quản trị viên Toàn diện — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Bảng Điều Khiển Quản Trị';

// Bộ lọc thời gian (Hôm nay, Tháng này, Năm nay, Tất cả)
$period = $_GET['period'] ?? 'all';
$date_cond_users = "";
$date_cond_jobs = "";
$date_cond_apps = "";

if ($period === 'today') {
    $date_cond_users = " AND DATE(created_at) = CURDATE()";
    $date_cond_jobs = " AND DATE(created_at) = CURDATE()";
    $date_cond_apps = " AND DATE(created_at) = CURDATE()";
} elseif ($period === 'month') {
    $date_cond_users = " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $date_cond_jobs = " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $date_cond_apps = " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($period === 'year') {
    $date_cond_users = " AND YEAR(created_at) = YEAR(CURDATE())";
    $date_cond_jobs = " AND YEAR(created_at) = YEAR(CURDATE())";
    $date_cond_apps = " AND YEAR(created_at) = YEAR(CURDATE())";
}

// 1. Thống kê KPI cốt lõi
$stats = [
    'total_users'       => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE 1=1 {$date_cond_users}")->fetchColumn(),
    'candidates'        => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='candidate' {$date_cond_users}")->fetchColumn(),
    'employers'         => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='employer' {$date_cond_users}")->fetchColumn(),
    'verified_employers'=> (int)$pdo->query("SELECT COUNT(*) FROM employer_profiles WHERE is_verified = 1")->fetchColumn(),
    
    'total_jobs'        => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE 1=1 {$date_cond_jobs}")->fetchColumn(),
    'active_jobs'       => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active' {$date_cond_jobs}")->fetchColumn(),
    'pending_jobs'      => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='pending' {$date_cond_jobs}")->fetchColumn(),
    'rejected_jobs'     => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='rejected' {$date_cond_jobs}")->fetchColumn(),
    
    'total_apps'        => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE 1=1 {$date_cond_apps}")->fetchColumn(),
    'total_cvs'         => (int)$pdo->query("SELECT COUNT(*) FROM cvs")->fetchColumn(),
];

// 2. Dữ liệu biểu đồ ứng tuyển theo 6 tháng gần nhất
$chart_apps_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_str = date('Y-m', strtotime("-$i months"));
    $month_label = date('m/Y', strtotime("-$i months"));
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$month_str}'")->fetchColumn();
    $chart_apps_data[] = ['label' => $month_label, 'count' => $cnt];
}
$max_app_count = max(array_column($chart_apps_data, 'count')) ?: 1;

// 3. Phân bố tin tuyển dụng theo ngành nghề
$categories_dist = $pdo->query("
    SELECT category, COUNT(*) as total_jobs
    FROM jobs
    GROUP BY category
    ORDER BY total_jobs DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Danh sách tin tuyển dụng mới nhất (kèm duyệt nhanh nếu là pending)
$recent_jobs = $pdo->query("
    SELECT j.*, u.name as employer_name, u.email as employer_email,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as app_count
    FROM jobs j
    LEFT JOIN users u ON u.id = j.employer_id
    ORDER BY j.created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Danh sách ứng viên mới đăng ký
$recent_candidates = $pdo->query("
    SELECT u.id, u.name, u.email, u.phone, u.created_at,
           (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as applied_count,
           (SELECT COUNT(*) FROM cvs WHERE user_id = u.id) as cv_count
    FROM users u
    WHERE u.role = 'candidate'
    ORDER BY u.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 6. Hoạt động ứng tuyển gần đây
$recent_apps = $pdo->query("
    SELECT a.id, a.created_at, a.status,
           u.name AS candidate_name, u.email AS candidate_email,
           j.id AS job_id, j.title AS job_title, j.company
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs  j ON j.id = a.job_id
    ORDER BY a.created_at DESC
    LIMIT 6
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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <style>
        .filter-period-btn {
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 20px;
            text-decoration: none;
            color: var(--text);
            background: #FFFFFF;
            border: 1px solid var(--line);
            transition: all 0.2s ease;
        }
        .filter-period-btn.active {
            background: var(--admin-bg);
            color: var(--accent);
            border-color: var(--admin-bg);
        }
        .chart-bar-container {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            height: 180px;
            padding-top: 20px;
            border-bottom: 1px solid var(--line);
        }
        .chart-bar-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: flex-end;
        }
        .chart-bar-fill {
            width: 100%;
            max-width: 36px;
            background: linear-gradient(180deg, var(--accent) 0%, rgba(153,255,153,0.3) 100%);
            border-radius: 6px 6px 0 0;
            transition: height 0.3s ease;
            position: relative;
        }
        .chart-bar-fill:hover {
            filter: brightness(0.95);
        }
        .chart-bar-value {
            position: absolute;
            top: -20px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--ink);
        }
        .chart-bar-label {
            margin-top: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
        }
        .cat-bar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .cat-bar-name {
            width: 160px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cat-bar-track {
            flex: 1;
            height: 8px;
            background: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
        }
        .cat-bar-fill {
            height: 100%;
            background: var(--ink);
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <!-- Tiêu đề & Bộ lọc thời gian -->
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title">Tổng quan hệ thống JobFinder</h1>
                <p class="section-desc">Theo dõi toàn diện hiệu suất tuyển dụng, kiểm duyệt tin và tăng trưởng người dùng.</p>
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <span style="font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase;">Thời gian:</span>
                <a href="dashboard.php?period=all" class="filter-period-btn <?= ($period === 'all') ? 'active' : '' ?>">Tất cả</a>
                <a href="dashboard.php?period=today" class="filter-period-btn <?= ($period === 'today') ? 'active' : '' ?>">Hôm nay</a>
                <a href="dashboard.php?period=month" class="filter-period-btn <?= ($period === 'month') ? 'active' : '' ?>">Tháng này</a>
                <a href="dashboard.php?period=year" class="filter-period-btn <?= ($period === 'year') ? 'active' : '' ?>">Năm nay</a>
                <a href="../index.php" target="_blank" class="btn btn-secondary btn-sm" style="margin-left:8px;">Giao diện ↗</a>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- HÀNG 1: THỐNG KÊ TÀI KHOẢN & DOANH NGHIỆP -->
        <div class="stats-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:20px;">
            <div class="stat-box">
                <div class="stat-box__label">Tổng tài khoản</div>
                <div class="stat-box__value"><?= number_format($stats['total_users']) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                    <?= number_format($stats['candidates']) ?> Ứng viên &middot; <?= number_format($stats['employers']) ?> NTD
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-box__label">Doanh nghiệp đã xác thực</div>
                <div class="stat-box__value" style="color:var(--status-success-text);"><?= number_format($stats['verified_employers']) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">Doanh nghiệp uy tín</div>
            </div>

            <div class="stat-box">
                <div class="stat-box__label">Tin đang tuyển</div>
                <div class="stat-box__value" style="color:var(--ink);"><?= number_format($stats['active_jobs']) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">Trên tổng <?= number_format($stats['total_jobs']) ?> tin</div>
            </div>

            <div class="stat-box" style="<?= ($stats['pending_jobs'] > 0) ? 'border-color:#F59E0B; background:#FFFDF7;' : '' ?>">
                <div class="stat-box__label">Tin chờ kiểm duyệt</div>
                <div class="stat-box__value" style="color:#D97706;"><?= number_format($stats['pending_jobs']) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                    <?php if ($stats['pending_jobs'] > 0): ?>
                        <a href="jobs.php?status=pending" style="color:#D97706; font-weight:700; text-decoration:underline;">Kiểm duyệt ngay →</a>
                    <?php else: ?>
                        Đã duyệt hết
                    <?php endif; ?>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-box__label">Lượt nộp hồ sơ</div>
                <div class="stat-box__value"><?= number_format($stats['total_apps']) ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;"><?= number_format($stats['total_cvs']) ?> CV trực tuyến</div>
            </div>
        </div>

        <!-- HÀNG 2: BIỂU ĐỒ ỨNG TUYỂN & PHÂN BỐ NGÀNH NGHỀ -->
        <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:20px; margin-bottom:24px;">
            <!-- Biểu đồ cột ứng tuyển theo tháng -->
            <div class="form-card" style="padding:24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h2 style="font-size:16px; font-weight:800; color:var(--ink);">Biểu đồ lượt ứng tuyển (6 tháng gần đây)</h2>
                        <p style="font-size:12px; color:var(--muted); margin-top:2px;">Số lượng hồ sơ nộp vào hệ thống</p>
                    </div>
                    <span class="badge badge--active">Thời gian thực</span>
                </div>

                <div class="chart-bar-container">
                    <?php foreach ($chart_apps_data as $m): 
                        $height_pct = round(($m['count'] / $max_app_count) * 100);
                        if ($height_pct < 10) $height_pct = 10;
                    ?>
                        <div class="chart-bar-col">
                            <div class="chart-bar-fill" style="height: <?= $height_pct ?>%;">
                                <span class="chart-bar-value"><?= $m['count'] ?></span>
                            </div>
                            <span class="chart-bar-label"><?= htmlspecialchars($m['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Phân bố tin theo ngành nghề -->
            <div class="form-card" style="padding:24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h2 style="font-size:16px; font-weight:800; color:var(--ink);">Top ngành nghề tuyển dụng</h2>
                        <p style="font-size:12px; color:var(--muted); margin-top:2px;">Tập trung nhu cầu nhân lực</p>
                    </div>
                    <a href="categories.php" class="btn btn-ghost btn-sm">Quản lý →</a>
                </div>

                <?php 
                $max_cat_jobs = !empty($categories_dist) ? max(array_column($categories_dist, 'total_jobs')) : 1;
                ?>
                <div style="padding-top:8px;">
                    <?php if (!empty($categories_dist)): ?>
                        <?php foreach ($categories_dist as $cd): 
                            $pct = round(($cd['total_jobs'] / $max_cat_jobs) * 100);
                        ?>
                            <div class="cat-bar-item">
                                <span class="cat-bar-name" title="<?= htmlspecialchars($cd['category']) ?>"><?= htmlspecialchars($cd['category']) ?></span>
                                <div class="cat-bar-track">
                                    <div class="cat-bar-fill" style="width:<?= $pct ?>%;"></div>
                                </div>
                                <span style="font-size:12px; font-weight:700; width:36px; text-align:right;"><?= $cd['total_jobs'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--muted); font-size:13px; text-align:center; padding:30px 0;">Chưa có dữ liệu ngành nghề.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- HÀNG 3: DANH SÁCH TIN MỚI NHẤT & DUYỆT NHANH -->
        <div class="table-card" style="margin-bottom:24px;">
            <div style="padding:18px 24px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="font-size:16px; font-weight:800; color:var(--ink);">Tin tuyển dụng mới nhất</h2>
                    <p style="font-size:12px; color:var(--muted);">Cập nhật và kiểm duyệt nội dung trực tiếp</p>
                </div>
                <div style="display:flex; gap:8px;">
                    <a href="job-create.php" class="btn btn-primary btn-sm">+ Tạo tin mới (Admin)</a>
                    <a href="jobs.php" class="btn btn-secondary btn-sm">Xem tất cả tin →</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vị trí tuyển dụng</th>
                            <th>Doanh nghiệp</th>
                            <th>Địa điểm & Ngành</th>
                            <th>Mức lương</th>
                            <th>Trạng thái</th>
                            <th>Số hồ sơ</th>
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
                                            <?php if (!empty($j['is_featured'])): ?>
                                                <span class="badge badge--active" style="font-size:10px; padding:2px 6px;">Nổi bật</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:11px; color:var(--muted);"><?= time_ago($j['created_at']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($j['company']) ?></div>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= htmlspecialchars($j['location']) ?> &middot; <?= htmlspecialchars($j['category']) ?>
                                    </td>
                                    <td>
                                        <span class="chip" style="font-weight:700;"><?= htmlspecialchars($j['salary']) ?></span>
                                    </td>
                                    <td>
                                        <?= get_job_status_badge($j['status']) ?>
                                    </td>
                                    <td style="font-weight:700;">
                                        <?= (int)$j['app_count'] ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <?php if ($j['status'] === 'pending'): ?>
                                                <form method="POST" action="jobs.php" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="padding:4px 10px; font-size:12px;">Duyệt</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="job-edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-ghost btn-sm">Sửa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:36px; color:var(--muted);">
                                    Chưa có tin tuyển dụng nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- HÀNG 4: ỨNG VIÊN MỚI & HỒ SƠ ỨNG TUYỂN MỚI -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <!-- Ứng viên mới -->
            <div class="table-card">
                <div style="padding:16px 20px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="font-size:15px; font-weight:800; color:var(--ink);">Ứng viên đăng ký mới</h2>
                    <a href="users.php?role=candidate" class="btn btn-ghost btn-sm">Xem tất cả →</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Họ tên & Email</th>
                                <th>Đã nộp</th>
                                <th>CV</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_candidates)): ?>
                                <?php foreach ($recent_candidates as $rc): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($rc['name']) ?></div>
                                            <div style="font-size:11px; color:var(--muted);"><?= htmlspecialchars($rc['email']) ?></div>
                                        </td>
                                        <td style="font-weight:700;"><?= $rc['applied_count'] ?> hồ sơ</td>
                                        <td style="font-weight:600;"><?= $rc['cv_count'] ?> CV</td>
                                        <td class="text-muted text-sm"><?= date('d/m/Y', strtotime($rc['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding:24px; color:var(--muted);">Chưa có ứng viên.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Hoạt động ứng tuyển gần đây -->
            <div class="table-card">
                <div style="padding:16px 20px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="font-size:15px; font-weight:800; color:var(--ink);">Hồ sơ ứng tuyển mới nhất</h2>
                    <a href="applications.php" class="btn btn-ghost btn-sm">Xem tất cả →</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ứng viên</th>
                                <th>Vị trí tuyển dụng</th>
                                <th>Vòng tuyển dụng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_apps)): ?>
                                <?php foreach ($recent_apps as $ra): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($ra['candidate_name']) ?></div>
                                            <div style="font-size:11px; color:var(--muted);"><?= time_ago($ra['created_at']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($ra['job_title']) ?></div>
                                            <div style="font-size:11px; color:var(--muted);"><?= htmlspecialchars($ra['company']) ?></div>
                                        </td>
                                        <td>
                                            <?= get_application_stage_badge($ra['status']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--muted);">Chưa có ứng tuyển.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

</body>
</html>

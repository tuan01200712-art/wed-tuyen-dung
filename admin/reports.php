<?php
/**
 * ============================================================
 * FILE: admin/reports.php
 * MÔ TẢ: Báo cáo & Phân tích Hiệu quả Tuyển dụng Nâng cao — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Báo Cáo & Thống Kê Tuyển Dụng';
$period = $_GET['period'] ?? '6months';

// 1. Thống kê đăng ký theo tháng (6 tháng gần đây)
$monthly_stats = [];
for ($i = 5; $i >= 0; $i--) {
    $m_key = date('Y-m', strtotime("-$i months"));
    $m_lbl = date('m/Y', strtotime("-$i months"));

    $users_cnt = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$m_key}'")->fetchColumn();
    $jobs_cnt = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$m_key}'")->fetchColumn();
    $jobs_approved_cnt = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active' AND DATE_FORMAT(created_at, '%Y-%m') = '{$m_key}'")->fetchColumn();
    $apps_cnt = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$m_key}'")->fetchColumn();

    $monthly_stats[] = [
        'month'         => $m_lbl,
        'users'         => $users_cnt,
        'jobs'          => $jobs_cnt,
        'jobs_approved' => $jobs_approved_cnt,
        'apps'          => $apps_cnt
    ];
}

// 2. Tỷ lệ tiếp nhận ứng viên (Hired / Total applications)
$total_all_apps = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn() ?: 1;
$total_hired_apps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('hired', 'offered')")->fetchColumn();
$hire_rate = round(($total_hired_apps / $total_all_apps) * 100, 1);

// 3. Tỷ lệ chuyển đổi Lượt xem tin -> Nộp hồ sơ (Conversion Rate)
$total_views = (int)$pdo->query("SELECT SUM(views) FROM jobs")->fetchColumn() ?: 1;
$conversion_rate = round(($total_all_apps / $total_views) * 100, 2);

// 4. Doanh nghiệp hoạt động nhiều nhất (Top Employers by active jobs & applications)
$top_employers = $pdo->query("
    SELECT u.id, u.name, ep.company_name,
           COUNT(DISTINCT j.id) as total_jobs,
           COUNT(DISTINCT a.id) as total_apps
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    LEFT JOIN jobs j ON j.employer_id = u.id
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE u.role = 'employer'
    GROUP BY u.id
    ORDER BY total_jobs DESC, total_apps DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Ngành nghề nhiều tin nhất
$top_categories = $pdo->query("
    SELECT category, COUNT(*) as job_count, SUM(views) as total_views
    FROM jobs
    GROUP BY category
    ORDER BY job_count DESC
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
        .metric-card {
            background: #FFFFFF;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .metric-card__number {
            font-size: 32px;
            font-weight: 800;
            color: var(--ink);
            margin: 8px 0 4px;
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                                <h1 class="section-title">Báo cáo & Hiệu quả Tuyển dụng</h1>
                <p class="section-desc">Phân tích chuyên sâu về tỷ lệ chuyển đổi, tăng trưởng việc làm và nhu cầu thị trường.</p>
            </div>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">Xuất báo cáo</button>
        </div>

        <!-- 4 THẺ CHỈ SỐ HIỆU SUẤT CAO CẤP -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:28px;">
            <div class="metric-card">
                <div class="stat-box__label">Tỷ lệ chuyển đổi sàn</div>
                <div class="metric-card__number" style="color:#059669;"><?= $conversion_rate ?>%</div>
                <div style="font-size:12px; color:var(--muted);"><?= number_format($total_all_apps) ?> ứng tuyển trên <?= number_format($total_views) ?> lượt xem tin</div>
            </div>

            <div class="metric-card">
                <div class="stat-box__label">Tỷ lệ tiếp nhận ứng viên</div>
                <div class="metric-card__number" style="color:var(--ink);"><?= $hire_rate ?>%</div>
                <div style="font-size:12px; color:var(--muted);"><?= number_format($total_hired_apps) ?> ứng viên được đề nghị / tuyển dụng</div>
            </div>

            <div class="metric-card">
                <div class="stat-box__label">Tin được duyệt</div>
                <?php 
                $tot_jobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn() ?: 1;
                $tot_approved = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active'")->fetchColumn();
                $appr_rate = round(($tot_approved / $tot_jobs) * 100);
                ?>
                <div class="metric-card__number" style="color:#2563EB;"><?= $appr_rate ?>%</div>
                <div style="font-size:12px; color:var(--muted);"><?= $tot_approved ?> tin duyệt trên <?= $tot_jobs ?> tin đăng</div>
            </div>

            <div class="metric-card">
                <div class="stat-box__label">Mức độ tương tác</div>
                <?php $avg_apps = round($total_all_apps / $tot_jobs, 1); ?>
                <div class="metric-card__number" style="color:var(--ink);"><?= $avg_apps ?></div>
                <div style="font-size:12px; color:var(--muted);">Hồ sơ trung bình trên mỗi tin đăng</div>
            </div>
        </div>

        <!-- BẢNG BÁO CÁO THEO THÁNG -->
        <div class="table-card" style="margin-bottom:28px;">
            <div style="padding:18px 24px; border-bottom:1px solid var(--line);">
                <h3 style="font-size:16px; font-weight:800; color:var(--ink);">Bảng theo dõi số liệu 6 tháng gần nhất</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tháng</th>
                            <th>Tài khoản mới</th>
                            <th>Tin tuyển dụng mới</th>
                            <th>Tin đã được duyệt</th>
                            <th>Lượt ứng tuyển</th>
                            <th>Tỷ lệ duyệt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_stats as $ms): 
                            $m_appr_pct = ($ms['jobs'] > 0) ? round(($ms['jobs_approved'] / $ms['jobs']) * 100) : 100;
                        ?>
                            <tr>
                                <td style="font-weight:700; color:var(--ink);"><?= $ms['month'] ?></td>
                                <td><?= number_format($ms['users']) ?> tài khoản</td>
                                <td><?= number_format($ms['jobs']) ?> tin</td>
                                <td><span class="badge badge--active"><?= number_format($ms['jobs_approved']) ?> tin</span></td>
                                <td style="font-weight:700;"><?= number_format($ms['apps']) ?> hồ sơ</td>
                                <td><?= $m_appr_pct ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2 KHỐI: TOP DOANH NGHIỆP & TOP NGÀNH NGHỀ -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <!-- Top Doanh nghiệp -->
            <div class="table-card">
                <div style="padding:16px 20px; border-bottom:1px solid var(--line);">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink);">Doanh nghiệp hoạt động tích cực nhất</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Doanh nghiệp</th>
                                <th>Tin đăng</th>
                                <th>CV thu hút</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_employers as $te): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($te['company_name'] ?: $te['name']) ?></div>
                                    </td>
                                    <td style="font-weight:700;"><?= $te['total_jobs'] ?> tin</td>
                                    <td><span class="chip" style="font-weight:700;"><?= $te['total_apps'] ?> CV</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Ngành nghề -->
            <div class="table-card">
                <div style="padding:16px 20px; border-bottom:1px solid var(--line);">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink);">Ngành nghề có nhu cầu cao nhất</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ngành nghề</th>
                                <th>Số tin tuyển</th>
                                <th>Lượt xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_categories as $tc): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($tc['category']) ?></div>
                                    </td>
                                    <td style="font-weight:700;"><?= $tc['job_count'] ?> tin</td>
                                    <td class="text-muted text-sm"><?= number_format($tc['total_views']) ?> xem</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

</body>
</html>

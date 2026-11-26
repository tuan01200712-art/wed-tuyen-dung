<?php
/**
 * ============================================================
 * FILE: admin/applications.php
 * MÔ TẢ: Quản trị toàn bộ hồ sơ ứng tuyển & Candidate Pipeline — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Hồ Sơ Ứng Tuyển';

// Xử lý POST (Cập nhật trạng thái vòng tuyển dụng hoặc Xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $app_id = (int)($_POST['app_id'] ?? 0);

    if ($app_id > 0) {
        if ($action === 'update_status') {
            $new_status = trim($_POST['status'] ?? '');
            $stages = get_application_stages();
            if (isset($stages[$new_status])) {
                $pdo->prepare("UPDATE applications SET status = ?, stage_updated_at = NOW() WHERE id = ?")->execute([$new_status, $app_id]);
                
                // Lấy thông tin ứng viên và job để thông báo
                $info = $pdo->query("SELECT a.user_id, j.title, j.company FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = {$app_id}")->fetch();
                if ($info) {
                    create_notification($pdo, $info['user_id'], 'Hồ sơ của bạn đã được cập nhật trạng thái', "Hồ sơ ứng tuyển vị trí '{$info['title']}' tại {$info['company']} đã chuyển sang vòng: " . $stages[$new_status]['name'], "my-applications.php");
                }

                log_activity($pdo, $admin_id, 'APP_STAGE_UPDATE', "Cập nhật trạng thái hồ sơ #{$app_id} sang {$new_status}");
                $_SESSION['flash_message'] = 'Đã cập nhật trạng thái hồ sơ #' . $app_id . ' sang "' . $stages[$new_status]['name'] . '".';
                $_SESSION['flash_type'] = 'success';
            }
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$app_id]);
            log_activity($pdo, $admin_id, 'APP_DELETE', "Xóa hồ sơ ứng tuyển #{$app_id}");
            $_SESSION['flash_message'] = 'Đã xóa hồ sơ ứng tuyển khỏi hệ thống.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: applications.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'all';
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$employer_filter = isset($_GET['employer_id']) ? (int)$_GET['employer_id'] : 0;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Chuẩn bị danh sách jobs và employers để làm dropdown lọc
$jobs_list = $pdo->query("SELECT id, title, company FROM jobs ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$employers_list = $pdo->query("
    SELECT u.id, u.name, ep.company_name
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    WHERE u.role = 'employer'
    ORDER BY COALESCE(ep.company_name, u.name) ASC
")->fetchAll(PDO::FETCH_ASSOC);

$sql_where = " WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql_where .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

if ($job_filter > 0) {
    $sql_where .= " AND a.job_id = :job_id";
    $params[':job_id'] = $job_filter;
}

if ($employer_filter > 0) {
    $sql_where .= " AND j.employer_id = :employer_id";
    $params[':employer_id'] = $employer_filter;
}

if (!empty($search)) {
    $sql_where .= " AND (u.name LIKE :search OR u.email LIKE :search OR j.title LIKE :search OR j.company LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// Đếm tổng số
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM applications a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN jobs j ON j.id = a.job_id
    {$sql_where}
");
$count_stmt->execute($params);
$total_apps = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_apps / $limit) ?: 1;

// Lấy danh sách hồ sơ
$sql = "
    SELECT a.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title, j.company, j.employer_id,
           c.id as cv_id, c.title as cv_title, c.cv_link
    FROM applications a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    {$sql_where}
    ORDER BY a.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stages = get_application_stages();
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
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title">Quản lý Hồ sơ ứng tuyển</h1>
                <p class="section-desc">Tổng cộng <strong><?= number_format($total_apps) ?></strong> lượt ứng tuyển trên toàn hệ thống.</p>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm & lọc nâng cao -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm ứng viên, email, vị trí..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="status" class="search-select">
                        <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Tất cả vòng tuyển dụng</option>
                        <?php foreach ($stages as $code => $stg): ?>
                            <option value="<?= $code ?>" <?= ($status_filter === $code) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($stg['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-field">
                    <select name="job_id" class="search-select">
                        <option value="0">Tất cả việc làm</option>
                        <?php foreach ($jobs_list as $jb): ?>
                            <option value="<?= $jb['id'] ?>" <?= ($job_filter === (int)$jb['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($jb['title']) ?> (<?= htmlspecialchars($jb['company']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-field">
                    <select name="employer_id" class="search-select">
                        <option value="0">Tất cả doanh nghiệp</option>
                        <?php foreach ($employers_list as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($employer_filter === (int)$emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['company_name'] ?: $emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <?php if ($status_filter !== 'all' || $job_filter > 0 || $employer_filter > 0 || !empty($search)): ?>
                        <a href="applications.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- BẢNG DANH SÁCH ỨNG TUYỂN -->
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
                            <th>Trạng thái hồ sơ</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($applications)): ?>
                            <?php foreach ($applications as $i => $app): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($app['candidate_name']) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            Email: <?= htmlspecialchars($app['candidate_email']) ?>
                                            <?php if (!empty($app['candidate_phone'])): ?>
                                                &middot; SĐT: <?= htmlspecialchars($app['candidate_phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:var(--ink);">
                                            <a href="../job-detail.php?id=<?= (int)$app['job_id'] ?>" target="_blank" style="color:var(--ink);">
                                                <?= htmlspecialchars($app['job_title']) ?> ↗
                                            </a>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($app['company']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_id'])): ?>
                                            <a href="../cv-preview.php?id=<?= (int)$app['cv_id'] ?>" target="_blank" class="chip" style="font-weight:700; text-decoration:none; color:var(--ink);">
                                                <?= htmlspecialchars($app['cv_title'] ?: 'Xem / In CV') ?> ↗
                                            </a>
                                        <?php elseif (!empty($app['cv_link'])): ?>
                                            <a href="<?= htmlspecialchars($app['cv_link']) ?>" target="_blank" class="chip" style="font-weight:700; text-decoration:none; color:var(--ink);">
                                                Link ngoài ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Thư ứng tuyển</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y H:i', strtotime($app['created_at'])) ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:flex; align-items:center; gap:6px;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                            <select name="status" class="form-control form-select" style="padding:4px 24px 4px 8px; font-size:12px; height:32px; font-weight:700;" onchange="this.form.submit()">
                                                <?php foreach ($stages as $code => $stg): ?>
                                                    <option value="<?= $code ?>" <?= ($app['status'] === $code) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($stg['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <?php if (!empty($app['cover_letter'])): ?>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="alert('Thư ngỏ từ ứng viên <?= htmlspecialchars(addslashes($app['candidate_name'])) ?>:\n\n<?= htmlspecialchars(addslashes($app['cover_letter'])) ?>')">
                                                    Thư ngỏ
                                                </button>
                                            <?php endif; ?>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bản ghi ứng tuyển này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy hồ sơ nào phù hợp với bộ lọc.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-top:1px solid var(--line);">
                    <div style="font-size:13px; color:var(--muted);">
                        Hiển thị trang <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong> (Tổng <?= $total_apps ?> hồ sơ)
                    </div>
                    <div style="display:flex; gap:8px;">
                        <?php if ($page > 1): ?>
                            <a href="applications.php?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&job_id=<?= $job_filter ?>&employer_id=<?= $employer_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">← Trang trước</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="applications.php?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&job_id=<?= $job_filter ?>&employer_id=<?= $employer_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">Trang sau →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>

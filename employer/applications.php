<?php
/**
 * ============================================================
 * FILE: employer/applications.php
 * MÔ TẢ: Candidate Pipeline & Kanban Board Tuyển dụng Đa vòng — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Hồ Sơ Ứng Tuyển';

// 1. Xử lý AJAX kéo thả chuyển vòng (Kanban Drag & Drop)
if (isset($_GET['action']) && $_GET['action'] === 'ajax_move') {
    header('Content-Type: application/json');
    $app_id = (int)($_POST['app_id'] ?? 0);
    $new_stage = trim($_POST['stage'] ?? '');
    $stages = get_application_stages();

    if ($app_id > 0 && isset($stages[$new_stage])) {
        $chk = $pdo->prepare("
            SELECT a.id, a.user_id, j.title, j.company 
            FROM applications a 
            JOIN jobs j ON j.id = a.job_id 
            WHERE a.id = ? AND j.employer_id = ?
        ");
        $chk->execute([$app_id, $employer_id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $pdo->prepare("UPDATE applications SET status = ?, stage_updated_at = NOW() WHERE id = ?")->execute([$new_stage, $app_id]);
            create_notification($pdo, $row['user_id'], 'Hồ sơ đã chuyển vòng tuyển dụng', "Hồ sơ ứng tuyển vị trí '{$row['title']}' của bạn đã được chuyển sang vòng: " . $stages[$new_stage]['name'], 'my-applications.php');
            log_activity($pdo, $employer_id, 'KANBAN_STAGE_CHANGE', "Chuyển ứng viên hồ sơ #{$app_id} sang vòng {$new_stage}");
            echo json_encode(['success' => true, 'stage_name' => $stages[$new_stage]['name']]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Quyền truy cập không hợp lệ.']);
    exit;
}

// 2. Xử lý POST đổi trạng thái thủ công (nếu không dùng JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $app_id = (int)($_POST['app_id'] ?? 0);
    $new_stage = trim($_POST['status'] ?? '');
    $stages = get_application_stages();

    if ($app_id > 0 && isset($stages[$new_stage])) {
        $chk = $pdo->prepare("
            SELECT a.id, a.user_id, j.title, j.company 
            FROM applications a 
            JOIN jobs j ON j.id = a.job_id 
            WHERE a.id = ? AND j.employer_id = ?
        ");
        $chk->execute([$app_id, $employer_id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $pdo->prepare("UPDATE applications SET status = ?, stage_updated_at = NOW() WHERE id = ?")->execute([$new_stage, $app_id]);
            create_notification($pdo, $row['user_id'], 'Hồ sơ đã chuyển vòng tuyển dụng', "Hồ sơ ứng tuyển vị trí '{$row['title']}' của bạn đã được chuyển sang vòng: " . $stages[$new_stage]['name'], 'my-applications.php');
            $_SESSION['flash_message'] = 'Đã chuyển hồ sơ ứng viên sang vòng "' . $stages[$new_stage]['name'] . '".';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$view_mode = $_GET['view'] ?? 'kanban'; // 'kanban' hoặc 'table'
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$search = trim($_GET['search'] ?? '');
$stages = get_application_stages();

// Lấy danh sách jobs của doanh nghiệp để lọc
$jobs_stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
$jobs_stmt->execute([$employer_id]);
$employer_jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Xây dựng truy vấn lấy ứng viên
$sql_where = " WHERE j.employer_id = :employer_id";
$params = [':employer_id' => $employer_id];

if ($job_filter > 0) {
    $sql_where .= " AND j.id = :job_id";
    $params[':job_id'] = $job_filter;
}

if (!empty($search)) {
    $sql_where .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search OR c.skills LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql = "
    SELECT a.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title,
           c.id as cv_id, c.title as cv_title, c.skills as cv_skills, c.cv_link,
           (SELECT COUNT(*) FROM application_notes WHERE application_id = a.id) as note_count,
           (SELECT COUNT(*) FROM interview_schedules WHERE application_id = a.id) as interview_count
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    {$sql_where}
    ORDER BY a.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$all_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_apps = count($all_applications);

// Phân loại ứng viên theo từng vòng (Kanban Columns)
$pipeline = [];
foreach (array_keys($stages) as $stg_code) {
    $pipeline[$stg_code] = [];
}

// Ánh xạ trạng thái cũ (nếu có)
$legacy_map = [
    'Đã nộp hồ sơ'  => 'new',
    'Đang xét duyệt' => 'viewed',
    'Phù hợp'        => 'suitable',
    'Từ chối'        => 'rejected'
];

foreach ($all_applications as $app) {
    $st = $app['status'];
    if (isset($legacy_map[$st])) {
        $st = $legacy_map[$st];
    }
    if (!isset($pipeline[$st])) {
        $st = 'new';
    }
    $pipeline[$st][] = $app;
}
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
    <style>
        .kanban-wrapper {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 24px;
            min-height: calc(100vh - 280px);
        }
        .kanban-column {
            flex: 0 0 290px;
            background: #F8FAFC;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 250px);
        }
        .kanban-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FFFFFF;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
        }
        .kanban-body {
            padding: 12px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .kanban-body.drag-over {
            background: rgba(153, 255, 153, 0.15);
            border: 2px dashed #16A34A;
        }
        .candidate-card {
            background: #FFFFFF;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 14px;
            box-shadow: var(--shadow-sm);
            cursor: grab;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: #CBD5E1;
        }
        .candidate-card:active {
            cursor: grabbing;
        }
        .view-toggle-btn {
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text);
            background: #FFFFFF;
            border: 1px solid var(--line);
        }
        .view-toggle-btn.active {
            background: var(--ink);
            color: #FFFFFF;
            border-color: var(--ink);
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:20px;">
            <div>
                <h1 class="section-title">Quản lý hồ sơ ứng tuyển</h1>
                <p class="section-desc">Theo dõi và cập nhật trạng thái hồ sơ ứng viên trên hệ thống JobFinder.</p>
            </div>
            
            <div style="display:flex; gap:10px; align-items:center;">
                <div style="display:flex; gap:4px; background:#E5E7EB; padding:3px; border-radius:10px;">
                    <a href="applications.php?view=kanban&job_id=<?= $job_filter ?>&search=<?= urlencode($search) ?>" class="view-toggle-btn <?= ($view_mode === 'kanban') ? 'active' : '' ?>">
                        Dạng bảng kéo thả
                    </a>
                    <a href="applications.php?view=table&job_id=<?= $job_filter ?>&search=<?= urlencode($search) ?>" class="view-toggle-btn <?= ($view_mode === 'table') ? 'active' : '' ?>">
                        Dạng danh sách
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- THANH LỌC THEO TIN & TÌM KIẾM -->
        <div class="smart-search-card" style="margin-bottom:20px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 2fr 1.5fr auto;">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
                
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm kiếm ứng viên theo họ tên, email, kỹ năng..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="job_id" class="search-select">
                        <option value="0">Tất cả tin tuyển dụng (<?= count($employer_jobs) ?> tin)</option>
                        <?php foreach ($employer_jobs as $ej): ?>
                            <option value="<?= $ej['id'] ?>" <?= ($job_filter === (int)$ej['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ej['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <?php if ($job_filter > 0 || !empty($search)): ?>
                        <a href="applications.php?view=<?= $view_mode ?>" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($view_mode === 'kanban'): ?>
            <!-- CHẾ ĐỘ 1: KANBAN BOARD 9 CỘT -->
            <div class="kanban-wrapper">
                <?php foreach ($stages as $stage_code => $stage_info): 
                    $stage_candidates = $pipeline[$stage_code] ?? [];
                ?>
                    <div class="kanban-column" id="col-<?= $stage_code ?>">
                        <div class="kanban-header">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="width:10px; height:10px; border-radius:50%; background:<?= $stage_info['color'] ?>;"></span>
                                <span style="font-size:13px; font-weight:800; color:var(--ink);"><?= htmlspecialchars($stage_info['name']) ?></span>
                            </div>
                            <span class="chip" style="font-size:11px; font-weight:700; padding:2px 8px;" id="count-<?= $stage_code ?>">
                                <?= count($stage_candidates) ?>
                            </span>
                        </div>

                        <div class="kanban-body" 
                             data-stage="<?= $stage_code ?>"
                             ondragover="onDragOver(event)" 
                             ondragleave="onDragLeave(event)" 
                             ondrop="onDrop(event, '<?= $stage_code ?>')">
                            
                            <?php if (!empty($stage_candidates)): ?>
                                <?php foreach ($stage_candidates as $cand): ?>
                                    <div class="candidate-card" 
                                         id="card-<?= $cand['id'] ?>"
                                         draggable="true" 
                                         ondragstart="onDragStart(event, <?= $cand['id'] ?>)">
                                        
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                                            <a href="application-view.php?id=<?= $cand['id'] ?>" style="font-weight:800; color:var(--ink); font-size:14px; text-decoration:none;">
                                                <?= htmlspecialchars($cand['candidate_name']) ?>
                                            </a>
                                            <?php if ((int)$cand['rating'] > 0): ?>
                                                <span style="font-size:12px; color:#F59E0B;">★ <?= $cand['rating'] ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div style="font-size:12px; color:#2563EB; font-weight:600; margin-bottom:8px; line-height:1.4;">
                                            <?= htmlspecialchars($cand['job_title']) ?>
                                        </div>

                                        <div style="font-size:11px; color:var(--muted); margin-bottom:10px;">
                                            Email: <?= htmlspecialchars($cand['candidate_email']) ?>
                                            <?php if (!empty($cand['candidate_phone'])): ?>
                                                <br>SĐT: <?= htmlspecialchars($cand['candidate_phone']) ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Footer thẻ -->
                                        <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--line); font-size:11px; color:var(--muted);">
                                            <span><?= time_ago($cand['created_at']) ?></span>
                                            
                                            <div style="display:flex; gap:6px;">
                                                <?php if (!empty($cand['cv_id'])): ?>
                                                    <a href="../cv-preview.php?id=<?= (int)$cand['cv_id'] ?>" target="_blank" title="Xem CV" style="font-weight:700; color:var(--ink); text-decoration:underline;">CV ↗</a>
                                                <?php endif; ?>
                                                <a href="application-view.php?id=<?= $cand['id'] ?>" style="font-weight:700; color:#16A34A; text-decoration:underline;">Chi tiết →</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align:center; padding:24px 0; color:#9CA3AF; font-size:12px;" class="empty-placeholder">
                                    Thả hồ sơ vào đây
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- CHẾ ĐỘ 2: DẠNG BẢNG CHI TIẾT (TABLE VIEW) -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Ứng viên</th>
                                <th>Vị trí ứng tuyển</th>
                                <th>Hồ sơ CV</th>
                                <th>Ngày nộp</th>
                                <th>Ghi chú & Lịch</th>
                                <th>TRẠNG THÁI HỒ SƠ</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_applications)): ?>
                                <?php foreach ($all_applications as $i => $app): ?>
                                    <tr>
                                        <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                        <td>
                                            <div style="font-weight:800; color:var(--ink);">
                                                <a href="application-view.php?id=<?= $app['id'] ?>" style="color:var(--ink);">
                                                    <?= htmlspecialchars($app['candidate_name']) ?>
                                                </a>
                                            </div>
                                            <div style="font-size:12px; color:var(--muted);">
                                                Email: <?= htmlspecialchars($app['candidate_email']) ?> &middot; SĐT: <?= htmlspecialchars($app['candidate_phone'] ?: '—') ?>
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
                                        <td class="text-muted text-sm"><?= date('d/m/Y H:i', strtotime($app['created_at'])) ?></td>
                                        <td class="text-sm">
                                            <span>📝 <?= $app['note_count'] ?> ghi chú</span> &middot; 
                                            <span>📅 <?= $app['interview_count'] ?> lịch</span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
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
                                            <a href="application-view.php?id=<?= (int)$app['id'] ?>" class="btn btn-secondary btn-sm">Chi tiết & Ghi chú →</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center; padding:48px; color:var(--muted);">Chưa có hồ sơ nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- JAVASCRIPT KANBAN DRAG & DROP & AJAX UPDATE -->
<script>
var draggedAppId = null;

function onDragStart(e, appId) {
    draggedAppId = appId;
    e.dataTransfer.setData('text/plain', appId);
    e.dataTransfer.effectAllowed = 'move';
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('drag-over');
}

function onDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

function onDrop(e, targetStage) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    var appId = draggedAppId || e.dataTransfer.getData('text/plain');

    if (!appId) return;

    var cardElem = document.getElementById('card-' + appId);
    if (!cardElem) return;

    // Di chuyển card trong DOM ngay lập tức
    var dropZone = e.currentTarget;
    var placeholder = dropZone.querySelector('.empty-placeholder');
    if (placeholder) placeholder.style.display = 'none';

    dropZone.appendChild(cardElem);

    // Gửi AJAX lưu vào Database
    var formData = new FormData();
    formData.append('app_id', appId);
    formData.append('stage', targetStage);

    fetch('applications.php?action=ajax_move', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data.success) {
            alert('Không thể lưu trạng thái: ' + (data.error || 'Lỗi kết nối'));
            location.reload();
        }
    })
    .catch(function(err) {
        console.error(err);
        location.reload();
    });
}
</script>

</body>
</html>

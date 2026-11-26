<?php
/**
 * ============================================================
 * FILE: admin/jobs.php
 * MÔ TẢ: Kiểm duyệt và quản trị tin tuyển dụng toàn diện — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Tin Tuyển Dụng';

// Xử lý các thao tác POST: Duyệt, Từ chối, Ẩn, Khôi phục, Đánh dấu nổi bật, Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $job_id = (int)($_POST['job_id'] ?? 0);

    if ($job_id > 0) {
        $j_info = $pdo->query("SELECT title, employer_id, status FROM jobs WHERE id = {$job_id}")->fetch(PDO::FETCH_ASSOC);

        if ($j_info) {
            if ($action === 'approve') {
                $pdo->prepare("UPDATE jobs SET status = 'active', reject_reason = NULL WHERE id = ?")->execute([$job_id]);
                log_activity($pdo, $admin_id, 'JOB_APPROVE', "Duyệt công khai tin tuyển dụng ID #{$job_id}: {$j_info['title']}");
                
                if ($j_info['employer_id']) {
                    create_notification($pdo, $j_info['employer_id'], 'Tin tuyển dụng đã được duyệt!', "Tin tuyển dụng '{$j_info['title']}' của bạn đã được kiểm duyệt và đang hiển thị công khai.", "employer/jobs.php");
                }
                
                $_SESSION['flash_message'] = 'Đã duyệt và công khai tin tuyển dụng #' . $job_id . '.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'reject') {
                $reason = trim($_POST['reject_reason'] ?? 'Nội dung chưa đạt tiêu chuẩn quy định của nền tảng.');
                $pdo->prepare("UPDATE jobs SET status = 'rejected', reject_reason = ? WHERE id = ?")->execute([$reason, $job_id]);
                log_activity($pdo, $admin_id, 'JOB_REJECT', "Từ chối tin tuyển dụng ID #{$job_id} với lý do: {$reason}");
                
                if ($j_info['employer_id']) {
                    create_notification($pdo, $j_info['employer_id'], 'Tin tuyển dụng bị từ chối', "Tin '{$j_info['title']}' bị từ chối. Lý do: {$reason}", "employer/job-edit.php?id={$job_id}");
                }

                $_SESSION['flash_message'] = 'Đã từ chối tin tuyển dụng #' . $job_id . ' kèm thông báo lý do đến doanh nghiệp.';
                $_SESSION['flash_type'] = 'warning';
            } elseif ($action === 'hide') {
                $pdo->prepare("UPDATE jobs SET status = 'inactive' WHERE id = ?")->execute([$job_id]);
                log_activity($pdo, $admin_id, 'JOB_HIDE', "Tạm dừng tin tuyển dụng ID #{$job_id}");
                $_SESSION['flash_message'] = 'Đã tạm dừng hiển thị tin tuyển dụng.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'restore') {
                $pdo->prepare("UPDATE jobs SET status = 'active' WHERE id = ?")->execute([$job_id]);
                log_activity($pdo, $admin_id, 'JOB_RESTORE', "Kích hoạt lại tin tuyển dụng ID #{$job_id}");
                $_SESSION['flash_message'] = 'Đã kích hoạt tin tuyển dụng trở lại trạng thái đang tuyển.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'toggle_featured') {
                $curr_feat = (int)$pdo->query("SELECT is_featured FROM jobs WHERE id = {$job_id}")->fetchColumn();
                $new_feat = ($curr_feat === 1) ? 0 : 1;
                $pdo->prepare("UPDATE jobs SET is_featured = ? WHERE id = ?")->execute([$new_feat, $job_id]);
                log_activity($pdo, $admin_id, 'JOB_FEATURE_TOGGLE', "Đổi cờ nổi bật tin ID #{$job_id} thành {$new_feat}");
                $_SESSION['flash_message'] = 'Đã ' . ($new_feat ? 'đánh dấu nổi bật' : 'bỏ đánh dấu nổi bật') . ' tin tuyển dụng.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$job_id]);
                log_activity($pdo, $admin_id, 'JOB_DELETE', "Xóa vĩnh viễn tin tuyển dụng ID #{$job_id}: {$j_info['title']}");
                $_SESSION['flash_message'] = 'Đã xóa tin tuyển dụng khỏi hệ thống.';
                $_SESSION['flash_type'] = 'success';
            }
        }
    }
    header('Location: jobs.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$employer_filter = isset($_GET['employer_id']) ? (int)$_GET['employer_id'] : 0;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Chuẩn bị danh sách bộ lọc
$categories_list = get_categories($pdo);
$employers_list = $pdo->query("
    SELECT u.id, u.name, ep.company_name
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    WHERE u.role = 'employer'
    ORDER BY COALESCE(ep.company_name, u.name) ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Xây dựng câu truy vấn
$sql_where = " WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql_where .= " AND j.status = :status";
    $params[':status'] = $status_filter;
}

if ($category_filter !== 'all' && !empty($category_filter)) {
    $sql_where .= " AND j.category = :category";
    $params[':category'] = $category_filter;
}

if ($employer_filter > 0) {
    $sql_where .= " AND j.employer_id = :employer_id";
    $params[':employer_id'] = $employer_filter;
}

if (!empty($search)) {
    $sql_where .= " AND (j.title LIKE :search OR j.company LIKE :search OR j.location LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// Đếm tổng số để phân trang
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j {$sql_where}");
$count_stmt->execute($params);
$total_jobs = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_jobs / $limit) ?: 1;

// Lấy danh sách tin
$sql = "
    SELECT j.*, u.name as employer_name, u.email as employer_email,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as app_count
    FROM jobs j
    LEFT JOIN users u ON u.id = j.employer_id
    {$sql_where}
    ORDER BY (j.status = 'pending') DESC, j.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 31, 23, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 520px;
            padding: 28px;
            box-shadow: var(--shadow-modal);
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title">Danh sách tin tuyển dụng</h1>
                <p class="section-desc">Tổng số <strong><?= number_format($total_jobs) ?></strong> tin đăng trong cơ sở dữ liệu.</p>
            </div>
            <a href="job-create.php" class="btn btn-primary">+ Tạo tin mới (Admin)</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- THANH TÌM KIẾM & BỘ LỌC ĐA NĂNG -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm theo tiêu đề, công ty..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="status" class="search-select">
                        <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Tất cả trạng thái</option>
                        <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Chờ kiểm duyệt</option>
                        <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Đang hiển thị</option>
                        <option value="rejected" <?= ($status_filter === 'rejected') ? 'selected' : '' ?>>Bị từ chối</option>
                        <option value="inactive" <?= ($status_filter === 'inactive') ? 'selected' : '' ?>>Tạm dừng</option>
                        <option value="draft" <?= ($status_filter === 'draft') ? 'selected' : '' ?>>Bản nháp</option>
                    </select>
                </div>

                <div class="search-field">
                    <select name="category" class="search-select">
                        <option value="all" <?= ($category_filter === 'all') ? 'selected' : '' ?>>Tất cả ngành nghề</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($category_filter === $cat['name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
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
                    <?php if ($status_filter !== 'all' || $category_filter !== 'all' || $employer_filter > 0 || !empty($search)): ?>
                        <a href="jobs.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- BẢNG DANH SÁCH TIN -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Tiêu đề & Vị trí</th>
                            <th>Doanh nghiệp</th>
                            <th>Địa điểm & Ngành</th>
                            <th>Mức lương</th>
                            <th>Lượt xem & CV</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Thao tác kiểm duyệt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $i => $j): ?>
                                <tr style="<?= ($j['status'] === 'pending') ? 'background:rgba(245, 158, 11, 0.04);' : '' ?>">
                                    <td class="text-muted" style="font-weight:700;"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <a href="../job-detail.php?id=<?= (int)$j['id'] ?>" target="_blank" style="color:var(--ink);">
                                                <?= htmlspecialchars($j['title']) ?> ↗
                                            </a>
                                            <?php if (!empty($j['is_featured'])): ?>
                                                <span class="badge badge--active" style="font-size:10px; padding:2px 6px;">Nổi bật</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            Đăng: <?= date('d/m/Y', strtotime($j['created_at'])) ?>
                                            <?php if (!empty($j['deadline'])): ?>
                                                &middot; Hạn: <?= date('d/m/Y', strtotime($j['deadline'])) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($j['reject_reason'])): ?>
                                            <div style="font-size:11px; color:#DC2626; margin-top:4px;">
                                                Lý do từ chối: <em><?= htmlspecialchars($j['reject_reason']) ?></em>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($j['company']) ?></div>
                                        <?php if (!empty($j['employer_email'])): ?>
                                            <div style="font-size:11px; color:var(--muted);"><?= htmlspecialchars($j['employer_email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= htmlspecialchars($j['location']) ?> &middot; <?= htmlspecialchars($j['category']) ?>
                                    </td>
                                    <td>
                                        <span class="chip" style="font-weight:700;"><?= htmlspecialchars($j['salary']) ?></span>
                                    </td>
                                    <td class="text-sm">
                                        <div>👁 <?= number_format($j['views']) ?> xem</div>
                                        <div style="font-weight:700; color:var(--ink);"><?= $j['app_count'] ?> hồ sơ</div>
                                    </td>
                                    <td>
                                        <?= get_job_status_badge($j['status']) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                                            <!-- DUYỆT NHANH NẾU ĐANG CHỜ -->
                                            <?php if ($j['status'] === 'pending' || $j['status'] === 'rejected' || $j['status'] === 'draft'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="padding:4px 10px; font-size:12px;">Duyệt tin</button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- TỪ CHỐI TIN -->
                                            <?php if ($j['status'] === 'pending' || $j['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-secondary btn-sm" style="padding:4px 10px; font-size:12px;" onclick="openRejectModal(<?= (int)$j['id'] ?>, '<?= htmlspecialchars(addslashes($j['title'])) ?>')">
                                                    Từ chối
                                                </button>
                                            <?php endif; ?>

                                            <!-- TẠM DỪNG / KHÔI PHỤC -->
                                            <?php if ($j['status'] === 'active'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="hide">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:12px;" title="Tạm dừng tin">Ẩn</button>
                                                </form>
                                            <?php elseif ($j['status'] === 'inactive'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:12px;" title="Kích hoạt lại">Mở lại</button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- ĐÁNH DẤU NỔI BẬT -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_featured">
                                                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:12px;" title="Đổi trạng thái nổi bật">
                                                    <?= (!empty($j['is_featured'])) ? '★ Bỏ sao' : '☆ Đánh sao' ?>
                                                </button>
                                            </form>

                                            <a href="job-edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:12px;">Sửa</a>

                                            <!-- XÓA TIN -->
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin tuyển dụng này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:12px;">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy tin tuyển dụng nào theo tiêu chí lọc.
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
                        Hiển thị trang <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong> (Tổng <?= $total_jobs ?> tin)
                    </div>
                    <div style="display:flex; gap:8px;">
                        <?php if ($page > 1): ?>
                            <a href="jobs.php?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&category=<?= urlencode($category_filter) ?>&employer_id=<?= $employer_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">← Trang trước</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="jobs.php?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&category=<?= urlencode($category_filter) ?>&employer_id=<?= $employer_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">Trang sau →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- MODAL TỪ CHỐI TIN KÈM LÝ DO -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-box">
        <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:8px;">Từ chối duyệt tin tuyển dụng</h3>
        <p style="font-size:13px; color:var(--muted); margin-bottom:16px;" id="rejectJobTitle"></p>
        
        <form method="POST" action="jobs.php">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="job_id" id="rejectJobId" value="">
            
            <div class="form-group">
                <label class="form-label" for="reject_reason">Lý do từ chối (Doanh nghiệp sẽ nhận được thông báo này) <span style="color:#DC2626;">*</span></label>
                <textarea id="reject_reason" name="reject_reason" class="form-control form-textarea" style="min-height:100px;" placeholder="Ví dụ: Thiếu thông tin liên hệ, mức lương không rõ ràng, mô tả công việc chưa đúng chuẩn quy định..." required></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Hủy bỏ</button>
                <button type="submit" class="btn btn-danger">Xác nhận từ chối tin</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, title) {
    document.getElementById('rejectJobId').value = id;
    document.getElementById('rejectJobTitle').innerText = 'Tin: ' + title;
    document.getElementById('rejectModal').classList.add('active');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
}
</script>

</body>
</html>

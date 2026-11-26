<?php
/**
 * ============================================================
 * FILE: employer/jobs.php
 * MÔ TẢ: Quản lý tin tuyển dụng theo trạng thái & quy trình JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Tin Tuyển Dụng';

// Xử lý POST (toggle status, delete, clone job)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $job_id = (int)($_POST['job_id'] ?? 0);

    if ($job_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
        $stmt->execute([$job_id, $employer_id]);
        $target_job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_job) {
            if ($action === 'toggle_status') {
                $new_status = ($target_job['status'] === 'active') ? 'inactive' : 'active';
                $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ? AND employer_id = ?")->execute([$new_status, $job_id, $employer_id]);
                log_activity($pdo, $employer_id, 'EMPLOYER_JOB_TOGGLE', "Đổi trạng thái tin ID #{$job_id} thành {$new_status}");
                $_SESSION['flash_message'] = 'Đã cập nhật trạng thái tin thành ' . ($new_status === 'active' ? 'Đang tuyển' : 'Tạm dừng') . '.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'clone') {
                // Sao chép tin tuyển dụng thành bản nháp mới
                $ins = $pdo->prepare("
                    INSERT INTO jobs (
                        employer_id, campaign_id, title, company, location, category,
                        salary, salary_value, min_salary, max_salary, salary_type,
                        job_type, quantity, level, experience, deadline,
                        description, requirements, benefits, education_level,
                        working_time, contact_name, contact_email, status, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, 'draft', NOW()
                    )
                ");
                $clone_title = '[Bản sao] ' . $target_job['title'];
                $new_deadline = date('Y-m-d', strtotime('+30 days'));

                $ins->execute([
                    $employer_id, $target_job['campaign_id'], $clone_title, $target_job['company'], $target_job['location'], $target_job['category'],
                    $target_job['salary'], $target_job['salary_value'], $target_job['min_salary'], $target_job['max_salary'], $target_job['salary_type'],
                    $target_job['job_type'], $target_job['quantity'], $target_job['level'], $target_job['experience'], $new_deadline,
                    $target_job['description'], $target_job['requirements'], $target_job['benefits'], $target_job['education_level'],
                    $target_job['working_time'], $target_job['contact_name'], $target_job['contact_email']
                ]);

                $cloned_id = $pdo->lastInsertId();
                log_activity($pdo, $employer_id, 'EMPLOYER_JOB_CLONE', "Sao chép tin #{$job_id} thành tin mới #{$cloned_id}");
                $_SESSION['flash_message'] = 'Đã sao chép tin tuyển dụng thành công dưới dạng Bản nháp. Bạn có thể chỉnh sửa và gửi duyệt.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'resubmit') {
                // Gửi duyệt lại tin
                $pdo->prepare("UPDATE jobs SET status = 'pending', reject_reason = NULL WHERE id = ? AND employer_id = ?")->execute([$job_id, $employer_id]);
                log_activity($pdo, $employer_id, 'EMPLOYER_JOB_RESUBMIT', "Gửi duyệt lại tin tuyển dụng #{$job_id}");
                $_SESSION['flash_message'] = 'Đã gửi yêu cầu kiểm duyệt lại tin tuyển dụng tới Ban Quản trị.';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'delete') {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
                $chk->execute([$job_id]);
                $has_apps = (int)$chk->fetchColumn();

                if ($has_apps > 0) {
                    // Nếu đã có hồ sơ, chuyển sang inactive để bảo toàn ứng viên
                    $pdo->prepare("UPDATE jobs SET status = 'inactive' WHERE id = ? AND employer_id = ?")->execute([$job_id, $employer_id]);
                    $_SESSION['flash_message'] = 'Tin đã có ' . $has_apps . ' hồ sơ nộp, hệ thống đã chuyển sang trạng thái "Tạm dừng" để bảo lưu dữ liệu.';
                    $_SESSION['flash_type'] = 'warning';
                } else {
                    $pdo->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?")->execute([$job_id, $employer_id]);
                    log_activity($pdo, $employer_id, 'EMPLOYER_JOB_DELETE', "Xóa tin tuyển dụng ID #{$job_id}");
                    $_SESSION['flash_message'] = 'Đã xóa tin tuyển dụng thành công.';
                    $_SESSION['flash_type'] = 'success';
                }
            }
        }
    }
    header('Location: jobs.php');
    exit;
}

$tab = $_GET['tab'] ?? 'all';

// Đếm số lượng theo từng Tab
$counts = [
    'all'      => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id}")->fetchColumn(),
    'active'   => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'active'")->fetchColumn(),
    'pending'  => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'pending'")->fetchColumn(),
    'draft'    => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'draft'")->fetchColumn(),
    'inactive' => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'inactive'")->fetchColumn(),
    'rejected' => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND status = 'rejected'")->fetchColumn(),
    'expired'  => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE employer_id = {$employer_id} AND (status = 'expired' OR (deadline IS NOT NULL AND deadline < CURDATE()))")->fetchColumn(),
];

$sql = "
    SELECT j.*, COUNT(a.id) as app_count,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status IN ('new', 'Đã nộp hồ sơ')) as new_app_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.employer_id = :employer_id
";
$params = [':employer_id' => $employer_id];

if ($tab === 'active') {
    $sql .= " AND j.status = 'active'";
} elseif ($tab === 'pending') {
    $sql .= " AND j.status = 'pending'";
} elseif ($tab === 'draft') {
    $sql .= " AND j.status = 'draft'";
} elseif ($tab === 'inactive') {
    $sql .= " AND j.status = 'inactive'";
} elseif ($tab === 'rejected') {
    $sql .= " AND j.status = 'rejected'";
} elseif ($tab === 'expired') {
    $sql .= " AND (j.status = 'expired' OR (j.deadline IS NOT NULL AND j.deadline < CURDATE()))";
}

$sql .= " GROUP BY j.id ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1 class="section-title">Danh sách tin tuyển dụng</h1>
                <p class="section-desc">Theo dõi tiến độ kiểm duyệt, số lượt quan tâm và hồ sơ ứng tuyển theo từng tin.</p>
            </div>
            <a href="job-create.php" class="btn btn-primary">+ Đăng tin tuyển dụng mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- TABS TRẠNG THÁI TIN TUYỂN DỤNG -->
        <div class="category-pills" style="margin-bottom:20px; flex-wrap:wrap;">
            <a href="jobs.php?tab=all" class="category-pill <?= ($tab === 'all') ? 'active' : '' ?>">
                Tất cả (<?= $counts['all'] ?>)
            </a>
            <a href="jobs.php?tab=active" class="category-pill <?= ($tab === 'active') ? 'active' : '' ?>">
                Đang hiển thị (<?= $counts['active'] ?>)
            </a>
            <a href="jobs.php?tab=pending" class="category-pill <?= ($tab === 'pending') ? 'active' : '' ?>" style="<?= ($counts['pending'] > 0) ? 'font-weight:700;' : '' ?>">
                Chờ duyệt (<?= $counts['pending'] ?>)
            </a>
            <a href="jobs.php?tab=draft" class="category-pill <?= ($tab === 'draft') ? 'active' : '' ?>">
                Bản nháp (<?= $counts['draft'] ?>)
            </a>
            <a href="jobs.php?tab=inactive" class="category-pill <?= ($tab === 'inactive') ? 'active' : '' ?>">
                Tạm dừng (<?= $counts['inactive'] ?>)
            </a>
            <a href="jobs.php?tab=rejected" class="category-pill <?= ($tab === 'rejected') ? 'active' : '' ?>" style="<?= ($counts['rejected'] > 0) ? 'color:#DC2626; border-color:#FECACA;' : '' ?>">
                Bị từ chối (<?= $counts['rejected'] ?>)
            </a>
            <a href="jobs.php?tab=expired" class="category-pill <?= ($tab === 'expired') ? 'active' : '' ?>">
                Hết hạn (<?= $counts['expired'] ?>)
            </a>
        </div>

        <!-- BẢNG DANH SÁCH TIN -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vị trí tuyển dụng</th>
                            <th>Mức lương & Ngành</th>
                            <th>Hạn ứng tuyển</th>
                            <th>Lượt xem & CV</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $j): ?>
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
                                        <div style="font-size:12px; color:var(--muted); margin-top:2px;">
                                            Đăng ngày: <?= date('d/m/Y', strtotime($j['created_at'])) ?> &middot; <?= htmlspecialchars($j['location']) ?>
                                        </div>
                                        <?php if (!empty($j['reject_reason']) && $j['status'] === 'rejected'): ?>
                                            <div style="font-size:11px; color:#DC2626; margin-top:4px; padding:4px 8px; background:#FEF2F2; border-radius:6px;">
                                                ⚠️ Lý do từ chối: <strong><?= htmlspecialchars($j['reject_reason']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="chip" style="font-weight:700;"><?= htmlspecialchars($j['salary']) ?></span>
                                        <div style="font-size:11px; color:var(--muted); margin-top:4px;"><?= htmlspecialchars($j['category']) ?></div>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= !empty($j['deadline']) ? date('d/m/Y', strtotime($j['deadline'])) : 'Không đặt' ?>
                                    </td>
                                    <td>
                                        <div style="font-size:12px; color:var(--muted);">👁 <?= number_format($j['views']) ?> xem</div>
                                        <div>
                                            <a href="applications.php?job_id=<?= (int)$j['id'] ?>" style="font-weight:800; color:#2563EB; text-decoration:underline;">
                                                <?= (int)$j['app_count'] ?> hồ sơ
                                                <?php if ((int)$j['new_app_count'] > 0): ?>
                                                    <span class="badge badge--active" style="font-size:10px; padding:1px 6px;">+<?= $j['new_app_count'] ?> mới</span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <?= get_job_status_badge($j['status']) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                                            <a href="applications.php?job_id=<?= (int)$j['id'] ?>" class="btn btn-secondary btn-sm" title="Quản lý hồ sơ ứng viên">
                                                Ứng viên
                                            </a>

                                            <a href="job-edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-ghost btn-sm">Sửa</a>

                                            <!-- SAO CHÉP TIN (CLONE) -->
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có muốn sao chép tin tuyển dụng này thành một bản nháp mới?');">
                                                <input type="hidden" name="action" value="clone">
                                                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" title="Sao chép thành tin nháp mới">Nhân bản</button>
                                            </form>

                                            <!-- GỬI DUYỆT LẠI NẾU BỊ TỪ CHỐI -->
                                            <?php if ($j['status'] === 'rejected' || $j['status'] === 'draft'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="resubmit">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="padding:4px 8px; font-size:12px;">Gửi duyệt</button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- TẠM DỪNG / MỞ LẠI -->
                                            <?php if ($j['status'] === 'active' || $j['status'] === 'inactive'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm">
                                                        <?= ($j['status'] === 'active') ? 'Tạm dừng' : 'Mở lại' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- XÓA -->
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy tin tuyển dụng nào theo danh mục đã chọn.
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

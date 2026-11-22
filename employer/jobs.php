<?php
/**
 * ============================================================
 * FILE: employer/jobs.php
 * MÔ TẢ: Quản lý danh sách tin tuyển dụng của công ty
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Tin Tuyển Dụng';

// Xử lý POST toggle status hoặc delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $job_id = (int)($_POST['job_id'] ?? 0);

    if ($job_id > 0) {
        if ($action === 'toggle_status') {
            $stmt = $pdo->prepare("SELECT status FROM jobs WHERE id = ? AND employer_id = ?");
            $stmt->execute([$job_id, $employer_id]);
            $current_status = $stmt->fetchColumn();

            if ($current_status) {
                $new_status = ($current_status === 'active') ? 'inactive' : 'active';
                $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ? AND employer_id = ?")->execute([$new_status, $job_id, $employer_id]);
                $_SESSION['flash_message'] = 'Đã cập nhật trạng thái tin đăng thành ' . ($new_status === 'active' ? 'Đang tuyển' : 'Tạm dừng') . '.';
                $_SESSION['flash_type'] = 'success';
            }
        } elseif ($action === 'delete') {
            // Kiểm tra xem có hồ sơ ứng tuyển nào không
            $chk = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
            $chk->execute([$job_id]);
            $has_apps = (int)$chk->fetchColumn();

            if ($has_apps > 0) {
                $_SESSION['flash_message'] = 'Không thể xóa tin tuyển dụng đã nhận ' . $has_apps . ' hồ sơ ứng tuyển. Bạn có thể chọn Tạm dừng tin thay vì xóa.';
                $_SESSION['flash_type'] = 'warning';
            } else {
                $del = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
                $del->execute([$job_id, $employer_id]);
                $_SESSION['flash_message'] = 'Đã xóa tin tuyển dụng thành công.';
                $_SESSION['flash_type'] = 'success';
            }
        }
    }
    header('Location: jobs.php');
    exit;
}

$tab = $_GET['tab'] ?? 'all';

$sql = "
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.employer_id = :employer_id
";
$params = [':employer_id' => $employer_id];

if ($tab === 'active') {
    $sql .= " AND j.status = 'active'";
} elseif ($tab === 'inactive') {
    $sql .= " AND j.status = 'inactive'";
} elseif ($tab === 'draft') {
    $sql .= " AND j.status = 'draft'";
}

$sql .= " GROUP BY j.id ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
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
                <span class="section-eyebrow">Quản lý nội dung</span>
                <h1 class="section-title">Danh sách tin tuyển dụng</h1>
            </div>
            <a href="job-create.php" class="btn btn-primary">+ Đăng tin tuyển dụng mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Tabs lọc trạng thái -->
        <div class="category-pills" style="margin-bottom:20px;">
            <a href="jobs.php?tab=all" class="category-pill <?= ($tab === 'all') ? 'active' : '' ?>">Tất cả</a>
            <a href="jobs.php?tab=active" class="category-pill <?= ($tab === 'active') ? 'active' : '' ?>">Đang tuyển</a>
            <a href="jobs.php?tab=inactive" class="category-pill <?= ($tab === 'inactive') ? 'active' : '' ?>">Tạm dừng</a>
            <a href="jobs.php?tab=draft" class="category-pill <?= ($tab === 'draft') ? 'active' : '' ?>">Bản nháp</a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vị trí tuyển dụng</th>
                            <th>Địa điểm & Ngành</th>
                            <th>Mức lương</th>
                            <th>Số hồ sơ</th>
                            <th>Trạng thái</th>
                            <th>Hạn nộp</th>
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
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">Đăng ngày: <?= date('d/m/Y', strtotime($j['created_at'])) ?></div>
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
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <a href="applications.php?job_id=<?= (int)$j['id'] ?>" class="btn btn-secondary btn-sm">Hồ sơ</a>
                                            <a href="job-edit.php?id=<?= (int)$j['id'] ?>" class="btn btn-ghost btn-sm">Sửa</a>
                                            
                                            <!-- Toggle status form -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm">
                                                    <?= ($j['status'] === 'active') ? 'Tạm dừng' : 'Mở lại' ?>
                                                </button>
                                            </form>

                                            <!-- Delete form -->
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
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy tin tuyển dụng nào theo bộ lọc đang chọn.
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

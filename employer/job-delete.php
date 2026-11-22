<?php
/**
 * ============================================================
 * FILE: employer/job-delete.php
 * MÔ TẢ: Xóa tin tuyển dụng với xác thực quyền sở hữu và kiểm tra ràng buộc
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$job_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

// Kiểm tra tin tuyển dụng thuộc về employer này
$stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$job_id, $employer_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['flash_message'] = 'Không tìm thấy tin tuyển dụng hoặc bạn không có quyền xóa.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: jobs.php');
    exit;
}

// Kiểm tra xem đã có ứng viên nộp hồ sơ vào tin này chưa
$chk = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
$chk->execute([$job_id]);
$has_apps = (int)$chk->fetchColumn();

// Xử lý xác nhận xóa qua POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($has_apps > 0) {
        // Nếu đã có hồ sơ, chuyển trạng thái sang inactive thay vì xóa vĩnh viễn để bảo toàn dữ liệu ứng tuyển
        $upd = $pdo->prepare("UPDATE jobs SET status = 'inactive' WHERE id = ? AND employer_id = ?");
        $upd->execute([$job_id, $employer_id]);

        $_SESSION['flash_message'] = 'Tin đã có ' . $has_apps . ' hồ sơ ứng tuyển, hệ thống đã tự động chuyển sang trạng thái "Tạm dừng" để bảo toàn dữ liệu.';
        $_SESSION['flash_type'] = 'warning';
    } else {
        $del = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
        $del->execute([$job_id, $employer_id]);

        $_SESSION['flash_message'] = 'Đã xóa tin tuyển dụng "' . htmlspecialchars($job['title']) . '" thành công.';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: jobs.php');
    exit;
}

// Nếu truy cập bằng GET -> Hiển thị màn hình xác nhận tối giản chuẩn Apple
$page_title = 'Xác nhận xóa tin tuyển dụng';
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
        <div style="max-width:560px; margin:40px auto;">
            <div class="form-card" style="text-align:center; padding:36px 32px;">
                <div style="width:56px; height:56px; border-radius:50%; background:var(--status-danger-bg); color:var(--status-danger-text); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:24px;">
                    ✕
                </div>

                <h1 style="font-size:22px; font-weight:800; color:var(--ink); margin-bottom:12px;">
                    Xác nhận xóa tin tuyển dụng?
                </h1>

                <p style="color:var(--text); font-size:15px; line-height:1.6; margin-bottom:24px;">
                    Bạn đang chuẩn bị xóa tin <strong>"<?= htmlspecialchars($job['title']) ?>"</strong>.
                    <?php if ($has_apps > 0): ?>
                        <br><br>
                        <span style="color:var(--status-danger-text); font-weight:600;">
                            Lưu ý: Tin này đã có <?= $has_apps ?> hồ sơ ứng tuyển. Thao tác này sẽ tự động chuyển tin về trạng thái "Tạm dừng" để bảo lưu dữ liệu ứng viên.
                        </span>
                    <?php else: ?>
                        <br>Thao tác này không thể hoàn tác sau khi thực hiện.
                    <?php endif; ?>
                </p>

                <form method="POST" action="job-delete.php?id=<?= $job_id ?>">
                    <div style="display:flex; justify-content:center; gap:12px;">
                        <a href="jobs.php" class="btn btn-secondary">Quay lại danh sách</a>
                        <button type="submit" class="btn btn-danger" style="padding:12px 24px;">
                            <?= ($has_apps > 0) ? 'Tạm dừng tin' : 'Xác nhận xóa vĩnh viễn' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>

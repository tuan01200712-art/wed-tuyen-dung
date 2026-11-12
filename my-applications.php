<?php

/**
 * ============================================================
 * FILE: my-applications.php
 * MÔ TẢ: Trang quản lý danh sách công việc mà người dùng đã ứng tuyển
 * - Chỉ cho phép người dùng đã đăng nhập truy cập
 * - Truy vấn kết nối giữa bảng `applications` và `jobs` qua INNER JOIN
 * - Hiển thị: Tên công việc, Công ty, Địa điểm, Mức lương, Ngày ứng tuyển,
 *   Trạng thái xét duyệt hồ sơ và nút xem lại chi tiết công việc.
 * ============================================================
 */

// Nạp kết nối cơ sở dữ liệu
require_once 'config/database.php';

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để xem danh sách công việc đã ứng tuyển.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode('my-applications.php'));
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Việc làm đã ứng tuyển';

// 2. Truy vấn danh sách ứng tuyển của người dùng hiện tại kèm thông tin công việc
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id as app_id,
            a.cover_letter,
            a.status as app_status,
            a.created_at as applied_at,
            j.id as job_id,
            j.title as job_title,
            j.company,
            j.location,
            j.salary,
            j.category,
            j.job_type
        FROM applications a
        INNER JOIN jobs j ON a.job_id = j.id
        WHERE a.user_id = :user_id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $applications = $stmt->fetchAll();
    $total_apps = count($applications);
} catch (PDOException $e) {
    $applications = [];
    $total_apps = 0;
}

// Nạp Header
require_once 'includes/header.php';
?>

<!-- Header trang -->
<div class="jobs-page-header">
    <div class="container">
        <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--dark-color); margin-bottom: 6px;">
            Lịch Sử Ứng Tuyển Của Bạn
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Bạn đã nộp hồ sơ vào <strong><?php echo $total_apps; ?></strong> vị trí việc làm.
        </p>
    </div>
</div>

<div class="container" style="padding-bottom: 50px;">
    <?php if (!empty($applications)): ?>
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Vị trí công việc & Công ty</th>
                            <th>Địa điểm & Ngành</th>
                            <th>Mức lương</th>
                            <th>Ngày ứng tuyển</th>
                            <th>Trạng thái hồ sơ</th>
                            <th style="text-align: right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $index => $app): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--text-muted);">
                                    <?php echo $index + 1; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;">
                                        <a href="job-detail.php?id=<?php echo (int)$app['job_id']; ?>">
                                            <?php echo htmlspecialchars($app['job_title']); ?>
                                        </a>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.88rem;">
                                        <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($app['company']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="margin-bottom: 4px;">
                                        <i class="fa-solid fa-location-dot" style="color: var(--primary-color);"></i>
                                        <?php echo htmlspecialchars($app['location']); ?>
                                    </div>
                                    <span class="tag tag-category" style="font-size: 0.75rem;">
                                        <?php echo htmlspecialchars($app['category']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: var(--danger-color);">
                                    <?php echo htmlspecialchars($app['salary']); ?>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($app['applied_at'])); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?php echo date('H:i', strtotime($app['applied_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status = $app['app_status'];
                                    $badge_class = 'badge-primary';
                                    if ($status === 'Đã duyệt' || $status === 'Trúng tuyển') {
                                        $badge_class = 'badge-success';
                                    } elseif ($status === 'Từ chối') {
                                        $badge_class = 'badge-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="fa-solid fa-clock-rotate-left"></i> <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="job-detail.php?id=<?php echo (int)$app['job_id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                        Xem tin <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-file-circle-question"></i>
            </div>
            <h3>Bạn chưa ứng tuyển công việc nào</h3>
            <p>Hãy dạo quanh danh sách việc làm để tìm vị trí phù hợp và gửi hồ sơ ngay nhé!</p>
            <a href="jobs.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-magnifying-glass"></i> Khám Phá Việc Làm Ngay
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
<?php

/**
 * ============================================================
 * FILE: job-detail.php
 * MÔ TẢ: Trang chi tiết thông tin công việc tuyển dụng
 * - Hiển thị: Tên công việc, Công ty, Địa điểm, Mức lương,
 *   Kinh nghiệm yêu cầu, Loại hình làm việc, Ngày đăng,
 *   Mô tả chi tiết và Yêu cầu ứng viên.
 * - Nút "Ứng tuyển ngay" (kiểm tra trạng thái đã ứng tuyển chưa)
 * ============================================================
 */

// Nạp kết nối cơ sở dữ liệu
require_once 'config/database.php';

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy id công việc từ URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

// Truy vấn thông tin chi tiết công việc từ MySQL bằng PDO Prepared Statement
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $job_id]);
$job = $stmt->fetch();

// Nếu không tìm thấy công việc
if (!$job) {
    $page_title = 'Không tìm thấy công việc';
    require_once 'includes/header.php';
    echo "
    <div class='container' style='padding: 60px 20px; text-align: center;'>
        <div class='empty-state'>
            <div class='empty-icon'><i class='fa-solid fa-triangle-exclamation'></i></div>
            <h3>Công việc không tồn tại hoặc đã hết hạn tuyển dụng</h3>
            <p>Vị trí tuyển dụng bạn đang tìm kiếm có thể đã được gỡ bỏ hoặc tạm dừng tuyển dụng.</p>
            <a href='jobs.php' class='btn btn-primary'><i class='fa-solid fa-arrow-left'></i> Quay Lại Danh Sách Việc Làm</a>
        </div>
    </div>";
    require_once 'includes/footer.php';
    exit;
}

$page_title = $job['title'] . ' - ' . $job['company'];

// Kiểm tra xem người dùng hiện tại đã ứng tuyển công việc này chưa
$already_applied = false;
if (isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = :user_id AND job_id = :job_id LIMIT 1");
    $check_stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':job_id'  => $job_id
    ]);
    if ($check_stmt->fetch()) {
        $already_applied = true;
    }
}

// Lấy 3 việc làm liên quan cùng ngành nghề (ngoại trừ công việc hiện tại)
try {
    $rel_stmt = $pdo->prepare("
        SELECT id, title, company, location, salary, job_type 
        FROM jobs 
        WHERE category = :category AND id != :current_id AND status = 'active' 
        LIMIT 3
    ");
    $rel_stmt->execute([
        ':category'   => $job['category'],
        ':current_id' => $job_id
    ]);
    $related_jobs = $rel_stmt->fetchAll();
} catch (PDOException $e) {
    $related_jobs = [];
}

// Nạp Header
require_once 'includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb điều hướng -->
    <nav style="padding: 20px 0 10px 0; font-size: 0.9rem; color: var(--text-muted);">
        <a href="index.php">Trang chủ</a> &nbsp;&gt;&nbsp;
        <a href="jobs.php">Việc làm</a> &nbsp;&gt;&nbsp;
        <a href="jobs.php?category=<?php echo urlencode($job['category']); ?>"><?php echo htmlspecialchars($job['category']); ?></a> &nbsp;&gt;&nbsp;
        <span><?php echo htmlspecialchars($job['title']); ?></span>
    </nav>

    <div class="job-detail-layout">
        <!-- Cột Trái: Nội Dung Chi Tiết Chính -->
        <div class="detail-main">
            <div class="detail-main-card">
                <!-- Tiêu đề & Công ty -->
                <div class="detail-header">
                    <h1 class="detail-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="detail-company">
                        <i class="fa-solid fa-building"></i>
                        <span><?php echo htmlspecialchars($job['company']); ?></span>
                    </div>

                    <!-- Thông số nhanh -->
                    <div class="detail-quick-info">
                        <div class="quick-info-item">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <div>
                                <div class="label">Mức lương</div>
                                <div class="value" style="color: var(--danger-color);"><?php echo htmlspecialchars($job['salary']); ?></div>
                            </div>
                        </div>

                        <div class="quick-info-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <div>
                                <div class="label">Địa điểm</div>
                                <div class="value"><?php echo htmlspecialchars($job['location']); ?></div>
                            </div>
                        </div>

                        <div class="quick-info-item">
                            <i class="fa-solid fa-briefcase"></i>
                            <div>
                                <div class="label">Kinh nghiệm</div>
                                <div class="value"><?php echo htmlspecialchars($job['experience']); ?></div>
                            </div>
                        </div>

                        <div class="quick-info-item">
                            <i class="fa-solid fa-clock"></i>
                            <div>
                                <div class="label">Hình thức</div>
                                <div class="value"><?php echo htmlspecialchars($job['job_type']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Ứng Tuyển Nhanh Ngay Đầu Bài -->
                    <div>
                        <?php if ($already_applied): ?>
                            <button class="btn btn-success btn-lg" disabled style="cursor: not-allowed; opacity: 0.85;">
                                <i class="fa-solid fa-circle-check"></i> Bạn đã ứng tuyển công việc này
                            </button>
                            <a href="my-applications.php" class="btn btn-outline btn-lg" style="margin-left: 10px;">
                                Xem hồ sơ ứng tuyển
                            </a>
                        <?php else: ?>
                            <a href="apply.php?job_id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-paper-plane"></i> Ứng Tuyển Ngay
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 1. Mô tả công việc -->
                <div class="detail-section">
                    <h3><i class="fa-solid fa-file-lines"></i> Mô Tả Công Việc</h3>
                    <div class="detail-body"><?php echo htmlspecialchars($job['description']); ?></div>
                </div>

                <!-- 2. Yêu cầu ứng viên -->
                <div class="detail-section">
                    <h3><i class="fa-solid fa-user-check"></i> Yêu Cầu Ứng Viên</h3>
                    <div class="detail-body"><?php echo htmlspecialchars($job['requirements']); ?></div>
                </div>

                <!-- 3. Nút Ứng Tuyển Cuối Bài -->
                <div style="padding-top: 25px; border-top: 1px solid var(--border-color); text-align: center;">
                    <?php if ($already_applied): ?>
                        <div class="alert alert-success" style="display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Hồ sơ của bạn đã được gửi tới nhà tuyển dụng.</span>
                        </div>
                    <?php else: ?>
                        <a href="apply.php?job_id=<?php echo (int)$job['id']; ?>" class="btn btn-primary btn-lg" style="min-width: 250px;">
                            <i class="fa-solid fa-paper-plane"></i> Ứng Tuyển Công Việc Này
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cột Phải: Sidebar Thông Tin Tổng Quan & Việc Làm Liên Quan -->
        <div class="detail-sidebar">
            <!-- Box Tóm tắt -->
            <div class="detail-sidebar-card">
                <h4 class="sidebar-title">Thông Tin Tuyển Dụng</h4>
                <ul class="sidebar-list">
                    <li>
                        <span class="text-muted">Ngành nghề</span>
                        <strong><?php echo htmlspecialchars($job['category']); ?></strong>
                    </li>
                    <li>
                        <span class="text-muted">Ngày đăng</span>
                        <span><?php echo date('d/m/Y', strtotime($job['created_at'])); ?></span>
                    </li>
                    <li>
                        <span class="text-muted">Trạng thái</span>
                        <span class="badge badge-success">Đang tuyển dụng</span>
                    </li>
                    <li>
                        <span class="text-muted">Hình thức làm việc</span>
                        <span><?php echo htmlspecialchars($job['job_type']); ?></span>
                    </li>
                </ul>
            </div>

            <!-- Box Việc làm tương tự -->
            <?php if (!empty($related_jobs)): ?>
                <div class="detail-sidebar-card">
                    <h4 class="sidebar-title">Việc Làm Cùng Ngành</h4>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($related_jobs as $rel): ?>
                            <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f3f5;">
                                <h5 style="font-size: 0.95rem; margin-bottom: 4px;">
                                    <a href="job-detail.php?id=<?php echo (int)$rel['id']; ?>">
                                        <?php echo htmlspecialchars($rel['title']); ?>
                                    </a>
                                </h5>
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($rel['company']); ?> &bull; <?php echo htmlspecialchars($rel['location']); ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--danger-color); font-weight: 600;">
                                    <?php echo htmlspecialchars($rel['salary']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <a href="jobs.php" class="btn btn-outline btn-block">
                <i class="fa-solid fa-list-ul"></i> Xem Tất Cả Việc Làm
            </a>
        </div>
    </div>
</div>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
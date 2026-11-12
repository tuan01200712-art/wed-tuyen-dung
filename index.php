<?php

/**
 * ============================================================
 * FILE: index.php
 * MÔ TẢ: Trang chủ website tuyển dụng JobFinder
 * - Banner giới thiệu & Thanh tìm kiếm việc làm nhanh
 * - Danh sách việc làm nổi bật (mới nhất từ MySQL)
 * - Danh sách các ngành nghề phổ biến (kèm số lượng công việc)
 * - Giới thiệu các tiện ích của JobFinder
 * ============================================================
 */

// Nạp kết nối cơ sở dữ liệu
require_once 'config/database.php';

$page_title = 'Trang chủ - Cổng Việc Làm Hàng Đầu';

// 1. Lấy danh sách việc làm nổi bật (6 công việc mới nhất có status = active)
try {
    $featured_stmt = $pdo->query("
        SELECT id, title, company, location, category, salary, job_type, created_at 
        FROM jobs 
        WHERE status = 'active' 
        ORDER BY created_at DESC 
        LIMIT 6
    ");
    $featured_jobs = $featured_stmt->fetchAll();
} catch (PDOException $e) {
    $featured_jobs = [];
}

// 2. Lấy thống kê số lượng công việc theo ngành nghề phổ biến
try {
    $cat_stmt = $pdo->query("
        SELECT category, COUNT(id) as total_jobs 
        FROM jobs 
        WHERE status = 'active' 
        GROUP BY category 
        ORDER BY total_jobs DESC 
        LIMIT 8
    ");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 3. Lấy danh sách địa điểm duy nhất để đưa vào dropdown tìm kiếm
try {
    $loc_stmt = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'active' ORDER BY location ASC");
    $locations = $loc_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations = [];
}

// Mảng icon đại diện cho từng ngành nghề
$category_icons = [
    'Công nghệ thông tin' => 'fa-laptop-code',
    'Marketing'           => 'fa-bullhorn',
    'Thiết kế đồ họa'     => 'fa-palette',
    'Tài chính - Kế toán' => 'fa-calculator',
    'Nhân sự'             => 'fa-people-roof',
    'Kinh doanh - Bán hàng' => 'fa-chart-line',
    'Ngân hàng - Dữ liệu' => 'fa-database',
    'Giáo dục - Đào tạo'  => 'fa-graduation-cap',
    'Dịch vụ khách hàng'  => 'fa-headset',
];

// Nạp Header
require_once 'includes/header.php';
?>

<!-- 1. Hero Section & Banner Tìm Kiếm -->
<section class="hero-section">
    <div class="container hero-content">
        <div class="hero-badge">
            <i class="fa-solid fa-fire"></i> Hơn 1,000+ cơ hội việc làm đang chờ đón bạn
        </div>
        <h1 class="hero-title">Khám Phá Công Việc Mơ Ước<br>Phát Triển Sự Nghiệp Cùng JobFinder</h1>
        <p class="hero-subtitle">Cầu nối vững chắc giữa ứng viên tài năng và hàng ngàn doanh nghiệp hàng đầu tại Việt Nam</p>

        <!-- Khung tìm kiếm việc làm nhanh -->
        <div class="search-box-card">
            <form action="jobs.php" method="GET" class="hero-search-form">
                <!-- Từ khóa -->
                <div class="search-input-group">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="keyword" placeholder="Tên công việc, vị trí, công ty...">
                </div>

                <!-- Địa điểm -->
                <div class="search-input-group">
                    <i class="fa-solid fa-location-dot"></i>
                    <select name="location">
                        <option value="">Tất cả địa điểm</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ngành nghề -->
                <div class="search-input-group">
                    <i class="fa-solid fa-layer-group"></i>
                    <select name="category">
                        <option value="">Tất cả ngành nghề</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nút tìm kiếm -->
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm Việc Ngay
                </button>
            </form>
        </div>
    </div>
</section>

<!-- 2. Danh Mục Ngành Nghề Phổ Biến -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Ngành Nghề Phổ Biến</h2>
                <p class="section-subtitle">Lựa chọn ngành nghề phù hợp với năng lực và định hướng tương lai của bạn</p>
            </div>
            <a href="jobs.php" class="btn btn-outline">
                Xem tất cả việc làm <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $cat_name = $cat['category'];
                    $icon = $category_icons[$cat_name] ?? 'fa-briefcase';
                    ?>
                    <a href="jobs.php?category=<?php echo urlencode($cat_name); ?>" class="category-card">
                        <div class="category-icon">
                            <i class="fa-solid <?php echo $icon; ?>"></i>
                        </div>
                        <div class="category-info">
                            <h4><?php echo htmlspecialchars($cat_name); ?></h4>
                            <p><?php echo (int)$cat['total_jobs']; ?> vị trí đang tuyển</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Chưa có dữ liệu ngành nghề.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 3. Danh Sách Việc Làm Nổi Bật -->
<section class="section" style="background-color: #f1f5f9;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Việc Làm Nổi Bật Mới Nhất</h2>
                <p class="section-subtitle">Cơ hội việc làm chất lượng cao từ các nhà tuyển dụng uy tín</p>
            </div>
            <a href="jobs.php" class="btn btn-primary">
                Xem toàn bộ việc làm <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="jobs-grid">
            <?php if (!empty($featured_jobs)): ?>
                <?php foreach ($featured_jobs as $job): ?>
                    <div class="job-card">
                        <div>
                            <div class="job-header">
                                <div class="company-logo-placeholder">
                                    <i class="fa-solid fa-building"></i>
                                </div>
                                <div class="job-title-group">
                                    <h3>
                                        <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h3>
                                    <span class="job-company"><?php echo htmlspecialchars($job['company']); ?></span>
                                </div>
                            </div>

                            <div class="job-tags">
                                <span class="tag tag-location">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($job['location']); ?>
                                </span>
                                <span class="tag tag-type">
                                    <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($job['job_type']); ?>
                                </span>
                                <span class="tag tag-category">
                                    <i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($job['category']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="job-footer">
                            <div class="job-salary">
                                <i class="fa-solid fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary']); ?>
                            </div>
                            <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-outline">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h3>Chưa có công việc nào</h3>
                    <p>Hệ thống hiện tại chưa có tin tuyển dụng nào được kích hoạt.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. Giới Thiệu Ngắn Về Website JobFinder -->
<section class="section about-section">
    <div class="container">
        <div class="section-header" style="justify-content: center; text-align: center;">
            <div>
                <h2 class="section-title">Tại Sao Nên Lựa Chọn JobFinder?</h2>
                <p class="section-subtitle">Nền tảng hỗ trợ sinh viên và người đi làm kết nối doanh nghiệp nhanh chóng và hiệu quả</p>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fa-solid fa-magnifying-glass-location"></i>
                </div>
                <h4>Tìm Kiếm Đa Chiều</h4>
                <p>Dễ dàng lọc công việc theo chức danh, địa điểm, mức lương và ngành nghề chỉ với vài cú nhấp chuột.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <h4>Ứng Tuyển Nhanh Chóng</h4>
                <p>Nộp hồ sơ trực tuyến thuận tiện kèm thư giới thiệu bản thân trực tiếp đến nhà tuyển dụng.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h4>Thông Tin Xác Thực</h4>
                <p>Các tin đăng tuyển đều được kiểm duyệt kỹ càng, đảm bảo môi trường làm việc minh bạch và an toàn.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h4>Theo Dõi Hồ Sơ</h4>
                <p>Quản lý danh sách các công việc đã nộp và trạng thái xét duyệt hồ sơ dễ dàng trong trang cá nhân.</p>
            </div>
        </div>
    </div>
</section>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
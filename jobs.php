<?php

/**
 * ============================================================
 * FILE: jobs.php
 * MÔ TẢ: Trang danh sách việc làm với đầy đủ bộ lọc và tìm kiếm
 * - Tìm kiếm theo tên công việc hoặc tên công ty
 * - Lọc theo địa điểm (Hà Nội, Hồ Chí Minh, Đà Nẵng, Cần Thơ...)
 * - Lọc theo ngành nghề (Công nghệ thông tin, Marketing,...)
 * - Hiển thị dạng thẻ (Card) chuẩn giao diện hiện đại
 * ============================================================
 */

// Nạp kết nối cơ sở dữ liệu
require_once 'config/database.php';

$page_title = 'Danh Sách Việc Làm Tuyển Dụng';

// Lấy tham số tìm kiếm từ GET
$keyword  = trim($_GET['keyword'] ?? '');
$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? '');

// Xây dựng câu truy vấn SQL động với Prepared Statements
$sql = "SELECT * FROM jobs WHERE status = 'active'";
$params = [];

// Điều kiện lọc theo từ khóa (tên công việc HOẶC công ty)
if (!empty($keyword)) {
    $sql .= " AND (title LIKE :keyword OR company LIKE :keyword)";
    $params[':keyword'] = "%{$keyword}%";
}

// Điều kiện lọc theo địa điểm
if (!empty($location)) {
    $sql .= " AND location = :location";
    $params[':location'] = $location;
}

// Điều kiện lọc theo ngành nghề
if (!empty($category)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

// Sắp xếp bài đăng mới nhất lên đầu
$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    $total_results = count($jobs);
} catch (PDOException $e) {
    $jobs = [];
    $total_results = 0;
}

// Lấy danh sách địa điểm phục vụ dropdown bộ lọc
try {
    $loc_stmt = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'active' ORDER BY location ASC");
    $locations_list = $loc_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations_list = [];
}

// Lấy danh sách ngành nghề phục vụ dropdown bộ lọc
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM jobs WHERE status = 'active' ORDER BY category ASC");
    $categories_list = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories_list = [];
}

// Nạp Header
require_once 'includes/header.php';
?>

<!-- Header tiêu đề trang Việc làm -->
<div class="jobs-page-header">
    <div class="container">
        <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--dark-color); margin-bottom: 6px;">
            Cơ Hội Việc Làm Dành Cho Bạn
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Tìm thấy <strong><?php echo $total_results; ?></strong> vị trí tuyển dụng phù hợp với yêu cầu của bạn.
        </p>
    </div>
</div>

<div class="container">
    <!-- Thanh Bộ Lọc & Tìm Kiếm -->
    <div class="filter-bar">
        <form action="jobs.php" method="GET" class="filter-form">
            <!-- Ô tìm kiếm tên công việc / công ty -->
            <div class="search-input-group">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    name="keyword"
                    placeholder="Tên công việc hoặc công ty..."
                    value="<?php echo htmlspecialchars($keyword); ?>">
            </div>

            <!-- Bộ lọc Địa điểm -->
            <div class="search-input-group">
                <i class="fa-solid fa-location-dot"></i>
                <select name="location">
                    <option value="">Tất cả địa điểm</option>
                    <?php foreach ($locations_list as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location === $loc) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Bộ lọc Ngành nghề -->
            <div class="search-input-group">
                <i class="fa-solid fa-layer-group"></i>
                <select name="category">
                    <option value="">Tất cả ngành nghề</option>
                    <?php foreach ($categories_list as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nút lọc -->
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Lọc Việc
            </button>

            <!-- Nút xóa bộ lọc -->
            <?php if (!empty($keyword) || !empty($location) || !empty($category)): ?>
                <a href="jobs.php" class="btn btn-light" title="Xóa bộ lọc">
                    <i class="fa-solid fa-rotate-left"></i> Đặt lại
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tóm tắt trạng thái lọc hiện tại -->
    <?php if (!empty($keyword) || !empty($location) || !empty($category)): ?>
        <div class="filter-summary">
            <div>
                <span>Đang lọc theo: </span>
                <?php if (!empty($keyword)): ?>
                    <span class="badge badge-primary">Từ khóa: "<?php echo htmlspecialchars($keyword); ?>"</span>
                <?php endif; ?>
                <?php if (!empty($location)): ?>
                    <span class="badge badge-primary">Địa điểm: <?php echo htmlspecialchars($location); ?></span>
                <?php endif; ?>
                <?php if (!empty($category)): ?>
                    <span class="badge badge-primary">Ngành nghề: <?php echo htmlspecialchars($category); ?></span>
                <?php endif; ?>
            </div>
            <a href="jobs.php" style="color: var(--danger-color); font-size: 0.85rem;">
                <i class="fa-solid fa-xmark"></i> Xóa tất cả bộ lọc
            </a>
        </div>
    <?php endif; ?>

    <!-- Danh sách công việc (Dạng thẻ Grid) -->
    <?php if (!empty($jobs)): ?>
        <div class="jobs-grid">
            <?php foreach ($jobs as $job): ?>
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
                            Xem chi tiết <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Hiển thị khi không tìm thấy kết quả -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </div>
            <h3>Không tìm thấy công việc phù hợp</h3>
            <p>Rất tiếc, hiện tại không có tin tuyển dụng nào khớp với tiêu chí tìm kiếm của bạn.</p>
            <a href="jobs.php" class="btn btn-primary">
                <i class="fa-solid fa-list-ul"></i> Xem Tất Cả Việc Làm
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
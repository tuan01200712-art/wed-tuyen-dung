<?php
/**
 * ============================================================
 * FILE: jobs.php
 * MÔ TẢ: Danh sách việc làm tuyển dụng — Chuẩn Apple Design
 * ============================================================
 */

require_once 'config/database.php';

$page_title = 'Khám Phá Việc Làm';

$keyword  = trim($_GET['keyword'] ?? '');
$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT * FROM jobs WHERE status = 'active'";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (title LIKE :keyword OR company LIKE :keyword OR description LIKE :keyword)";
    $params[':keyword'] = "%{$keyword}%";
}

if (!empty($location)) {
    $sql .= " AND location = :location";
    $params[':location'] = $location;
}

if (!empty($category)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

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

try {
    $loc_stmt = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'active' ORDER BY location ASC");
    $locations_list = $loc_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations_list = [];
}

try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM jobs WHERE status = 'active' ORDER BY category ASC");
    $categories_list = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories_list = [];
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:48px;">
    <div class="container">
        <!-- Tiêu đề trang -->
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Khám phá cơ hội</span>
                <h1 class="section-title">Danh sách việc làm</h1>
                <p class="section-desc">
                    Hiện có <strong><?php echo $total_results; ?></strong> vị trí tuyển dụng phù hợp với tiêu chí của bạn.
                </p>
            </div>
        </div>

        <!-- Khung tìm kiếm & Bộ lọc -->
        <div class="smart-search-card" style="margin-bottom: 28px;">
            <form action="jobs.php" method="GET" class="search-form-row" style="grid-template-columns: 1.5fr 1fr 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="keyword" class="search-input" placeholder="Tên công việc, kỹ năng, công ty..." value="<?php echo htmlspecialchars($keyword); ?>">
                </div>

                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <select name="location" class="search-select">
                        <option value="">Tất cả địa điểm</option>
                        <?php foreach ($locations_list as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location === $loc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    <select name="category" class="search-select">
                        <option value="">Tất cả ngành nghề</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc việc làm</button>
                    <?php if (!empty($keyword) || !empty($location) || !empty($category)): ?>
                        <a href="jobs.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Category Pills nhanh -->
        <div class="category-pills" style="margin-bottom:28px;">
            <a href="jobs.php" class="category-pill <?php echo (empty($category)) ? 'active' : ''; ?>">Tất cả</a>
            <?php foreach ($categories_list as $cat): ?>
                <a href="jobs.php?category=<?php echo urlencode($cat); ?><?php echo !empty($keyword) ? '&keyword='.urlencode($keyword) : ''; ?><?php echo !empty($location) ? '&location='.urlencode($location) : ''; ?>" 
                   class="category-pill <?php echo ($category === $cat) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Lưới danh sách công việc -->
        <div class="job-grid">
            <?php if (!empty($jobs)): ?>
                <?php foreach ($jobs as $job): 
                    $company_initial = mb_strtoupper(mb_substr($job['company'], 0, 2));
                ?>
                    <div class="job-card">
                        <div>
                            <div class="job-card__header">
                                <div class="job-card__avatar">
                                    <?php echo htmlspecialchars($company_initial); ?>
                                </div>
                                <div class="job-card__info">
                                    <h3 class="job-card__title">
                                        <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="job-card__company"><?php echo htmlspecialchars($job['company']); ?></div>
                                </div>
                            </div>

                            <div class="job-card__chips">
                                <span class="chip"><?php echo htmlspecialchars($job['location']); ?></span>
                                <span class="chip"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                <span class="chip"><?php echo htmlspecialchars($job['experience']); ?></span>
                                <span class="chip"><?php echo htmlspecialchars($job['category']); ?></span>
                            </div>
                        </div>

                        <div class="job-card__footer">
                            <div class="job-card__salary">
                                <?php echo htmlspecialchars($job['salary']); ?>
                            </div>
                            <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-secondary btn-sm">
                                Chi tiết →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; padding: 64px 20px; background: var(--surface); border-radius: var(--radius-xl); text-align: center; border: 1px solid var(--line);">
                    <h3 style="font-size:20px; font-weight:700; margin-bottom:8px;">Không tìm thấy kết quả phù hợp</h3>
                    <p class="text-muted" style="margin-bottom:20px;">Vui lòng thử tìm kiếm với từ khóa khác hoặc bỏ các bộ lọc đang chọn.</p>
                    <a href="jobs.php" class="btn btn-secondary btn-sm">Xem tất cả công việc</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

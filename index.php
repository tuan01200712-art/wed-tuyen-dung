<?php
/**
 * ============================================================
 * FILE: index.php
 * MÔ TẢ: Trang chủ JobFinder — Chuẩn thẩm mỹ Apple Design & Web 2026
 * ============================================================
 */

require_once 'config/database.php';

$page_title = 'Khám Phá Cơ Hội Nghề Nghiệp';

// 1. Lấy danh sách việc làm mới nhất
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

// 2. Lấy thống kê số lượng công việc theo ngành nghề
try {
    $cat_stmt = $pdo->query("
        SELECT category, COUNT(id) as total_jobs 
        FROM jobs 
        WHERE status = 'active' 
        GROUP BY category 
        ORDER BY total_jobs DESC 
        LIMIT 6
    ");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 3. Lấy danh sách địa điểm phục vụ bộ lọc tìm kiếm
try {
    $loc_stmt = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'active' ORDER BY location ASC");
    $locations = $loc_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations = [];
}

require_once 'includes/header.php';
?>

<!-- 1. HERO SECTION (CHUẨN APPLE TECH 2026) -->
<section class="hero-clean">
    <div class="container hero-grid">
        <div class="hero-content">
            <div class="hero-pill">
                <span class="hero-pill-badge">2026</span>
                Cổng tuyển dụng thông minh &middot; Minh bạch & Trực tiếp
            </div>
            
            <h1 class="hero-heading">
                Tìm việc chuẩn xác.<br>
                Ứng tuyển <span class="mark">tức thì</span>.
            </h1>
            
            <p class="hero-subheading">
                Nền tảng kết nối ứng viên chất lượng với doanh nghiệp hàng đầu tại Việt Nam. Không thủ tục rườm rà, tập trung vào năng lực và thư giới thiệu thực chất.
            </p>

            <!-- Khung tìm kiếm thông minh tích hợp -->
            <div class="smart-search-card">
                <form action="jobs.php" method="GET" class="search-form-row">
                    <div class="search-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="keyword" class="search-input" placeholder="Vị trí, kỹ năng hoặc công ty...">
                    </div>

                    <div class="search-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <select name="location" class="search-select">
                            <option value="">Tất cả địa điểm</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Tìm việc →
                    </button>
                </form>
            </div>
        </div>

        <!-- Khung xem trước trực quan (Live Preview Frame) -->
        <div class="hero-preview">
            <div class="preview-window">
                <div class="window-header">
                    <div class="window-dots">
                        <span class="window-dot" style="background:#ff5f56;"></span>
                        <span class="window-dot" style="background:#ffbd2e;"></span>
                        <span class="window-dot" style="background:#27c93f;"></span>
                    </div>
                    <span class="window-badge">Hồ sơ ứng tuyển trực tiếp</span>
                </div>

                <!-- Thẻ việc làm thực tế -->
                <div class="preview-job-item">
                    <div class="preview-job-top">
                        <div class="preview-avatar">FS</div>
                        <div>
                            <div class="preview-title">Senior PHP / Backend Engineer</div>
                            <div class="preview-company">FPT Software &middot; Cầu Giấy, Hà Nội</div>
                        </div>
                    </div>
                    <div class="preview-meta">
                        <span class="preview-salary">20 - 35 triệu</span>
                        <span class="chip">Toàn thời gian</span>
                        <span class="chip">2 - 3 năm</span>
                    </div>
                </div>

                <!-- Thẻ theo dõi ứng viên -->
                <div class="preview-applicant-chip">
                    <div class="applicant-left">
                        <span class="status-dot"></span>
                        <div>
                            <div style="font-size:13px; font-weight:700; color:var(--ink);">Nguyễn Văn An &middot; Đã nộp CV</div>
                            <div style="font-size:11px; color:var(--muted);">Kèm Thư giới thiệu &middot; Cập nhật 10 phút trước</div>
                        </div>
                    </div>
                    <span class="badge badge--active" style="font-size:11px;">Đang xét duyệt</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. BENTO GRID (HIỆN ĐẠI, KHÔNG TRÙNG LẶP RẬP KHUÔN) -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-header__left">
                <span class="section-eyebrow">Hệ thống tối ưu</span>
                <h2 class="section-title">Thiết kế cho hiệu suất ứng tuyển và tuyển dụng.</h2>
                <p class="section-desc">Mọi tính năng được xây dựng nhằm loại bỏ sự phức tạp, giúp bạn đạt được kết quả nhanh nhất.</p>
            </div>
        </div>

        <div class="bento-grid">
            <!-- Bento 1: Large (7 cols) -->
            <div class="bento-card bento-card--large">
                <div>
                    <span class="bento-tag">Trình tạo hồ sơ</span>
                    <h3 class="bento-title">Tạo CV chuyên nghiệp trong 5 phút.</h3>
                    <p class="bento-desc">Soạn thảo học vấn, kinh nghiệm và kỹ năng với chế độ xem trước trực tiếp. Tự động lưu và đính kèm ngay khi ứng tuyển.</p>
                </div>
                <div class="bento-visual" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:13px; font-weight:700;">Hồ sơ Fullstack Developer</div>
                        <div style="font-size:11px; color:var(--muted);">Đã hoàn thiện 100% &middot; Sẵn sàng ứng tuyển</div>
                    </div>
                    <a href="cv-builder.php" class="btn btn-sm btn-dark">Tạo CV ngay →</a>
                </div>
            </div>

            <!-- Bento 2: Medium (5 cols) -->
            <div class="bento-card bento-card--medium">
                <div>
                    <span class="bento-tag">Minh bạch</span>
                    <h3 class="bento-title">Thông tin rõ ràng, không giấu lương.</h3>
                    <p class="bento-desc">Mọi vị trí tuyển dụng đều công khai khoảng lương, chế độ làm việc và địa điểm cụ thể.</p>
                </div>
                <div class="bento-visual">
                    <div class="bento-metric">100%</div>
                    <div class="bento-metric-label">Tin tuyển dụng có công khai mức lương thực tế</div>
                </div>
            </div>

            <!-- Bento 3: Medium (5 cols) -->
            <div class="bento-card bento-card--medium">
                <div>
                    <span class="bento-tag">Tiến độ hồ sơ</span>
                    <h3 class="bento-title">Nắm rõ trạng thái xét duyệt.</h3>
                    <p class="bento-desc">Theo dõi thời gian thực: Đã nộp, Đang xét duyệt, Phù hợp hoặc Từ chối ngay trong trang cá nhân.</p>
                </div>
                <div class="bento-visual" style="display:flex; gap:8px;">
                    <span class="badge badge--pending">Đang xét</span>
                    <span class="badge badge--success">Phù hợp ✓</span>
                    <span class="badge badge--danger">Từ chối</span>
                </div>
            </div>

            <!-- Bento 4: Large (7 cols) -->
            <div class="bento-card bento-card--large">
                <div>
                    <span class="bento-tag">Dành cho Doanh nghiệp</span>
                    <h3 class="bento-title">Cổng tuyển dụng doanh nghiệp độc lập.</h3>
                    <p class="bento-desc">Đăng tin tuyển dụng nhanh, quản lý danh sách hồ sơ ứng viên và cập nhật trạng thái tuyển dụng trong một màn hình duy nhất.</p>
                </div>
                <div class="bento-visual" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:13px; font-weight:700;">Tiếp cận ứng viên trẻ tiềm năng</div>
                        <div style="font-size:11px; color:var(--muted);">Dành riêng cho HR & Quản lý tuyển dụng</div>
                    </div>
                    <a href="employer/dashboard.php" class="btn btn-sm btn-primary">Kênh Doanh nghiệp →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. VIỆC LÀM MỚI NHẤT -->
<section class="section section--sm">
    <div class="container">
        <div class="section-header">
            <div>
                <span class="section-eyebrow">Cơ hội việc làm</span>
                <h2 class="section-title">Vị trí mới cập nhật</h2>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">Xem tất cả việc làm →</a>
        </div>

        <!-- Lọc nhanh theo ngành nghề dạng Pills -->
        <div class="category-pills">
            <a href="jobs.php" class="category-pill active">Tất cả ngành nghề</a>
            <?php foreach ($categories as $cat): ?>
                <a href="jobs.php?category=<?php echo urlencode($cat['category']); ?>" class="category-pill">
                    <?php echo htmlspecialchars($cat['category']); ?> (<?php echo (int)$cat['total_jobs']; ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Lưới thẻ việc làm -->
        <div class="job-grid">
            <?php if (!empty($featured_jobs)): ?>
                <?php foreach ($featured_jobs as $job): 
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
                <div style="grid-column: 1 / -1; padding: 48px; background: var(--surface); border-radius: var(--radius-lg); text-align: center; border: 1px solid var(--line);">
                    <p class="text-muted">Hiện chưa có tin tuyển dụng nào được đăng tải.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. CALL TO ACTION (TỐI GIẢN APPLE) -->
<section class="section" style="padding-top:20px;">
    <div class="container">
        <div style="background:var(--ink); color:var(--surface); border-radius:var(--radius-xl); padding:56px 40px; text-align:center; max-width:960px; margin:0 auto;">
            <h2 style="color:var(--surface); font-size:clamp(28px, 3.5vw, 42px); font-weight:800; letter-spacing:-0.03em; margin-bottom:12px;">
                Sẵn sàng phát triển sự nghiệp của bạn?
            </h2>
            <p style="color:rgba(255,255,255,0.75); font-size:16px; max-width:540px; margin:0 auto 32px; line-height:1.5;">
                Khám phá hàng trăm cơ hội việc làm thực tế hoặc bắt đầu tuyển dụng nhân tài cùng JobFinder ngay hôm nay.
            </p>
            <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
                <a href="jobs.php" class="btn btn-primary btn-lg">Khám phá việc làm →</a>
                <a href="employer/job-create.php" class="btn btn-secondary btn-lg" style="background:transparent; color:#ffffff; border-color:rgba(255,255,255,0.3);">
                    Đăng tin tuyển dụng
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

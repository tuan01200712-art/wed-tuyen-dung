<?php
/**
 * FILE: index.php
 * Trang chủ JobFinder — Chuẩn web tuyển dụng thực tế, 100% Tiếng Việt
 */

require_once 'config/database.php';
$page_title = 'Tìm Việc Làm & Tuyển Dụng Uy Tín';

// 1. Việc làm mới nhất / nổi bật (9 tin)
try {
    $featured_stmt = $pdo->query("
        SELECT j.id, j.title, j.company, ep.company_logo, j.location, j.category, j.salary, j.job_type, j.is_featured, j.created_at
        FROM jobs j
        LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
        LEFT JOIN categories cat ON j.category = cat.name
        WHERE j.status IN ('active', 'open', 'approved')
        ORDER BY j.is_featured DESC, j.created_at DESC
        LIMIT 9
    ");
    $featured_jobs = $featured_stmt->fetchAll();
} catch (PDOException $e) {
    $featured_jobs = [];
}

// 2. Danh sách tin đã lưu
$saved_job_ids = [];
if (isset($_SESSION['user_id'])) {
    try {
        $s_stmt = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
        $s_stmt->execute([$_SESSION['user_id']]);
        $saved_job_ids = $s_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// 3. 12 ngành nghề từ CSDL kèm số lượng tin thực tế
$categories_data = get_categories($pdo);
$cat_counts = [];
try {
    $cat_stmt = $pdo->query("SELECT category, COUNT(id) as total_jobs FROM jobs WHERE status IN ('active', 'open', 'approved') GROUP BY category");
    foreach ($cat_stmt->fetchAll() as $row) {
        $cat_counts[$row['category']] = (int)$row['total_jobs'];
    }
} catch (PDOException $e) {}

// 4. Tỉnh/Thành phố
$city_options = ['TP.HCM', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai', 'Cần Thơ', 'Hải Phòng', 'Làm việc từ xa'];

// 5. Doanh nghiệp nổi bật
try {
    $emp_stmt = $pdo->query("
        SELECT ep.company_name, ep.company_logo, ep.industry, ep.address, ep.is_verified,
               (SELECT COUNT(*) FROM jobs WHERE employer_id = ep.user_id AND status = 'active') as active_jobs
        FROM employer_profiles ep
        WHERE ep.is_verified = 1
        ORDER BY active_jobs DESC, ep.id ASC
        LIMIT 6
    ");
    $top_employers = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $top_employers = [];
}

require_once 'includes/header.php';
?>

<!-- 1. THANH TÌM KIẾM CHÍNH -->
<section class="hero-search-section">
    <div class="container">
        <div class="hero-search-header">
            <h1 class="hero-search-title">Tìm việc làm phù hợp với năng lực của bạn</h1>
            <p class="hero-search-sub">Kết nối trực tiếp với các doanh nghiệp hàng đầu tại Việt Nam. Minh bạch mức lương và quy trình tuyển dụng.</p>
        </div>

        <div class="hero-search-bar">
            <form action="jobs.php" method="GET" class="hero-search-form">
                <div class="search-field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="homeKeyword" name="keyword" class="search-input" placeholder="Vị trí tuyển dụng, kỹ năng hoặc công ty..." autocomplete="off">
                </div>

                <div class="search-field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <select name="location" class="search-select">
                        <option value="">Tất cả địa điểm</option>
                        <?php foreach ($city_options as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    <select name="category" class="search-select">
                        <option value="">Tất cả ngành nghề</option>
                        <?php foreach ($categories_data as $cat_row): ?>
                            <option value="<?php echo htmlspecialchars($cat_row['name']); ?>"><?php echo htmlspecialchars($cat_row['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="height:44px; padding:0 24px;">Tìm việc</button>
            </form>
        </div>
    </div>
</section>

<!-- 2. VIỆC LÀM TỐT NHẤT / MỚI NHẤT -->
<section class="section section--sm">
    <div class="container">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h2 class="section-title" style="font-size:24px;">Việc làm tốt nhất & Mới nhất</h2>
                <p class="section-desc" style="font-size:14px; margin-top:4px;">Các vị trí đang mở tuyển trực tiếp từ doanh nghiệp.</p>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">Xem tất cả →</a>
        </div>

        <div class="job-grid">
            <?php if (!empty($featured_jobs)): ?>
                <?php foreach ($featured_jobs as $job):
                    $is_saved = in_array($job['id'], $saved_job_ids);
                ?>
                    <div class="job-card">
                        <div>
                            <div class="job-card__header">
                                <?php echo render_company_logo($job['company'], $job['company_logo'] ?? '', '', 44); ?>
                                <div class="job-card__info">
                                    <h3 class="job-card__title" title="<?php echo htmlspecialchars($job['title']); ?>">
                                        <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="job-card__company"><?php echo htmlspecialchars($job['company']); ?></div>
                                </div>
                                <button type="button"
                                        class="job-save-btn <?php echo $is_saved ? 'saved' : ''; ?>"
                                        data-job-id="<?php echo (int)$job['id']; ?>"
                                        onclick="handleHomeSave(this, <?php echo (int)$job['id']; ?>)"
                                        title="Lưu tin tuyển dụng">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="<?php echo $is_saved ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="job-card__chips" style="margin-top:14px;">
                                <span class="chip chip--brand mono"><?php echo htmlspecialchars($job['salary']); ?></span>
                                <span class="chip"><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                        </div>

                        <div class="job-card__footer">
                            <span style="font-size:12px; color:var(--muted);"><?php echo htmlspecialchars($job['job_type']); ?></span>
                            <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-secondary btn-xs">Ứng tuyển →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column:1/-1; padding:48px; background:var(--surface); border-radius:var(--radius-lg); text-align:center; border:1px solid var(--line);">
                    <p class="text-muted">Hiện chưa có tin tuyển dụng nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 3. DANH MỤC NGÀNH NGHỀ PHỔ BIẾN -->
<section class="section section--sm" style="background:#FFFFFF; border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
    <div class="container">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h2 class="section-title" style="font-size:24px;">Ngành nghề phổ biến</h2>
                <p class="section-desc" style="font-size:14px; margin-top:4px;">Khám phá cơ hội việc làm theo lĩnh vực chuyên môn.</p>
            </div>
        </div>

        <div class="categories-grid">
            <?php foreach ($categories_data as $cat_row):
                $cat_name = $cat_row['name'];
            ?>
                <a href="jobs.php?category=<?php echo urlencode($cat_name); ?>" class="category-card">
                    <span class="category-card__name"><?php echo htmlspecialchars($cat_name); ?></span>
                    <span class="category-card__count"><?php echo (int)($cat_counts[$cat_name] ?? 0); ?> việc</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4. DOANH NGHIỆP NỔI BẬT -->
<?php if (!empty($top_employers)): ?>
<section class="section section--sm">
    <div class="container">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h2 class="section-title" style="font-size:24px;">Doanh nghiệp nổi bật</h2>
                <p class="section-desc" style="font-size:14px; margin-top:4px;">Các công ty đã xác thực đang tuyển dụng trên JobFinder.</p>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">Tất cả tin tuyển dụng →</a>
        </div>

        <div class="job-grid">
            <?php foreach ($top_employers as $emp): ?>
                <a href="jobs.php?keyword=<?php echo urlencode($emp['company_name']); ?>" class="job-card" style="gap:12px;">
                    <div class="job-card__header">
                        <?php echo render_company_logo($emp['company_name'], $emp['company_logo'] ?? '', '', 44); ?>
                        <div class="job-card__info">
                            <div class="job-card__title"><?php echo htmlspecialchars($emp['company_name']); ?></div>
                            <div class="job-card__company"><?php echo htmlspecialchars($emp['industry'] ?? 'Doanh nghiệp'); ?></div>
                        </div>
                    </div>
                    <div class="job-card__footer">
                        <span style="font-size:12px; color:var(--brand); font-weight:600;">Đã xác thực</span>
                        <span class="mono" style="font-size:12px; font-weight:700; color:var(--ink);"><?php echo (int)($emp['active_jobs'] ?? 0); ?> việc làm →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jfInitAutocomplete === 'function') {
        jfInitAutocomplete(document.getElementById('homeKeyword'), [
            'Lập trình viên Backend PHP / Laravel',
            'Lập trình viên Frontend ReactJS',
            'Kỹ sư DevOps / Cloud',
            'Chuyên viên Thiết kế UI/UX',
            'Chuyên viên Marketing số',
            'Nhân viên Kinh doanh B2B',
            'Chuyên viên Kế toán tổng hợp',
            'Chuyên viên Tuyển dụng nhân sự',
            'Nhân viên Điều phối Logistics',
            'Dược sĩ Lâm sàng',
            'Giảng viên Tiếng Anh',
            'Kỹ sư Xây dựng',
            'Chuyên viên Pháp chế doanh nghiệp',
            'FPT Software', 'VNG Corporation', 'MoMo', 'Shopee Việt Nam', 'Techcombank', 'Vinamilk'
        ]);
    }
});

function handleHomeSave(btn, jobId) {
    <?php if (isset($_SESSION['user_id'])): ?>
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action', 'ajax_toggle');
    fd.append('job_id', jobId);
    fetch('saved-jobs.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.status === 'success') {
                const svg = btn.querySelector('svg');
                btn.classList.toggle('saved', !!data.saved);
                if (svg) svg.setAttribute('fill', data.saved ? 'currentColor' : 'none');
                if (typeof updateSavedBadge === 'function' && typeof data.saved_count !== 'undefined') {
                    updateSavedBadge(data.saved_count);
                }
                if (window.showToast) window.showToast(data.message, 'success');
            }
        }).catch(() => { btn.disabled = false; });
    <?php else: ?>
    if (window.showToast) window.showToast('Vui lòng đăng nhập để lưu việc làm.', 'warning');
    setTimeout(function() { window.location.href = 'login.php?redirect=' + encodeURIComponent('job-detail.php?id=' + jobId); }, 700);
    <?php endif; ?>
}
</script>

<?php require_once 'includes/footer.php'; ?>

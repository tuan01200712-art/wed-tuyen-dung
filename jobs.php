<?php
/**
 * FILE: jobs.php
 * Danh sách việc làm — Bộ lọc 2 cột, logo công ty, gợi ý chức danh
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Tìm Kiếm Việc Làm';

$keyword      = trim($_GET['keyword'] ?? '');
$location     = trim($_GET['location'] ?? '');
$category     = trim($_GET['category'] ?? '');
$experience   = trim($_GET['experience'] ?? '');
$job_type     = trim($_GET['job_type'] ?? '');
$salary_range = trim($_GET['salary_range'] ?? '');
$sort         = trim($_GET['sort'] ?? 'newest');
$page         = max(1, (int)($_GET['page'] ?? 1));
$per_page     = 8;
$offset       = ($page - 1) * $per_page;

$where = " WHERE status = 'active'";
$params = [];

if (!empty($keyword)) {
    $where .= " AND (title LIKE :keyword OR company LIKE :keyword OR description LIKE :keyword)";
    $params[':keyword'] = "%{$keyword}%";
}
if (!empty($location)) {
    $where .= " AND location LIKE :location";
    $params[':location'] = "%{$location}%";
}
if (!empty($category)) {
    $where .= " AND category = :category";
    $params[':category'] = $category;
}
if (!empty($experience)) {
    $where .= " AND experience = :experience";
    $params[':experience'] = $experience;
}
if (!empty($job_type)) {
    $where .= " AND job_type = :job_type";
    $params[':job_type'] = $job_type;
}
if (!empty($salary_range)) {
    if ($salary_range === 'under_10') {
        $where .= " AND salary_value > 0 AND salary_value < 10";
    } elseif ($salary_range === '10_20') {
        $where .= " AND salary_value >= 10 AND salary_value <= 20";
    } elseif ($salary_range === '20_30') {
        $where .= " AND salary_value > 20 AND salary_value <= 30";
    } elseif ($salary_range === 'over_30') {
        $where .= " AND salary_value > 30";
    } elseif ($salary_range === 'negotiable') {
        $where .= " AND (salary LIKE '%Thỏa thuận%' OR salary_value = 0 OR salary_value IS NULL)";
    }
}

$order_by = " ORDER BY is_featured DESC, created_at DESC";
if ($sort === 'salary_desc') {
    $order_by = " ORDER BY salary_value DESC, created_at DESC";
} elseif ($sort === 'views') {
    $order_by = " ORDER BY views DESC, created_at DESC";
} elseif ($sort === 'newest') {
    $order_by = " ORDER BY created_at DESC";
}

try {
    $cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs" . $where);
    $cnt_stmt->execute($params);
    $total_results = (int)$cnt_stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total_results / $per_page));
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }

    $sql = "SELECT * FROM jobs" . $where . $order_by . " LIMIT {$per_page} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    $jobs = [];
    $total_results = 0;
    $total_pages = 1;
}

$saved_job_ids = [];
if (isset($_SESSION['user_id'])) {
    try {
        $s_stmt = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
        $s_stmt->execute([$_SESSION['user_id']]);
        $saved_job_ids = $s_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

$categories_list = [];
foreach (get_categories($pdo) as $c) {
    $categories_list[] = $c['name'];
}

$locations_list   = ['TP.HCM', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai', 'Cần Thơ', 'Hải Phòng', 'Làm việc từ xa'];
$experiences_list = ['Không yêu cầu', 'Dưới 1 năm', '1 - 2 năm', '2 - 3 năm', '3 - 5 năm', 'Trên 5 năm'];
$job_types_list   = ['Toàn thời gian', 'Bán thời gian', 'Thực tập', 'Làm việc từ xa', 'Linh hoạt'];

function build_jobs_url($overrides = []) {
    $q = array_merge($_GET, $overrides);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) unset($q[$k]);
    }
    return 'jobs.php' . (!empty($q) ? '?' . http_build_query($q) : '');
}

require_once 'includes/header.php';
?>

<section class="section section--sm" style="padding-top:32px;">
    <div class="container">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title" style="font-size:26px;">Tìm kiếm việc làm</h1>
                <p class="section-desc" style="font-size:14px; margin-top:4px;">
                    Hiển thị <strong class="mono"><?php echo number_format($total_results); ?></strong> việc làm phù hợp
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <label for="sortSelect" style="font-size:13px; font-weight:600; color:var(--muted);">Sắp xếp:</label>
                <select id="sortSelect" class="form-control form-select" style="width:auto; padding:8px 34px 8px 12px; font-size:13px;" onchange="location.href=this.value;">
                    <option value="<?php echo htmlspecialchars(build_jobs_url(['sort' => 'newest', 'page' => 1])); ?>" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="<?php echo htmlspecialchars(build_jobs_url(['sort' => 'salary_desc', 'page' => 1])); ?>" <?php echo ($sort === 'salary_desc') ? 'selected' : ''; ?>>Lương cao nhất</option>
                    <option value="<?php echo htmlspecialchars(build_jobs_url(['sort' => 'views', 'page' => 1])); ?>" <?php echo ($sort === 'views') ? 'selected' : ''; ?>>Xem nhiều nhất</option>
                </select>
            </div>
        </div>

        <div class="jobs-layout">
            <aside class="jobs-sidebar">
                <div class="jobs-sidebar-title">
                    <span>Bộ lọc tìm kiếm</span>
                    <?php if ($keyword || $location || $category || $salary_range || $experience || $job_type): ?>
                        <a href="jobs.php" style="font-size:12px; font-weight:600; color:var(--brand);">Đặt lại</a>
                    <?php endif; ?>
                </div>

                <form action="jobs.php" method="GET">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">

                    <div class="form-group">
                        <label class="form-label" for="fKeyword">Từ khóa</label>
                        <input type="text" id="fKeyword" name="keyword" class="form-control" placeholder="Vị trí, công ty..." value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fCategory">Ngành nghề</label>
                        <select id="fCategory" name="category" class="form-control form-select">
                            <option value="">Tất cả ngành nghề</option>
                            <?php foreach ($categories_list as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fLocation">Địa điểm</label>
                        <select id="fLocation" name="location" class="form-control form-select">
                            <option value="">Tất cả địa điểm</option>
                            <?php foreach ($locations_list as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location === $loc) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fSalary">Mức lương</label>
                        <select id="fSalary" name="salary_range" class="form-control form-select">
                            <option value="">Tất cả mức lương</option>
                            <option value="under_10" <?php echo ($salary_range === 'under_10') ? 'selected' : ''; ?>>Dưới 10 triệu</option>
                            <option value="10_20" <?php echo ($salary_range === '10_20') ? 'selected' : ''; ?>>10 – 20 triệu</option>
                            <option value="20_30" <?php echo ($salary_range === '20_30') ? 'selected' : ''; ?>>20 – 30 triệu</option>
                            <option value="over_30" <?php echo ($salary_range === 'over_30') ? 'selected' : ''; ?>>Trên 30 triệu</option>
                            <option value="negotiable" <?php echo ($salary_range === 'negotiable') ? 'selected' : ''; ?>>Thỏa thuận</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fExp">Kinh nghiệm</label>
                        <select id="fExp" name="experience" class="form-control form-select">
                            <option value="">Tất cả kinh nghiệm</option>
                            <?php foreach ($experiences_list as $exp): ?>
                                <option value="<?php echo htmlspecialchars($exp); ?>" <?php echo ($experience === $exp) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exp); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fType">Hình thức làm việc</label>
                        <select id="fType" name="job_type" class="form-control form-select">
                            <option value="">Tất cả hình thức</option>
                            <?php foreach ($job_types_list as $jt): ?>
                                <option value="<?php echo htmlspecialchars($jt); ?>" <?php echo ($job_type === $jt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">Áp dụng bộ lọc</button>
                </form>
            </aside>

            <div>
                <div class="jobs-list-grid">
                    <?php if (!empty($jobs)): ?>
                        <?php foreach ($jobs as $job):
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
                                                onclick="toggleCardSave(this, <?php echo (int)$job['id']; ?>)"
                                                title="<?php echo $is_saved ? 'Bỏ lưu' : 'Lưu tin'; ?>">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="<?php echo $is_saved ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="job-card__chips" style="margin-top:14px;">
                                        <span class="chip chip--brand mono"><?php echo htmlspecialchars($job['salary']); ?></span>
                                        <span class="chip"><?php echo htmlspecialchars($job['location']); ?></span>
                                        <span class="chip"><?php echo htmlspecialchars($job['experience']); ?></span>
                                    </div>
                                </div>

                                <div class="job-card__footer">
                                    <span style="font-size:12px; color:var(--muted);"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                    <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-secondary btn-xs">Chi tiết →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column:1/-1; padding:56px 24px; background:var(--surface); border-radius:var(--radius-lg); text-align:center; border:1px solid var(--line);">
                            <h3 style="font-size:18px; margin-bottom:8px;">Không tìm thấy việc làm phù hợp</h3>
                            <p class="text-muted" style="margin-bottom:18px; font-size:14px;">Thử thay đổi từ khóa hoặc xóa bớt tiêu chí lọc.</p>
                            <a href="jobs.php" class="btn btn-secondary btn-sm">Xem tất cả việc làm</a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-bar">
                        <a href="<?php echo htmlspecialchars(build_jobs_url(['page' => max(1, $page - 1)])); ?>" class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">← Trước</a>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <a href="<?php echo htmlspecialchars(build_jobs_url(['page' => $p])); ?>" class="page-btn <?php echo ($p === $page) ? 'active' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                        <a href="<?php echo htmlspecialchars(build_jobs_url(['page' => min($total_pages, $page + 1)])); ?>" class="page-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">Sau →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jfInitAutocomplete === 'function') {
        jfInitAutocomplete(document.getElementById('fKeyword'), [
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
            'Chuyên viên Pháp chế doanh nghiệp'
        ]);
    }
});

function toggleCardSave(btn, jobId) {
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
    setTimeout(function() { window.location.href = 'login.php?redirect=' + encodeURIComponent('jobs.php'); }, 700);
    <?php endif; ?>
}
</script>

<?php require_once 'includes/footer.php'; ?>

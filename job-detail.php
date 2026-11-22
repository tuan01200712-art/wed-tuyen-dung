<?php
/**
 * ============================================================
 * FILE: job-detail.php
 * MÔ TẢ: Chi tiết việc làm — Chuẩn thiết kế Apple Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

// Cập nhật lượt xem
try {
    $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?")->execute([$job_id]);
} catch (Exception $e) {}

// Truy vấn thông tin chi tiết
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $job_id]);
$job = $stmt->fetch();

if (!$job) {
    $page_title = 'Không tìm thấy công việc';
    require_once 'includes/header.php';
    echo "
    <div class='container' style='padding: 80px 20px; text-align: center;'>
        <div style='background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-xl); padding:48px 24px; max-width:540px; margin:0 auto;'>
            <h2 style='font-size:22px; font-weight:700; margin-bottom:12px;'>Vị trí không tồn tại hoặc đã hết hạn</h2>
            <p style='color:var(--muted); margin-bottom:24px;'>Tin tuyển dụng có thể đã được gỡ bỏ hoặc hoàn tất quá trình nhận hồ sơ.</p>
            <a href='jobs.php' class='btn btn-primary'>← Khám phá việc làm khác</a>
        </div>
    </div>";
    require_once 'includes/footer.php';
    exit;
}

$page_title = $job['title'] . ' — ' . $job['company'];

// Kiểm tra trạng thái ứng tuyển của user hiện tại
$already_applied = false;
$user_cvs = [];
if (isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = :user_id AND job_id = :job_id LIMIT 1");
    $check_stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':job_id'  => $job_id
    ]);
    if ($check_stmt->fetch()) {
        $already_applied = true;
    }

    $cv_stmt = $pdo->prepare("SELECT id, title, is_default, cv_link, updated_at FROM cvs WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $cv_stmt->execute([$_SESSION['user_id']]);
    $user_cvs = $cv_stmt->fetchAll();
}

// Lấy 3 việc làm cùng ngành nghề
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

$company_initial = mb_strtoupper(mb_substr($job['company'], 0, 2));

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container">
        <!-- Breadcrumb navigation -->
        <div style="font-size:13px; font-weight:600; color:var(--muted); margin-bottom:24px; display:flex; align-items:center; gap:8px;">
            <a href="index.php" style="color:var(--muted);">Trang chủ</a>
            <span>/</span>
            <a href="jobs.php" style="color:var(--muted);">Việc làm</a>
            <span>/</span>
            <span style="color:var(--ink);"><?php echo htmlspecialchars($job['title']); ?></span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 360px; gap:36px; align-items:flex-start;">
            <!-- Cột trái: Nội dung chi tiết công việc -->
            <div>
                <!-- Header công việc -->
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:36px; margin-bottom:28px;">
                    <div style="display:flex; align-items:center; gap:18px; margin-bottom:20px;">
                        <div class="job-card__avatar" style="width:60px; height:60px; font-size:20px; border-radius:var(--radius-md);">
                            <?php echo htmlspecialchars($company_initial); ?>
                        </div>
                        <div>
                            <h1 style="font-size:clamp(22px, 2.5vw, 32px); font-weight:800; letter-spacing:-0.03em; color:var(--ink); line-height:1.2;">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </h1>
                            <div style="font-size:15px; font-weight:600; color:var(--muted); margin-top:4px;">
                                <?php echo htmlspecialchars($job['company']); ?> &middot; <?php echo htmlspecialchars($job['location']); ?>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px; padding-top:16px; border-top:1px solid var(--line-subtle);">
                        <span class="chip" style="background-color:var(--status-success-bg); color:var(--status-success-text); font-weight:700;">
                            <?php echo htmlspecialchars($job['salary']); ?>
                        </span>
                        <span class="chip"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        <span class="chip"><?php echo htmlspecialchars($job['experience']); ?></span>
                        <span class="chip"><?php echo htmlspecialchars($job['category']); ?></span>
                        <?php if (!empty($job['deadline'])): ?>
                            <span class="chip">Hạn nộp: <?php echo date('d/m/Y', strtotime($job['deadline'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Thẻ mô tả công việc -->
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:36px; margin-bottom:28px;">
                    <h2 style="font-size:18px; font-weight:700; margin-bottom:16px; color:var(--ink);">Mô tả công việc</h2>
                    <div style="color:var(--ink-secondary); line-height:1.7; font-size:15px; white-space:pre-line;">
                        <?php echo htmlspecialchars($job['description']); ?>
                    </div>
                </div>

                <!-- Thẻ yêu cầu ứng viên -->
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:36px; margin-bottom:28px;">
                    <h2 style="font-size:18px; font-weight:700; margin-bottom:16px; color:var(--ink);">Yêu cầu ứng viên</h2>
                    <div style="color:var(--ink-secondary); line-height:1.7; font-size:15px; white-space:pre-line;">
                        <?php echo htmlspecialchars($job['requirements']); ?>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Khung thao tác & Tóm tắt -->
            <div style="position:sticky; top:92px;">
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:28px; box-shadow:var(--shadow-card); margin-bottom:24px;">
                    <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--muted); margin-bottom:6px;">
                        Mức lương dự kiến
                    </div>
                    <div style="font-size:26px; font-weight:800; color:var(--ink); letter-spacing:-0.03em; margin-bottom:20px;">
                        <?php echo htmlspecialchars($job['salary']); ?>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:24px; font-size:13px; color:var(--ink-secondary);">
                        <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--line-subtle);">
                            <span class="text-muted">Địa điểm</span>
                            <strong><?php echo htmlspecialchars($job['location']); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--line-subtle);">
                            <span class="text-muted">Kinh nghiệm</span>
                            <strong><?php echo htmlspecialchars($job['experience']); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--line-subtle);">
                            <span class="text-muted">Hình thức</span>
                            <strong><?php echo htmlspecialchars($job['job_type']); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--line-subtle);">
                            <span class="text-muted">Lượt xem</span>
                            <strong><?php echo (int)($job['views'] ?? 0); ?> lượt</strong>
                        </div>
                    </div>

                    <?php if ($already_applied): ?>
                        <div style="background:#f0fbf2; border:1px solid #b7f2cb; border-radius:var(--radius-md); padding:16px; text-align:center; margin-bottom:12px;">
                            <div style="color:var(--status-success-text); font-weight:700; font-size:14px; margin-bottom:4px;">
                                Đã nộp hồ sơ thành công
                            </div>
                            <div style="font-size:12px; color:var(--muted);">
                                Theo dõi trạng thái tại <a href="my-applications.php" style="color:var(--ink); text-decoration:underline;">Hồ sơ của tôi</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button type="button" class="btn btn-primary" onclick="openApplyModal()" style="width:100%; padding:14px;">
                                Ứng tuyển trực tiếp →
                            </button>
                            <div style="text-align:center; margin-top:10px;">
                                <a href="apply.php?job_id=<?php echo (int)$job['id']; ?>" class="text-xs text-muted" style="text-decoration:underline;">
                                    Hoặc mở trang ứng tuyển riêng
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode('job-detail.php?id=' . $job['id']); ?>" class="btn btn-primary" style="width:100%; padding:14px;">
                                Đăng nhập để ứng tuyển →
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Thẻ bảo đảm của JobFinder -->
                <div style="background:var(--surface-subtle); border-radius:var(--radius-lg); border:1px solid var(--line-subtle); padding:20px; font-size:13px; color:var(--muted); line-height:1.5;">
                    <div style="font-weight:700; color:var(--ink); margin-bottom:4px;">Quy trình minh bạch</div>
                    Hồ sơ của bạn được gửi trực tiếp đến bộ phận tuyển dụng của <?php echo htmlspecialchars($job['company']); ?> kèm thông báo cập nhật trạng thái thời gian thực.
                </div>
            </div>
        </div>

        <!-- Việc làm cùng ngành nghề -->
        <?php if (!empty($related_jobs)): ?>
            <div style="margin-top:64px; padding-top:48px; border-top:1px solid var(--line);">
                <div class="section-header" style="margin-bottom:24px;">
                    <div>
                        <span class="section-eyebrow">Khám phá thêm</span>
                        <h2 style="font-size:22px; font-weight:700;">Việc làm cùng ngành <?php echo htmlspecialchars($job['category']); ?></h2>
                    </div>
                </div>

                <div class="job-grid">
                    <?php foreach ($related_jobs as $r_job): 
                        $r_initial = mb_strtoupper(mb_substr($r_job['company'], 0, 2));
                    ?>
                        <div class="job-card">
                            <div>
                                <div class="job-card__header">
                                    <div class="job-card__avatar">
                                        <?php echo htmlspecialchars($r_initial); ?>
                                    </div>
                                    <div class="job-card__info">
                                        <h3 class="job-card__title">
                                            <a href="job-detail.php?id=<?php echo (int)$r_job['id']; ?>">
                                                <?php echo htmlspecialchars($r_job['title']); ?>
                                            </a>
                                        </h3>
                                        <div class="job-card__company"><?php echo htmlspecialchars($r_job['company']); ?></div>
                                    </div>
                                </div>

                                <div class="job-card__chips">
                                    <span class="chip"><?php echo htmlspecialchars($r_job['location']); ?></span>
                                    <span class="chip"><?php echo htmlspecialchars($r_job['job_type']); ?></span>
                                </div>
                            </div>

                            <div class="job-card__footer">
                                <div class="job-card__salary">
                                    <?php echo htmlspecialchars($r_job['salary']); ?>
                                </div>
                                <a href="job-detail.php?id=<?php echo (int)$r_job['id']; ?>" class="btn btn-secondary btn-sm">
                                    Chi tiết →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (isset($_SESSION['user_id']) && !$already_applied): ?>
<!-- Modal Ứng Tuyển Trực Tiếp Tại Trang Chi Tiết -->
<div id="quickApplyModal" style="display:none; position:fixed; inset:0; z-index:2000; background:rgba(11,31,23,0.5); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); max-width:620px; width:100%; max-height:90vh; overflow-y:auto; padding:36px; box-shadow:var(--shadow-popover); position:relative;">
        <button type="button" onclick="closeApplyModal()" style="position:absolute; top:24px; right:24px; background:none; border:none; cursor:pointer; font-size:20px; color:var(--muted); line-height:1;">✕</button>
        
        <span class="section-eyebrow">Nộp hồ sơ trực tiếp</span>
        <h2 style="font-size:20px; font-weight:800; margin-bottom:4px; color:var(--ink);">
            <?php echo htmlspecialchars($job['title']); ?>
        </h2>
        <p style="font-size:13px; color:var(--muted); margin-bottom:20px;">
            Nhà tuyển dụng: <strong><?php echo htmlspecialchars($job['company']); ?></strong> &middot; <?php echo htmlspecialchars($job['salary']); ?>
        </p>

        <form action="apply.php" method="POST">
            <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">

            <div class="form-group">
                <label class="form-label">Chọn hồ sơ CV đính kèm</label>
                <?php if (empty($user_cvs)): ?>
                    <div style="padding:14px; background:var(--surface-subtle); border:1px dashed var(--line); border-radius:var(--radius-md); font-size:12px; color:var(--muted); margin-bottom:12px;">
                        Bạn chưa có CV trên hệ thống. <a href="cv-builder.php" target="_blank" style="color:var(--ink); font-weight:700; text-decoration:underline;">Tạo CV ngay ↗</a>
                    </div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                        <?php foreach ($user_cvs as $idx => $ucv): ?>
                            <label style="display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid var(--line); border-radius:var(--radius-sm); cursor:pointer;">
                                <input type="radio" name="cv_id" value="<?php echo (int)$ucv['id']; ?>" <?php echo ($idx === 0) ? 'checked' : ''; ?>>
                                <div style="flex:1;">
                                    <span style="font-size:13px; font-weight:700;"><?php echo htmlspecialchars($ucv['title']); ?></span>
                                    <?php if ($ucv['is_default']): ?>
                                        <span class="badge badge--active" style="font-size:10px; margin-left:6px;">Mặc định</span>
                                    <?php endif; ?>
                                </div>
                                <a href="cv-preview.php?id=<?php echo (int)$ucv['id']; ?>" target="_blank" style="font-size:12px; color:var(--muted); text-decoration:underline;" onclick="event.stopPropagation()">Xem ↗</a>
                            </label>
                        <?php endforeach; ?>
                        <label style="display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid var(--line); border-radius:var(--radius-sm); cursor:pointer;">
                            <input type="radio" name="cv_id" value="">
                            <span style="font-size:13px; color:var(--muted);">Không đính kèm CV (Chỉ gửi thư giới thiệu)</span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="cover_letter_quick">Thư giới thiệu bản thân <span style="color:var(--status-danger-text);">*</span></label>
                <textarea id="cover_letter_quick" name="cover_letter" class="form-control form-textarea" style="min-height:120px;" placeholder="Tóm tắt lý do bạn phù hợp với công việc này..." required></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                <button type="button" onclick="closeApplyModal()" class="btn btn-secondary">Đóng</button>
                <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Gửi hồ sơ ngay →</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModal() {
    const modal = document.getElementById('quickApplyModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}
function closeApplyModal() {
    const modal = document.getElementById('quickApplyModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}
window.addEventListener('click', function(e) {
    const modal = document.getElementById('quickApplyModal');
    if (e.target === modal) closeApplyModal();
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

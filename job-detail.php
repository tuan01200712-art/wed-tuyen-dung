<?php
/**
 * FILE: job-detail.php
 * Chi tiết việc làm — Bố cục 2 cột chuẩn: Trái (Thông tin chung + Mô tả/Yêu cầu/Quyền lợi), Phải (Thông tin công ty)
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

try {
    $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?")->execute([$job_id]);
} catch (Exception $e) {}

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $job_id]);
$job = $stmt->fetch();

if (!$job) {
    $page_title = 'Không tìm thấy công việc';
    require_once 'includes/header.php';
    echo "
    <div class='container' style='padding: 80px 20px; text-align: center;'>
        <div class='detail-card' style='max-width:520px; margin:0 auto;'>
            <h2 style='font-size:20px; margin-bottom:10px;'>Vị trí không tồn tại hoặc đã hết hạn</h2>
            <p class='text-muted' style='margin-bottom:20px;'>Tin tuyển dụng có thể đã đóng hoặc bị gỡ bỏ.</p>
            <a href='jobs.php' class='btn btn-primary btn-sm'>← Quay lại danh sách việc làm</a>
        </div>
    </div>";
    require_once 'includes/footer.php';
    exit;
}

$page_title = $job['title'] . ' — ' . $job['company'];

// Lấy thông tin công ty từ employer_profiles
$employer_profile = null;
try {
    if (!empty($job['employer_id'])) {
        $ep_stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ? LIMIT 1");
        $ep_stmt->execute([$job['employer_id']]);
        $employer_profile = $ep_stmt->fetch();
    }
    if (!$employer_profile) {
        $ep_stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE company_name = ? LIMIT 1");
        $ep_stmt->execute([$job['company']]);
        $employer_profile = $ep_stmt->fetch();
    }
} catch (Exception $e) {}

// Kiểm tra đã ứng tuyển / lưu tin / danh sách CV
$already_applied = false;
$is_saved = false;
$user_cvs = [];
if (isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = :user_id AND job_id = :job_id LIMIT 1");
    $check_stmt->execute([':user_id' => $_SESSION['user_id'], ':job_id' => $job_id]);
    if ($check_stmt->fetch()) $already_applied = true;

    try {
        $save_check = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ? LIMIT 1");
        $save_check->execute([$_SESSION['user_id'], $job_id]);
        if ($save_check->fetch()) $is_saved = true;
    } catch (Exception $e) {}

    $cv_stmt = $pdo->prepare("SELECT id, title, is_default FROM cvs WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $cv_stmt->execute([$_SESSION['user_id']]);
    $user_cvs = $cv_stmt->fetchAll();
}

// Việc làm cùng ngành nghề
try {
    $rel_stmt = $pdo->prepare("SELECT id, title, company, location, salary, job_type FROM jobs WHERE category = :category AND id != :current_id AND status = 'active' LIMIT 3");
    $rel_stmt->execute([':category' => $job['category'], ':current_id' => $job_id]);
    $related_jobs = $rel_stmt->fetchAll();
} catch (PDOException $e) {
    $related_jobs = [];
}

$company_initial = mb_strtoupper(mb_substr($job['company'], 0, 2));

require_once 'includes/header.php';
?>

<section class="section section--sm" style="padding-top:28px;">
    <div class="container">
        <!-- Breadcrumb -->
        <div style="font-size:13px; color:var(--muted); margin-bottom:20px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <a href="jobs.php">Việc làm</a>
            <span>/</span>
            <span style="color:var(--ink); font-weight:600;"><?php echo htmlspecialchars($job['title']); ?></span>
        </div>

        <div class="job-detail-layout">

            <!-- CỘT TRÁI: THÔNG TIN CHUNG + NÚT HÀNH ĐỘNG + MÔ TẢ / YÊU CẦU / QUYỀN LỢI -->
            <div>
                <div class="detail-card">
                    <div style="display:flex; align-items:flex-start; gap:16px; margin-bottom:20px;">
                        <?php echo render_company_logo($job['company'], $job['company_logo'] ?? ($employer_profile['company_logo'] ?? ''), '', 56); ?>
                        <div style="flex:1; min-width:0;">
                            <h1 style="font-size:clamp(20px, 2.4vw, 28px); font-weight:800; color:var(--ink); line-height:1.25;">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </h1>
                            <div style="font-size:15px; font-weight:600; color:var(--muted); margin-top:4px;">
                                <?php echo htmlspecialchars($job['company']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin chung: Lương, Địa điểm, Kinh nghiệm, Hạn nộp -->
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px; padding:16px; background:var(--surface-subtle); border:1px solid var(--line); border-radius:var(--radius-md); margin-bottom:20px;">
                        <div>
                            <div style="font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase;">Mức lương</div>
                            <div class="mono" style="font-size:14px; font-weight:700; color:var(--brand); margin-top:2px;"><?php echo htmlspecialchars($job['salary']); ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase;">Địa điểm</div>
                            <div style="font-size:14px; font-weight:600; color:var(--ink); margin-top:2px;"><?php echo htmlspecialchars($job['location']); ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase;">Kinh nghiệm</div>
                            <div style="font-size:14px; font-weight:600; color:var(--ink); margin-top:2px;"><?php echo htmlspecialchars($job['experience']); ?></div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase;">Hạn nộp hồ sơ</div>
                            <div class="mono" style="font-size:14px; font-weight:600; color:var(--ink); margin-top:2px;">
                                <?php echo !empty($job['deadline']) ? date('d/m/Y', strtotime($job['deadline'])) : 'Tuyển liên tục'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Ứng tuyển ngay + Lưu tin -->
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                        <?php if ($already_applied): ?>
                            <div style="flex:1; background:var(--brand-light); border:1px solid var(--brand-mid); border-radius:var(--radius-sm); padding:11px 16px; font-size:13px; color:var(--brand-text); font-weight:600;">
                                Bạn đã ứng tuyển vị trí này. <a href="my-applications.php" style="text-decoration:underline; margin-left:6px;">Xem trạng thái →</a>
                            </div>
                        <?php elseif (isset($_SESSION['user_id'])): ?>
                            <button type="button" class="btn btn-primary btn-lg" onclick="openApplyModal()" style="flex:1;">
                                Ứng tuyển ngay
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode('job-detail.php?id=' . $job['id']); ?>" class="btn btn-primary btn-lg" style="flex:1;">
                                Đăng nhập để ứng tuyển
                            </a>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button type="button" id="btnSaveJob" onclick="toggleSaveJobDetail(<?php echo (int)$job['id']; ?>)" class="btn btn-secondary btn-lg" style="<?php echo $is_saved ? 'border-color:var(--status-danger-text); color:var(--status-danger-text);' : ''; ?>">
                                <svg id="saveIcon" width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $is_saved ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                <span id="saveText"><?php echo $is_saved ? 'Đã lưu' : 'Lưu tin'; ?></span>
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode('job-detail.php?id=' . $job['id']); ?>" class="btn btn-secondary btn-lg">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                Lưu tin
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 1. Mô tả công việc -->
                <div class="detail-card">
                    <h2 class="detail-card__title">Mô tả công việc</h2>
                    <div class="detail-card__body"><?php echo htmlspecialchars($job['description']); ?></div>
                </div>

                <!-- 2. Yêu cầu ứng viên -->
                <div class="detail-card">
                    <h2 class="detail-card__title">Yêu cầu ứng viên</h2>
                    <div class="detail-card__body"><?php echo htmlspecialchars($job['requirements']); ?></div>
                </div>

                <!-- 3. Quyền lợi -->
                <?php if (!empty($job['benefits'])): ?>
                <div class="detail-card">
                    <h2 class="detail-card__title">Quyền lợi</h2>
                    <div class="detail-card__body"><?php echo htmlspecialchars($job['benefits']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- CỘT PHẢI: KHUNG THÔNG TIN CÔNG TY + THÔNG TIN BỔ SUNG -->
            <aside style="position:sticky; top:84px;">
                <div class="detail-card">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <?php echo render_company_logo($employer_profile['company_name'] ?? $job['company'], $employer_profile['company_logo'] ?? ($job['company_logo'] ?? ''), '', 48); ?>
                        <div style="min-width:0;">
                            <div style="font-size:15px; font-weight:800; color:var(--ink);">
                                <?php echo htmlspecialchars($employer_profile['company_name'] ?? $job['company']); ?>
                            </div>
                            <?php if (!empty($employer_profile['is_verified'])): ?>
                                <span style="font-size:11px; color:var(--brand); font-weight:600;">Doanh nghiệp đã xác thực</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:12px; font-size:13px; border-top:1px solid var(--line-subtle); padding-top:14px;">
                        <div>
                            <div style="color:var(--muted); font-size:12px;">Quy mô</div>
                            <div style="font-weight:600; color:var(--ink); margin-top:2px;">
                                <?php echo htmlspecialchars($employer_profile['size'] ?? 'Đang cập nhật'); ?>
                            </div>
                        </div>
                        <div>
                            <div style="color:var(--muted); font-size:12px;">Lĩnh vực</div>
                            <div style="font-weight:600; color:var(--ink); margin-top:2px;">
                                <?php echo htmlspecialchars($employer_profile['industry'] ?? $job['category']); ?>
                            </div>
                        </div>
                        <div>
                            <div style="color:var(--muted); font-size:12px;">Địa chỉ</div>
                            <div style="font-weight:600; color:var(--ink); margin-top:2px; line-height:1.45;">
                                <?php echo htmlspecialchars($employer_profile['address'] ?? $job['location']); ?>
                            </div>
                        </div>
                        <?php if (!empty($employer_profile['website'])): ?>
                        <div>
                            <div style="color:var(--muted); font-size:12px;">Website</div>
                            <a href="<?php echo htmlspecialchars($employer_profile['website']); ?>" target="_blank" rel="noopener" style="font-weight:600; color:var(--brand); text-decoration:underline; word-break:break-all;">
                                <?php echo htmlspecialchars($employer_profile['website']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-card" style="padding:20px;">
                    <div style="font-size:13px; font-weight:700; color:var(--ink); margin-bottom:10px;">Thông tin chung khác</div>
                    <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                        <div style="display:flex; justify-content:space-between;">
                            <span class="text-muted">Cấp bậc</span>
                            <strong><?php echo htmlspecialchars($job['level'] ?? 'Nhân viên'); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span class="text-muted">Hình thức</span>
                            <strong><?php echo htmlspecialchars($job['job_type']); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span class="text-muted">Số lượng</span>
                            <strong><?php echo (int)($job['quantity'] ?? 1); ?> người</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Việc làm cùng ngành -->
        <?php if (!empty($related_jobs)): ?>
            <div style="margin-top:48px; padding-top:36px; border-top:1px solid var(--line);">
                <h2 style="font-size:20px; font-weight:800; margin-bottom:20px;">Việc làm cùng ngành <?php echo htmlspecialchars($job['category']); ?></h2>
                <div class="job-grid">
                    <?php foreach ($related_jobs as $r_job):
                        $r_initial = mb_strtoupper(mb_substr($r_job['company'], 0, 2));
                    ?>
                        <div class="job-card">
                            <div>
                                <div class="job-card__header">
                                    <?php echo render_company_logo($r_job['company'], '', '', 44); ?>
                                    <div class="job-card__info">
                                        <h3 class="job-card__title" title="<?php echo htmlspecialchars($r_job['title']); ?>">
                                            <a href="job-detail.php?id=<?php echo (int)$r_job['id']; ?>"><?php echo htmlspecialchars($r_job['title']); ?></a>
                                        </h3>
                                        <div class="job-card__company"><?php echo htmlspecialchars($r_job['company']); ?></div>
                                    </div>
                                </div>
                                <div class="job-card__chips" style="margin-top:12px;">
                                    <span class="chip chip--brand mono"><?php echo htmlspecialchars($r_job['salary']); ?></span>
                                    <span class="chip"><?php echo htmlspecialchars($r_job['location']); ?></span>
                                </div>
                            </div>
                            <div class="job-card__footer">
                                <span style="font-size:12px; color:var(--muted);"><?php echo htmlspecialchars($r_job['job_type']); ?></span>
                                <a href="job-detail.php?id=<?php echo (int)$r_job['id']; ?>" class="btn btn-secondary btn-xs">Chi tiết →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (isset($_SESSION['user_id']) && !$already_applied): ?>
<div id="quickApplyModal" class="jf-modal-overlay modal-hidden" style="display:none;">
    <div class="jf-modal" style="max-width:580px; max-height:90vh; overflow-y:auto;">
        <div class="jf-modal-header">
            <div>
                <div class="jf-modal-title"><?php echo htmlspecialchars($job['title']); ?></div>
                <div style="font-size:12px; color:var(--muted); margin-top:2px;"><?php echo htmlspecialchars($job['company']); ?> &middot; <?php echo htmlspecialchars($job['salary']); ?></div>
            </div>
            <button type="button" class="jf-modal-close" onclick="closeApplyModal()" aria-label="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form action="apply.php" method="POST">
            <div class="jf-modal-body">
                <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">

                <div class="form-group">
                    <label class="form-label">Chọn hồ sơ CV đính kèm</label>
                    <?php if (empty($user_cvs)): ?>
                        <div style="padding:14px; background:var(--surface-subtle); border:1px dashed var(--line); border-radius:var(--radius-sm); font-size:13px; color:var(--muted);">
                            Bạn chưa có CV trên hệ thống. <a href="cv-builder.php" target="_blank" style="color:var(--brand); font-weight:700; text-decoration:underline;">Tạo CV ngay ↗</a>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php foreach ($user_cvs as $idx => $ucv): ?>
                                <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--line); border-radius:var(--radius-sm); cursor:pointer;">
                                    <input type="radio" name="cv_id" value="<?php echo (int)$ucv['id']; ?>" <?php echo ($idx === 0) ? 'checked' : ''; ?>>
                                    <span style="flex:1; font-size:13px; font-weight:600;"><?php echo htmlspecialchars($ucv['title']); ?></span>
                                    <a href="cv-preview.php?id=<?php echo (int)$ucv['id']; ?>" target="_blank" style="font-size:12px; color:var(--brand); text-decoration:underline;" onclick="event.stopPropagation()">Xem CV</a>
                                </label>
                            <?php endforeach; ?>
                            <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--line); border-radius:var(--radius-sm); cursor:pointer;">
                                <input type="radio" name="cv_id" value="">
                                <span style="font-size:13px; color:var(--muted);">Chỉ gửi thư giới thiệu (không đính kèm CV)</span>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="cover_letter_quick">Thư giới thiệu <span style="color:var(--status-danger-text);">*</span></label>
                    <textarea id="cover_letter_quick" name="cover_letter" class="form-control form-textarea" placeholder="Tóm tắt kinh nghiệm và lý do bạn phù hợp với vị trí này..." required></textarea>
                </div>
            </div>

            <div class="jf-modal-footer">
                <button type="button" onclick="closeApplyModal()" class="btn btn-secondary btn-sm">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Gửi hồ sơ ứng tuyển</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModal()  { showModal('quickApplyModal', 'flex'); }
function closeApplyModal() { hideModal('quickApplyModal'); }
document.addEventListener('click', function(e) {
    const m = document.getElementById('quickApplyModal');
    if (m && e.target === m) closeApplyModal();
});
</script>
<?php endif; ?>

<script>
function toggleSaveJobDetail(jobId) {
    const btn = document.getElementById('btnSaveJob');
    const icon = document.getElementById('saveIcon');
    const text = document.getElementById('saveText');
    if (!btn) return;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action', 'ajax_toggle');
    fd.append('job_id', jobId);
    fetch('saved-jobs.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.status === 'success') {
                btn.style.borderColor = data.saved ? 'var(--status-danger-text)' : '';
                btn.style.color = data.saved ? 'var(--status-danger-text)' : '';
                icon.setAttribute('fill', data.saved ? 'currentColor' : 'none');
                text.textContent = data.saved ? 'Đã lưu' : 'Lưu tin';
                if (typeof updateSavedBadge === 'function' && typeof data.saved_count !== 'undefined') {
                    updateSavedBadge(data.saved_count);
                }
                if (window.showToast) window.showToast(data.message, 'success');
            }
        }).catch(() => { btn.disabled = false; });
}
</script>

<?php require_once 'includes/footer.php'; ?>

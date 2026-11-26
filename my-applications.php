<?php
/**
 * FILE: my-applications.php
 * Theo dõi việc làm đã ứng tuyển — Khớp 100% schema bảng applications (job_portal)
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để xem danh sách hồ sơ đã ứng tuyển.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode('my-applications.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Việc Làm Đã Ứng Tuyển';

// Xử lý hủy ứng tuyển
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'withdraw') {
    $withdraw_id = (int)($_POST['app_id'] ?? 0);
    if ($withdraw_id > 0) {
        try {
            $del_stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND user_id = ?");
            $del_stmt->execute([$withdraw_id, $user_id]);
            $_SESSION['flash_message'] = 'Đã hủy đơn ứng tuyển thành công.';
            $_SESSION['flash_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = 'Không thể hủy đơn ứng tuyển: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    }
    header('Location: my-applications.php');
    exit;
}

$query_error = '';
try {
    $stmt = $pdo->prepare("
        SELECT
            a.id AS app_id,
            a.cv_id,
            a.cover_letter,
            a.status AS app_status,
            a.created_at AS applied_at,
            j.id AS job_id,
            j.title AS job_title,
            j.company,
            ep.company_logo,
            j.location,
            j.salary,
            j.category,
            j.job_type,
            ep.company_name AS ep_company_name,
            c.title AS cv_title,
            iv.id AS interview_id,
            iv.interview_date,
            iv.interview_time,
            iv.interview_type,
            iv.location_or_link,
            iv.interviewer,
            iv.status AS interview_status,
            iv.notes AS interview_notes
        FROM applications a
        LEFT JOIN jobs j ON a.job_id = j.id
        LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
        LEFT JOIN cvs c ON a.cv_id = c.id
        LEFT JOIN (
            SELECT application_id, MAX(id) AS max_iv_id
            FROM interview_schedules
            WHERE status != 'cancelled'
            GROUP BY application_id
        ) latest_iv ON a.id = latest_iv.application_id
        LEFT JOIN interview_schedules iv ON latest_iv.max_iv_id = iv.id
        WHERE a.user_id = :user_id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $applications = $stmt->fetchAll();
    $total_apps = count($applications);
} catch (PDOException $e) {
    $applications = [];
    $total_apps = 0;
    $query_error = 'Lỗi truy vấn dữ liệu: ' . $e->getMessage();
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:36px;">
    <div class="container">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title" style="font-size:26px;">Việc làm đã ứng tuyển</h1>
                <p class="section-desc" style="font-size:14px; margin-top:4px;">
                    Bạn đã nộp hồ sơ vào <strong class="mono"><?php echo $total_apps; ?></strong> vị trí việc làm.
                </p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="saved-jobs.php" class="btn btn-secondary btn-sm">Việc đã lưu</a>
                <a href="my-cvs.php" class="btn btn-secondary btn-sm">Quản lý CV</a>
                <a href="jobs.php" class="btn btn-primary btn-sm">Tìm việc mới →</a>
            </div>
        </div>

        <?php if (!empty($query_error)): ?>
            <div class="flash-bar flash--danger"><?php echo htmlspecialchars($query_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($applications)): ?>
            <div style="background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:48px;">#</th>
                                <th>Vị trí & Công ty</th>
                                <th>Mức lương</th>
                                <th>CV đã dùng</th>
                                <th>Ngày nộp</th>
                                <th>Trạng thái</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $i => $app):
                                $job_title = $app['job_title'] ?: 'Tin tuyển dụng #' . (int)$app['app_id'];
                                $company_name = $app['company'] ?: ($app['ep_company_name'] ?: 'Doanh nghiệp');
                            ?>
                                <tr>
                                    <td class="text-muted mono" style="font-weight:700;"><?php echo $i + 1; ?></td>
                                    <td>
                                        <div style="display:flex; align-items:flex-start; gap:10px;">
                                            <?php echo render_company_logo($company_name, $app['company_logo'] ?? '', '', 38); ?>
                                            <div>
                                                <div style="font-weight:700; color:var(--ink); margin-bottom:2px;">
                                                    <?php if (!empty($app['job_id'])): ?>
                                                        <a href="job-detail.php?id=<?php echo (int)$app['job_id']; ?>" style="color:var(--ink);">
                                                            <?php echo htmlspecialchars($job_title); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($job_title); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size:12px; color:var(--muted);">
                                                    <?php echo htmlspecialchars($company_name); ?>
                                                    <?php if (!empty($app['location'])): ?>
                                                        &middot; <?php echo htmlspecialchars($app['location']); ?>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!empty($app['interview_date'])): ?>
                                                    <div style="margin-top:8px; background:var(--brand-light); border:1px solid var(--brand-mid); border-radius:var(--radius-sm); padding:8px 10px; font-size:12px; color:var(--brand-text);">
                                                        <div style="font-weight:700;">
                                                            Lịch phỏng vấn: <?php echo date('d/m/Y', strtotime($app['interview_date'])); ?>
                                                            <?php if (!empty($app['interview_time'])): ?>
                                                                lúc <?php echo date('H:i', strtotime($app['interview_time'])); ?>
                                                            <?php endif; ?>
                                                            (<?php echo $app['interview_type'] === 'online' ? 'Trực tuyến' : 'Trực tiếp'; ?>)
                                                        </div>
                                                        <?php if (!empty($app['location_or_link'])): ?>
                                                            <div style="margin-top:2px; font-size:11px;">
                                                                <strong>Địa điểm / Link:</strong>
                                                                <?php if (filter_var($app['location_or_link'], FILTER_VALIDATE_URL)): ?>
                                                                    <a href="<?php echo htmlspecialchars($app['location_or_link']); ?>" target="_blank" style="text-decoration:underline; font-weight:600;">Tham gia phỏng vấn ↗</a>
                                                                <?php else: ?>
                                                                    <span><?php echo htmlspecialchars($app['location_or_link']); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="chip chip--brand mono">
                                            <?php echo htmlspecialchars($app['salary'] ?: 'Thỏa thuận'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_title'])): ?>
                                            <a href="cv-preview.php?id=<?php echo (int)$app['cv_id']; ?>" target="_blank" style="font-size:13px; font-weight:600; color:var(--brand); text-decoration:underline;">
                                                <?php echo htmlspecialchars($app['cv_title']); ?> ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Thư giới thiệu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm mono">
                                        <?php echo date('d/m/Y', strtotime($app['applied_at'])); ?>
                                    </td>
                                    <td>
                                        <?php echo get_application_stage_badge($app['app_status'] ?? 'new'); ?>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <?php if (!empty($app['job_id'])): ?>
                                                <a href="job-detail.php?id=<?php echo (int)$app['job_id']; ?>" class="btn btn-secondary btn-xs">
                                                    Xem lại việc
                                                </a>
                                            <?php endif; ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn ứng tuyển này?');">
                                                <input type="hidden" name="action" value="withdraw">
                                                <input type="hidden" name="app_id" value="<?php echo (int)$app['app_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-xs">Hủy ứng tuyển</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div style="background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-xl); padding:64px 24px; text-align:center;">
                <h3 style="font-size:20px; font-weight:700; margin-bottom:8px;">Chưa có hồ sơ ứng tuyển nào</h3>
                <p class="text-muted" style="margin-bottom:24px;">Bạn chưa gửi đơn ứng tuyển vào công việc nào. Hãy khám phá và tìm cơ hội phù hợp ngay bây giờ.</p>
                <a href="jobs.php" class="btn btn-primary">Khám phá việc làm ngay →</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

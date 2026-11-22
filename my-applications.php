<?php
/**
 * ============================================================
 * FILE: my-applications.php
 * MÔ TẢ: Theo dõi việc làm đã ứng tuyển — Chuẩn Apple Design
 * ============================================================
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
$page_title = 'Hồ sơ đã ứng tuyển';

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id as app_id,
            a.cv_id,
            a.cover_letter,
            a.status as app_status,
            a.created_at as applied_at,
            j.id as job_id,
            j.title as job_title,
            j.company,
            j.location,
            j.salary,
            j.category,
            j.job_type,
            c.title as cv_title
        FROM applications a
        INNER JOIN jobs j ON a.job_id = j.id
        LEFT JOIN cvs c ON a.cv_id = c.id
        WHERE a.user_id = :user_id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $applications = $stmt->fetchAll();
    $total_apps = count($applications);
} catch (PDOException $e) {
    $applications = [];
    $total_apps = 0;
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container">
        <!-- Header -->
        <div class="section-header">
            <div>
                <span class="section-eyebrow">Hồ sơ cá nhân</span>
                <h1 class="section-title">Việc làm đã ứng tuyển</h1>
                <p class="section-desc">
                    Bạn đã nộp hồ sơ vào <strong><?php echo $total_apps; ?></strong> vị trí việc làm.
                </p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="my-cvs.php" class="btn btn-secondary btn-sm">Quản lý hồ sơ CV</a>
                <a href="jobs.php" class="btn btn-primary btn-sm">Tìm việc mới →</a>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?php echo $_SESSION['flash_type'] ?? 'success'; ?>">
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <?php if (!empty($applications)): ?>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Vị trí & Công ty</th>
                                <th>Mức lương</th>
                                <th>Hồ sơ đính kèm</th>
                                <th>Ngày nộp</th>
                                <th>Trạng thái</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $i => $app): 
                                $status_badge_map = [
                                    'Đã nộp hồ sơ'  => 'badge--pending',
                                    'Đang xét duyệt' => 'badge--pending',
                                    'Phù hợp'        => 'badge--success',
                                    'Từ chối'        => 'badge--danger'
                                ];
                                $badge_class = $status_badge_map[$app['app_status']] ?? 'badge--pending';
                            ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?php echo $i + 1; ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink); margin-bottom:2px;">
                                            <a href="job-detail.php?id=<?php echo (int)$app['job_id']; ?>" style="color:var(--ink);">
                                                <?php echo htmlspecialchars($app['job_title']); ?>
                                            </a>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            <?php echo htmlspecialchars($app['company']); ?> &middot; <?php echo htmlspecialchars($app['location']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="chip" style="background:var(--status-success-bg); color:var(--status-success-text); font-weight:700;">
                                            <?php echo htmlspecialchars($app['salary']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_title'])): ?>
                                            <a href="cv-preview.php?id=<?php echo (int)$app['cv_id']; ?>" target="_blank" style="font-size:13px; font-weight:600; text-decoration:underline;">
                                                <?php echo htmlspecialchars($app['cv_title']); ?> ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Thư giới thiệu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?php echo date('d/m/Y', strtotime($app['applied_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($app['app_status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="job-detail.php?id=<?php echo (int)$app['job_id']; ?>" class="btn btn-secondary btn-sm">
                                            Xem lại tin
                                        </a>
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

<?php
/**
 * ============================================================
 * FILE: apply.php
 * MÔ TẢ: Trang nộp đơn ứng tuyển công việc kèm chọn CV
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : (isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập tài khoản trước khi ứng tuyển công việc.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode('apply.php?job_id=' . $job_id));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $job_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['flash_message'] = 'Công việc không tồn tại hoặc đã dừng nhận hồ sơ.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: jobs.php');
    exit;
}

// Kiểm tra ứng tuyển trùng lặp
$check_stmt = $pdo->prepare("SELECT id, created_at FROM applications WHERE user_id = :user_id AND job_id = :job_id LIMIT 1");
$check_stmt->execute([':user_id' => $user_id, ':job_id' => $job_id]);
$existing_app = $check_stmt->fetch();

if ($existing_app) {
    $_SESSION['flash_message'] = 'Bạn đã nộp hồ sơ vào vị trí này vào ngày ' . date('d/m/Y', strtotime($existing_app['created_at'])) . '.';
    $_SESSION['flash_type'] = 'info';
    header('Location: my-applications.php');
    exit;
}

// Lấy danh sách CV của ứng viên
$cv_stmt = $pdo->prepare("SELECT id, title, is_default, updated_at, created_at FROM cvs WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$cv_stmt->execute([$user_id]);
$user_cvs = $cv_stmt->fetchAll();

$page_title = 'Nộp Hồ Sơ — ' . $job['title'];
$errors = [];
$cover_letter = '';
$selected_cv_id = !empty($user_cvs) ? (int)$user_cvs[0]['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    $post_cv_id = !empty($_POST['cv_id']) ? (int)$_POST['cv_id'] : null;

    if (empty($cover_letter)) {
        $errors[] = 'Vui lòng viết một vài dòng thư giới thiệu ngắn về bản thân và lý do bạn phù hợp với công việc.';
    }

    if (empty($errors)) {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO applications (user_id, job_id, cv_id, cover_letter, status, created_at)
                VALUES (:user_id, :job_id, :cv_id, :cover_letter, 'Đã nộp hồ sơ', NOW())
            ");
            $insert_stmt->execute([
                ':user_id'      => $user_id,
                ':job_id'       => $job_id,
                ':cv_id'        => $post_cv_id,
                ':cover_letter' => $cover_letter
            ]);

            $_SESSION['flash_message'] = 'Nộp hồ sơ thành công! Nhà tuyển dụng sẽ xem xét và phản hồi trong thời gian sớm nhất.';
            $_SESSION['flash_type'] = 'success';
            header('Location: my-applications.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã xảy ra lỗi khi lưu hồ sơ. Vui lòng thử lại sau.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container" style="max-width:760px;">
        <!-- Header tóm tắt vị trí -->
        <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:28px; margin-bottom:28px;">
            <span class="section-eyebrow">Ứng tuyển trực tiếp</span>
            <h1 style="font-size:24px; font-weight:800; letter-spacing:-0.025em; color:var(--ink); margin-bottom:6px;">
                <?php echo htmlspecialchars($job['title']); ?>
            </h1>
            <div style="font-size:14px; font-weight:600; color:var(--muted); margin-bottom:16px;">
                <?php echo htmlspecialchars($job['company']); ?> &middot; <?php echo htmlspecialchars($job['location']); ?>
            </div>
            <div style="display:flex; gap:8px;">
                <span class="chip" style="background:var(--status-success-bg); color:var(--status-success-text); font-weight:700;">
                    <?php echo htmlspecialchars($job['salary']); ?>
                </span>
                <span class="chip"><?php echo htmlspecialchars($job['job_type']); ?></span>
            </div>
        </div>

        <!-- Form nộp hồ sơ -->
        <div class="form-card">
            <?php if (!empty($errors)): ?>
                <div class="flash-bar flash--danger">
                    <?php echo htmlspecialchars($errors[0]); ?>
                </div>
            <?php endif; ?>

            <form action="apply.php" method="POST">
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">

                <!-- Thông tin ứng viên -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Họ và tên ứng viên</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" readonly style="background:var(--surface-subtle); color:var(--muted);">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Email liên hệ</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background:var(--surface-subtle); color:var(--muted);">
                    </div>
                </div>

                <!-- Chọn CV đính kèm -->
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label class="form-label" style="margin-bottom:0;">Đính kèm hồ sơ CV</label>
                        <a href="cv-builder.php" target="_blank" style="font-size:12px; font-weight:700; color:var(--ink); text-decoration:underline;">+ Tạo CV mới</a>
                    </div>

                    <?php if (empty($user_cvs)): ?>
                        <div style="padding:16px; border:1px dashed var(--line); border-radius:var(--radius-md); background:var(--surface-subtle); margin-bottom:12px;">
                            <div style="font-size:13px; color:var(--ink-secondary); margin-bottom:4px;">Bạn chưa có hồ sơ CV trên JobFinder.</div>
                            <div style="font-size:12px; color:var(--muted);">Bạn có thể nộp kèm thư giới thiệu ngay bên dưới hoặc <a href="cv-builder.php" target="_blank" style="color:var(--ink); text-decoration:underline; font-weight:600;">tạo CV trực tuyến</a> trước khi nộp.</div>
                        </div>
                    <?php else: ?>
                        <div class="radio-cards-grid" style="grid-template-columns:1fr; margin-bottom:16px;">
                            <?php foreach ($user_cvs as $i => $cv): ?>
                                <label class="radio-card">
                                    <input type="radio" name="cv_id" value="<?php echo $cv['id']; ?>" <?php echo ($i === 0) ? 'checked' : ''; ?>>
                                    <div class="radio-card-content" style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <span class="radio-card-title"><?php echo htmlspecialchars($cv['title']); ?></span>
                                            <?php if ($cv['is_default']): ?>
                                                <span class="badge badge--active" style="font-size:10px;">Mặc định</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="radio-card-desc">
                                            Cập nhật: <?php echo date('d/m/Y', strtotime($cv['updated_at'] ?? $cv['created_at'])); ?> &middot; 
                                            <a href="cv-preview.php?id=<?php echo $cv['id']; ?>" target="_blank" style="color:var(--ink); text-decoration:underline;">Xem trước ↗</a>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>

                            <label class="radio-card">
                                <input type="radio" name="cv_id" value="">
                                <div class="radio-card-content">
                                    <span class="radio-card-title">Không đính kèm CV</span>
                                    <div class="radio-card-desc">Chỉ gửi thư giới thiệu bản thân trực tiếp đến nhà tuyển dụng.</div>
                                </div>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thư giới thiệu (Cover Letter) -->
                <div class="form-group">
                    <label class="form-label">
                        Thư giới thiệu bản thân <span style="color:var(--status-danger-text);">*</span>
                        <span class="form-label-desc">(Lý do bạn phù hợp, dự án đã làm hoặc định hướng phát triển)</span>
                    </label>
                    <textarea name="cover_letter" class="form-control form-textarea" placeholder="Kính gửi bộ phận tuyển dụng, tôi mong muốn ứng tuyển vào vị trí này vì..."><?php echo htmlspecialchars($cover_letter); ?></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px;">
                    <a href="job-detail.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" style="padding:14px 28px;">
                        Gửi hồ sơ ứng tuyển →
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

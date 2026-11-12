<?php

/**
 * ============================================================
 * FILE: apply.php
 * MÔ TẢ: Trang nộp đơn ứng tuyển công việc
 * - Yêu cầu đăng nhập trước khi ứng tuyển (nếu chưa đăng nhập -> chuyển sang login.php)
 * - Hiển thị tóm tắt thông tin công việc đang ứng tuyển
 * - Nhập thư giới thiệu bản thân (Cover letter)
 * - Kiểm tra không cho phép ứng tuyển trùng lặp một công việc nhiều lần
 * - Lưu bản ghi vào bảng `applications` trong MySQL
 * ============================================================
 */

// Nạp kết nối cơ sở dữ liệu
require_once 'config/database.php';

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra đăng nhập: Chỉ cho phép người dùng đã đăng nhập ứng tuyển
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : (isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập tài khoản trước khi ứng tuyển công việc.';
    $_SESSION['flash_type'] = 'warning';

    // Lưu lại URL để sau khi đăng nhập sẽ tự động quay lại trang apply
    $redirect_url = 'apply.php?job_id=' . $job_id;
    header('Location: login.php?redirect=' . urlencode($redirect_url));
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Kiểm tra mã công việc hợp lệ
if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

// 2. Lấy thông tin công việc từ MySQL
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $job_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['flash_message'] = 'Công việc không tồn tại hoặc đã hết hạn ứng tuyển.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: jobs.php');
    exit;
}

// 3. Kiểm tra xem người dùng đã từng ứng tuyển công việc này chưa
$check_stmt = $pdo->prepare("SELECT id, created_at FROM applications WHERE user_id = :user_id AND job_id = :job_id LIMIT 1");
$check_stmt->execute([
    ':user_id' => $user_id,
    ':job_id'  => $job_id
]);
$existing_app = $check_stmt->fetch();

if ($existing_app) {
    // Đã ứng tuyển trước đó, thông báo và chuyển sang trang danh sách đã ứng tuyển
    $_SESSION['flash_message'] = 'Bạn đã nộp hồ sơ ứng tuyển vào vị trí này vào ngày ' . date('d/m/Y', strtotime($existing_app['created_at'])) . '.';
    $_SESSION['flash_type'] = 'info';
    header('Location: my-applications.php');
    exit;
}

$page_title = 'Ứng tuyển: ' . $job['title'];
$errors = [];
$cover_letter = '';

// 4. Xử lý khi người dùng nhấn nút "Gửi hồ sơ ứng tuyển" (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = trim($_POST['cover_letter'] ?? '');

    // Kiểm tra độ dài thư giới thiệu (tùy chọn nhưng nếu nhập thì không quá ngắn)
    if (empty($cover_letter)) {
        $errors[] = 'Vui lòng viết một vài dòng thư giới thiệu ngắn về bản thân và lý do bạn phù hợp với công việc.';
    } elseif (mb_strlen($cover_letter) < 20) {
        $errors[] = 'Thư giới thiệu nên có ít nhất 20 ký tự để nhà tuyển dụng hiểu rõ hơn về bạn.';
    }

    // Nếu không có lỗi, tiến hành lưu vào MySQL
    if (empty($errors)) {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO applications (user_id, job_id, cover_letter, status, created_at)
                VALUES (:user_id, :job_id, :cover_letter, 'Đã nộp hồ sơ', NOW())
            ");
            $insert_stmt->execute([
                ':user_id'      => $user_id,
                ':job_id'       => $job_id,
                ':cover_letter' => $cover_letter
            ]);

            // Thiết lập thông báo thành công
            $_SESSION['flash_message'] = 'Ứng tuyển thành công! Nhà tuyển dụng sẽ xem xét hồ sơ của bạn.';
            $_SESSION['flash_type'] = 'success';

            // Chuyển hướng sang trang danh sách công việc đã ứng tuyển
            header('Location: my-applications.php');
            exit;
        } catch (PDOException $e) {
            // Kiểm tra lỗi trùng lặp khóa duy nhất (UNIQUE constraint)
            if ($e->getCode() == 23000) {
                $errors[] = 'Bạn đã ứng tuyển công việc này rồi.';
            } else {
                $errors[] = 'Có lỗi xảy ra khi lưu thông tin ứng tuyển. Vui lòng thử lại.';
            }
        }
    }
}

// Nạp Header
require_once 'includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper" style="padding: 30px 0;">
        <div class="auth-card" style="max-width: 650px;">
            <div class="auth-header">
                <h2>Ứng Tuyển Công Việc</h2>
                <p>Nộp hồ sơ trực tiếp đến nhà tuyển dụng <strong><?php echo htmlspecialchars($job['company']); ?></strong></p>
            </div>

            <!-- Tóm tắt vị trí ứng tuyển -->
            <div style="background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 25px;">
                <h4 style="font-size: 1.15rem; color: var(--primary-dark); margin-bottom: 6px;">
                    <?php echo htmlspecialchars($job['title']); ?>
                </h4>
                <div style="font-size: 0.9rem; color: var(--text-muted); display: flex; flex-wrap: wrap; gap: 15px;">
                    <span><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($job['company']); ?></span>
                    <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                    <span><i class="fa-solid fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary']); ?></span>
                </div>
            </div>

            <!-- Hiển thị lỗi nếu có -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form Ứng tuyển -->
            <form action="apply.php" method="POST" class="auth-form">
                <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">

                <!-- Thông tin ứng viên (tự động điền từ tài khoản đã đăng nhập) -->
                <div class="form-group">
                    <label>Ứng viên nộp hồ sơ</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" readonly style="background-color: #e9ecef;">
                </div>

                <div class="form-group">
                    <label>Địa chỉ Email liên hệ</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background-color: #e9ecef;">
                </div>

                <!-- Thư giới thiệu (Cover Letter) -->
                <div class="form-group">
                    <label for="cover_letter">Thư giới thiệu bản thân <span class="required">*</span></label>
                    <textarea
                        name="cover_letter"
                        id="cover_letter"
                        class="form-control"
                        rows="6"
                        placeholder="Hãy viết ngắn gọn về kinh nghiệm, kỹ năng nổi bật của bạn và lý do vì sao bạn là ứng viên phù hợp nhất cho vị trí này..."
                        required><?php echo htmlspecialchars($cover_letter); ?></textarea>
                    <p class="form-hint">Một bức thư giới thiệu ấn tượng và chân thành sẽ giúp bạn ghi điểm tuyệt đối trong mắt nhà tuyển dụng.</p>
                </div>

                <!-- Nút thao tác -->
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <a href="job-detail.php?id=<?php echo (int)$job['id']; ?>" class="btn btn-light" style="flex: 1;">
                        <i class="fa-solid fa-arrow-left"></i> Quay Lại
                    </a>
                    <button type="submit" class="btn btn-primary" style="flex: 2;">
                        <i class="fa-solid fa-paper-plane"></i> Gửi Hồ Sơ Ứng Tuyển
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
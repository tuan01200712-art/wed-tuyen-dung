<?php
/**
 * ============================================================
 * FILE: login.php
 * MÔ TẢ: Đăng nhập hệ thống — Chuẩn Apple Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif (($_SESSION['role'] ?? '') === 'employer') {
        header('Location: employer/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$page_title = 'Đăng Nhập';
$error = '';
$email = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                $error = 'Tài khoản của bạn đã bị tạm khóa. Vui lòng liên hệ ban quản trị để được hỗ trợ.';
            } else {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?? 'candidate';

                $_SESSION['flash_message'] = 'Đăng nhập thành công. Chào mừng trở lại!';
                $_SESSION['flash_type'] = 'success';

                if (!empty($redirect) && strpos($redirect, 'http') === false) {
                    header('Location: ' . $redirect);
                } elseif ($_SESSION['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($_SESSION['role'] === 'employer') {
                    header('Location: employer/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        } else {
            $error = 'Email hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:48px;">
    <div class="container" style="max-width:480px;">
        <div class="section-header" style="text-align:center; display:block; margin-bottom:28px;">
            <span class="section-eyebrow">Xác thực tài khoản</span>
            <h1 class="section-title" style="font-size:28px;">Đăng nhập JobFinder</h1>
            <p class="section-desc" style="font-size:14px;">Truy cập hồ sơ ứng tuyển hoặc kênh quản trị tuyển dụng.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-bar flash--danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?php echo $_SESSION['flash_type'] ?? 'success'; ?>">
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div class="form-card">
            <form action="login.php" method="POST">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                <div class="form-group">
                    <label class="form-label" for="email">Địa chỉ Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="ten@vidu.com" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu..." required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; margin-top:8px;">
                    Đăng nhập →
                </button>
            </form>

            <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--line-subtle); text-align:center; font-size:13px; color:var(--muted);">
                Chưa có tài khoản? <a href="register.php" style="color:var(--ink); font-weight:700; text-decoration:underline;">Đăng ký ngay</a>
            </div>
        </div>

        <!-- Gợi ý tài khoản kiểm thử hệ thống -->
        <div style="margin-top:20px; padding:16px; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-md); font-size:12px; color:var(--muted); line-height:1.6;">
            <div style="font-weight:700; color:var(--ink); margin-bottom:4px;">Tài khoản mẫu thử nghiệm (Mật khẩu: 123456):</div>
            <div>&middot; <strong>Quản trị viên:</strong> admin@jobfinder.vn</div>
            <div>&middot; <strong>Nhà tuyển dụng:</strong> tuyendung@fpt.com</div>
            <div>&middot; <strong>Ứng viên:</strong> an.nguyen@gmail.com</div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<?php

/**
 * ============================================================
 * FILE: login.php
 * MÔ TẢ: Trang đăng nhập hệ thống cho người dùng / ứng viên
 * - Xác thực email qua PDO Prepared Statement
 * - Xác thực mật khẩu qua password_verify()
 * - Lưu thông tin đăng nhập vào PHP Session
 * ============================================================
 */

// Nạp kết nối cơ sở dữ liệu
require_once 'config/database.php';

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Đăng nhập';
$error = '';
$email = '';

// Kiểm tra tham số redirect (ví dụ khi bấm Ứng tuyển mà chưa đăng nhập)
$redirect = $_GET['redirect'] ?? '';

// Xử lý khi người dùng gửi form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        // Truy vấn tìm người dùng theo email
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Kiểm tra xem tài khoản có tồn tại và mật khẩu có khớp không
        if ($user && password_verify($password, $user['password'])) {
            // Đăng nhập thành công, lưu thông tin vào Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            $_SESSION['flash_message'] = 'Đăng nhập thành công! Chúc bạn tìm được công việc ưng ý.';
            $_SESSION['flash_type'] = 'success';

            // Nếu có trang cần chuyển hướng tiếp tục (ví dụ trang apply.php?job_id=...)
            if (!empty($redirect) && strpos($redirect, 'http') === false) {
                header('Location: ' . $redirect);
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            // Sai email hoặc mật khẩu
            $error = 'Email hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại.';
        }
    }
}

// Nạp Header
require_once 'includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Chào Mừng Trở Lại!</h2>
                <p>Đăng nhập để ứng tuyển và quản lý hồ sơ việc làm</p>
            </div>

            <!-- Hiển thị thông báo lỗi (nếu có) -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <!-- Form Đăng nhập -->
            <form action="login.php" method="POST" class="auth-form" novalidate>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                <div class="form-group">
                    <label for="email">Địa chỉ Email <span class="required">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Nhập email của bạn"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu <span class="required">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Nhập mật khẩu"
                        required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập
                </button>
            </form>

            <div class="auth-footer">
                Chưa có tài khoản? <a href="register.php"><strong>Đăng ký ngay</strong></a>
            </div>
        </div>
    </div>
</div>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
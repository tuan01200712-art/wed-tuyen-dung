<?php

/**
 * ============================================================
 * FILE: register.php
 * MÔ TẢ: Trang đăng ký tài khoản ứng viên mới
 * - Kiểm tra dữ liệu nhập (họ tên, email, mật khẩu >= 6 ký tự)
 * - Kiểm tra email trùng lặp trong database bằng PDO Prepared Statement
 * - Mã hóa mật khẩu an toàn bằng password_hash(..., PASSWORD_DEFAULT)
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

$page_title = 'Đăng ký tài khoản';
$errors = [];
$name = '';
$email = '';

// Xử lý khi người dùng gửi form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Kiểm tra họ và tên
    if (empty($name)) {
        $errors[] = 'Vui lòng nhập họ và tên của bạn.';
    } elseif (mb_strlen($name) < 2) {
        $errors[] = 'Họ và tên phải có ít nhất 2 ký tự.';
    }

    // 2. Kiểm tra email
    if (empty($email)) {
        $errors[] = 'Vui lòng nhập địa chỉ email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không đúng định dạng.';
    } else {
        // Kiểm tra email đã tồn tại trong CSDL chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Địa chỉ email này đã được sử dụng. Vui lòng chọn email khác hoặc đăng nhập.';
        }
    }

    // 3. Kiểm tra mật khẩu
    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có độ dài từ 6 ký tự trở lên.';
    }

    // 4. Kiểm tra xác nhận mật khẩu
    if ($password !== $confirm_password) {
        $errors[] = 'Xác nhận mật khẩu không trùng khớp.';
    }

    // 5. Nếu không có lỗi, tiến hành lưu vào cơ sở dữ liệu
    if (empty($errors)) {
        // Mã hóa mật khẩu bằng hàm chuẩn của PHP
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, created_at) 
                VALUES (:name, :email, :password, NOW())
            ");
            $insert_stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashed_password
            ]);

            // Tự động đăng nhập cho người dùng mới đăng ký
            $new_user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Đặt thông báo thành công
            $_SESSION['flash_message'] = 'Đăng ký tài khoản thành công! Chào mừng bạn đến với JobFinder.';
            $_SESSION['flash_type'] = 'success';

            // Chuyển hướng về trang chủ
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại sau.';
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
                <h2>Tạo Tài Khoản Mới</h2>
                <p>Khám phá hàng ngàn cơ hội nghề nghiệp hấp dẫn ngay hôm nay</p>
            </div>

            <!-- Hiển thị thông báo lỗi (nếu có) -->
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

            <!-- Form Đăng ký -->
            <form action="register.php" method="POST" class="auth-form" id="registerForm" novalidate>
                <div class="form-group">
                    <label for="name">Họ và tên <span class="required">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="Ví dụ: Nguyễn Văn An"
                        value="<?php echo htmlspecialchars($name); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="email">Địa chỉ Email <span class="required">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Ví dụ: an.nguyen@gmail.com"
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
                        placeholder="Tối thiểu 6 ký tự"
                        required>
                    <p class="form-hint">Mật khẩu cần tối thiểu 6 ký tự để bảo mật tài khoản.</p>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu <span class="required">*</span></label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Nhập lại mật khẩu ở trên"
                        required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fa-solid fa-user-plus"></i> Đăng Ký Tài Khoản
                </button>
            </form>

            <div class="auth-footer">
                Đã có tài khoản? <a href="login.php"><strong>Đăng nhập ngay</strong></a>
            </div>
        </div>
    </div>
</div>

<?php
// Nạp Footer
require_once 'includes/footer.php';
?>
<?php
/**
 * ============================================================
 * FILE: register.php
 * MÔ TẢ: Đăng ký tài khoản (Ứng viên / Nhà tuyển dụng) — Chuẩn Apple Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Đăng Ký Tài Khoản';
$errors = [];
$name = '';
$email = '';
$role = 'candidate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? 'candidate', ['candidate', 'employer']) ? $_POST['role'] : 'candidate';

    if (empty($name)) {
        $errors[] = 'Vui lòng nhập họ và tên của bạn.';
    } elseif (mb_strlen($name) < 2) {
        $errors[] = 'Họ và tên phải có ít nhất 2 ký tự.';
    }

    if (empty($email)) {
        $errors[] = 'Vui lòng nhập địa chỉ email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không đúng định dạng.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Địa chỉ email này đã được sử dụng. Vui lòng chọn email khác.';
        }
    }

    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có độ dài từ 6 ký tự trở lên.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Xác nhận mật khẩu không trùng khớp.';
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, is_active, created_at) 
                VALUES (:name, :email, :password, :role, 1, NOW())
            ");
            $insert_stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashed_password,
                ':role'     => $role
            ]);

            $new_user_id = (int)$pdo->lastInsertId();

            // Nếu là nhà tuyển dụng, tạo sẵn hồ sơ công ty
            if ($role === 'employer') {
                $comp_name = !empty($_POST['company_name']) ? trim($_POST['company_name']) : $name . ' Company';
                $ep_stmt = $pdo->prepare("INSERT INTO employer_profiles (user_id, company_name, created_at) VALUES (?, ?, NOW())");
                $ep_stmt->execute([$new_user_id, $comp_name]);
            }

            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = $role;

            $_SESSION['flash_message'] = 'Đăng ký tài khoản thành công! Chào mừng bạn đến với JobFinder.';
            $_SESSION['flash_type'] = 'success';

            if ($role === 'employer') {
                header('Location: employer/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại sau.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container" style="max-width:540px;">
        <div class="section-header" style="text-align:center; display:block; margin-bottom:28px;">
            <span class="section-eyebrow">Tài khoản mới</span>
            <h1 class="section-title" style="font-size:28px;">Đăng ký JobFinder</h1>
            <p class="section-desc" style="font-size:14px;">Bắt đầu tìm việc hoặc kết nối ứng viên tài năng.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?php echo htmlspecialchars($errors[0]); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="register.php" method="POST">
                <!-- Chọn vai trò -->
                <div class="form-group">
                    <label class="form-label">Bạn tham gia với tư cách *</label>
                    <div class="radio-cards-grid">
                        <label class="radio-card">
                            <input type="radio" name="role" value="candidate" <?php echo ($role === 'candidate') ? 'checked' : ''; ?> onchange="toggleCompanyInput(false)">
                            <div class="radio-card-content">
                                <div class="radio-card-title">Ứng viên</div>
                                <div class="radio-card-desc">Tìm việc làm & nộp hồ sơ</div>
                            </div>
                        </label>

                        <label class="radio-card">
                            <input type="radio" name="role" value="employer" <?php echo ($role === 'employer') ? 'checked' : ''; ?> onchange="toggleCompanyInput(true)">
                            <div class="radio-card-content">
                                <div class="radio-card-title">Nhà tuyển dụng</div>
                                <div class="radio-card-desc">Đăng tin & tuyển nhân sự</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Tên người dùng -->
                <div class="form-group">
                    <label class="form-label" for="name">Họ và tên *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <!-- Tên công ty (nếu là employer) -->
                <div class="form-group" id="companyField" style="display:<?php echo ($role === 'employer') ? 'block' : 'none'; ?>;">
                    <label class="form-label" for="company_name">Tên công ty / Doanh nghiệp *</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" placeholder="Tên công ty tuyển dụng...">
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" for="email">Địa chỉ Email *</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="ten@vidu.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <!-- Mật khẩu -->
                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu *</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự..." required>
                </div>

                <!-- Xác nhận mật khẩu -->
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Xác nhận mật khẩu *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu..." required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; margin-top:10px;">
                    Tạo tài khoản →
                </button>
            </form>

            <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--line-subtle); text-align:center; font-size:13px; color:var(--muted);">
                Đã có tài khoản? <a href="login.php" style="color:var(--ink); font-weight:700; text-decoration:underline;">Đăng nhập</a>
            </div>
        </div>
    </div>
</section>

<script>
function toggleCompanyInput(isEmployer) {
    const compField = document.getElementById('companyField');
    if (compField) {
        compField.style.display = isEmployer ? 'block' : 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>

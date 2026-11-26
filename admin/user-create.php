<?php
/**
 * ============================================================
 * FILE: admin/user-create.php
 * MÔ TẢ: Thêm tài khoản người dùng mới bởi Quản trị viên
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Thêm Người Dùng Mới';
$errors = [];

$name = '';
$email = '';
$phone = '';
$role = 'candidate';
$is_active = 1;
$company_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['candidate', 'employer', 'admin']) ? $_POST['role'] : 'candidate';
    $is_active = (int)($_POST['is_active'] ?? 1);
    $company_name = trim($_POST['company_name'] ?? '');

    if (empty($name)) $errors[] = 'Họ và tên không được để trống.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Mật khẩu phải từ 6 ký tự trở lên.';

    if (empty($errors)) {
        // Kiểm tra trùng email
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = 'Email này đã tồn tại trong hệ thống. Vui lòng chọn email khác.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, phone, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $email, $hash, $role, $phone ?: null, $is_active]);
                $new_id = (int)$pdo->lastInsertId();

                if ($role === 'employer') {
                    $c_name = !empty($company_name) ? $company_name : ($name . ' Corporation');
                    $ep_stmt = $pdo->prepare("
                        INSERT INTO employer_profiles (user_id, company_name, is_verified, created_at)
                        VALUES (?, ?, 1, NOW())
                    ");
                    $ep_stmt->execute([$new_id, $c_name]);
                }

                $_SESSION['flash_message'] = 'Đã tạo tài khoản thành công cho "' . htmlspecialchars($name) . '".';
                $_SESSION['flash_type'] = 'success';
                header('Location: users.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Lỗi hệ thống khi tạo tài khoản. Vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title">Thêm tài khoản mới</h1>
                <p class="section-desc">Khởi tạo trực tiếp tài khoản Ứng viên, Doanh nghiệp hoặc Quản trị viên.</p>
            </div>
            <a href="users.php" class="btn btn-secondary btn-sm">← Quay lại danh sách</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <div class="form-card" style="max-width:680px;">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Họ và tên <span style="color:var(--status-danger-text);">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Ví dụ: Hoàng Minh Trí" value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="email">Địa chỉ Email <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="user@domain.com" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu khởi tạo <span style="color:var(--status-danger-text);">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                            <button type="button" class="password-toggle-btn" aria-label="Hiện mật khẩu">
                                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="09xxxxxxxx" value="<?= htmlspecialchars($phone) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">Vai trò tài khoản <span style="color:var(--status-danger-text);">*</span></label>
                        <select id="role" name="role" class="form-control form-select" onchange="toggleCompanyInput(this.value)">
                            <option value="candidate" <?= ($role === 'candidate') ? 'selected' : '' ?>>Ứng viên (Candidate)</option>
                            <option value="employer" <?= ($role === 'employer') ? 'selected' : '' ?>>Nhà tuyển dụng (Employer)</option>
                            <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="companyGroup" style="<?= ($role === 'employer') ? '' : 'display:none;' ?>">
                    <label class="form-label" for="company_name">Tên công ty / Doanh nghiệp</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" placeholder="Ví dụ: Công ty Cổ phần Công nghệ ABC" value="<?= htmlspecialchars($company_name) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Trạng thái tài khoản</label>
                    <div style="display:flex; gap:20px; align-items:center;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="is_active" value="1" <?= ($is_active === 1) ? 'checked' : '' ?>>
                            <span class="badge badge--active">Kích hoạt ngay</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="is_active" value="0" <?= ($is_active === 0) ? 'checked' : '' ?>>
                            <span class="badge badge--danger">Tạm khóa</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:32px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                    <a href="users.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" style="padding:14px 28px;">Khởi tạo tài khoản →</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function toggleCompanyInput(role) {
    var grp = document.getElementById('companyGroup');
    if (role === 'employer') {
        grp.style.display = 'block';
    } else {
        grp.style.display = 'none';
    }
}
</script>

<script src="../assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>

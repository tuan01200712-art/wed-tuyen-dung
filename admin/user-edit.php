<?php
/**
 * ============================================================
 * FILE: admin/user-edit.php
 * MÔ TẢ: Chỉnh sửa tài khoản người dùng
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($target_id <= 0) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$target_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash_message'] = 'Không tìm thấy người dùng.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: users.php');
    exit;
}

$page_title = 'Sửa Người Dùng: ' . $user['name'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['candidate', 'employer', 'admin']) ? $_POST['role'] : $user['role'];
    $phone = trim($_POST['phone'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);

    if (empty($name)) $errors[] = 'Họ và tên không được để trống.';

    // Không thể tự đổi role hoặc tự khóa chính mình
    if ($target_id === $admin_id) {
        $role = 'admin';
        $is_active = 1;
    }

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("UPDATE users SET name = ?, role = ?, phone = ?, is_active = ? WHERE id = ?");
            $upd->execute([$name, $role, $phone ?: null, $is_active, $target_id]);

            // Nếu đổi sang employer và chưa có employer_profiles, tạo 1 profile mặc định
            if ($role === 'employer') {
                $has_ep = (int)$pdo->query("SELECT COUNT(*) FROM employer_profiles WHERE user_id = {$target_id}")->fetchColumn();
                if ($has_ep === 0) {
                    $pdo->prepare("INSERT INTO employer_profiles (user_id, company_name, created_at) VALUES (?, ?, NOW())")->execute([$target_id, $name . ' Company']);
                }
            }

            $_SESSION['flash_message'] = 'Cập nhật tài khoản người dùng thành công.';
            $_SESSION['flash_type'] = 'success';
            header('Location: users.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã xảy ra lỗi khi cập nhật tài khoản.';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Quản trị người dùng</span>
                <h1 class="section-title">Chỉnh sửa tài khoản</h1>
                <p class="section-desc">Cập nhật quyền hạn và trạng thái hoạt động của người dùng.</p>
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
                    <label class="form-label" for="name">Họ và tên *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ Email</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background:var(--surface-subtle); color:var(--muted); cursor:not-allowed;">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="role">Vai trò tài khoản *</label>
                        <select id="role" name="role" class="form-control form-select" <?= ($target_id === $admin_id) ? 'disabled' : '' ?>>
                            <option value="candidate" <?= ($user['role'] === 'candidate') ? 'selected' : '' ?>>Ứng viên (Candidate)</option>
                            <option value="employer" <?= ($user['role'] === 'employer') ? 'selected' : '' ?>>Nhà tuyển dụng (Employer)</option>
                            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                        </select>
                        <?php if ($target_id === $admin_id): ?>
                            <span style="font-size:11px; color:var(--muted);">Không thể đổi vai trò của chính mình.</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="is_active">Trạng thái tài khoản</label>
                    <select id="is_active" name="is_active" class="form-control form-select" <?= ($target_id === $admin_id) ? 'disabled' : '' ?>>
                        <option value="1" <?= ((int)$user['is_active'] === 1) ? 'selected' : '' ?>>Đang hoạt động (Active)</option>
                        <option value="0" <?= ((int)$user['is_active'] === 0) ? 'selected' : '' ?>>Tạm khóa tài khoản (Blocked)</option>
                    </select>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                    <a href="users.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>

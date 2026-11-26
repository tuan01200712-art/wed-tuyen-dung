<?php
/**
 * ============================================================
 * FILE: profile.php
 * MÔ TẢ: Cài đặt tài khoản ứng viên — Chuẩn Apple Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode($_SERVER['PHP_SELF']));
    exit;
}
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        $_SESSION['flash_message'] = 'Họ và tên không được để trống.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: profile.php');
        exit;
    }

    $upd = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $upd->execute([$name, $phone ?: null, $user_id]);

    $_SESSION['user_name'] = $name;
    $_SESSION['flash_message'] = 'Cập nhật hồ sơ tài khoản thành công.';
    $_SESSION['flash_type'] = 'success';
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare("SELECT name, email, phone, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$page_title = 'Cài Đặt Tài Khoản';
require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container" style="max-width:580px;">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title">Cài đặt thông tin</h1>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Họ và tên <span style="color:var(--status-danger-text);">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ Email</label>
                    <input type="email" class="form-control"
                           value="<?= htmlspecialchars($user['email']) ?>"
                           readonly style="background:var(--surface-subtle); color:var(--muted); cursor:not-allowed;">
                    <span style="font-size:11px; color:var(--muted); margin-top:4px; display:block;">Email đăng ký không thể thay đổi.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input type="text" id="phone" name="phone" class="form-control"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="Ví dụ: 0912 345 678">
                </div>

                <div class="form-group" style="margin-bottom:28px;">
                    <label class="form-label">Vai trò tài khoản</label>
                    <div>
                        <span class="badge badge--active"><?= strtoupper($user['role']) ?></span>
                        <span style="font-size:12px; color:var(--muted); margin-left:8px;">Tham gia ngày <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px;">Lưu thay đổi</button>
            </form>
        </div>

        <div style="margin-top:24px; padding:20px; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-weight:700; font-size:14px;">Quản lý hồ sơ CV của bạn</div>
                <div style="font-size:12px; color:var(--muted);">Xem và chỉnh sửa các mẫu CV ứng tuyển.</div>
            </div>
            <a href="my-cvs.php" class="btn btn-secondary btn-sm">Quản lý CV →</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

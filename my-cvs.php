<?php
/**
 * ============================================================
 * FILE: my-cvs.php
 * MÔ TẢ: Danh sách hồ sơ CV của ứng viên — Chuẩn Apple Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để quản lý CV.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode($_SERVER['PHP_SELF']));
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// --- Xử lý POST (PRG pattern) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $cv_id = (int)($_POST['cv_id'] ?? 0);
        if ($cv_id > 0) {
            $del = $pdo->prepare("DELETE FROM cvs WHERE id = ? AND user_id = ?");
            $del->execute([$cv_id, $user_id]);
            $_SESSION['flash_message'] = 'Đã xóa CV thành công.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: my-cvs.php');
        exit;
    }

    if ($action === 'set_default') {
        $cv_id = (int)($_POST['cv_id'] ?? 0);
        if ($cv_id > 0) {
            $pdo->prepare("UPDATE cvs SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("UPDATE cvs SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$cv_id, $user_id]);
            $_SESSION['flash_message'] = 'Đã đặt làm CV mặc định khi ứng tuyển.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: my-cvs.php');
        exit;
    }

    header('Location: my-cvs.php');
    exit;
}

// --- Load danh sách CVs ---
$stmt = $pdo->prepare("SELECT * FROM cvs WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$cvs = $stmt->fetchAll();

$page_title = 'Hồ Sơ CV Của Tôi';
require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container">
        <!-- Header -->
        <div class="section-header">
            <div>
                <h1 class="section-title">Hồ sơ CV của tôi</h1>
                <p class="section-desc">Quản lý và cập nhật các mẫu CV để ứng tuyển nhanh chóng vào các vị trí phù hợp.</p>
            </div>
            <a href="cv-builder.php" class="btn btn-primary">+ Tạo CV mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <?php if (empty($cvs)): ?>
            <div style="background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-xl); padding:64px 20px; text-align:center;">
                <h3 style="font-size:20px; font-weight:700; margin-bottom:8px;">Bạn chưa có hồ sơ CV nào</h3>
                <p class="text-muted" style="margin-bottom:24px;">Tạo một bản CV hoàn chỉnh trong 5 phút để gây ấn tượng mạnh với nhà tuyển dụng.</p>
                <a href="cv-builder.php" class="btn btn-primary">Tạo CV đầu tiên ngay →</a>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
                <?php foreach ($cvs as $cv): ?>
                    <div style="background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; transition:var(--transition);">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; gap:8px;">
                                <h3 style="font-size:16px; font-weight:700; color:var(--ink); line-height:1.3;">
                                    <?= htmlspecialchars($cv['title']) ?>
                                </h3>
                                <?php if ($cv['is_default']): ?>
                                    <span class="badge badge--active" style="font-size:11px;">MẶC ĐỊNH</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:13px; color:var(--ink-secondary); margin-bottom:6px;">
                                Ứng viên: <strong><?= htmlspecialchars($cv['full_name']) ?></strong>
                            </div>
                            <div style="font-size:12px; color:var(--muted); margin-bottom:20px;">
                                Cập nhật: <?= date('d/m/Y', strtotime($cv['updated_at'] ?? $cv['created_at'])) ?>
                            </div>
                        </div>

                        <div style="display:flex; flex-wrap:wrap; gap:8px; padding-top:16px; border-top:1px solid var(--line-subtle);">
                            <a href="cv-preview.php?id=<?= (int)$cv['id'] ?>" target="_blank" class="btn btn-secondary btn-sm">Xem trước ↗</a>
                            <a href="cv-builder.php?id=<?= (int)$cv['id'] ?>" class="btn btn-secondary btn-sm">Chỉnh sửa</a>
                            
                            <?php if (!$cv['is_default']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="cv_id" value="<?= (int)$cv['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" style="font-size:12px;">Đặt mặc định</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display:inline; margin-left:auto;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa CV này?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="cv_id" value="<?= (int)$cv['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="font-size:12px;">Xóa</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

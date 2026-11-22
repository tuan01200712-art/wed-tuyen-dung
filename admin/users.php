<?php
/**
 * ============================================================
 * FILE: admin/users.php
 * MÔ TẢ: Quản lý người dùng toàn hệ thống (CRUD) — Chuẩn Apple Design
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Người Dùng';

// Xử lý POST (toggle lock hoặc delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_id = (int)($_POST['user_id'] ?? 0);

    if ($target_id > 0) {
        if ($target_id === $admin_id) {
            $_SESSION['flash_message'] = 'Không thể thao tác trên chính tài khoản đang đăng nhập.';
            $_SESSION['flash_type'] = 'danger';
        } elseif ($action === 'toggle_lock') {
            $curr = (int)$pdo->query("SELECT is_active FROM users WHERE id = {$target_id}")->fetchColumn();
            $new_val = ($curr === 1) ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_val, $target_id]);
            $_SESSION['flash_message'] = 'Đã ' . ($new_val === 0 ? 'tạm khóa' : 'mở khóa') . ' tài khoản thành công.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
            $_SESSION['flash_message'] = 'Đã xóa tài khoản vĩnh viễn khỏi hệ thống.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: users.php');
    exit;
}

$role_filter = $_GET['role'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($role_filter !== 'all' && in_array($role_filter, ['candidate', 'employer', 'admin'])) {
    $sql .= " AND role = :role";
    $params[':role'] = $role_filter;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$total_count = count($users);
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
                <span class="section-eyebrow">Dữ liệu tài khoản</span>
                <h1 class="section-title">Danh sách người dùng</h1>
                <p class="section-desc">Tổng số <strong><?= $total_count ?></strong> tài khoản trong hệ thống JobFinder.</p>
            </div>
            <a href="user-create.php" class="btn btn-primary">+ Thêm người dùng mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm & lọc -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1.5fr 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm theo tên, email hoặc số điện thoại..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="role" class="search-select">
                        <option value="all" <?= ($role_filter === 'all') ? 'selected' : '' ?>>Tất cả vai trò</option>
                        <option value="candidate" <?= ($role_filter === 'candidate') ? 'selected' : '' ?>>Ứng viên (Candidate)</option>
                        <option value="employer" <?= ($role_filter === 'employer') ? 'selected' : '' ?>>Nhà tuyển dụng (Employer)</option>
                        <option value="admin" <?= ($role_filter === 'admin') ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <?php if ($role_filter !== 'all' || !empty($search)): ?>
                        <a href="users.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách người dùng -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Họ và tên</th>
                            <th>Địa chỉ Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tham gia</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($u['name']) ?>
                                        </div>
                                        <?php if (!empty($u['phone'])): ?>
                                            <div style="font-size:12px; color:var(--muted);">📱 <?= htmlspecialchars($u['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($u['email']) ?>" style="text-decoration:underline;">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge badge--danger">ADMIN</span>
                                        <?php elseif ($u['role'] === 'employer'): ?>
                                            <span class="badge badge--employer">DOANH NGHIỆP</span>
                                        <?php else: ?>
                                            <span class="badge badge--active">ỨNG VIÊN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$u['is_active'] === 1): ?>
                                            <span style="color:var(--status-success-text); font-weight:700; font-size:12px;">● Đang hoạt động</span>
                                        <?php else: ?>
                                            <span style="color:var(--status-danger-text); font-weight:700; font-size:12px;">● Đã tạm khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <a href="user-edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-secondary btn-sm">Sửa</a>

                                            <?php if ((int)$u['id'] !== $admin_id): ?>
                                                <!-- Toggle Lock -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_lock">
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" style="font-size:12px;">
                                                        <?= ((int)$u['is_active'] === 1) ? 'Khóa' : 'Mở khóa' ?>
                                                    </button>
                                                </form>

                                                <!-- Delete -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này? Toàn bộ dữ liệu liên quan sẽ bị xóa.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" style="font-size:12px;">Xóa</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy tài khoản nào phù hợp với điều kiện tìm kiếm.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>

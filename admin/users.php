<?php
/**
 * ============================================================
 * FILE: admin/users.php
 * MÔ TẢ: Quản lý người dùng toàn hệ thống (CRUD) — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Người Dùng';

// Xử lý POST (toggle lock, delete, đổi vai trò)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_id = (int)($_POST['user_id'] ?? 0);

    if ($target_id > 0) {
        if ($target_id === $admin_id && in_array($action, ['toggle_lock', 'delete', 'change_role'])) {
            $_SESSION['flash_message'] = 'Không thể thao tác trên chính tài khoản Quản trị viên đang đăng nhập.';
            $_SESSION['flash_type'] = 'danger';
        } elseif ($action === 'toggle_lock') {
            $curr = (int)$pdo->query("SELECT is_active FROM users WHERE id = {$target_id}")->fetchColumn();
            $new_val = ($curr === 1) ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_val, $target_id]);
            log_activity($pdo, $admin_id, 'USER_LOCK_TOGGLE', "Đổi trạng thái tài khoản ID #{$target_id} thành " . ($new_val ? 'Kích hoạt' : 'Khóa'));
            $_SESSION['flash_message'] = 'Đã ' . ($new_val === 0 ? 'tạm khóa' : 'mở khóa') . ' tài khoản thành công.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'change_role') {
            $new_role = $_POST['new_role'] ?? '';
            if (in_array($new_role, ['candidate', 'employer', 'admin'])) {
                $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $target_id]);
                // Nếu đổi sang employer và chưa có profile, tạo profile tự động
                if ($new_role === 'employer') {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM employer_profiles WHERE user_id = ?");
                    $chk->execute([$target_id]);
                    if ((int)$chk->fetchColumn() === 0) {
                        $u_name = $pdo->query("SELECT name FROM users WHERE id = {$target_id}")->fetchColumn();
                        $pdo->prepare("INSERT INTO employer_profiles (user_id, company_name, is_verified, created_at) VALUES (?, ?, 0, NOW())")->execute([$target_id, $u_name . ' Company']);
                    }
                }
                log_activity($pdo, $admin_id, 'USER_ROLE_CHANGE', "Đổi vai trò người dùng ID #{$target_id} thành {$new_role}");
                $_SESSION['flash_message'] = 'Đã cập nhật vai trò người dùng thành "' . $new_role . '".';
                $_SESSION['flash_type'] = 'success';
            }
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
            log_activity($pdo, $admin_id, 'USER_DELETE', "Xóa tài khoản người dùng ID #{$target_id}");
            $_SESSION['flash_message'] = 'Đã xóa tài khoản vĩnh viễn khỏi hệ thống.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: users.php');
    exit;
}

$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$sql_where = " WHERE 1=1";
$params = [];

if ($role_filter !== 'all' && in_array($role_filter, ['candidate', 'employer', 'admin'])) {
    $sql_where .= " AND u.role = :role";
    $params[':role'] = $role_filter;
}

if ($status_filter === 'active') {
    $sql_where .= " AND u.is_active = 1";
} elseif ($status_filter === 'locked') {
    $sql_where .= " AND u.is_active = 0";
}

if (!empty($search)) {
    $sql_where .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// Đếm tổng số để phân trang
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u {$sql_where}");
$count_stmt->execute($params);
$total_users = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit) ?: 1;

// Lấy danh sách kèm thống kê
$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as app_count,
           (SELECT COUNT(*) FROM cvs WHERE user_id = u.id) as cv_count,
           (SELECT COUNT(*) FROM jobs WHERE employer_id = u.id) as job_count,
           ep.company_name, ep.is_verified
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    {$sql_where}
    ORDER BY u.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1 class="section-title">Danh sách người dùng</h1>
                <p class="section-desc">Tổng số <strong><?= number_format($total_users) ?></strong> tài khoản trong hệ thống JobFinder.</p>
            </div>
            <a href="user-create.php" class="btn btn-primary">+ Thêm người dùng mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm & lọc nâng cao -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1.5fr 1fr 1fr auto;">
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

                <div class="search-field">
                    <select name="status" class="search-select">
                        <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Tất cả trạng thái</option>
                        <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Đang hoạt động</option>
                        <option value="locked" <?= ($status_filter === 'locked') ? 'selected' : '' ?>>Bị tạm khóa</option>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <?php if ($role_filter !== 'all' || $status_filter !== 'all' || !empty($search)): ?>
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
                            <th style="width:40px;">#</th>
                            <th>Họ và tên</th>
                            <th>Liên hệ</th>
                            <th>Vai trò & Đổi quyền</th>
                            <th>Thống kê hoạt động</th>
                            <th>Trạng thái</th>
                            <th>Ngày tham gia</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($u['name']) ?>
                                            <?php if ($u['id'] === $admin_id): ?>
                                                <span class="badge" style="background:#0B1F17; color:#99FF99; font-size:10px;">Bạn</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($u['role'] === 'employer' && !empty($u['company_name'])): ?>
                                            <div style="font-size:12px; color:var(--muted);">
                                                <?= htmlspecialchars($u['company_name']) ?>
                                                <?php if (!empty($u['is_verified'])): ?>
                                                    <span style="color:#059669; font-weight:700;">✓</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <div>Email: <?= htmlspecialchars($u['email']) ?></div>
                                        <div>SĐT: <?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '—' ?></div>
                                    </td>
                                    <td>
                                        <?php if ($u['id'] === $admin_id): ?>
                                            <span class="badge badge--admin">Admin</span>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                <select name="new_role" class="form-control form-select" style="padding:4px 24px 4px 8px; font-size:12px; height:32px; font-weight:600;" onchange="if(confirm('Bạn có chắc chắn muốn đổi vai trò của người dùng này?')) this.form.submit();">
                                                    <option value="candidate" <?= ($u['role'] === 'candidate') ? 'selected' : '' ?>>Ứng viên</option>
                                                    <option value="employer" <?= ($u['role'] === 'employer') ? 'selected' : '' ?>>Doanh nghiệp</option>
                                                    <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>Quản trị viên</option>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-sm">
                                        <?php if ($u['role'] === 'candidate'): ?>
                                            <span><?= $u['app_count'] ?> hồ sơ đã nộp</span> &middot; 
                                            <span class="text-muted"><?= $u['cv_count'] ?> CV</span>
                                        <?php elseif ($u['role'] === 'employer'): ?>
                                            <span style="font-weight:700;"><?= $u['job_count'] ?></span> tin tuyển dụng
                                        <?php else: ?>
                                            <span class="text-muted">Quản trị toàn quyền</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$u['is_active'] === 1): ?>
                                            <span class="badge badge--active">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge badge--danger">Bị tạm khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <a href="user-edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">Sửa</a>
                                            
                                            <?php if ($u['id'] !== $admin_id): ?>
                                                <!-- Toggle lock -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_lock">
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" title="<?= ((int)$u['is_active'] === 1) ? 'Khóa tài khoản' : 'Mở khóa' ?>">
                                                        <?= ((int)$u['is_active'] === 1) ? 'Khóa' : 'Mở' ?>
                                                    </button>
                                                </form>

                                                <!-- Delete button -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn người dùng <?= htmlspecialchars(addslashes($u['name'])) ?>? Thao tác này sẽ xóa toàn bộ dữ liệu liên quan!');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy người dùng nào phù hợp với bộ lọc.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG -->
            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-top:1px solid var(--line);">
                    <div style="font-size:13px; color:var(--muted);">
                        Hiển thị trang <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong> (Tổng <?= $total_users ?> tài khoản)
                    </div>
                    <div style="display:flex; gap:8px;">
                        <?php if ($page > 1): ?>
                            <a href="users.php?page=<?= $page - 1 ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">← Trang trước</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="users.php?page=<?= $page + 1 ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">Trang sau →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>

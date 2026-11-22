<?php
/**
 * ============================================================
 * FILE: admin/employers.php
 * MÔ TẢ: Quản lý và kiểm duyệt tài khoản Nhà tuyển dụng
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Quản Lý Doanh Nghiệp';

// Xử lý POST (Xác minh / Khóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        if ($action === 'toggle_verify') {
            $stmt = $pdo->prepare("SELECT is_verified FROM employer_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $is_verified = (int)$stmt->fetchColumn();
            $new_val = ($is_verified === 1) ? 0 : 1;

            $pdo->prepare("UPDATE employer_profiles SET is_verified = ? WHERE user_id = ?")->execute([$new_val, $user_id]);
            $_SESSION['flash_message'] = 'Đã ' . ($new_val === 1 ? 'xác minh chính thức' : 'hủy xác minh') . ' doanh nghiệp.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'toggle_lock') {
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $is_active = (int)$stmt->fetchColumn();
            $new_val = ($is_active === 1) ? 0 : 1;

            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_val, $user_id]);
            $_SESSION['flash_message'] = 'Đã ' . ($new_val === 0 ? 'tạm khóa' : 'mở khóa') . ' tài khoản doanh nghiệp.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: employers.php');
    exit;
}

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.id as user_id, u.name as contact_name, u.email as contact_email, u.phone as contact_phone,
           u.is_active, u.created_at as registered_at,
           ep.id as profile_id, ep.company_name, ep.website, ep.industry, ep.size, ep.is_verified,
           COUNT(j.id) as total_jobs
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    LEFT JOIN jobs j ON j.employer_id = u.id
    WHERE u.role = 'employer'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR ep.company_name LIKE :search OR ep.industry LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employers = $stmt->fetchAll();
$total_count = count($employers);
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
                <span class="section-eyebrow">Đối tác doanh nghiệp</span>
                <h1 class="section-title">Danh sách Nhà tuyển dụng</h1>
                <p class="section-desc">Tổng số <strong><?= $total_count ?></strong> đơn vị doanh nghiệp đăng ký tuyển dụng trên nền tảng.</p>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm theo tên công ty, người đại diện hoặc email..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <?php if (!empty($search)): ?>
                        <a href="employers.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách doanh nghiệp -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Tên công ty & Đại diện</th>
                            <th>Lĩnh vực & Quy mô</th>
                            <th>Website</th>
                            <th>Số tin đăng</th>
                            <th>Xác minh</th>
                            <th>Trạng thái TK</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employers)): ?>
                            <?php foreach ($employers as $i => $emp): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($emp['company_name'] ?: $emp['contact_name']) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            Đại diện: <?= htmlspecialchars($emp['contact_name']) ?> (<?= htmlspecialchars($emp['contact_email']) ?>)
                                        </div>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= htmlspecialchars($emp['industry'] ?: 'Chưa cập nhật') ?>
                                        <br>
                                        <span style="font-size:11px;"><?= htmlspecialchars($emp['size'] ?: 'Chưa rõ quy mô') ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($emp['website'])): ?>
                                            <a href="<?= htmlspecialchars($emp['website']) ?>" target="_blank" style="text-decoration:underline; font-size:13px;">
                                                Truy cập ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:700;">
                                        <?= (int)$emp['total_jobs'] ?> tin
                                    </td>
                                    <td>
                                        <?php if ((int)$emp['is_verified'] === 1): ?>
                                            <span class="badge badge--success">ĐÃ XÁC MINH ✓</span>
                                        <?php else: ?>
                                            <span class="badge badge--pending">CHƯA XÁC MINH</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$emp['is_active'] === 1): ?>
                                            <span style="color:var(--status-success-text); font-weight:700; font-size:12px;">● Hoạt động</span>
                                        <?php else: ?>
                                            <span style="color:var(--status-danger-text); font-weight:700; font-size:12px;">● Đã khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <!-- Toggle verify -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_verify">
                                                <input type="hidden" name="user_id" value="<?= (int)$emp['user_id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:12px;">
                                                    <?= ((int)$emp['is_verified'] === 1) ? 'Bỏ xác minh' : 'Xác minh ✓' ?>
                                                </button>
                                            </form>

                                            <!-- Toggle lock -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_lock">
                                                <input type="hidden" name="user_id" value="<?= (int)$emp['user_id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:12px;">
                                                    <?= ((int)$emp['is_active'] === 1) ? 'Khóa' : 'Mở' ?>
                                                </button>
                                            </form>

                                            <a href="user-edit.php?id=<?= (int)$emp['user_id'] ?>" class="btn btn-ghost btn-sm">Sửa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy dữ liệu nhà tuyển dụng nào.
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

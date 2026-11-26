<?php
/**
 * ============================================================
 * FILE: admin/employers.php
 * MÔ TẢ: Quản lý và kiểm duyệt tài khoản Nhà tuyển dụng & Hồ sơ pháp lý
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Doanh Nghiệp';

// Xử lý POST (Xác minh / Hủy xác minh / Khóa / Xóa)
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
            log_activity($pdo, $admin_id, 'EMPLOYER_VERIFY_TOGGLE', "Đổi cờ xác thực doanh nghiệp User ID #{$user_id} thành {$new_val}");

            if ($new_val === 1) {
                create_notification($pdo, $user_id, 'Hồ sơ doanh nghiệp đã được xác thực', 'Chúc mừng! Doanh nghiệp của bạn đã được Quản trị viên cấp huy hiệu Xác thực chính thức.', 'employer/profile.php');
            }

            $_SESSION['flash_message'] = 'Đã ' . ($new_val === 1 ? 'xác minh chính thức' : 'hủy xác minh') . ' doanh nghiệp.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'toggle_lock') {
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $is_active = (int)$stmt->fetchColumn();
            $new_val = ($is_active === 1) ? 0 : 1;

            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_val, $user_id]);
            log_activity($pdo, $admin_id, 'EMPLOYER_LOCK_TOGGLE', "Đổi trạng thái khóa doanh nghiệp User ID #{$user_id} thành " . ($new_val ? 'Kích hoạt' : 'Khóa'));
            $_SESSION['flash_message'] = 'Đã ' . ($new_val === 0 ? 'tạm khóa' : 'mở khóa') . ' tài khoản doanh nghiệp.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            log_activity($pdo, $admin_id, 'EMPLOYER_DELETE', "Xóa doanh nghiệp User ID #{$user_id}");
            $_SESSION['flash_message'] = 'Đã xóa toàn bộ tài khoản và hồ sơ doanh nghiệp khỏi hệ thống.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header('Location: employers.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$verify_filter = $_GET['verify'] ?? 'all';

$sql = "
    SELECT u.id as user_id, u.name as contact_name, u.email as contact_email, u.phone as contact_phone,
           u.is_active, u.created_at as registered_at,
           ep.id as profile_id, ep.company_name, ep.company_logo, ep.website, ep.industry, ep.size, 
           ep.address, ep.tax_code, ep.license_document, ep.description, ep.is_verified,
           COUNT(j.id) as total_jobs
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    LEFT JOIN jobs j ON j.employer_id = u.id
    WHERE u.role = 'employer'
";
$params = [];

if ($verify_filter === 'verified') {
    $sql .= " AND ep.is_verified = 1";
} elseif ($verify_filter === 'unverified') {
    $sql .= " AND (ep.is_verified = 0 OR ep.is_verified IS NULL)";
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR ep.company_name LIKE :search OR ep.industry LIKE :search OR ep.tax_code LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " GROUP BY u.id ORDER BY ep.is_verified ASC, u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 31, 23, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 600px;
            padding: 28px;
            box-shadow: var(--shadow-modal);
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title">Danh sách Nhà tuyển dụng</h1>
                <p class="section-desc">Quản lý và kiểm duyệt pháp lý <strong><?= $total_count ?></strong> tài khoản doanh nghiệp.</p>
            </div>
            <a href="user-create.php" class="btn btn-primary">+ Thêm doanh nghiệp mới</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm & lọc -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 2fr 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm theo tên công ty, mã số thuế, email hoặc người phụ trách..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="search-field">
                    <select name="verify" class="search-select">
                        <option value="all" <?= ($verify_filter === 'all') ? 'selected' : '' ?>>Tất cả trạng thái xác thực</option>
                        <option value="verified" <?= ($verify_filter === 'verified') ? 'selected' : '' ?>>Đã xác minh chính thức</option>
                        <option value="unverified" <?= ($verify_filter === 'unverified') ? 'selected' : '' ?>>Chưa xác minh</option>
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <?php if ($verify_filter !== 'all' || !empty($search)): ?>
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
                            <th>Doanh nghiệp</th>
                            <th>Người phụ trách & Liên hệ</th>
                            <th>Mã số thuế & Pháp lý</th>
                            <th>Tin đăng</th>
                            <th>Xác thực</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employers)): ?>
                            <?php foreach ($employers as $i => $e): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:800; color:var(--ink);">
                                            <?= htmlspecialchars($e['company_name'] ?: $e['contact_name']) ?>
                                            <?php if (!empty($e['is_verified'])): ?>
                                                <span title="Doanh nghiệp đã xác thực" style="color:#059669; font-weight:800; margin-left:2px;">✓</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            <?= htmlspecialchars($e['industry'] ?: 'Chưa cập nhật ngành') ?> &middot; <?= htmlspecialchars($e['size'] ?: 'Quy mô N/A') ?>
                                        </div>
                                        <?php if (!empty($e['website'])): ?>
                                            <a href="<?= htmlspecialchars($e['website']) ?>" target="_blank" style="font-size:11px; text-decoration:underline; color:var(--text);">
                                                <?= htmlspecialchars($e['website']) ?> ↗
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <div style="font-weight:600; color:var(--ink);"><?= htmlspecialchars($e['contact_name']) ?></div>
                                        <div>Email: <?= htmlspecialchars($e['contact_email']) ?></div>
                                        <div>SĐT: <?= !empty($e['contact_phone']) ? htmlspecialchars($e['contact_phone']) : '—' ?></div>
                                    </td>
                                    <td class="text-sm">
                                        <?php if (!empty($e['tax_code'])): ?>
                                            <span class="chip" style="font-weight:700;">MST: <?= htmlspecialchars($e['tax_code']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa nộp</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="jobs.php?employer_id=<?= (int)$e['user_id'] ?>" style="font-weight:700; color:var(--ink); text-decoration:underline;">
                                            <?= (int)$e['total_jobs'] ?> tin đăng →
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($e['is_verified'])): ?>
                                            <span class="badge badge--active">Đã xác minh</span>
                                        <?php else: ?>
                                            <span class="badge badge--pending">Chưa xác minh</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$e['is_active'] === 1): ?>
                                            <span class="badge badge--active">Kích hoạt</span>
                                        <?php else: ?>
                                            <span class="badge badge--danger">Tạm khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <!-- Xem chi tiết modal -->
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="showCompanyModal(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)">
                                                Hồ sơ
                                            </button>

                                            <!-- Toggle verify -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_verify">
                                                <input type="hidden" name="user_id" value="<?= (int)$e['user_id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" title="<?= (!empty($e['is_verified'])) ? 'Hủy xác minh' : 'Xác thực ngay' ?>">
                                                    <?= (!empty($e['is_verified'])) ? 'Hủy xác thực' : 'Xác thực ✓' ?>
                                                </button>
                                            </form>

                                            <!-- Toggle lock -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_lock">
                                                <input type="hidden" name="user_id" value="<?= (int)$e['user_id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm" title="<?= ((int)$e['is_active'] === 1) ? 'Khóa tài khoản' : 'Mở khóa' ?>">
                                                    <?= ((int)$e['is_active'] === 1) ? 'Khóa' : 'Mở' ?>
                                                </button>
                                            </form>

                                            <!-- Delete -->
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa doanh nghiệp này cùng toàn bộ tin đăng?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= (int)$e['user_id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy doanh nghiệp nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- MODAL XEM CHI TIẾT DOANH NGHIỆP -->
<div class="modal-overlay" id="companyModal">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size:18px; font-weight:800; color:var(--ink);" id="modalCName">Chi tiết doanh nghiệp</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeCompanyModal()">✕</button>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; font-size:13px;">
            <div><strong>Ngành nghề:</strong> <span id="modalIndustry">—</span></div>
            <div><strong>Quy mô:</strong> <span id="modalSize">—</span></div>
            <div><strong>Website:</strong> <span id="modalWebsite">—</span></div>
            <div><strong>Mã số thuế:</strong> <span id="modalTaxCode">—</span></div>
            <div style="grid-column: span 2;"><strong>Địa chỉ trụ sở:</strong> <span id="modalAddress">—</span></div>
        </div>

        <div style="margin-bottom:16px;">
            <strong style="font-size:13px;">Giới thiệu công ty:</strong>
            <p id="modalDesc" style="font-size:13px; color:var(--text); line-height:1.6; margin-top:4px; padding:12px; background:#F5F5F7; border-radius:8px;"></p>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn btn-secondary" onclick="closeCompanyModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
function showCompanyModal(data) {
    document.getElementById('modalCName').innerText = data.company_name || data.contact_name;
    document.getElementById('modalIndustry').innerText = data.industry || 'Chưa cập nhật';
    document.getElementById('modalSize').innerText = data.size || 'Chưa cập nhật';
    document.getElementById('modalWebsite').innerText = data.website || 'Chưa cập nhật';
    document.getElementById('modalTaxCode').innerText = data.tax_code || 'Chưa có';
    document.getElementById('modalAddress').innerText = data.address || 'Chưa cập nhật';
    document.getElementById('modalDesc').innerText = data.description || 'Chưa có thông tin giới thiệu.';
    document.getElementById('companyModal').classList.add('active');
}
function closeCompanyModal() {
    document.getElementById('companyModal').classList.remove('active');
}
</script>

</body>
</html>

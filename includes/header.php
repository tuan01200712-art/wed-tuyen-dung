<?php
/**
 * FILE: includes/header.php
 * Thanh điều hướng gọn gàng theo đúng vai trò người dùng
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? ($_SESSION['role'] ?? 'candidate') : '';

$unread_notifs = 0;
$latest_notifs = [];
if ($is_logged_in) {
    if (!isset($pdo)) { @include_once __DIR__ . '/../config/database.php'; }
    if (isset($pdo)) {
        try {
            $n_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $n_stmt->execute([$_SESSION['user_id']]);
            $unread_notifs = (int)$n_stmt->fetchColumn();

            $nl_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $nl_stmt->execute([$_SESSION['user_id']]);
            $latest_notifs = $nl_stmt->fetchAll();
        } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — JobFinder' : 'JobFinder — Tìm việc làm & Tuyển dụng'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">

    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="jf-toast-container" id="jfToastContainer"></div>

<header class="navbar" id="navbar">
    <div class="container nav-container">
        <a href="index.php" class="logo" title="JobFinder — Trang chủ">
            <img src="assets/images/logo.svg" alt="JobFinder" class="logo-img" height="40">
        </a>

        <button class="mobile-toggle" id="mobileToggle" aria-label="Mở menu điều hướng" aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <nav class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li>
                    <a href="jobs.php" class="nav-link <?php echo in_array($current_page, ['jobs.php','job-detail.php']) ? 'active' : ''; ?>">
                        Việc làm
                    </a>
                </li>

                <?php if (!$is_logged_in): ?>
                    <li>
                        <a href="cv-builder.php" class="nav-link <?php echo $current_page === 'cv-builder.php' ? 'active' : ''; ?>">
                            Tạo CV
                        </a>
                    </li>
                <?php elseif ($user_role === 'candidate'): ?>
                    <li>
                        <a href="my-cvs.php" class="nav-link <?php echo in_array($current_page, ['my-cvs.php','cv-builder.php','cv-preview.php']) ? 'active' : ''; ?>">
                            Hồ sơ & CV
                        </a>
                    </li>
                    <li>
                        <a href="my-applications.php" class="nav-link <?php echo $current_page === 'my-applications.php' ? 'active' : ''; ?>">
                            Đã ứng tuyển
                        </a>
                    </li>
                <?php elseif ($user_role === 'employer'): ?>
                    <li>
                        <a href="employer/dashboard.php" class="nav-link">
                            Quản lý tuyển dụng
                        </a>
                    </li>
                <?php elseif ($user_role === 'admin'): ?>
                    <li>
                        <a href="admin/dashboard.php" class="nav-link">
                            Trang Quản trị
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="nav-auth">
                <?php if ($is_logged_in): ?>
                    <div class="user-dropdown" id="notifDropdown" style="position:relative;">
                        <button class="user-btn" id="notifBtn" type="button" aria-expanded="false" title="Thông báo" style="padding:7px 9px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            <?php if ($unread_notifs > 0): ?>
                                <span style="position:absolute; top:-4px; right:-4px; background:var(--status-danger-text); color:#fff; font-family:var(--font-mono); font-size:9px; font-weight:700; border-radius:50%; width:16px; height:16px; display:flex; align-items:center; justify-content:center; border:2px solid #fff;"><?php echo $unread_notifs; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-card" id="notifMenu" style="width:300px; right:0; padding:10px; max-height:340px; overflow-y:auto;">
                            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--muted); padding-bottom:8px; border-bottom:1px solid var(--line); margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                                <span>Thông báo</span>
                                <?php if ($unread_notifs > 0): ?>
                                    <span class="badge badge--danger" style="font-size:9px;"><?php echo $unread_notifs; ?> mới</span>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($latest_notifs)): ?>
                                <div style="padding:16px 8px; text-align:center; font-size:12px; color:var(--muted);">Không có thông báo mới</div>
                            <?php else: ?>
                                <div style="display:flex; flex-direction:column; gap:5px;">
                                    <?php foreach ($latest_notifs as $nt): ?>
                                        <a href="<?php echo !empty($nt['link']) ? htmlspecialchars($nt['link']) : '#'; ?>" style="display:flex; gap:8px; align-items:flex-start; padding:8px 10px; border-radius:var(--radius-sm); background:<?php echo $nt['is_read'] ? 'transparent' : 'var(--brand-light)'; ?>; border:1px solid <?php echo $nt['is_read'] ? 'transparent' : 'var(--brand-mid)'; ?>; font-size:12px; color:var(--ink); text-decoration:none;">
                                            <span style="width:6px; height:6px; border-radius:50%; margin-top:5px; flex-shrink:0; background:<?php echo $nt['is_read'] ? 'var(--line)' : 'var(--brand)'; ?>;"></span>
                                            <div style="flex:1; min-width:0;">
                                                <div style="font-weight:600; margin-bottom:1px;"><?php echo htmlspecialchars($nt['title']); ?></div>
                                                <div style="color:var(--muted); font-size:11px; line-height:1.4;"><?php echo htmlspecialchars(mb_substr($nt['message'], 0, 72)); ?><?php echo mb_strlen($nt['message']) > 72 ? '…' : ''; ?></div>
                                                <div style="font-size:10px; color:var(--muted-light); margin-top:3px; font-family:var(--font-mono);"><?php echo date('H:i d/m', strtotime($nt['created_at'])); ?></div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="user-dropdown" id="userDropdown">
                        <button class="user-btn" id="userBtn" type="button" aria-expanded="false">
                            <span class="user-avatar-mini"><?php echo mb_strtoupper(mb_substr($user_name, 0, 1)); ?></span>
                            <span class="user-name-text"><?php echo htmlspecialchars($user_name); ?></span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div class="dropdown-card" id="dropdownMenu">
                            <?php if ($user_role === 'candidate'): ?>
                                <a href="profile.php" class="dropdown-item">Hồ sơ cá nhân</a>
                                <a href="saved-jobs.php" class="dropdown-item">Việc đã lưu</a>
                            <?php elseif ($user_role === 'employer'): ?>
                                <a href="employer/job-create.php" class="dropdown-item">Đăng tin mới</a>
                                <a href="employer/profile.php" class="dropdown-item">Hồ sơ công ty</a>
                            <?php elseif ($user_role === 'admin'): ?>
                                <a href="admin/dashboard.php" class="dropdown-item">Trang Quản trị</a>
                            <?php endif; ?>

                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item" style="color:var(--status-danger-text);">
                                <span>Đăng xuất</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-ghost btn-sm <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
                        Đăng nhập
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm">
                        Đăng ký
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">

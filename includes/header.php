<?php
/**
 * ============================================================
 * FILE: includes/header.php
 * MÔ TẢ: Header thanh điều hướng kính mờ (Glassmorphism) chuẩn Apple
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? ($_SESSION['role'] ?? 'candidate') : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — JobFinder' : 'JobFinder — Cổng Cơ Hội Việc Làm Hiện Đại'; ?></title>

    <!-- Google Fonts: Plus Jakarta Sans & Inter (Clean High-Tech Sans-Serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="logo">
            <span class="logo-badge">JF</span>
            <span>Jobfinder</span>
        </a>

        <button class="mobile-toggle" id="mobileToggle" aria-label="Mở Menu Điều Hướng">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <nav class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li>
                    <a href="index.php" class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                        Khám phá
                    </a>
                </li>
                <li>
                    <a href="jobs.php" class="nav-link <?php echo ($current_page === 'jobs.php' || $current_page === 'job-detail.php') ? 'active' : ''; ?>">
                        Việc làm
                    </a>
                </li>

                <?php if ($is_logged_in && $user_role === 'candidate'): ?>
                    <li>
                        <a href="my-applications.php" class="nav-link <?php echo ($current_page === 'my-applications.php') ? 'active' : ''; ?>">
                            Đã ứng tuyển
                        </a>
                    </li>
                    <li>
                        <a href="my-cvs.php" class="nav-link <?php echo ($current_page === 'my-cvs.php' || $current_page === 'cv-builder.php') ? 'active' : ''; ?>">
                            Hồ sơ CV
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($is_logged_in && $user_role === 'employer'): ?>
                    <li>
                        <a href="employer/dashboard.php" class="nav-link">
                            Bảng điều khiển NTD
                        </a>
                    </li>
                    <li>
                        <a href="employer/job-create.php" class="nav-link">
                            + Đăng tin mới
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($is_logged_in && $user_role === 'admin'): ?>
                    <li>
                        <a href="admin/dashboard.php" class="nav-link">
                            Trang quản trị (Admin)
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="nav-auth">
                <?php if ($is_logged_in): ?>
                    <div class="user-dropdown" id="userDropdown">
                        <button class="user-btn" id="userBtn" type="button" aria-expanded="false">
                            <span class="user-avatar-mini">
                                <?php echo mb_strtoupper(mb_substr(htmlspecialchars($user_name), 0, 1)); ?>
                            </span>
                            <span class="user-name-text"><?php echo htmlspecialchars($user_name); ?></span>
                            <?php if ($user_role === 'employer'): ?>
                                <span class="badge badge--employer" style="font-size:10px; padding:2px 6px;">NTD</span>
                            <?php elseif ($user_role === 'admin'): ?>
                                <span class="badge badge--danger" style="font-size:10px; padding:2px 6px;">ADMIN</span>
                            <?php endif; ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div class="dropdown-card" id="dropdownMenu">
                            <?php if ($user_role === 'candidate'): ?>
                                <a href="my-applications.php" class="dropdown-item">
                                    Việc làm đã nộp
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </a>
                                <a href="my-cvs.php" class="dropdown-item">
                                    Quản lý hồ sơ CV
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </a>
                                <a href="profile.php" class="dropdown-item">
                                    Cài đặt tài khoản
                                </a>
                            <?php elseif ($user_role === 'employer'): ?>
                                <a href="employer/dashboard.php" class="dropdown-item">
                                    Kênh Nhà tuyển dụng
                                </a>
                                <a href="employer/jobs.php" class="dropdown-item">
                                    Quản lý tin tuyển dụng
                                </a>
                                <a href="employer/applications.php" class="dropdown-item">
                                    Danh sách ứng viên
                                </a>
                                <a href="employer/profile.php" class="dropdown-item">
                                    Hồ sơ công ty
                                </a>
                            <?php elseif ($user_role === 'admin'): ?>
                                <a href="admin/dashboard.php" class="dropdown-item">
                                    Bảng điều khiển Admin
                                </a>
                                <a href="admin/users.php" class="dropdown-item">
                                    Quản lý người dùng
                                </a>
                                <a href="admin/jobs.php" class="dropdown-item">
                                    Kiểm duyệt tin đăng
                                </a>
                            <?php endif; ?>

                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item" style="color:var(--status-danger-text);">
                                Đăng xuất
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-ghost btn-sm <?php echo ($current_page === 'login.php') ? 'active' : ''; ?>">
                        Đăng nhập
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm <?php echo ($current_page === 'register.php') ? 'active' : ''; ?>">
                        Đăng ký tài khoản
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">

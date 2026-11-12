<?php

/**
 * ============================================================
 * FILE: includes/header.php
 * MÔ TẢ: Phần đầu trang web, thanh điều hướng (Navbar) & quản lý Session
 * ============================================================
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy tên trang hiện tại để gán class active vào menu
$current_page = basename($_SERVER['PHP_SELF']);

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - JobFinder' : 'JobFinder - Website Tuyển Dụng Việc Làm Hàng Đầu'; ?></title>

    <!-- Google Fonts (Be Vietnam Pro cho tiếng Việt đẹp mắt) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- CSS Tùy biến chính của dự án -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Header & Navigation Bar -->
    <header class="navbar">
        <div class="container nav-container">
            <!-- Logo -->
            <a href="index.php" class="logo">
                <span class="logo-icon"><i class="fa-solid fa-briefcase"></i></span>
                <span class="logo-text">Job<span>Finder</span></span>
            </a>

            <!-- Nút bật menu mobile -->
            <button class="mobile-toggle" id="mobileToggle" aria-label="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Menu chính -->
            <nav class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li>
                        <a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-house"></i> Trang chủ
                        </a>
                    </li>
                    <li>
                        <a href="jobs.php" class="<?php echo ($current_page === 'jobs.php' || $current_page === 'job-detail.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-magnifying-glass"></i> Việc làm
                        </a>
                    </li>
                </ul>

                <!-- Khu vực tài khoản / Đăng nhập -->
                <div class="nav-auth">
                    <?php if ($is_logged_in): ?>
                        <div class="user-dropdown">
                            <button class="user-btn" id="userBtn">
                                <span class="avatar-circle">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                            </button>
                            <div class="dropdown-menu" id="dropdownMenu">
                                <a href="my-applications.php" class="<?php echo ($current_page === 'my-applications.php') ? 'active' : ''; ?>">
                                    <i class="fa-solid fa-file-lines"></i> Việc đã ứng tuyển
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="logout-link">
                                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline <?php echo ($current_page === 'login.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                        </a>
                        <a href="register.php" class="btn btn-primary <?php echo ($current_page === 'register.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-user-plus"></i> Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Thông báo nhanh Session Flash Message (nếu có) -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container flash-container">
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>">
                <i class="fa-solid <?php echo ($_SESSION['flash_type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <span><?php echo htmlspecialchars($_SESSION['flash_message']); ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
            </div>
        </div>
        <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <!-- Main Content Wrapper -->
    <main class="main-content">
<?php

/**
 * ============================================================
 * FILE: logout.php
 * MÔ TẢ: Trang đăng xuất người dùng
 * - Hủy toàn bộ dữ liệu Session
 * - Chuyển hướng người dùng an toàn về trang chủ
 * ============================================================
 */

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa tất cả các biến session
$_SESSION = [];

// Xóa session cookie nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Khởi động lại session mới để gửi thông báo flash
session_start();
$_SESSION['flash_message'] = 'Bạn đã đăng xuất thành công.';
$_SESSION['flash_type'] = 'info';

// Chuyển hướng về trang chủ
header('Location: index.php');
exit;

<?php
/**
 * Middleware: Bảo vệ phân hệ Nhà tuyển dụng
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employer') {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập bằng tài khoản Nhà tuyển dụng.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

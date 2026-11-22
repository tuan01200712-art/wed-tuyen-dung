<?php
/**
 * Middleware: Bảo vệ phân hệ Quản trị viên (Admin)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['flash_message'] = 'Khu vực giới hạn. Yêu cầu quyền Quản trị viên.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

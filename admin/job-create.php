<?php
/**
 * ============================================================
 * FILE: admin/job-create.php
 * MÔ TẢ: Đường dẫn tắt chuyển tiếp đến tạo tin tuyển dụng Admin
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$_GET['action'] = 'create';
require_once 'job-edit.php';

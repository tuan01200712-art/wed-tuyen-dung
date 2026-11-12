<?php
/**
 * ============================================================
 * FILE: config/database.php
 * MÔ TẢ: Cấu hình kết nối cơ sở dữ liệu MySQL bằng PDO
 * ============================================================
 * 
 * Mặc định trên XAMPP:
 * - Host: localhost
 * - User: root
 * - Password: '' (trống trên Windows, trên macOS XAMPP mặc định cũng là rỗng)
 * - Database: job_portal
 * - Port: 3306
 */

$host = '127.0.0.1'; // hoặc 'localhost'
$db   = 'job_portal';
$user = 'root';
$pass = '';          // Mật khẩu mặc định của XAMPP là rỗng
$port = 3306;
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Ném Exception khi có lỗi SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trả về mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Sử dụng Prepared Statement thật của MySQL
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Nếu chưa tạo database hoặc sai mật khẩu, hiển thị thông báo thân thiện
    die("
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #f5c6cb; background: #f8d7da; color: #721c24; border-radius: 8px;'>
            <h3 style='margin-top: 0;'>❌ Lỗi kết nối Cơ Sở Dữ Liệu!</h3>
            <p><strong>Chi tiết lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr style='border: none; border-top: 1px solid #f5c6cb;'>
            <p><strong>Hướng dẫn khắc phục:</strong></p>
            <ol style='padding-left: 20px; line-height: 1.6;'>
                <li>Kiểm tra xem MySQL trong bảng điều khiển XAMPP đã được BẬT (Start) chưa.</li>
                <li>Vào <code>http://localhost/phpmyadmin</code> và đảm bảo bạn đã import file <code>database/jobs.sql</code> (Database: <strong>job_portal</strong>).</li>
                <li>Kiểm tra user và password trong file <code>config/database.php</code>.</li>
            </ol>
        </div>
    ");
}

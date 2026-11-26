<?php
/**
 * ============================================================
 * FILE: employer/interviews.php
 * MÔ TẢ: Quản lý Lịch phỏng vấn ứng viên — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Lịch Phỏng Vấn Ứng Viên';
$errors = [];

// Xử lý POST (Đổi trạng thái lịch hoặc Xóa / Hủy)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $interview_id = (int)($_POST['interview_id'] ?? 0);

    if ($interview_id > 0) {
        $chk = $pdo->prepare("SELECT i.*, j.title as job_title, j.company FROM interview_schedules i JOIN jobs j ON j.id = i.job_id WHERE i.id = ? AND i.employer_id = ?");
        $chk->execute([$interview_id, $employer_id]);
        $iv = $chk->fetch(PDO::FETCH_ASSOC);

        if ($iv) {
            if ($action === 'update_status') {
                $new_status = trim($_POST['status'] ?? '');
                if (in_array($new_status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
                    $pdo->prepare("UPDATE interview_schedules SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$new_status, $interview_id]);
                    
                    // Thông báo tới ứng viên nếu đổi sang hoàn thành hoặc hủy
                    $status_text_map = [
                        'confirmed' => 'đã được xác nhận',
                        'completed' => 'đã hoàn thành',
                        'cancelled' => 'đã bị hủy'
                    ];
                    if (isset($status_text_map[$new_status])) {
                        create_notification($pdo, $iv['candidate_id'], 'Cập nhật lịch phỏng vấn', "Lịch phỏng vấn vị trí '{$iv['job_title']}' {$status_text_map[$new_status]}.", 'my-applications.php');
                    }

                    log_activity($pdo, $employer_id, 'INTERVIEW_STATUS_UPDATE', "Cập nhật lịch #{$interview_id} sang {$new_status}");
                    $_SESSION['flash_message'] = 'Đã cập nhật trạng thái lịch phỏng vấn.';
                    $_SESSION['flash_type'] = 'success';
                }
            } elseif ($action === 'delete') {
                $pdo->prepare("DELETE FROM interview_schedules WHERE id = ?")->execute([$interview_id]);
                log_activity($pdo, $employer_id, 'INTERVIEW_DELETE', "Xóa lịch phỏng vấn #{$interview_id}");
                $_SESSION['flash_message'] = 'Đã xóa lịch phỏng vấn.';
                $_SESSION['flash_type'] = 'success';
            }
        }
    }
    header('Location: interviews.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'all';

$sql = "
    SELECT i.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title
    FROM interview_schedules i
    JOIN users u ON u.id = i.candidate_id
    JOIN jobs j ON j.id = i.job_id
    WHERE i.employer_id = :employer_id
";
$params = [':employer_id' => $employer_id];

if ($status_filter !== 'all' && in_array($status_filter, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    $sql .= " AND i.status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY i.interview_date ASC, i.interview_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_interviews = count($interviews);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Doanh Nghiệp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title">Lịch phỏng vấn tuyển dụng</h1>
                <p class="section-desc">Theo dõi các cuộc phỏng vấn Online và Trực tiếp với ứng viên.</p>
            </div>
            <a href="applications.php" class="btn btn-primary btn-sm">+ Xếp lịch phỏng vấn</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Bộ lọc trạng thái -->
        <div class="category-pills" style="margin-bottom:20px;">
            <a href="interviews.php?status=all" class="category-pill <?= ($status_filter === 'all') ? 'active' : '' ?>">Tất cả</a>
            <a href="interviews.php?status=confirmed" class="category-pill <?= ($status_filter === 'confirmed') ? 'active' : '' ?>">Đã xác nhận</a>
            <a href="interviews.php?status=pending" class="category-pill <?= ($status_filter === 'pending') ? 'active' : '' ?>">Chờ xác nhận</a>
            <a href="interviews.php?status=completed" class="category-pill <?= ($status_filter === 'completed') ? 'active' : '' ?>">Đã hoàn thành</a>
            <a href="interviews.php?status=cancelled" class="category-pill <?= ($status_filter === 'cancelled') ? 'active' : '' ?>">Đã hủy</a>
        </div>

        <!-- BẢNG LỊCH PHỎNG VẤN -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ứng viên & Liên hệ</th>
                            <th>Vị trí ứng tuyển</th>
                            <th>Thời gian phỏng vấn</th>
                            <th>Hình thức & Địa điểm / Link</th>
                            <th>Người phỏng vấn</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($interviews)): ?>
                            <?php foreach ($interviews as $iv): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($iv['candidate_name']) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            Email: <?= htmlspecialchars($iv['candidate_email']) ?>
                                            <?php if (!empty($iv['candidate_phone'])): ?>
                                                &middot; SĐT: <?= htmlspecialchars($iv['candidate_phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="font-weight:600; font-size:13px;">
                                        <?= htmlspecialchars($iv['job_title']) ?>
                                    </td>
                                    <td>
                                        <div style="font-weight:800; color:#2563EB;">
                                            <?= date('H:i', strtotime($iv['interview_time'])) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--muted);">
                                            <?= date('d/m/Y', strtotime($iv['interview_date'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="chip" style="font-size:11px; font-weight:700;">
                                            <?= ($iv['interview_type'] === 'online') ? 'Trực tuyến' : 'Trực tiếp' ?>
                                        </span>
                                        <div style="font-size:12px; margin-top:4px;">
                                            <?php if ($iv['interview_type'] === 'online'): ?>
                                                <a href="<?= htmlspecialchars($iv['location_or_link']) ?>" target="_blank" style="color:#2563EB; font-weight:700; text-decoration:underline;">
                                                    Vào phòng họp ↗
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted"><?= htmlspecialchars($iv['location_or_link']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-sm">
                                        <?= htmlspecialchars($iv['interviewer'] ?: 'HR Team') ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="interview_id" value="<?= (int)$iv['id'] ?>">
                                            <select name="status" class="form-control form-select" style="padding:4px 24px 4px 8px; font-size:12px; height:32px; font-weight:700;" onchange="this.form.submit()">
                                                <option value="confirmed" <?= ($iv['status'] === 'confirmed') ? 'selected' : '' ?>>Đã xác nhận</option>
                                                <option value="pending" <?= ($iv['status'] === 'pending') ? 'selected' : '' ?>>Chờ xác nhận</option>
                                                <option value="completed" <?= ($iv['status'] === 'completed') ? 'selected' : '' ?>>Đã hoàn thành</option>
                                                <option value="cancelled" <?= ($iv['status'] === 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <a href="application-view.php?id=<?= (int)$iv['application_id'] ?>" class="btn btn-secondary btn-sm">Xem hồ sơ</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lịch phỏng vấn này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="interview_id" value="<?= (int)$iv['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không có lịch phỏng vấn nào theo trạng thái đã chọn.
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

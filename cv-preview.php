<?php
/**
 * ============================================================
 * FILE: cv-preview.php
 * MÔ TẢ: Xem trước hồ sơ CV chuẩn in ấn — Apple Tech Clean Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'candidate';
$cv_id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$back_url = 'my-cvs.php';
$back_label = '← Quay lại danh sách CV';
if ($user_role === 'admin') {
    $back_url = 'admin/applications.php';
    $back_label = '← Quay lại Quản lý hồ sơ';
} elseif ($user_role === 'employer') {
    $back_url = 'employer/applications.php';
    $back_label = '← Quay lại Quản lý hồ sơ';
}

if ($cv_id <= 0) {
    header('Location: ' . $back_url);
    exit;
}

$cv = false;
if ($user_role === 'admin') {
    // Quản trị viên: được xem/in toàn bộ CV trên hệ thống
    $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? LIMIT 1");
    $stmt->execute([$cv_id]);
    $cv = $stmt->fetch();
} elseif ($user_role === 'employer') {
    // Nhà tuyển dụng: được xem/in nếu ứng viên đã nộp CV này vào tin của nhà tuyển dụng
    $stmt = $pdo->prepare("
        SELECT c.*
        FROM cvs c
        INNER JOIN applications a ON a.cv_id = c.id
        INNER JOIN jobs j ON j.id = a.job_id
        WHERE c.id = ? AND j.employer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$cv_id, $user_id]);
    $cv = $stmt->fetch();
} else {
    // Ứng viên: chỉ xem/in CV thuộc quyền sở hữu của mình
    $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$cv_id, $user_id]);
    $cv = $stmt->fetch();
}

if (!$cv) {
    $_SESSION['flash_message'] = 'Hồ sơ CV không tồn tại hoặc bạn không có quyền truy cập.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . $back_url);
    exit;
}

function cv_section(string $heading, ?string $content, bool $is_skills = false): void {
    if (empty(trim($content ?? ''))) return;
    echo '<section style="border-top:1px solid #e5e5ea; margin-top:28px; padding-top:20px;">';
    echo '<h2 style="font-size:12px; letter-spacing:0.08em; font-weight:800; text-transform:uppercase; margin-bottom:12px; color:#0b1f17;">' . htmlspecialchars($heading) . '</h2>';
    if ($is_skills) {
        $skills = array_filter(array_map('trim', explode(',', $content)));
        echo '<div style="display:flex; flex-wrap:wrap; gap:6px;">';
        foreach ($skills as $sk) {
            echo '<span style="display:inline-block; background:#f5f5f7; border:1px solid #e5e5ea; border-radius:9999px; padding:4px 12px; font-size:12px; font-weight:600; color:#0b1f17;">' . htmlspecialchars($sk) . '</span>';
        }
        echo '</div>';
    } else {
        echo '<div style="white-space:pre-wrap; font-size:14px; line-height:1.65; color:#3a4741;">' . htmlspecialchars($content) . '</div>';
    }
    echo '</section>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cv['full_name']) ?> — Hồ Sơ Nghề Nghiệp</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Plus Jakarta Sans", "Inter", -apple-system, sans-serif;
            background: #f5f5f7;
            color: #0b1f17;
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        .cv-actions {
            max-width: 800px;
            margin: 24px auto 0;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .btn-print {
            background: #0b1f17;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #19382c;
        }
        .btn-back {
            background: #ffffff;
            color: #0b1f17;
            border-color: #e5e5ea;
        }
        .btn-back:hover {
            background: #f0f0f4;
        }
        .cv-sheet {
            max-width: 800px;
            margin: 20px auto 60px;
            background: #ffffff;
            border: 1px solid #e5e5ea;
            border-radius: 16px;
            padding: 56px;
            box-shadow: 0 4px 20px rgba(11, 31, 23, 0.05);
        }
        @media print {
            body { background: #fff; }
            .cv-actions { display: none !important; }
            .cv-sheet {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

<div class="cv-actions">
    <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-back"><?= htmlspecialchars($back_label) ?></a>
    <button onclick="window.print()" class="btn btn-print">In / Xuất PDF</button>
</div>

<article class="cv-sheet">
    <header style="border-bottom:2px solid #0b1f17; padding-bottom:24px; margin-bottom:28px;">
        <h1 style="font-size:32px; font-weight:800; letter-spacing:-0.03em; color:#0b1f17; line-height:1.15;">
            <?= htmlspecialchars($cv['full_name']) ?>
        </h1>
        <div style="font-size:16px; font-weight:600; color:#3a4741; margin-top:6px;">
            <?= htmlspecialchars($cv['title']) ?>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:12px; font-size:13px; color:#76837e; margin-top:12px;">
            <span>Email: <?= htmlspecialchars($cv['email']) ?></span>
            <?php if (!empty($cv['phone'])): ?>
                <span>&middot;</span><span>SĐT: <?= htmlspecialchars($cv['phone']) ?></span>
            <?php endif; ?>
            <?php if (!empty($cv['address'])): ?>
                <span>&middot;</span><span><?= htmlspecialchars($cv['address']) ?></span>
            <?php endif; ?>
            <?php if (!empty($cv['cv_link'])): ?>
                <span>&middot;</span><span><a href="<?= htmlspecialchars($cv['cv_link']) ?>" target="_blank" style="color:#0b1f17; text-decoration:underline;">Link Portfolio/CV Online ↗</a></span>
            <?php endif; ?>
        </div>
    </header>

    <?php cv_section('Giới thiệu bản thân', $cv['summary']); ?>
    <?php cv_section('Học vấn', $cv['education']); ?>
    <?php cv_section('Kinh nghiệm làm việc', $cv['experience']); ?>
    <?php cv_section('Kỹ năng chuyên môn', $cv['skills'], true); ?>
    <?php cv_section('Chứng chỉ & Giải thưởng', $cv['certifications']); ?>
    <?php cv_section('Ngôn ngữ', $cv['languages']); ?>
</article>

</body>
</html>

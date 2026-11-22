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
$user_id = (int)$_SESSION['user_id'];
$cv_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cv_id <= 0) {
    header('Location: my-cvs.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND user_id = ?");
$stmt->execute([$cv_id, $user_id]);
$cv = $stmt->fetch();

if (!$cv) {
    $_SESSION['flash_message'] = 'Hồ sơ CV không tồn tại hoặc không thuộc quyền sở hữu của bạn.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: my-cvs.php');
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
    <a href="my-cvs.php" class="btn btn-back">← Quay lại danh sách CV</a>
    <button onclick="window.print()" class="btn btn-print">🖨 In / Xuất PDF</button>
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
            <span>📧 <?= htmlspecialchars($cv['email']) ?></span>
            <?php if (!empty($cv['phone'])): ?>
                <span>&middot;</span><span>📱 <?= htmlspecialchars($cv['phone']) ?></span>
            <?php endif; ?>
            <?php if (!empty($cv['address'])): ?>
                <span>&middot;</span><span>📍 <?= htmlspecialchars($cv['address']) ?></span>
            <?php endif; ?>
            <?php if (!empty($cv['cv_link'])): ?>
                <span>&middot;</span><span>🔗 <a href="<?= htmlspecialchars($cv['cv_link']) ?>" target="_blank" style="color:#0b1f17; text-decoration:underline;">Link Portfolio/CV Online ↗</a></span>
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

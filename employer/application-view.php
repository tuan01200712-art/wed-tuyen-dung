<?php
/**
 * ============================================================
 * FILE: employer/application-view.php
 * MÔ TẢ: Chi tiết ứng viên, Sàng lọc Đa vòng, Ghi chú nội bộ & Gắn nhãn — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($app_id <= 0) {
    header('Location: applications.php');
    exit;
}

// Lấy thông tin ứng tuyển
$stmt = $pdo->prepare("
    SELECT a.*, u.id as candidate_user_id, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title, j.salary as job_salary, j.location as job_location, j.company,
           c.id as cv_id, c.title as cv_title, c.summary as cv_summary, c.education as cv_education,
           c.experience as cv_experience, c.skills as cv_skills, c.certifications as cv_certifications,
           c.languages as cv_languages, c.address as cv_address, c.cv_link
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    WHERE a.id = :app_id AND j.employer_id = :employer_id
    LIMIT 1
");
$stmt->execute([':app_id' => $app_id, ':employer_id' => $employer_id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    $_SESSION['flash_message'] = 'Không tìm thấy hồ sơ ứng tuyển hoặc bạn không có quyền truy cập.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: applications.php');
    exit;
}

$stages = get_application_stages();

// Xử lý các thao tác POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Cập nhật vòng tuyển dụng & đánh giá sao
    if ($action === 'update_pipeline') {
        $new_status = trim($_POST['status'] ?? '');
        $rating = max(0, min(5, (int)($_POST['rating'] ?? 0)));

        if (isset($stages[$new_status])) {
            $pdo->prepare("UPDATE applications SET status = ?, rating = ?, stage_updated_at = NOW() WHERE id = ?")->execute([$new_status, $rating, $app_id]);
            create_notification($pdo, $app['candidate_user_id'], 'Hồ sơ đã chuyển vòng tuyển dụng', "Hồ sơ ứng tuyển vị trí '{$app['job_title']}' tại {$app['company']} đã chuyển sang vòng: " . $stages[$new_status]['name'], 'my-applications.php');
            log_activity($pdo, $employer_id, 'CANDIDATE_STAGE_UPDATE', "Cập nhật hồ sơ #{$app_id} sang vòng {$new_status}");
            $_SESSION['flash_message'] = 'Đã cập nhật vòng tuyển dụng sang "' . $stages[$new_status]['name'] . '".';
            $_SESSION['flash_type'] = 'success';
        }
    }
    // 2. Thêm ghi chú nội bộ (Chỉ NTD xem)
    elseif ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if (!empty($note)) {
            $pdo->prepare("INSERT INTO application_notes (application_id, employer_id, note, created_at) VALUES (?, ?, ?, NOW())")->execute([$app_id, $employer_id, $note]);
            $_SESSION['flash_message'] = 'Đã lưu ghi chú nội bộ thành công.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    // 3. Gắn nhãn ứng viên
    elseif ($action === 'add_tag') {
        $tag_name = trim($_POST['tag_name'] ?? '');
        $color = trim($_POST['color'] ?? '#16A36A');
        if (!empty($tag_name)) {
            $pdo->prepare("INSERT INTO application_tags (application_id, tag_name, color, created_at) VALUES (?, ?, ?, NOW())")->execute([$app_id, $tag_name, $color]);
            $_SESSION['flash_message'] = 'Đã gắn nhãn cho ứng viên.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    // 4. Xóa nhãn
    elseif ($action === 'delete_tag') {
        $tag_id = (int)($_POST['tag_id'] ?? 0);
        if ($tag_id > 0) {
            $pdo->prepare("DELETE FROM application_tags WHERE id = ? AND application_id = ?")->execute([$tag_id, $app_id]);
            $_SESSION['flash_message'] = 'Đã gỡ nhãn.';
            $_SESSION['flash_type'] = 'success';
        }
    }
    // 5. Lên lịch phỏng vấn nhanh
    elseif ($action === 'schedule_interview') {
        $iv_date = $_POST['interview_date'] ?? '';
        $iv_time = $_POST['interview_time'] ?? '';
        $iv_type = $_POST['interview_type'] ?? 'online';
        $location_or_link = trim($_POST['location_or_link'] ?? '');
        $interviewer = trim($_POST['interviewer'] ?? '');
        $notes = trim($_POST['interview_notes'] ?? '');

        if (!empty($iv_date) && !empty($iv_time) && !empty($location_or_link)) {
            $pdo->prepare("
                INSERT INTO interview_schedules (
                    application_id, employer_id, job_id, candidate_id, 
                    interview_date, interview_time, interview_type, location_or_link, 
                    interviewer, notes, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
            ")->execute([
                $app_id, $employer_id, $app['job_id'], $app['candidate_user_id'],
                $iv_date, $iv_time, $iv_type, $location_or_link,
                $interviewer, $notes
            ]);

            // Tự động chuyển hồ sơ sang vòng 'interview_scheduled'
            $pdo->prepare("UPDATE applications SET status = 'interview_scheduled', stage_updated_at = NOW() WHERE id = ?")->execute([$app_id]);

            // Thông báo cho ứng viên
            $type_text = ($iv_type === 'online') ? 'Phỏng vấn Online' : 'Phỏng vấn Trực tiếp';
            create_notification($pdo, $app['candidate_user_id'], 'Bạn có lịch phỏng vấn mới!', "{$app['company']} đã lên lịch {$type_text} vào lúc {$iv_time} ngày " . date('d/m/Y', strtotime($iv_date)), 'my-applications.php');

            log_activity($pdo, $employer_id, 'INTERVIEW_CREATE', "Lên lịch phỏng vấn cho ứng viên #{$app['candidate_user_id']} vị trí #{$app['job_id']}");
            $_SESSION['flash_message'] = 'Đã lên lịch phỏng vấn thành công và gửi thông báo tới ứng viên!';
            $_SESSION['flash_type'] = 'success';
        }
    }

    header('Location: application-view.php?id=' . $app_id);
    exit;
}

// Lấy danh sách ghi chú nội bộ
$notes = [];
try {
    $n_stmt = $pdo->prepare("SELECT * FROM application_notes WHERE application_id = ? ORDER BY created_at DESC");
    $n_stmt->execute([$app_id]);
    $notes = $n_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Lấy danh sách nhãn
$tags = [];
try {
    $t_stmt = $pdo->prepare("SELECT * FROM application_tags WHERE application_id = ? ORDER BY id ASC");
    $t_stmt->execute([$app_id]);
    $tags = $t_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Lấy lịch phỏng vấn đã xếp
$interviews = [];
try {
    $i_stmt = $pdo->prepare("SELECT * FROM interview_schedules WHERE application_id = ? ORDER BY interview_date DESC");
    $i_stmt->execute([$app_id]);
    $interviews = $i_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$page_title = 'Ứng viên: ' . $app['candidate_name'];
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
                <a href="applications.php?job_id=<?= (int)$app['job_id'] ?>" style="font-size:12px; font-weight:700; color:var(--muted); text-decoration:none;">
                    ← Quay lại danh sách hồ sơ (<?= htmlspecialchars($app['job_title']) ?>)
                </a>
                <h1 class="section-title" style="margin-top:6px;">Hồ sơ ứng viên: <?= htmlspecialchars($app['candidate_name']) ?></h1>
            </div>
            
            <div style="display:flex; gap:10px;">
                <?php if (!empty($app['cv_id'])): ?>
                    <a href="../cv-preview.php?id=<?= (int)$app['cv_id'] ?>" target="_blank" class="btn btn-secondary btn-sm">
                        📄 Mở toàn màn hình / In CV ↗
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('interviewFormBlock').scrollIntoView({behavior:'smooth'});">
                    📅 Hẹn phỏng vấn
                </button>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- BỐ CỤC 2 CỘT: CỘT TRÁI (HỒ SƠ CV & THƯ ỨNG TUYỂN) - CỘT PHẢI (PIPELINE, GHI CHÚ, NHÃN, PHỎNG VẤN) -->
        <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:24px; align-items:start;">
            
            <!-- CỘT TRÁI: CHI TIẾT HỒ SƠ ỨNG VIÊN -->
            <div>
                <!-- Thẻ thông tin tóm tắt -->
                <div class="form-card" style="margin-bottom:20px; padding:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h2 style="font-size:20px; font-weight:800; color:var(--ink);"><?= htmlspecialchars($app['candidate_name']) ?></h2>
                            <div style="font-size:14px; font-weight:600; color:var(--text); margin-top:2px;">
                                <?= htmlspecialchars($app['cv_title'] ?: 'Ứng viên tiềm năng') ?>
                            </div>
                            <div style="font-size:13px; color:var(--muted); margin-top:6px;">
                                Email: <?= htmlspecialchars($app['candidate_email']) ?> &middot; SĐT: <?= htmlspecialchars($app['candidate_phone'] ?: 'Chưa cập nhật') ?>
                            </div>
                        </div>

                        <!-- Nhãn ứng viên hiện có -->
                        <div style="display:flex; flex-wrap:wrap; gap:6px; max-width:240px; justify-content:flex-end;">
                            <?php foreach ($tags as $tg): ?>
                                <span class="chip" style="font-size:11px; font-weight:700; background:<?= htmlspecialchars($tg['color']) ?>22; color:<?= htmlspecialchars($tg['color']) ?>; border:1px solid <?= htmlspecialchars($tg['color']) ?>44;">
                                    <?= htmlspecialchars($tg['tag_name']) ?>
                                    <form method="POST" style="display:inline; margin-left:4px;">
                                        <input type="hidden" name="action" value="delete_tag">
                                        <input type="hidden" name="tag_id" value="<?= $tg['id'] ?>">
                                        <button type="submit" style="background:none; border:none; cursor:pointer; color:inherit; font-size:10px; padding:0;">✕</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($app['cv_link'])): ?>
                        <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--line);">
                            <span style="font-size:12px; font-weight:700; color:var(--muted);">Liên kết đính kèm:</span>
                            <a href="<?= htmlspecialchars($app['cv_link']) ?>" target="_blank" style="font-size:13px; font-weight:700; color:#2563EB; margin-left:6px; text-decoration:underline;">
                                <?= htmlspecialchars($app['cv_link']) ?> ↗
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thư ứng tuyển (Cover Letter) -->
                <?php if (!empty($app['cover_letter'])): ?>
                    <div class="form-card" style="margin-bottom:20px; padding:24px;">
                        <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin-bottom:12px;">✉️ Thư giới thiệu từ ứng viên</h3>
                        <div style="font-size:14px; line-height:1.7; color:var(--text); white-space:pre-line; background:#F8FAFC; padding:16px; border-radius:10px; border:1px solid var(--line);">
                            <?= htmlspecialchars($app['cover_letter']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Nội dung CV chi tiết -->
                <?php if (!empty($app['cv_id'])): ?>
                    <div class="form-card" style="padding:28px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--line); padding-bottom:14px;">
                            <h3 style="font-size:16px; font-weight:800; color:var(--ink);">Hồ sơ năng lực (Nội dung CV)</h3>
                            <span class="chip" style="font-size:12px; font-weight:700;">Hệ thống JobFinder</span>
                        </div>

                        <?php if (!empty($app['cv_summary'])): ?>
                            <div style="margin-bottom:20px;">
                                <h4 style="font-size:13px; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:8px;">Tóm tắt bản thân</h4>
                                <p style="font-size:14px; line-height:1.6; color:var(--text);"><?= nl2br(htmlspecialchars($app['cv_summary'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cv_experience'])): ?>
                            <div style="margin-bottom:20px;">
                                <h4 style="font-size:13px; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:8px;">Kinh nghiệm làm việc</h4>
                                <div style="font-size:14px; line-height:1.6; color:var(--text); white-space:pre-line;"><?= htmlspecialchars($app['cv_experience']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cv_education'])): ?>
                            <div style="margin-bottom:20px;">
                                <h4 style="font-size:13px; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:8px;">Học vấn & Bằng cấp</h4>
                                <div style="font-size:14px; line-height:1.6; color:var(--text); white-space:pre-line;"><?= htmlspecialchars($app['cv_education']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cv_skills'])): ?>
                            <div style="margin-bottom:20px;">
                                <h4 style="font-size:13px; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:8px;">Kỹ năng chuyên môn</h4>
                                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                    <?php 
                                    $skills_arr = array_filter(array_map('trim', explode(',', $app['cv_skills'])));
                                    foreach ($skills_arr as $sk): 
                                    ?>
                                        <span class="chip" style="font-weight:700;"><?= htmlspecialchars($sk) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CỘT PHẢI: QUẢN TRỊ VÒNG TUYỂN DỤNG, GHI CHÚ, GẮN NHÃN & HẸN PHỎNG VẤN -->
            <div>
                <!-- KHỐI 1: CẬP NHẬT VÒNG TUYỂN DỤNG & ĐÁNH GIÁ SAO -->
                <div class="form-card" style="margin-bottom:20px; padding:22px;">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin-bottom:14px;">Trạng thái xử lý hồ sơ</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_pipeline">
                        
                        <div class="form-group">
                            <label class="form-label" for="status">Chuyển vòng tuyển dụng:</label>
                            <select id="status" name="status" class="form-control form-select" style="font-weight:700; font-size:13px;">
                                <?php foreach ($stages as $code => $stg): ?>
                                    <option value="<?= $code ?>" <?= ($app['status'] === $code) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($stg['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rating">Đánh giá độ phù hợp (Sao):</label>
                            <select id="rating" name="rating" class="form-control form-select">
                                <option value="0" <?= ((int)$app['rating'] === 0) ? 'selected' : '' ?>>Chưa chấm sao</option>
                                <option value="5" <?= ((int)$app['rating'] === 5) ? 'selected' : '' ?>>★★★★★ (5 sao - Xuất sắc)</option>
                                <option value="4" <?= ((int)$app['rating'] === 4) ? 'selected' : '' ?>>★★★★☆ (4 sao - Rất tốt)</option>
                                <option value="3" <?= ((int)$app['rating'] === 3) ? 'selected' : '' ?>>★★★☆☆ (3 sao - Đạt chuẩn)</option>
                                <option value="2" <?= ((int)$app['rating'] === 2) ? 'selected' : '' ?>>★★☆☆☆ (2 sao - Cân nhắc)</option>
                                <option value="1" <?= ((int)$app['rating'] === 1) ? 'selected' : '' ?>>★☆☆☆☆ (1 sao - Kém)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;">Lưu cập nhật vòng →</button>
                    </form>
                </div>

                <!-- KHỐI 2: GẮN NHÃN ỨNG VIÊN -->
                <div class="form-card" style="margin-bottom:20px; padding:22px;">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin-bottom:12px;">Gắn nhãn ứng viên</h3>
                    
                    <form method="POST" style="display:flex; gap:8px;">
                        <input type="hidden" name="action" value="add_tag">
                        <select name="tag_name" class="form-control form-select" style="font-size:12px;">
                            <option value="Tiềm năng">Tiềm năng</option>
                            <option value="Cần xem xét">Cần xem xét</option>
                            <option value="Ưu tiên cao">Ưu tiên cao</option>
                            <option value="Không phù hợp">Không phù hợp</option>
                            <option value="Phù hợp dự án mới">Phù hợp dự án mới</option>
                        </select>
                        <select name="color" class="form-control form-select" style="width:90px; font-size:12px;">
                            <option value="#16A36A">Xanh lá</option>
                            <option value="#2563EB">Xanh dương</option>
                            <option value="#D97706">Cam</option>
                            <option value="#DC2626">Đỏ</option>
                            <option value="#8B5CF6">Tím</option>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">+ Gắn</button>
                    </form>
                </div>

                <!-- KHỐI 3: GHI CHÚ NỘI BỘ (CHỈ NTD THẤY) -->
                <div class="form-card" style="margin-bottom:20px; padding:22px;">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin-bottom:4px;">Ghi chú nội bộ (Private Notes)</h3>
                    <p style="font-size:11px; color:var(--muted); margin-bottom:14px;">Chỉ người phụ trách tuyển dụng nhìn thấy, không hiển thị cho ứng viên.</p>

                    <form method="POST" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="add_note">
                        <textarea name="note" class="form-control form-textarea" style="min-height:70px; font-size:13px;" placeholder="Ghi lại nhận xét chuyên môn, đánh giá tác phong sau phỏng vấn..." required></textarea>
                        <button type="submit" class="btn btn-secondary btn-sm" style="margin-top:8px; width:100%;">+ Thêm ghi chú mới</button>
                    </form>

                    <?php if (!empty($notes)): ?>
                        <div style="display:flex; flex-direction:column; gap:8px; max-height:220px; overflow-y:auto;">
                            <?php foreach ($notes as $nt): ?>
                                <div style="padding:10px 12px; background:#F8FAFC; border:1px solid var(--line); border-radius:8px; font-size:12px;">
                                    <div style="color:var(--text); line-height:1.5;"><?= htmlspecialchars($nt['note']) ?></div>
                                    <div style="font-size:10px; color:var(--muted); margin-top:4px;"><?= time_ago($nt['created_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size:12px; color:var(--muted); text-align:center; padding:10px 0;">Chưa có ghi chú nội bộ.</div>
                    <?php endif; ?>
                </div>

                <!-- KHỐI 4: HẸN LỊCH PHỎNG VẤN TRỰC TIẾP -->
                <div class="form-card" id="interviewFormBlock" style="padding:22px;">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin-bottom:4px;">Hẹn lịch phỏng vấn</h3>
                    <p style="font-size:11px; color:var(--muted); margin-bottom:14px;">Ứng viên sẽ nhận được thông báo thời gian & liên kết phỏng vấn.</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="schedule_interview">

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div class="form-group">
                                <label class="form-label" style="font-size:11px;">Ngày phỏng vấn <span style="color:#DC2626;">*</span></label>
                                <input type="date" name="interview_date" class="form-control" style="font-size:12px;" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" style="font-size:11px;">Giờ bắt đầu <span style="color:#DC2626;">*</span></label>
                                <input type="time" name="interview_time" class="form-control" style="font-size:12px;" value="14:00" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:11px;">Hình thức phỏng vấn</label>
                            <select name="interview_type" class="form-control form-select" style="font-size:12px;">
                                <option value="online">Online (Google Meet / Zoom)</option>
                                <option value="offline">Trực tiếp tại văn phòng</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:11px;">Địa điểm / Link phòng họp <span style="color:#DC2626;">*</span></label>
                            <input type="text" name="location_or_link" class="form-control" style="font-size:12px;" placeholder="https://meet.google.com/... hoặc Tầng 5, Tòa nhà FPT" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:11px;">Người tham gia phỏng vấn</label>
                            <input type="text" name="interviewer" class="form-control" style="font-size:12px;" placeholder="Họ tên Trưởng bộ phận / HR">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:10px; font-size:13px;">Xác nhận đặt lịch & Thông báo →</button>
                    </form>

                    <!-- Danh sách lịch đã xếp -->
                    <?php if (!empty($interviews)): ?>
                        <div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--line);">
                            <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase;">Lịch phỏng vấn đã xếp:</span>
                            <?php foreach ($interviews as $iv): ?>
                                <div style="margin-top:6px; padding:8px 10px; background:#F0FDF4; border:1px solid #BBF7D0; border-radius:6px; font-size:12px;">
                                    <strong><?= date('d/m/Y', strtotime($iv['interview_date'])) ?> lúc <?= date('H:i', strtotime($iv['interview_time'])) ?></strong>
                                    <div style="color:var(--text); font-size:11px;"><?= htmlspecialchars($iv['location_or_link']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </main>
</div>

</body>
</html>

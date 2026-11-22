<?php
/**
 * ============================================================
 * FILE: employer/application-view.php
 * MÔ TẢ: Xem chi tiết hồ sơ & Duyệt trạng thái ứng viên
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

// Xử lý cập nhật trạng thái hồ sơ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = trim($_POST['status'] ?? '');
    $valid_statuses = ['Đã nộp hồ sơ', 'Đang xét duyệt', 'Phù hợp', 'Từ chối'];

    if (in_array($new_status, $valid_statuses)) {
        $upd_stmt = $pdo->prepare("
            UPDATE applications a
            JOIN jobs j ON j.id = a.job_id
            SET a.status = :status, a.updated_at = NOW()
            WHERE a.id = :app_id AND j.employer_id = :employer_id
        ");
        $upd_stmt->execute([
            ':status'      => $new_status,
            ':app_id'      => $app_id,
            ':employer_id' => $employer_id
        ]);

        $_SESSION['flash_message'] = 'Đã cập nhật trạng thái hồ sơ thành: "' . $new_status . '".';
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: application-view.php?id=' . $app_id);
    exit;
}

// Truy vấn thông tin chi tiết ứng tuyển
$stmt = $pdo->prepare("
    SELECT a.*, u.name as candidate_name, u.email as candidate_email, u.phone as candidate_phone,
           j.id as job_id, j.title as job_title, j.salary as job_salary, j.location as job_location,
           c.title as cv_title, c.summary as cv_summary, c.education as cv_education,
           c.experience as cv_experience, c.skills as cv_skills, c.certifications as cv_certifications,
           c.languages as cv_languages, c.address as cv_address
    FROM applications a
    JOIN users u ON u.id = a.user_id
    JOIN jobs j ON j.id = a.job_id
    LEFT JOIN cvs c ON c.id = a.cv_id
    WHERE a.id = :app_id AND j.employer_id = :employer_id
    LIMIT 1
");
$stmt->execute([':app_id' => $app_id, ':employer_id' => $employer_id]);
$app = $stmt->fetch();

if (!$app) {
    $_SESSION['flash_message'] = 'Không tìm thấy hồ sơ hoặc bạn không có quyền xem hồ sơ này.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: applications.php');
    exit;
}

$page_title = 'Hồ sơ: ' . $app['candidate_name'];

$status_badge_map = [
    'Đã nộp hồ sơ'  => 'badge--pending',
    'Đang xét duyệt' => 'badge--pending',
    'Phù hợp'        => 'badge--success',
    'Từ chối'        => 'badge--danger'
];
$badge_class = $status_badge_map[$app['status']] ?? 'badge--pending';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Doanh Nghiệp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Duyệt hồ sơ</span>
                <h1 class="section-title"><?= htmlspecialchars($app['candidate_name']) ?></h1>
                <p class="section-desc">Ứng tuyển vị trí <strong><?= htmlspecialchars($app['job_title']) ?></strong></p>
            </div>
            <a href="applications.php" class="btn btn-secondary btn-sm">← Quay lại danh sách</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 340px; gap:28px; align-items:flex-start;">
            <!-- Cột trái: Thông tin CV & Thư giới thiệu -->
            <div>
                <!-- Thông tin ứng viên -->
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:32px; margin-bottom:24px;">
                    <h2 style="font-size:18px; font-weight:700; margin-bottom:16px;">Thông tin liên hệ</h2>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; font-size:14px;">
                        <div>
                            <span class="text-muted" style="display:block; font-size:12px; margin-bottom:2px;">Họ và tên:</span>
                            <strong><?= htmlspecialchars($app['candidate_name']) ?></strong>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:12px; margin-bottom:2px;">Email:</span>
                            <a href="mailto:<?= htmlspecialchars($app['candidate_email']) ?>" style="text-decoration:underline;">
                                <?= htmlspecialchars($app['candidate_email']) ?>
                            </a>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:12px; margin-bottom:2px;">Số điện thoại:</span>
                            <strong><?= htmlspecialchars($app['candidate_phone'] ?: 'Chưa cập nhật') ?></strong>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:12px; margin-bottom:2px;">Địa chỉ:</span>
                            <strong><?= htmlspecialchars($app['cv_address'] ?: 'Chưa cập nhật') ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Thư giới thiệu -->
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:32px; margin-bottom:24px;">
                    <h2 style="font-size:18px; font-weight:700; margin-bottom:12px;">Thư giới thiệu của ứng viên</h2>
                    <div style="font-size:14px; line-height:1.7; color:var(--ink-secondary); background:var(--surface-subtle); border-radius:var(--radius-md); padding:20px; white-space:pre-line;">
                        <?= htmlspecialchars($app['cover_letter'] ?: 'Ứng viên không để lại thư giới thiệu.') ?>
                    </div>
                </div>

                <!-- Nội dung CV đính kèm (nếu có) -->
                <?php if (!empty($app['cv_title'])): ?>
                    <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:32px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid var(--line);">
                            <div>
                                <span class="section-eyebrow">Hồ sơ đính kèm</span>
                                <h2 style="font-size:20px; font-weight:800;"><?= htmlspecialchars($app['cv_title']) ?></h2>
                            </div>
                            <a href="../cv-preview.php?id=<?= (int)$app['cv_id'] ?>" target="_blank" class="btn btn-secondary btn-sm">Xem bản in ↗</a>
                        </div>

                        <?php if (!empty($app['cv_summary'])): ?>
                            <div style="margin-bottom:24px;">
                                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:6px;">Giới thiệu bản thân</div>
                                <div style="font-size:14px; line-height:1.6; color:var(--ink-secondary); white-space:pre-line;">
                                    <?= htmlspecialchars($app['cv_summary']) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cv_education'])): ?>
                            <div style="margin-bottom:24px;">
                                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:6px;">Học vấn</div>
                                <div style="font-size:14px; line-height:1.6; color:var(--ink-secondary); white-space:pre-line;">
                                    <?= htmlspecialchars($app['cv_education']) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cv_experience'])): ?>
                            <div style="margin-bottom:24px;">
                                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:6px;">Kinh nghiệm thực tế</div>
                                <div style="font-size:14px; line-height:1.6; color:var(--ink-secondary); white-space:pre-line;">
                                    <?= htmlspecialchars($app['cv_experience']) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($app['cv_skills'])): ?>
                            <div style="margin-bottom:24px;">
                                <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:8px;">Kỹ năng</div>
                                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                    <?php foreach (array_filter(array_map('trim', explode(',', $app['cv_skills']))) as $sk): ?>
                                        <span class="chip"><?= htmlspecialchars($sk) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cột phải: Cập nhật trạng thái xét duyệt -->
            <div style="position:sticky; top:24px;">
                <div style="background:var(--surface); border-radius:var(--radius-xl); border:1px solid var(--line); padding:28px; box-shadow:var(--shadow-card);">
                    <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:6px;">
                        Trạng thái hiện tại
                    </div>
                    <div style="margin-bottom:20px;">
                        <span class="badge <?= $badge_class ?>" style="font-size:13px; padding:6px 14px;">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </div>

                    <div style="font-size:12px; color:var(--muted); margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid var(--line-subtle);">
                        Nộp lúc: <?= date('d/m/Y H:i', strtotime($app['created_at'])) ?>
                        <?php if (!empty($app['updated_at'])): ?>
                            <br>Xét duyệt lúc: <?= date('d/m/Y H:i', strtotime($app['updated_at'])) ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <label class="form-label" style="margin-bottom:12px;">Cập nhật tiến độ xét duyệt</label>
                        
                        <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px 14px; border:1px solid var(--line); border-radius:var(--radius-sm);">
                                <input type="radio" name="status" value="Đã nộp hồ sơ" <?= ($app['status'] === 'Đã nộp hồ sơ') ? 'checked' : '' ?>>
                                <span style="font-size:13px; font-weight:600;">Đã nộp hồ sơ</span>
                            </label>

                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px 14px; border:1px solid var(--line); border-radius:var(--radius-sm);">
                                <input type="radio" name="status" value="Đang xét duyệt" <?= ($app['status'] === 'Đang xét duyệt') ? 'checked' : '' ?>>
                                <span style="font-size:13px; font-weight:600; color:var(--status-pending-text);">Đang xét duyệt</span>
                            </label>

                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px 14px; border:1px solid var(--line); border-radius:var(--radius-sm);">
                                <input type="radio" name="status" value="Phù hợp" <?= ($app['status'] === 'Phù hợp') ? 'checked' : '' ?>>
                                <span style="font-size:13px; font-weight:600; color:var(--status-success-text);">Phù hợp tuyển dụng ✓</span>
                            </label>

                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px 14px; border:1px solid var(--line); border-radius:var(--radius-sm);">
                                <input type="radio" name="status" value="Từ chối" <?= ($app['status'] === 'Từ chối') ? 'checked' : '' ?>>
                                <span style="font-size:13px; font-weight:600; color:var(--status-danger-text);">Từ chối hồ sơ</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">
                            Lưu trạng thái mới
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>

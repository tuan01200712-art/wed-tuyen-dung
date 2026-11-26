<?php
/**
 * ============================================================
 * FILE: employer/campaigns.php
 * MÔ TẢ: Quản lý Chiến dịch Tuyển dụng (Campaign Management) — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Chiến Dịch Tuyển Dụng';
$errors = [];

// Xử lý POST Thêm / Sửa / Xóa chiến dịch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $target_hires = max(1, (int)($_POST['target_hires'] ?? 1));
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        if (empty($title)) {
            $errors[] = 'Vui lòng nhập tên chiến dịch tuyển dụng.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO campaigns (employer_id, title, description, target_hires, start_date, end_date, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([$employer_id, $title, $description, $target_hires, $start_date, $end_date]);
                log_activity($pdo, $employer_id, 'CAMPAIGN_CREATE', "Tạo chiến dịch: {$title}");
                $_SESSION['flash_message'] = 'Đã khởi tạo chiến dịch tuyển dụng "' . htmlspecialchars($title) . '" thành công.';
                $_SESSION['flash_type'] = 'success';
                header('Location: campaigns.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Lỗi khi lưu chiến dịch: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $camp_id = (int)($_POST['campaign_id'] ?? 0);
        if ($camp_id > 0) {
            $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND employer_id = ?")->execute([$camp_id, $employer_id]);
            log_activity($pdo, $employer_id, 'CAMPAIGN_DELETE', "Xóa chiến dịch ID #{$camp_id}");
            $_SESSION['flash_message'] = 'Đã xóa chiến dịch thành công.';
            $_SESSION['flash_type'] = 'success';
            header('Location: campaigns.php');
            exit;
        }
    }
}

// Lấy danh sách chiến dịch kèm thống kê tin và hồ sơ
$campaigns = [];
try {
    $sql = "
        SELECT c.*,
               COUNT(DISTINCT j.id) as total_jobs,
               COUNT(DISTINCT a.id) as total_apps,
               COUNT(DISTINCT CASE WHEN a.status IN ('hired', 'offered') THEN a.id END) as hired_count
        FROM campaigns c
        LEFT JOIN jobs j ON j.campaign_id = c.id
        LEFT JOIN applications a ON a.job_id = j.id
        WHERE c.employer_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employer_id]);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
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
                <h1 class="section-title">Chiến dịch tuyển dụng</h1>
                <p class="section-desc">Gộp nhiều vị trí việc làm vào các chiến dịch mục tiêu (vd: Thực tập sinh 2026, Mở rộng chi nhánh).</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('createCampBlock').style.display='block'; window.scrollTo({top: 100, behavior:'smooth'});">
                + Tạo chiến dịch mới
            </button>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <!-- FORM TẠO CHIẾN DỊCH (ẨN / HIỆN) -->
        <div class="form-card" id="createCampBlock" style="display:none; margin-bottom:24px; max-width:800px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:16px; font-weight:800; color:var(--ink);">Khởi tạo Chiến dịch Tuyển dụng Mới</h3>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('createCampBlock').style.display='none';">✕ Đóng</button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label class="form-label" for="title">Tên chiến dịch <span style="color:#DC2626;">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Ví dụ: Chiến Dịch Tuyển Dụng Kỹ Sư Công Nghệ Q1/2026" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="target_hires">Mục tiêu tuyển (Người)</label>
                        <input type="number" id="target_hires" name="target_hires" class="form-control" min="1" value="5" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="start_date">Ngày bắt đầu</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_date">Ngày kết thúc</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+90 days')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Mô tả mục tiêu chiến dịch</label>
                    <textarea id="description" name="description" class="form-control form-textarea" style="min-height:80px;" placeholder="Mục đích chiến dịch, bộ phận phụ trách..."></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createCampBlock').style.display='none';">Hủy</button>
                    <button type="submit" class="btn btn-primary" style="padding:10px 24px;">Lưu chiến dịch →</button>
                </div>
            </form>
        </div>

        <!-- DANH SÁCH CHIẾN DỊCH -->
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:20px;">
            <?php if (!empty($campaigns)): ?>
                <?php foreach ($campaigns as $cp): 
                    $progress_pct = min(100, round(($cp['hired_count'] / ($cp['target_hires'] ?: 1)) * 100));
                ?>
                    <div class="form-card" style="padding:22px; display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                                <span class="badge badge--active">Đang diễn ra</span>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chiến dịch này? Các tin tuyển dụng sẽ không bị xóa.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="campaign_id" value="<?= $cp['id'] ?>">
                                    <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--muted); font-size:12px;">✕ Xóa</button>
                                </form>
                            </div>

                            <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin-bottom:6px;">
                                <?= htmlspecialchars($cp['title']) ?>
                            </h3>

                            <?php if (!empty($cp['description'])): ?>
                                <p style="font-size:13px; color:var(--muted); line-height:1.5; margin-bottom:14px;">
                                    <?= htmlspecialchars($cp['description']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Thanh tiến độ tuyển dụng (Hired / Target) -->
                            <div style="margin-bottom:16px;">
                                <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:700; margin-bottom:6px;">
                                    <span>Tiến độ tuyển dụng</span>
                                    <span style="color:#16A34A;"><?= $cp['hired_count'] ?> / <?= $cp['target_hires'] ?> người (<?= $progress_pct ?>%)</span>
                                </div>
                                <div style="height:8px; background:#E5E7EB; border-radius:4px; overflow:hidden;">
                                    <div style="height:100%; width:<?= $progress_pct ?>%; background:#16A34A; border-radius:4px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Thống kê tin và CV gắn với chiến dịch -->
                        <div style="padding-top:14px; border-top:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; font-size:12px;">
                            <div style="color:var(--text); font-weight:600;">
                                <span>📌 <?= $cp['total_jobs'] ?> vị trí</span> &middot; 
                                <span>📄 <?= $cp['total_apps'] ?> hồ sơ</span>
                            </div>

                            <a href="jobs.php" class="btn btn-secondary btn-sm" style="font-size:11px;">
                                Gắn tin mới →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px; background:#FFFFFF; border:1px solid var(--line); border-radius:14px;">
                    <div style="font-size:40px; margin-bottom:12px;">🎯</div>
                    <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:6px;">Chưa có chiến dịch tuyển dụng nào</h3>
                    <p style="font-size:14px; color:var(--muted); max-width:440px; margin:0 auto 20px;">
                        Tạo chiến dịch giúp bạn tập hợp các vị trí tuyển dụng vào cùng một mục tiêu và theo dõi chỉ tiêu nhân sự hiệu quả.
                    </p>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('createCampBlock').style.display='block';">
                        + Tạo chiến dịch đầu tiên
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>

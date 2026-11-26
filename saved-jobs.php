<?php
/**
 * ============================================================
 * FILE: saved-jobs.php
 * MÔ TẢ: Danh sách việc làm đã lưu của Ứng viên — Hệ thống JobFinder
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('saved-jobs.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Việc Làm Đã Lưu';

// Xử lý AJAX hoặc POST Lưu / Bỏ lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $job_id = (int)($_POST['job_id'] ?? 0);
    $is_ajax = ($action === 'ajax_toggle') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($job_id > 0) {
        $chk = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $chk->execute([$user_id, $job_id]);
        $saved = $chk->fetch();

        if ($saved) {
            // Đã lưu -> Bỏ lưu
            $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?")->execute([$user_id, $job_id]);
            $is_saved = false;
            $msg = 'Đã bỏ lưu tin việc làm.';
        } else {
            // Chưa lưu -> Lưu
            $pdo->prepare("INSERT INTO saved_jobs (user_id, job_id, created_at) VALUES (?, ?, NOW())")->execute([$user_id, $job_id]);
            $is_saved = true;
            $msg = 'Đã lưu tin việc làm vào danh sách.';
        }

        if ($is_ajax) {
            $cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
            $cnt_stmt->execute([$user_id]);
            $saved_count = (int)$cnt_stmt->fetchColumn();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'success' => true, 'saved' => $is_saved, 'saved_count' => $saved_count, 'message' => $msg]);
            exit;
        }

        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = 'success';
    } elseif ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Tin tuyển dụng không hợp lệ.']);
        exit;
    }
    header('Location: saved-jobs.php');
    exit;
}

// Lấy danh sách việc làm đã lưu
$stmt = $pdo->prepare("
    SELECT j.*, sj.created_at as saved_at,
           (SELECT COUNT(*) FROM applications WHERE user_id = ? AND job_id = j.id) as has_applied
    FROM saved_jobs sj
    JOIN jobs j ON j.id = sj.job_id
    WHERE sj.user_id = ?
    ORDER BY sj.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$saved_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<section class="section" style="padding-top:40px;">
    <div class="container">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title">Việc làm đã lưu</h1>
                <p class="section-desc">Bạn đang lưu <strong><?= count($saved_jobs) ?></strong> công việc để ứng tuyển sau.</p>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">Tìm thêm việc làm →</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <?php if (!empty($saved_jobs)): ?>
            <div class="job-grid">
                <?php foreach ($saved_jobs as $job): 
                    $company_initial = mb_strtoupper(mb_substr($job['company'], 0, 2));
                ?>
                    <div class="job-card">
                        <div>
                            <div class="job-card__header">
                                <div class="job-card__avatar">
                                    <?= htmlspecialchars($company_initial) ?>
                                </div>
                                <div class="job-card__info">
                                    <h3 class="job-card__title">
                                        <a href="job-detail.php?id=<?= (int)$job['id'] ?>">
                                            <?= htmlspecialchars($job['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="job-card__company"><?= htmlspecialchars($job['company']) ?></div>
                                </div>
                            </div>

                            <div class="job-card__chips">
                                <span class="chip"><?= htmlspecialchars($job['location']) ?></span>
                                <span class="chip"><?= htmlspecialchars($job['job_type']) ?></span>
                                <?php if (!empty($job['experience'])): ?>
                                    <span class="chip"><?= htmlspecialchars($job['experience']) ?></span>
                                <?php endif; ?>
                                <span class="chip"><?= htmlspecialchars($job['category']) ?></span>
                            </div>
                        </div>

                        <div>
                            <div class="job-card__footer">
                                <div class="job-card__salary"><?= htmlspecialchars($job['salary']) ?></div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_save">
                                        <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--status-danger-text); font-size:12px;">Bỏ lưu</button>
                                    </form>

                                    <?php if (!empty($job['has_applied'])): ?>
                                        <span class="badge badge--active">Đã ứng tuyển</span>
                                    <?php else: ?>
                                        <a href="job-detail.php?id=<?= (int)$job['id'] ?>" class="btn btn-primary btn-sm">Ứng tuyển →</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="font-size:11px; color:var(--muted); margin-top:8px;">
                                Đã lưu <?= time_ago($job['saved_at']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:68px 24px; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius-xl);">
                <div style="width:52px; height:52px; border-radius:var(--radius-md); background:var(--soft-mint); color:var(--emerald-deep); display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin-bottom:8px;">Bạn chưa lưu công việc nào</h3>
                <p style="font-size:14px; color:var(--muted); max-width:420px; margin:0 auto 24px;">
                    Khi tìm kiếm việc làm, hãy bấm nút "Lưu tin" để xem lại và ứng tuyển khi chuẩn bị xong hồ sơ CV.
                </p>
                <a href="jobs.php" class="btn btn-primary">Khám phá việc làm hấp dẫn →</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

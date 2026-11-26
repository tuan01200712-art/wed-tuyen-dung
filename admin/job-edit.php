<?php
/**
 * ============================================================
 * FILE: admin/job-edit.php
 * MÔ TẢ: Thêm mới hoặc chỉnh sửa tin tuyển dụng bất kỳ bởi Admin
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_create = isset($_GET['action']) && $_GET['action'] === 'create';
$errors = [];

if ($is_create) {
    $page_title = 'Tạo Tin Tuyển Dụng Mới (Admin)';
    $job = [
        'title'        => '',
        'company'      => '',
        'location'     => 'Hà Nội',
        'category'     => 'Công nghệ thông tin',
        'salary'       => '',
        'salary_value' => 0,
        'job_type'     => 'Toàn thời gian',
        'experience'   => 'Không yêu cầu',
        'deadline'     => '',
        'description'  => '',
        'requirements' => '',
        'status'       => 'active',
        'employer_id'  => null
    ];
} else {
    if ($job_id <= 0) {
        header('Location: jobs.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) {
        $_SESSION['flash_message'] = 'Không tìm thấy tin tuyển dụng.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: jobs.php');
        exit;
    }
    $page_title = 'Sửa Tin: ' . $job['title'];
}

// Lấy danh sách employers để gán quyền sở hữu
$employers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'employer' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $company      = trim($_POST['company'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $salary       = trim($_POST['salary'] ?? '');
    $salary_value = (int)($_POST['salary_value'] ?? 0);
    $job_type     = trim($_POST['job_type'] ?? 'Toàn thời gian');
    $experience   = trim($_POST['experience'] ?? 'Không yêu cầu');
    $deadline     = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $description  = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $benefits     = trim($_POST['benefits'] ?? '');
    $level        = trim($_POST['level'] ?? ($job['level'] ?? 'Nhân viên'));
    $quantity     = max(1, (int)($_POST['quantity'] ?? ($job['quantity'] ?? 1)));
    $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
    $status       = in_array($_POST['status'] ?? 'active', ['active', 'pending', 'rejected', 'inactive', 'draft', 'expired']) ? $_POST['status'] : 'active';
    $emp_id       = !empty($_POST['employer_id']) ? (int)$_POST['employer_id'] : null;

    if (empty($title)) $errors[] = 'Tiêu đề không được để trống.';
    if (empty($company)) $errors[] = 'Tên công ty không được để trống.';
    if (empty($location)) $errors[] = 'Địa điểm không được để trống.';
    if (empty($salary)) $errors[] = 'Mức lương không được để trống.';

    if (empty($errors)) {
        try {
            if ($is_create) {
                $ins = $pdo->prepare("
                    INSERT INTO jobs (employer_id, title, company, location, category, salary, salary_value, job_type, quantity, level, experience, deadline, description, requirements, benefits, is_featured, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $ins->execute([
                    $emp_id, $title, $company, $location, $category,
                    $salary, $salary_value, $job_type, $quantity, $level, $experience, $deadline,
                    $description, $requirements, $benefits, $is_featured, $status
                ]);
                log_activity($pdo, $admin_id, 'JOB_CREATE', "Tạo tin tuyển dụng: {$title}");
                $_SESSION['flash_message'] = 'Tạo tin tuyển dụng thành công.';
            } else {
                $upd = $pdo->prepare("
                    UPDATE jobs SET 
                        employer_id = ?, title = ?, company = ?, location = ?, category = ?,
                        salary = ?, salary_value = ?, job_type = ?, quantity = ?, level = ?, experience = ?, deadline = ?,
                        description = ?, requirements = ?, benefits = ?, is_featured = ?, status = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $emp_id, $title, $company, $location, $category,
                    $salary, $salary_value, $job_type, $quantity, $level, $experience, $deadline,
                    $description, $requirements, $benefits, $is_featured, $status, $job_id
                ]);
                log_activity($pdo, $admin_id, 'JOB_UPDATE', "Cập nhật tin ID #{$job_id}: {$title}");
                $_SESSION['flash_message'] = 'Cập nhật tin tuyển dụng thành công.';
            }
            $_SESSION['flash_type'] = 'success';
            header('Location: jobs.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã xảy ra lỗi khi lưu tin tuyển dụng.';
        }
    }
}

$categories_list = array_column(get_categories($pdo), 'name');
$locations_list = array_column(get_locations($pdo), 'name');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Admin</title>
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
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title"><?= $is_create ? 'Thêm mới tin tuyển dụng' : 'Chỉnh sửa tin tuyển dụng' ?></h1>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">← Quay lại danh sách</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <div class="form-card" style="max-width:840px;">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="title">Tiêu đề vị trí tuyển dụng <span style="color:var(--status-danger-text);">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($job['title']) ?>" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="company">Tên công ty <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="text" id="company" name="company" class="form-control" value="<?= htmlspecialchars($job['company']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="employer_id">Gán cho tài khoản Doanh nghiệp</label>
                        <select id="employer_id" name="employer_id" class="form-control form-select">
                            <option value="">Không gán (Tin hệ thống đăng)</option>
                            <?php foreach ($employers as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>" <?= ((int)$job['employer_id'] === (int)$emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="location">Địa điểm làm việc <span style="color:var(--status-danger-text);">*</span></label>
                        <select id="location" name="location" class="form-control form-select" required>
                            <?php if (!in_array($job['location'], $locations_list) && !empty($job['location'])): ?>
                                <option value="<?= htmlspecialchars($job['location']) ?>" selected><?= htmlspecialchars($job['location']) ?></option>
                            <?php endif; ?>
                            <?php foreach ($locations_list as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= ($job['location'] === $loc) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">Ngành nghề <span style="color:var(--status-danger-text);">*</span></label>
                        <select id="category" name="category" class="form-control form-select">
                            <?php foreach ($categories_list as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= ($job['category'] === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="salary">Mức lương hiển thị <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="text" id="salary" name="salary" class="form-control" value="<?= htmlspecialchars($job['salary']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="job_type">Hình thức làm việc</label>
                        <select id="job_type" name="job_type" class="form-control form-select">
                            <option value="Toàn thời gian" <?= ($job['job_type'] === 'Toàn thời gian') ? 'selected' : '' ?>>Toàn thời gian</option>
                            <option value="Bán thời gian" <?= ($job['job_type'] === 'Bán thời gian') ? 'selected' : '' ?>>Bán thời gian</option>
                            <option value="Thực tập" <?= ($job['job_type'] === 'Thực tập') ? 'selected' : '' ?>>Thực tập</option>
                            <option value="Làm việc từ xa" <?= ($job['job_type'] === 'Làm việc từ xa' || $job['job_type'] === 'Làm từ xa (Remote)') ? 'selected' : '' ?>>Làm việc từ xa</option>
                            <option value="Linh hoạt" <?= ($job['job_type'] === 'Linh hoạt') ? 'selected' : '' ?>>Linh hoạt</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="deadline">Hạn nộp hồ sơ</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" value="<?= htmlspecialchars($job['deadline'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Mô tả chi tiết công việc</label>
                    <textarea id="description" name="description" class="form-control form-textarea" style="min-height:160px;"><?= htmlspecialchars($job['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="requirements">Yêu cầu tuyển dụng</label>
                    <textarea id="requirements" name="requirements" class="form-control form-textarea" style="min-height:160px;"><?= htmlspecialchars($job['requirements']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="benefits">Quyền lợi & Đãi ngộ</label>
                    <textarea id="benefits" name="benefits" class="form-control form-textarea" style="min-height:120px;"><?= htmlspecialchars($job['benefits'] ?? '') ?></textarea>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="form-group">
                        <label class="form-label" for="quantity">Số lượng tuyển</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?= (int)($job['quantity'] ?? 1) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="level">Cấp bậc</label>
                        <select id="level" name="level" class="form-control form-select">
                            <option value="Thực tập sinh" <?= (($job['level'] ?? '') === 'Thực tập sinh') ? 'selected' : '' ?>>Thực tập sinh</option>
                            <option value="Nhân viên" <?= (($job['level'] ?? '') === 'Nhân viên') ? 'selected' : '' ?>>Nhân viên</option>
                            <option value="Chuyên viên / Senior" <?= (($job['level'] ?? '') === 'Chuyên viên / Senior') ? 'selected' : '' ?>>Chuyên viên / Senior</option>
                            <option value="Trưởng nhóm / Leader" <?= (($job['level'] ?? '') === 'Trưởng nhóm / Leader') ? 'selected' : '' ?>>Trưởng nhóm / Leader</option>
                            <option value="Trưởng phòng / Manager" <?= (($job['level'] ?? '') === 'Trưởng phòng / Manager') ? 'selected' : '' ?>>Trưởng phòng / Manager</option>
                            <option value="Giám đốc / Director" <?= (($job['level'] ?? '') === 'Giám đốc / Director') ? 'selected' : '' ?>>Giám đốc / Director</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_featured" value="1" <?= (!empty($job['is_featured'])) ? 'checked' : '' ?> style="width:18px; height:18px;">
                        <span style="font-weight:700; color:var(--ink);">Đánh dấu là Tin Tuyển Dụng Nổi Bật (Featured Badge)</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Trạng thái phát hành</label>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="active" <?= ($job['status'] === 'active') ? 'checked' : '' ?>>
                            <span>Công khai (Active)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="pending" <?= ($job['status'] === 'pending') ? 'checked' : '' ?>>
                            <span>Chờ duyệt (Pending)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="rejected" <?= ($job['status'] === 'rejected') ? 'checked' : '' ?>>
                            <span>Bị từ chối (Rejected)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="inactive" <?= ($job['status'] === 'inactive') ? 'checked' : '' ?>>
                            <span>Tạm dừng (Inactive)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="draft" <?= ($job['status'] === 'draft') ? 'checked' : '' ?>>
                            <span>Bản nháp (Draft)</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                    <a href="jobs.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Lưu tin tuyển dụng</button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>

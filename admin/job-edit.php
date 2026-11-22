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
    $status       = in_array($_POST['status'] ?? 'active', ['active', 'inactive', 'draft']) ? $_POST['status'] : 'active';
    $emp_id       = !empty($_POST['employer_id']) ? (int)$_POST['employer_id'] : null;

    if (empty($title)) $errors[] = 'Tiêu đề không được để trống.';
    if (empty($company)) $errors[] = 'Tên công ty không được để trống.';
    if (empty($location)) $errors[] = 'Địa điểm không được để trống.';
    if (empty($salary)) $errors[] = 'Mức lương không được để trống.';

    if (empty($errors)) {
        try {
            if ($is_create) {
                $ins = $pdo->prepare("
                    INSERT INTO jobs (employer_id, title, company, location, category, salary, salary_value, job_type, experience, deadline, description, requirements, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $ins->execute([
                    $emp_id, $title, $company, $location, $category,
                    $salary, $salary_value, $job_type, $experience, $deadline,
                    $description, $requirements, $status
                ]);
                $_SESSION['flash_message'] = 'Tạo tin tuyển dụng thành công.';
            } else {
                $upd = $pdo->prepare("
                    UPDATE jobs SET 
                        employer_id = ?, title = ?, company = ?, location = ?, category = ?,
                        salary = ?, salary_value = ?, job_type = ?, experience = ?, deadline = ?,
                        description = ?, requirements = ?, status = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $emp_id, $title, $company, $location, $category,
                    $salary, $salary_value, $job_type, $experience, $deadline,
                    $description, $requirements, $status, $job_id
                ]);
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

$categories_list = [
    'Công nghệ thông tin', 'Marketing', 'Thiết kế đồ họa', 'Tài chính - Kế toán',
    'Nhân sự', 'Kinh doanh - Bán hàng', 'Ngân hàng - Dữ liệu', 'Giáo dục - Đào tạo',
    'Dịch vụ khách hàng', 'Khác'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JobFinder Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Quản trị tin tuyển dụng</span>
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
                        <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($job['location']) ?>" required>
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
                            <option value="Làm từ xa (Remote)" <?= ($job['job_type'] === 'Làm từ xa (Remote)') ? 'selected' : '' ?>>Làm từ xa (Remote)</option>
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
                    <label class="form-label">Trạng thái phát hành</label>
                    <div style="display:flex; gap:16px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="active" <?= ($job['status'] === 'active') ? 'checked' : '' ?>>
                            <span>Công khai (Active)</span>
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

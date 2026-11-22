<?php
/**
 * ============================================================
 * FILE: employer/job-edit.php
 * MÔ TẢ: Chỉnh sửa tin tuyển dụng của công ty
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$job_id, $employer_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['flash_message'] = 'Không tìm thấy tin tuyển dụng hoặc bạn không có quyền sửa tin này.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: jobs.php');
    exit;
}

$page_title = 'Chỉnh Sửa Tin Tuyển Dụng';
$errors = [];

$title = $job['title'];
$company = $job['company'];
$location = $job['location'];
$category = $job['category'];
$salary = $job['salary'];
$salary_value = $job['salary_value'];
$job_type = $job['job_type'];
$experience = $job['experience'];
$deadline = $job['deadline'];
$description = $job['description'];
$requirements = $job['requirements'];
$status = $job['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $salary_value = (int)($_POST['salary_value'] ?? 0);
    $job_type = trim($_POST['job_type'] ?? 'Toàn thời gian');
    $experience = trim($_POST['experience'] ?? 'Không yêu cầu');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive', 'draft']) ? $_POST['status'] : 'active';

    if (empty($title)) $errors[] = 'Vui lòng nhập tiêu đề vị trí tuyển dụng.';
    if (empty($company)) $errors[] = 'Vui lòng nhập tên công ty.';
    if (empty($location)) $errors[] = 'Vui lòng nhập địa điểm làm việc.';
    if (empty($salary)) $errors[] = 'Vui lòng nhập mức lương dự kiến.';
    if (empty($description)) $errors[] = 'Vui lòng nhập mô tả công việc.';
    if (empty($requirements)) $errors[] = 'Vui lòng nhập yêu cầu ứng viên.';

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("
                UPDATE jobs SET 
                    title = ?, company = ?, location = ?, category = ?, salary = ?, 
                    salary_value = ?, job_type = ?, experience = ?, deadline = ?, 
                    description = ?, requirements = ?, status = ?
                WHERE id = ? AND employer_id = ?
            ");
            $upd->execute([
                $title, $company, $location, $category, $salary,
                $salary_value, $job_type, $experience, $deadline,
                $description, $requirements, $status, $job_id, $employer_id
            ]);

            $_SESSION['flash_message'] = 'Cập nhật tin tuyển dụng thành công!';
            $_SESSION['flash_type'] = 'success';
            header('Location: jobs.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã xảy ra lỗi khi cập nhật. Vui lòng thử lại.';
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
                <span class="section-eyebrow">Chỉnh sửa nội dung</span>
                <h1 class="section-title">Cập nhật tin tuyển dụng</h1>
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
                    <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="company">Tên công ty <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="text" id="company" name="company" class="form-control" value="<?= htmlspecialchars($company) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Địa điểm làm việc <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($location) ?>" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="category">Ngành nghề <span style="color:var(--status-danger-text);">*</span></label>
                        <select id="category" name="category" class="form-control form-select">
                            <?php foreach ($categories_list as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="job_type">Hình thức làm việc</label>
                        <select id="job_type" name="job_type" class="form-control form-select">
                            <option value="Toàn thời gian" <?= ($job_type === 'Toàn thời gian') ? 'selected' : '' ?>>Toàn thời gian</option>
                            <option value="Bán thời gian" <?= ($job_type === 'Bán thời gian') ? 'selected' : '' ?>>Bán thời gian</option>
                            <option value="Thực tập" <?= ($job_type === 'Thực tập') ? 'selected' : '' ?>>Thực tập</option>
                            <option value="Làm từ xa (Remote)" <?= ($job_type === 'Làm từ xa (Remote)') ? 'selected' : '' ?>>Làm từ xa (Remote)</option>
                            <option value="Hợp đồng dự án" <?= ($job_type === 'Hợp đồng dự án') ? 'selected' : '' ?>>Hợp đồng dự án</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="salary">Mức lương hiển thị <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="text" id="salary" name="salary" class="form-control" value="<?= htmlspecialchars($salary) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="experience">Yêu cầu kinh nghiệm</label>
                        <select id="experience" name="experience" class="form-control form-select">
                            <option value="Không yêu cầu" <?= ($experience === 'Không yêu cầu') ? 'selected' : '' ?>>Không yêu cầu</option>
                            <option value="Dưới 1 năm" <?= ($experience === 'Dưới 1 năm') ? 'selected' : '' ?>>Dưới 1 năm</option>
                            <option value="1 - 2 năm" <?= ($experience === '1 - 2 năm') ? 'selected' : '' ?>>1 - 2 năm</option>
                            <option value="2 - 3 năm" <?= ($experience === '2 - 3 năm') ? 'selected' : '' ?>>2 - 3 năm</option>
                            <option value="3 - 5 năm" <?= ($experience === '3 - 5 năm') ? 'selected' : '' ?>>3 - 5 năm</option>
                            <option value="Trên 5 năm" <?= ($experience === 'Trên 5 năm') ? 'selected' : '' ?>>Trên 5 năm</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="deadline">Hạn nộp hồ sơ</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" value="<?= htmlspecialchars($deadline ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Mô tả công việc <span style="color:var(--status-danger-text);">*</span></label>
                    <textarea id="description" name="description" class="form-control form-textarea" style="min-height:160px;" required><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="requirements">Yêu cầu ứng viên <span style="color:var(--status-danger-text);">*</span></label>
                    <textarea id="requirements" name="requirements" class="form-control form-textarea" style="min-height:160px;" required><?= htmlspecialchars($requirements) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Trạng thái phát hành</label>
                    <div style="display:flex; gap:16px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="active" <?= ($status === 'active') ? 'checked' : '' ?>>
                            <span>Đang tuyển dụng</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="inactive" <?= ($status === 'inactive') ? 'checked' : '' ?>>
                            <span>Tạm dừng tuyển</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="status" value="draft" <?= ($status === 'draft') ? 'checked' : '' ?>>
                            <span>Lưu bản nháp</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:32px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                    <a href="jobs.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" style="padding:14px 28px;">Lưu thay đổi →</button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>

<?php
/**
 * ============================================================
 * FILE: admin/job-create.php
 * MÔ TẢ: Module Đăng tin tuyển dụng độc lập dành cho Quản trị viên
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Tạo Tin Tuyển Dụng Mới (Admin)';
$errors = [];

// Lấy danh sách doanh nghiệp để Admin chỉ định người đăng
$employers = $pdo->query("
    SELECT u.id, u.name, u.email, ep.company_name
    FROM users u
    LEFT JOIN employer_profiles ep ON ep.user_id = u.id
    WHERE u.role = 'employer'
    ORDER BY COALESCE(ep.company_name, u.name) ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh mục và địa điểm từ CSDL
$categories_list = get_categories($pdo);
$locations_list = get_locations($pdo);

$employer_id = !empty($_GET['employer_id']) ? (int)$_GET['employer_id'] : 0;
$title = '';
$company = '';
$location = 'Hà Nội';
$category = 'Công nghệ thông tin';
$job_type = 'Toàn thời gian';
$quantity = 1;
$level = 'Nhân viên';
$experience = 'Không yêu cầu';
$salary_type = 'deal';
$salary = 'Thỏa thuận';
$min_salary = 0;
$max_salary = 0;
$deadline = date('Y-m-d', strtotime('+30 days'));
$description = '';
$requirements = '';
$benefits = '';
$education_level = 'Không yêu cầu';
$working_time = 'Thứ 2 - Thứ 6 (8h30 - 17h30)';
$contact_name = '';
$contact_email = '';
$is_featured = 0;
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employer_id = !empty($_POST['employer_id']) ? (int)$_POST['employer_id'] : null;
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $location = trim($_POST['location'] ?? 'Hà Nội');
    $category = trim($_POST['category'] ?? 'Công nghệ thông tin');
    $job_type = trim($_POST['job_type'] ?? 'Toàn thời gian');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $level = trim($_POST['level'] ?? 'Nhân viên');
    $experience = trim($_POST['experience'] ?? 'Không yêu cầu');
    $salary_type = trim($_POST['salary_type'] ?? 'deal');
    $salary = trim($_POST['salary'] ?? 'Thỏa thuận');
    $min_salary = (int)($_POST['min_salary'] ?? 0);
    $max_salary = (int)($_POST['max_salary'] ?? 0);
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $benefits = trim($_POST['benefits'] ?? '');
    $education_level = trim($_POST['education_level'] ?? 'Không yêu cầu');
    $working_time = trim($_POST['working_time'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? 'active', ['active', 'pending', 'draft', 'inactive']) ? $_POST['status'] : 'active';

    // Tự động gán tên công ty nếu chọn employer
    if (!empty($employer_id) && empty($company)) {
        foreach ($employers as $emp) {
            if ($emp['id'] === $employer_id) {
                $company = $emp['company_name'] ?: $emp['name'];
                break;
            }
        }
    }

    if (empty($title)) $errors[] = 'Vui lòng nhập tiêu đề vị trí tuyển dụng.';
    if (empty($company)) $errors[] = 'Vui lòng nhập tên công ty hoặc chọn doanh nghiệp chỉ định.';
    if (empty($location)) $errors[] = 'Vui lòng chọn địa điểm làm việc.';
    if (empty($description)) $errors[] = 'Vui lòng nhập mô tả công việc.';
    if (empty($requirements)) $errors[] = 'Vui lòng nhập yêu cầu ứng viên.';

    if (empty($errors)) {
        try {
            $salary_value = ($salary_type === 'range') ? round(($min_salary + $max_salary) / 2) : $min_salary;

            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    employer_id, title, company, location, category, 
                    salary, salary_value, min_salary, max_salary, salary_type,
                    job_type, quantity, level, experience, deadline, 
                    description, requirements, benefits, education_level,
                    working_time, contact_name, contact_email, is_featured, 
                    status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, NOW()
                )
            ");

            $stmt->execute([
                $employer_id, $title, $company, $location, $category,
                $salary, $salary_value, $min_salary, $max_salary, $salary_type,
                $job_type, $quantity, $level, $experience, $deadline,
                $description, $requirements, $benefits, $education_level,
                $working_time, $contact_name, $contact_email, $is_featured,
                $status
            ]);

            $new_job_id = $pdo->lastInsertId();
            log_activity($pdo, $admin_id, 'JOB_CREATE_ADMIN', "Tạo tin tuyển dụng mới ID #{$new_job_id}: {$title}");

            // Nếu gán cho employer, gửi thông báo cho employer đó
            if ($employer_id) {
                create_notification($pdo, $employer_id, 'Tin tuyển dụng mới đã được tạo', "Quản trị viên đã khởi tạo tin tuyển dụng '{$title}' cho doanh nghiệp của bạn.", "employer/jobs.php");
            }

            $_SESSION['flash_message'] = 'Đã tạo tin tuyển dụng thành công với mã #' . $new_job_id . '.';
            $_SESSION['flash_type'] = 'success';
            header('Location: jobs.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Đã xảy ra lỗi khi lưu vào cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}
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
                <h1 class="section-title">Tạo tin tuyển dụng mới</h1>
                <p class="section-desc">Đăng tin trực tiếp với toàn quyền quản trị hoặc chỉ định cho doanh nghiệp thành viên.</p>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">← Quay lại danh sách</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <div class="form-card" style="max-width:960px;">
            <form method="POST">
                <!-- KHỐI 1: CHỈ ĐỊNH DOANH NGHIỆP & VỊ TRÍ -->
                <div style="border-bottom:1px solid var(--line); padding-bottom:24px; margin-bottom:24px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">1. Thông tin doanh nghiệp & Vị trí</h3>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="employer_id">Gán cho Doanh nghiệp (Tùy chọn)</label>
                            <select id="employer_id" name="employer_id" class="form-control form-select" onchange="autoFillCompany(this)">
                                <option value="">-- Tin do Hệ thống trực tiếp đăng --</option>
                                <?php foreach ($employers as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" data-company="<?= htmlspecialchars($emp['company_name'] ?: $emp['name']) ?>" <?= ($employer_id === (int)$emp['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['company_name'] ?: $emp['name']) ?> (<?= htmlspecialchars($emp['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="company">Tên hiển thị công ty <span style="color:var(--status-danger-text);">*</span></label>
                            <input type="text" id="company" name="company" class="form-control" placeholder="Tên công ty tuyển dụng" value="<?= htmlspecialchars($company) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="title">Tiêu đề vị trí tuyển dụng <span style="color:var(--status-danger-text);">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Ví dụ: Senior Backend Engineer (NodeJS / Golang)" value="<?= htmlspecialchars($title) ?>" required>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="category">Ngành nghề <span style="color:var(--status-danger-text);">*</span></label>
                            <select id="category" name="category" class="form-control form-select">
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($category === $cat['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="location">Địa điểm làm việc <span style="color:var(--status-danger-text);">*</span></label>
                            <select id="location" name="location" class="form-control form-select">
                                <?php foreach ($locations_list as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc['name']) ?>" <?= ($location === $loc['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="job_type">Hình thức</label>
                            <select id="job_type" name="job_type" class="form-control form-select">
                                <option value="Toàn thời gian" <?= ($job_type === 'Toàn thời gian') ? 'selected' : '' ?>>Toàn thời gian</option>
                                <option value="Bán thời gian" <?= ($job_type === 'Bán thời gian') ? 'selected' : '' ?>>Bán thời gian</option>
                                <option value="Thực tập" <?= ($job_type === 'Thực tập') ? 'selected' : '' ?>>Thực tập</option>
                                <option value="Làm từ xa (Remote)" <?= ($job_type === 'Làm từ xa (Remote)') ? 'selected' : '' ?>>Làm từ xa (Remote)</option>
                                <option value="Hợp đồng dự án" <?= ($job_type === 'Hợp đồng dự án') ? 'selected' : '' ?>>Hợp đồng dự án</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- KHỐI 2: ĐÃI NGỘ & TIÊU CHÍ TUYỂN DỤNG -->
                <div style="border-bottom:1px solid var(--line); padding-bottom:24px; margin-bottom:24px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">2. Chế độ lương & Yêu cầu nhân sự</h3>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="salary_type">Kiểu mức lương</label>
                            <select id="salary_type" name="salary_type" class="form-control form-select" onchange="toggleSalaryFields(this.value)">
                                <option value="deal" <?= ($salary_type === 'deal') ? 'selected' : '' ?>>Thỏa thuận</option>
                                <option value="range" <?= ($salary_type === 'range') ? 'selected' : '' ?>>Trong khoảng (Min - Max)</option>
                                <option value="from" <?= ($salary_type === 'from') ? 'selected' : '' ?>>Từ mức tối thiểu</option>
                                <option value="up_to" <?= ($salary_type === 'up_to') ? 'selected' : '' ?>>Lên đến tối đa</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="salary">Mức lương hiển thị <span style="color:var(--status-danger-text);">*</span></label>
                            <input type="text" id="salary" name="salary" class="form-control" placeholder="Ví dụ: 18 - 30 triệu" value="<?= htmlspecialchars($salary) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="deadline">Hạn chót ứng tuyển</label>
                            <input type="date" id="deadline" name="deadline" class="form-control" value="<?= htmlspecialchars($deadline) ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="quantity">Số lượng tuyển</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?= (int)$quantity ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="level">Cấp bậc</label>
                            <select id="level" name="level" class="form-control form-select">
                                <option value="Thực tập sinh" <?= ($level === 'Thực tập sinh') ? 'selected' : '' ?>>Thực tập sinh</option>
                                <option value="Nhân viên" <?= ($level === 'Nhân viên') ? 'selected' : '' ?>>Nhân viên</option>
                                <option value="Chuyên viên / Senior" <?= ($level === 'Chuyên viên / Senior') ? 'selected' : '' ?>>Chuyên viên / Senior</option>
                                <option value="Trưởng nhóm / Leader" <?= ($level === 'Trưởng nhóm / Leader') ? 'selected' : '' ?>>Trưởng nhóm / Leader</option>
                                <option value="Trưởng phòng / Manager" <?= ($level === 'Trưởng phòng / Manager') ? 'selected' : '' ?>>Trưởng phòng / Manager</option>
                                <option value="Giám đốc / Director" <?= ($level === 'Giám đốc / Director') ? 'selected' : '' ?>>Giám đốc / Director</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="experience">Kinh nghiệm</label>
                            <select id="experience" name="experience" class="form-control form-select">
                                <option value="Không yêu cầu" <?= ($experience === 'Không yêu cầu') ? 'selected' : '' ?>>Không yêu cầu</option>
                                <option value="Dưới 1 năm" <?= ($experience === 'Dưới 1 năm') ? 'selected' : '' ?>>Dưới 1 năm</option>
                                <option value="1 - 2 năm" <?= ($experience === '1 - 2 năm') ? 'selected' : '' ?>>1 - 2 năm</option>
                                <option value="2 - 3 năm" <?= ($experience === '2 - 3 năm') ? 'selected' : '' ?>>2 - 3 năm</option>
                                <option value="3 - 5 năm" <?= ($experience === '3 - 5 năm') ? 'selected' : '' ?>>3 - 5 năm</option>
                                <option value="Trên 5 năm" <?= ($experience === 'Trên 5 năm') ? 'selected' : '' ?>>Trên 5 năm</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- KHỐI 3: NỘI DUNG TUYỂN DỤNG CHI TIẾT -->
                <div style="border-bottom:1px solid var(--line); padding-bottom:24px; margin-bottom:24px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">3. Chi tiết mô tả & Quyền lợi</h3>

                    <div class="form-group">
                        <label class="form-label" for="description">Mô tả công việc <span style="color:var(--status-danger-text);">*</span></label>
                        <textarea id="description" name="description" class="form-control form-textarea" style="min-height:140px;" placeholder="Liệt kê trách nhiệm công việc, mục tiêu cần đạt..." required><?= htmlspecialchars($description) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="requirements">Yêu cầu ứng viên <span style="color:var(--status-danger-text);">*</span></label>
                        <textarea id="requirements" name="requirements" class="form-control form-textarea" style="min-height:140px;" placeholder="Yêu cầu về kỹ năng, thái độ, công nghệ sử dụng..." required><?= htmlspecialchars($requirements) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="benefits">Quyền lợi & Đãi ngộ</label>
                        <textarea id="benefits" name="benefits" class="form-control form-textarea" style="min-height:100px;" placeholder="Bảo hiểm, thưởng lễ tết, lộ trình đào tạo, môi trường làm việc..."><?= htmlspecialchars($benefits) ?></textarea>
                    </div>
                </div>

                <!-- KHỐI 4: TRẠNG THÁI & TÙY CHỌN QUẢN TRỊ -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:center;">
                    <div class="form-group">
                        <label class="form-label">Trạng thái công khai</label>
                        <select name="status" class="form-control form-select">
                            <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Hiển thị ngay (Đang tuyển)</option>
                            <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Bản nháp</option>
                            <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Tạm dừng</option>
                        </select>
                    </div>

                    <div class="form-group" style="padding-top:20px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="is_featured" value="1" <?= ($is_featured === 1) ? 'checked' : '' ?> style="width:18px; height:18px;">
                            <span style="font-weight:700; color:var(--ink);">Đánh dấu là Tin Tuyển Dụng Nổi Bật (Featured Badge)</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:32px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                    <a href="jobs.php" class="btn btn-secondary">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary" style="padding:14px 32px;">Lưu & Đăng Tin →</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function autoFillCompany(sel) {
    var opt = sel.options[sel.selectedIndex];
    var cName = opt.getAttribute('data-company');
    if (cName) {
        document.getElementById('company').value = cName;
    }
}
function toggleSalaryFields(type) {
    var salInput = document.getElementById('salary');
    if (type === 'deal') {
        salInput.value = 'Thỏa thuận';
    } else if (type === 'range') {
        salInput.value = '15 - 25 triệu';
    } else if (type === 'from') {
        salInput.value = 'Từ 15 triệu';
    } else if (type === 'up_to') {
        salInput.value = 'Lên đến 35 triệu';
    }
}
</script>

</body>
</html>

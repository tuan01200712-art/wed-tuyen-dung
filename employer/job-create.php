<?php
/**
 * ============================================================
 * FILE: employer/job-create.php
 * MÔ TẢ: Quy trình Đăng tin Tuyển dụng 5 Bước Chuyên nghiệp — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Đăng Tin Tuyển Dụng Mới';
$errors = [];

// Lấy thông tin công ty từ hồ sơ
$ep_stmt = $pdo->prepare("SELECT company_name, industry, address, contact_person, contact_phone FROM employer_profiles WHERE user_id = ?");
$ep_stmt->execute([$employer_id]);
$ep = $ep_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$default_company = $ep['company_name'] ?? $_SESSION['user_name'];
$default_location = $ep['address'] ?? 'Hà Nội';

// Lấy danh mục ngành nghề, địa điểm và chiến dịch tuyển dụng của doanh nghiệp
$categories_list = get_categories($pdo);
$locations_list = get_locations($pdo);
$campaigns_list = [];
try {
    $c_stmt = $pdo->prepare("SELECT id, title FROM campaigns WHERE employer_id = ? AND status = 'active' ORDER BY created_at DESC");
    $c_stmt->execute([$employer_id]);
    $campaigns_list = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$title = '';
$company = $default_company;
$location = 'Hà Nội';
$category = 'Công nghệ thông tin';
$job_type = 'Toàn thời gian';
$quantity = 1;
$level = 'Nhân viên';
$experience = 'Không yêu cầu';
$salary_type = 'range';
$salary = '15 - 25 triệu';
$min_salary = 15000000;
$max_salary = 25000000;
$deadline = date('Y-m-d', strtotime('+30 days'));
$description = '';
$requirements = '';
$benefits = '';
$education_level = 'Đại học trở lên';
$working_time = 'Thứ 2 - Thứ 6 (8h30 - 17h30)';
$contact_name = $ep['contact_person'] ?? $_SESSION['user_name'];
$contact_email = $_SESSION['user_email'];
$campaign_id = null;
$status = 'pending'; // Hệ thống JobFinder: Mặc định gửi duyệt

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? $default_company);
    $location = trim($_POST['location'] ?? 'Hà Nội');
    $category = trim($_POST['category'] ?? 'Công nghệ thông tin');
    $job_type = trim($_POST['job_type'] ?? 'Toàn thời gian');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $level = trim($_POST['level'] ?? 'Nhân viên');
    $experience = trim($_POST['experience'] ?? 'Không yêu cầu');
    $salary_type = trim($_POST['salary_type'] ?? 'range');
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
    $campaign_id = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
    $submit_action = $_POST['submit_action'] ?? 'send_approval';

    $status = ($submit_action === 'save_draft') ? 'draft' : 'active';

    if (empty($title)) $errors[] = 'Vui lòng nhập tiêu đề vị trí tuyển dụng.';
    if (empty($company)) $errors[] = 'Vui lòng nhập tên công ty.';
    if (empty($location)) $errors[] = 'Vui lòng chọn địa điểm làm việc.';
    if (empty($description)) $errors[] = 'Vui lòng nhập mô tả công việc.';
    if (empty($requirements)) $errors[] = 'Vui lòng nhập yêu cầu ứng viên.';

    if (empty($errors)) {
        try {
            $salary_value = ($salary_type === 'range') ? round(($min_salary + $max_salary) / 2) : $min_salary;

            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    employer_id, campaign_id, title, company, location, category,
                    salary, salary_value, min_salary, max_salary, salary_type,
                    job_type, quantity, level, experience, deadline,
                    description, requirements, benefits, education_level,
                    working_time, contact_name, contact_email, is_featured,
                    status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, 0,
                    ?, NOW()
                )
            ");

            $stmt->execute([
                $employer_id, $campaign_id, $title, $company, $location, $category,
                $salary, $salary_value, $min_salary, $max_salary, $salary_type,
                $job_type, $quantity, $level, $experience, $deadline,
                $description, $requirements, $benefits, $education_level,
                $working_time, $contact_name, $contact_email,
                $status
            ]);

            $new_job_id = $pdo->lastInsertId();
            log_activity($pdo, $employer_id, 'EMPLOYER_JOB_CREATE', "Đăng tin tuyển dụng mới ID #{$new_job_id}: {$title} (Trạng thái: {$status})");

            if ($status === 'active') {
                $admin_id_first = (int)$pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
                if ($admin_id_first) {
                    create_notification($pdo, $admin_id_first, 'Tin tuyển dụng mới vừa được đăng', "Doanh nghiệp {$company} vừa đăng tin '{$title}'.", "admin/jobs.php");
                }
                $_SESSION['flash_message'] = 'Đăng tin thành công! Tin tuyển dụng đã hiển thị công khai trên hệ thống.';
            } else {
                $_SESSION['flash_message'] = 'Đã lưu tin tuyển dụng dưới dạng Bản nháp.';
            }

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
    <style>
        .wizard-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 32px;
            padding: 0 10px;
        }
        .wizard-steps::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 30px;
            right: 30px;
            height: 2px;
            background: #E5E7EB;
            z-index: 1;
        }
        .wizard-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }
        .wizard-step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 2px solid #D1D5DB;
            color: #6B7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .wizard-step.active .wizard-step-circle {
            background: var(--admin-bg);
            border-color: var(--admin-bg);
            color: var(--accent);
            box-shadow: 0 0 0 4px rgba(11, 31, 23, 0.1);
        }
        .wizard-step.completed .wizard-step-circle {
            background: #16A34A;
            border-color: #16A34A;
            color: #FFFFFF;
        }
        .wizard-step-label {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #6B7280;
        }
        .wizard-step.active .wizard-step-label {
            color: var(--ink);
        }
        .step-panel {
            display: none;
        }
        .step-panel.active {
            display: block;
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title">Tạo tin tuyển dụng mới</h1>
                <p class="section-desc">Điền thông tin theo 5 bước chuẩn hóa để tin đăng đạt tỷ lệ ứng tuyển cao nhất.</p>
            </div>
            <a href="jobs.php" class="btn btn-secondary btn-sm">← Quay lại danh sách</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <!-- THANH TIẾN TRÌNH 5 BƯỚC -->
        <div class="wizard-steps">
            <div class="wizard-step active" onclick="goToStep(1)" id="stepTab-1">
                <div class="wizard-step-circle">1</div>
                <span class="wizard-step-label">Thông tin chung</span>
            </div>
            <div class="wizard-step" onclick="goToStep(2)" id="stepTab-2">
                <div class="wizard-step-circle">2</div>
                <span class="wizard-step-label">Lương & Tiêu chí</span>
            </div>
            <div class="wizard-step" onclick="goToStep(3)" id="stepTab-3">
                <div class="wizard-step-circle">3</div>
                <span class="wizard-step-label">Mô tả & Quyền lợi</span>
            </div>
            <div class="wizard-step" onclick="goToStep(4)" id="stepTab-4">
                <div class="wizard-step-circle">4</div>
                <span class="wizard-step-label">Nhận hồ sơ</span>
            </div>
            <div class="wizard-step" onclick="goToStep(5)" id="stepTab-5">
                <div class="wizard-step-circle">5</div>
                <span class="wizard-step-label">Xem trước & Gửi</span>
            </div>
        </div>

        <div class="form-card" style="max-width:920px; margin:0 auto;">
            <form method="POST" id="jobCreateForm">
                <!-- BƯỚC 1: THÔNG TIN CHUNG -->
                <div class="step-panel active" id="stepPanel-1">
                    <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:20px;">Bước 1: Vị trí & Địa điểm làm việc</h3>

                    <div class="form-group">
                        <label class="form-label" for="title">Tiêu đề vị trí tuyển dụng <span style="color:#DC2626;">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Ví dụ: Senior Backend Engineer (NodeJS / Golang)" value="<?= htmlspecialchars($title) ?>" required>
                        <span style="font-size:12px; color:var(--muted); margin-top:4px; display:block;">Nên nêu rõ cấp bậc, kỹ năng chính để ứng viên dễ tìm thấy.</span>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="company">Tên công ty hiển thị <span style="color:#DC2626;">*</span></label>
                            <input type="text" id="company" name="company" class="form-control" value="<?= htmlspecialchars($company) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="category">Ngành nghề chính <span style="color:#DC2626;">*</span></label>
                            <select id="category" name="category" class="form-control form-select">
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($category === $cat['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="location">Địa điểm làm việc <span style="color:#DC2626;">*</span></label>
                            <select id="location" name="location" class="form-control form-select">
                                <?php foreach ($locations_list as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc['name']) ?>" <?= ($location === $loc['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="job_type">Hình thức làm việc</label>
                            <select id="job_type" name="job_type" class="form-control form-select">
                                <option value="Toàn thời gian">Toàn thời gian</option>
                                <option value="Bán thời gian">Bán thời gian</option>
                                <option value="Thực tập">Thực tập</option>
                                <option value="Làm việc từ xa">Làm việc từ xa</option>
                                <option value="Linh hoạt">Linh hoạt</option>
                                <option value="Hợp đồng dự án">Hợp đồng dự án</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="quantity">Số lượng cần tuyển</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; margin-top:28px;">
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Tiếp theo: Lương & Tiêu chí →</button>
                    </div>
                </div>

                <!-- BƯỚC 2: THÔNG TIN CÔNG VIỆC & LƯƠNG -->
                <div class="step-panel" id="stepPanel-2">
                    <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:20px;">Bước 2: Chế độ lương, cấp bậc & thời hạn</h3>

                    <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="salary_type">Cách hiển thị mức lương</label>
                            <select id="salary_type" name="salary_type" class="form-control form-select" onchange="updateSalaryHelper(this.value)">
                                <option value="range">Trong khoảng (Min - Max)</option>
                                <option value="deal">Thỏa thuận</option>
                                <option value="from">Từ mức tối thiểu</option>
                                <option value="up_to">Lên đến tối đa</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="salary">Mức lương hiển thị cho ứng viên <span style="color:#DC2626;">*</span></label>
                            <input type="text" id="salary" name="salary" class="form-control" placeholder="Ví dụ: 15 - 25 triệu" value="<?= htmlspecialchars($salary) ?>" required>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="level">Cấp bậc chuyên môn</label>
                            <select id="level" name="level" class="form-control form-select">
                                <option value="Thực tập sinh">Thực tập sinh</option>
                                <option value="Nhân viên" selected>Nhân viên</option>
                                <option value="Chuyên viên / Senior">Chuyên viên / Senior</option>
                                <option value="Trưởng nhóm / Leader">Trưởng nhóm / Leader</option>
                                <option value="Trưởng phòng / Manager">Trưởng phòng / Manager</option>
                                <option value="Giám đốc / Director">Giám đốc / Director</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="experience">Yêu cầu kinh nghiệm</label>
                            <select id="experience" name="experience" class="form-control form-select">
                                <option value="Không yêu cầu">Không yêu cầu</option>
                                <option value="Dưới 1 năm">Dưới 1 năm</option>
                                <option value="1 - 2 năm">1 - 2 năm</option>
                                <option value="2 - 3 năm">2 - 3 năm</option>
                                <option value="3 - 5 năm">3 - 5 năm</option>
                                <option value="Trên 5 năm">Trên 5 năm</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="deadline">Hạn chót ứng tuyển</label>
                            <input type="date" id="deadline" name="deadline" class="form-control" value="<?= htmlspecialchars($deadline) ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="education_level">Trình độ học vấn</label>
                            <select id="education_level" name="education_level" class="form-control form-select">
                                <option value="Không yêu cầu">Không yêu cầu</option>
                                <option value="Trung cấp / Cao đẳng">Trung cấp / Cao đẳng</option>
                                <option value="Đại học trở lên" selected>Đại học trở lên</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="working_time">Thời gian làm việc</label>
                            <input type="text" id="working_time" name="working_time" class="form-control" placeholder="Ví dụ: Thứ 2 - Thứ 6 (8h30 - 17h30)" value="<?= htmlspecialchars($working_time) ?>">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-top:28px;">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(1)">← Quay lại</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Tiếp theo: Mô tả & Quyền lợi →</button>
                    </div>
                </div>

                <!-- BƯỚC 3: MÔ TẢ & QUYỀN LỢI -->
                <div class="step-panel" id="stepPanel-3">
                    <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:20px;">Bước 3: Nội dung công việc, yêu cầu & quyền lợi</h3>

                    <div class="form-group">
                        <label class="form-label" for="description">Mô tả công việc <span style="color:#DC2626;">*</span></label>
                        <textarea id="description" name="description" class="form-control form-textarea" style="min-height:140px;" placeholder="• Trách nhiệm công việc chính hàng ngày...&#10;• Tham gia phát triển sản phẩm...&#10;• Phối hợp với đội ngũ..." required><?= htmlspecialchars($description) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="requirements">Yêu cầu ứng viên <span style="color:#DC2626;">*</span></label>
                        <textarea id="requirements" name="requirements" class="form-control form-textarea" style="min-height:140px;" placeholder="• Kỹ năng chuyên môn bắt buộc...&#10;• Khả năng làm việc nhóm, tư duy giải quyết vấn đề...&#10;• Ưu tiên các ứng viên có chứng chỉ..." required><?= htmlspecialchars($requirements) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="benefits">Chế độ đãi ngộ & Quyền lợi hấp dẫn</label>
                        <textarea id="benefits" name="benefits" class="form-control form-textarea" style="min-height:120px;" placeholder="• Thu nhập cạnh tranh, review lương định kỳ...&#10;• Bảo hiểm sức khỏe cao cấp...&#10;• Phụ cấp ăn trưa, teambuilding, du lịch hàng năm..."><?= htmlspecialchars($benefits) ?></textarea>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-top:28px;">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(2)">← Quay lại</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(4)">Tiếp theo: Nhận hồ sơ →</button>
                    </div>
                </div>

                <!-- BƯỚC 4: THÔNG TIN NHẬN HỒ SƠ & CHIẾN DỊCH -->
                <div class="step-panel" id="stepPanel-4">
                    <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:20px;">Bước 4: Thông tin liên hệ & Gắn chiến dịch</h3>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="contact_name">Người phụ trách tuyển dụng</label>
                            <input type="text" id="contact_name" name="contact_name" class="form-control" value="<?= htmlspecialchars($contact_name) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contact_email">Email nhận thông báo hồ sơ</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?= htmlspecialchars($contact_email) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="campaign_id">Gắn vào Chiến dịch tuyển dụng (Tùy chọn)</label>
                        <select id="campaign_id" name="campaign_id" class="form-control form-select">
                            <option value="">-- Không gắn chiến dịch (Tin độc lập) --</option>
                            <?php foreach ($campaigns_list as $camp): ?>
                                <option value="<?= $camp['id'] ?>" <?= ($campaign_id === (int)$camp['id']) ? 'selected' : '' ?>>
                                    🎯 <?= htmlspecialchars($camp['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="font-size:12px; color:var(--muted); margin-top:4px; display:block;">Gắn vào chiến dịch giúp bạn quản trị ngân sách và tiến độ tuyển dụng tập trung.</span>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-top:28px;">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(3)">← Quay lại</button>
                        <button type="button" class="btn btn-primary" onclick="generatePreview(); nextStep(5);">Xem trước tin tuyển dụng →</button>
                    </div>
                </div>

                <!-- BƯỚC 5: XEM TRƯỚC (PREVIEW) & XÁC NHẬN -->
                <div class="step-panel" id="stepPanel-5">
                    <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:16px;">Bước 5: Xem trước tin tuyển dụng & Xuất bản</h3>
                    <p style="font-size:13px; color:var(--muted); margin-bottom:20px;">Vui lòng kiểm tra lại thông tin trước khi hoàn tất đăng tin.</p>

                    <!-- Khung Preview giống Job Detail thực tế -->
                    <div style="border:1px solid var(--line); border-radius:14px; padding:24px; background:#F8FAFC; margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
                            <div>
                                <span class="badge badge--pending" id="prevCategory">Ngành nghề</span>
                                <h2 style="font-size:22px; font-weight:800; color:var(--ink); margin-top:6px;" id="prevTitle">Tiêu đề</h2>
                                <div style="font-size:14px; font-weight:600; color:var(--text); margin-top:4px;" id="prevCompany">Công ty</div>
                            </div>
                            <span class="chip" style="font-size:16px; font-weight:800; background:var(--status-success-bg); color:var(--status-success-text);" id="prevSalary">15 - 25 triệu</span>
                        </div>

                        <div style="display:flex; gap:16px; font-size:13px; color:var(--muted); border-top:1px solid var(--line); border-bottom:1px solid var(--line); padding:10px 0; margin-bottom:16px; flex-wrap:wrap;">
                            <div><strong id="prevLocation">Hà Nội</strong></div>
                            <div><strong id="prevJobType">Toàn thời gian</strong></div>
                            <div>🎓 <strong id="prevLevel">Nhân viên</strong></div>
                            <div>Hạn nộp: <strong id="prevDeadline">30 ngày tới</strong></div>
                        </div>

                        <div style="margin-bottom:14px;">
                            <strong style="font-size:14px; color:var(--ink);">Mô tả công việc:</strong>
                            <div id="prevDesc" style="font-size:13px; color:var(--text); white-space:pre-line; margin-top:6px; line-height:1.6;"></div>
                        </div>

                        <div>
                            <strong style="font-size:14px; color:var(--ink);">Quyền lợi ứng viên:</strong>
                            <div id="prevBenefits" style="font-size:13px; color:var(--text); white-space:pre-line; margin-top:6px; line-height:1.6;"></div>
                        </div>
                    </div>

                    <!-- 2 NÚT THAO TÁC: LƯU NHÁP HOẶC GỬI DUYỆT -->
                    <div style="display:flex; justify-content:space-between; align-items:center; padding-top:20px; border-top:1px solid var(--line);">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(4)">← Chỉnh sửa lại</button>
                        
                        <div style="display:flex; gap:12px;">
                            <button type="submit" name="submit_action" value="save_draft" class="btn btn-secondary">
                                Lưu bản nháp (Draft)
                            </button>
                            <button type="submit" name="submit_action" value="send_approval" class="btn btn-primary" style="padding:14px 32px;">
                                Gửi kiểm duyệt (Send for Approval) →
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
var currentStep = 1;

function goToStep(step) {
    if (step < 1 || step > 5) return;
    
    // Ẩn panel hiện tại
    document.querySelectorAll('.step-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.wizard-step').forEach(function(s) { s.classList.remove('active'); });

    // Hiển thị panel mới
    document.getElementById('stepPanel-' + step).classList.add('active');
    document.getElementById('stepTab-' + step).classList.add('active');

    // Đánh dấu completed cho các bước trước
    for (var i = 1; i < step; i++) {
        document.getElementById('stepTab-' + i).classList.add('completed');
    }
    currentStep = step;
    window.scrollTo({top: 100, behavior: 'smooth'});
}

function nextStep(target) {
    if (target === 2) {
        var title = document.getElementById('title').value.trim();
        var company = document.getElementById('company').value.trim();
        if (!title || !company) {
            alert('Vui lòng nhập đầy đủ tiêu đề và tên công ty trước khi sang bước tiếp theo.');
            return;
        }
    } else if (target === 4) {
        var desc = document.getElementById('description').value.trim();
        var req = document.getElementById('requirements').value.trim();
        if (!desc || !req) {
            alert('Vui lòng nhập mô tả công việc và yêu cầu ứng viên.');
            return;
        }
    }
    goToStep(target);
}

function updateSalaryHelper(type) {
    var sal = document.getElementById('salary');
    if (type === 'deal') sal.value = 'Thỏa thuận';
    else if (type === 'range') sal.value = '15 - 25 triệu';
    else if (type === 'from') sal.value = 'Từ 15 triệu';
    else if (type === 'up_to') sal.value = 'Lên đến 35 triệu';
}

function generatePreview() {
    document.getElementById('prevTitle').innerText = document.getElementById('title').value || 'Chưa nhập tiêu đề';
    document.getElementById('prevCompany').innerText = document.getElementById('company').value || 'Công ty';
    document.getElementById('prevCategory').innerText = document.getElementById('category').value;
    document.getElementById('prevSalary').innerText = document.getElementById('salary').value || 'Thỏa thuận';
    document.getElementById('prevLocation').innerText = document.getElementById('location').value;
    document.getElementById('prevJobType').innerText = document.getElementById('job_type').value;
    document.getElementById('prevLevel').innerText = document.getElementById('level').value;
    document.getElementById('prevDeadline').innerText = document.getElementById('deadline').value || 'Không đặt hạn';
    document.getElementById('prevDesc').innerText = document.getElementById('description').value || 'Chưa nhập mô tả';
    document.getElementById('prevBenefits').innerText = document.getElementById('benefits').value || 'Theo quy định công ty';
}
</script>

</body>
</html>

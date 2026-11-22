<?php
/**
 * ============================================================
 * FILE: employer/profile.php
 * MÔ TẢ: Cập nhật hồ sơ công ty của nhà tuyển dụng
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Hồ Sơ Doanh Nghiệp';

$stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->execute([$employer_id]);
$profile = $stmt->fetch();

$stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$employer_id]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $website      = trim($_POST['website'] ?? '');
    $industry     = trim($_POST['industry'] ?? '');
    $size         = trim($_POST['size'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $address      = trim($_POST['address'] ?? '');

    if ($company_name === '') $errors[] = 'Tên công ty là bắt buộc.';

    if (empty($errors)) {
        if ($profile) {
            $stmt = $pdo->prepare("UPDATE employer_profiles SET company_name=?, website=?, industry=?, size=?, description=?, address=? WHERE user_id=?");
            $stmt->execute([$company_name, $website, $industry, $size, $description, $address, $employer_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO employer_profiles (user_id, company_name, website, industry, size, description, address, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$employer_id, $company_name, $website, $industry, $size, $description, $address]);
        }
        $_SESSION['flash_message'] = 'Cập nhật hồ sơ doanh nghiệp thành công.';
        $_SESSION['flash_type'] = 'success';
        header('Location: profile.php');
        exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <span class="section-eyebrow">Hồ sơ thương hiệu</span>
                <h1 class="section-title">Hồ sơ công ty</h1>
                <p class="section-desc">Thông tin công ty được hiển thị công khai trên các tin tuyển dụng của bạn.</p>
            </div>
            <?php if (!empty($profile['is_verified'])): ?>
                <span class="badge badge--success" style="font-size:12px; padding:6px 12px;">ĐÃ XÁC MINH DOANH NGHIỆP ✓</span>
            <?php else: ?>
                <span class="badge badge--pending" style="font-size:12px; padding:6px 12px;">CHỜ XÁC MINH</span>
            <?php endif; ?>
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

        <div class="form-card" style="max-width:840px;">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="company_name">Tên công ty / Doanh nghiệp <span style="color:var(--status-danger-text);">*</span></label>
                    <input type="text" id="company_name" name="company_name" class="form-control" value="<?= htmlspecialchars($profile['company_name'] ?? '') ?>" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="website">Website công ty</label>
                        <input type="url" id="website" name="website" class="form-control" placeholder="https://example.com" value="<?= htmlspecialchars($profile['website'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="industry">Lĩnh vực hoạt động</label>
                        <input type="text" id="industry" name="industry" class="form-control" placeholder="Ví dụ: Công nghệ thông tin, Thương mại điện tử" value="<?= htmlspecialchars($profile['industry'] ?? '') ?>">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="size">Quy mô nhân sự</label>
                        <select id="size" name="size" class="form-control form-select">
                            <?php $current_size = $profile['size'] ?? ''; ?>
                            <option value="Dưới 10 nhân viên" <?= ($current_size === 'Dưới 10 nhân viên') ? 'selected' : '' ?>>Dưới 10 nhân viên</option>
                            <option value="10 - 50 nhân viên" <?= ($current_size === '10 - 50 nhân viên') ? 'selected' : '' ?>>10 - 50 nhân viên</option>
                            <option value="50 - 200 nhân viên" <?= ($current_size === '50 - 200 nhân viên') ? 'selected' : '' ?>>50 - 200 nhân viên</option>
                            <option value="200 - 500 nhân viên" <?= ($current_size === '200 - 500 nhân viên') ? 'selected' : '' ?>>200 - 500 nhân viên</option>
                            <option value="500+ nhân viên" <?= ($current_size === '500+ nhân viên') ? 'selected' : '' ?>>500+ nhân viên</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Địa chỉ trụ sở chính</label>
                        <input type="text" id="address" name="address" class="form-control" placeholder="Ví dụ: Tòa nhà FPT, Phố Duy Tân, Cầu Giấy, Hà Nội" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Giới thiệu văn hóa & môi trường làm việc</label>
                    <textarea id="description" name="description" class="form-control form-textarea" style="min-height:140px;" placeholder="Tầm nhìn, giá trị cốt lõi, đãi ngộ và môi trường làm việc tại công ty..."><?= htmlspecialchars($profile['description'] ?? '') ?></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; margin-top:28px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                    <button type="submit" class="btn btn-primary" style="padding:14px 28px;">Lưu hồ sơ công ty →</button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>

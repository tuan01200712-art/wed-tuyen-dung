<?php
/**
 * ============================================================
 * FILE: employer/profile.php
 * MÔ TẢ: Hồ sơ Doanh nghiệp, Thương hiệu & Xác thực Pháp lý — Hệ thống JobFinder
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-employer.php';

$employer_id = (int)$_SESSION['user_id'];
$page_title = 'Hồ Sơ Doanh Nghiệp';

$stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->execute([$employer_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$employer_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name    = trim($_POST['company_name'] ?? '');
    $website         = trim($_POST['website'] ?? '');
    $industry        = trim($_POST['industry'] ?? '');
    $size            = trim($_POST['size'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $tax_code        = trim($_POST['tax_code'] ?? '');
    $license_doc     = trim($_POST['license_document'] ?? '');
    $contact_person  = trim($_POST['contact_person'] ?? '');
    $contact_phone   = trim($_POST['contact_phone'] ?? '');

    if ($company_name === '') {
        $errors[] = 'Tên công ty là bắt buộc.';
    } elseif (mb_strlen($company_name) > 150) {
        $errors[] = 'Tên công ty không được vượt quá 150 ký tự.';
    }
    if ($website !== '' && mb_strlen($website) > 255) {
        $errors[] = 'Đường dẫn website không được vượt quá 255 ký tự.';
    }
    if ($address !== '' && mb_strlen($address) > 255) {
        $errors[] = 'Địa chỉ trụ sở không được vượt quá 255 ký tự.';
    }
    if ($tax_code !== '' && mb_strlen($tax_code) > 50) {
        $errors[] = 'Mã số thuế không được vượt quá 50 ký tự.';
    }
    if ($license_doc !== '' && mb_strlen($license_doc) > 255) {
        $errors[] = 'Thông tin giấy phép ĐKKD không được vượt quá 255 ký tự.';
    }
    if ($contact_person !== '' && mb_strlen($contact_person) > 100) {
        $errors[] = 'Tên người đại diện không được vượt quá 100 ký tự.';
    }
    if ($contact_phone !== '' && mb_strlen($contact_phone) > 50) {
        $errors[] = 'Số điện thoại liên hệ không được vượt quá 50 ký tự.';
    }

    if (empty($errors)) {
        try {
            if ($profile) {
                $stmt = $pdo->prepare("
                    UPDATE employer_profiles 
                    SET company_name = ?, website = ?, industry = ?, size = ?, 
                        description = ?, address = ?, tax_code = ?, license_document = ?, 
                        contact_person = ?, contact_phone = ?, updated_at = NOW() 
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $company_name, $website, $industry, $size,
                    $description, $address, $tax_code, $license_doc,
                    $contact_person, $contact_phone, $employer_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO employer_profiles (
                        user_id, company_name, website, industry, size, 
                        description, address, tax_code, license_document, 
                        contact_person, contact_phone, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $employer_id, $company_name, $website, $industry, $size,
                    $description, $address, $tax_code, $license_doc,
                    $contact_person, $contact_phone
                ]);
            }

            $_SESSION['flash_message'] = 'Cập nhật hồ sơ doanh nghiệp và thông tin pháp lý thành công.';
            $_SESSION['flash_type'] = 'success';
            header('Location: profile.php');
            exit;
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '22001') {
                $errors[] = 'Một hoặc nhiều trường dữ liệu vượt quá độ dài cho phép của cơ sở dữ liệu (ví dụ: Số điện thoại tối đa 20–50 ký tự). Vui lòng rút gọn và thử lại.';
            } else {
                $errors[] = 'Không thể lưu hồ sơ doanh nghiệp. Vui lòng kiểm tra lại thông tin và thử lại.';
            }
        }
    }
}

$categories_list = get_categories($pdo);
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
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 31, 23, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 650px;
            padding: 28px;
            box-shadow: var(--shadow-modal);
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="portal-layout">
    <?php include '../includes/employer-sidebar.php'; ?>

    <main class="portal-main">
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title">Hồ sơ công ty & Xác thực pháp lý</h1>
                <p class="section-desc">Cung cấp thông tin chuẩn xác để nhận huy hiệu Doanh nghiệp Xác thực từ JobFinder.</p>
            </div>
            
            <button type="button" class="btn btn-secondary btn-sm" onclick="openPreviewModal()">
                Xem trước trang công ty
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

        <!-- BANNER TRẠNG THÁI XÁC THỰC -->
        <?php if (!empty($profile['is_verified'])): ?>
            <div style="background:#ECFDF5; border:1px solid #A7F3D0; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; margin-bottom:24px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#16A34A; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:18px;">✓</div>
                <div>
                    <h3 style="font-size:15px; font-weight:800; color:#065F46; margin:0;">Doanh nghiệp đã được xác thực chính thức</h3>
                    <p style="font-size:12px; color:#047857; margin:2px 0 0;">Tin tuyển dụng của bạn được ưu tiên gắn huy hiệu uy tín và thu hút nhiều ứng viên tiềm năng hơn.</p>
                </div>
            </div>
        <?php else: ?>
            <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; margin-bottom:24px;">
                <div style="width:36px; height:36px; border-radius:50%; background:#F59E0B; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:18px;">!</div>
                <div>
                    <h3 style="font-size:15px; font-weight:800; color:#92400E; margin:0;">Chưa xác thực pháp lý doanh nghiệp</h3>
                    <p style="font-size:12px; color:#B45309; margin:2px 0 0;">Vui lòng điền Mã số thuế và thông tin giấy phép kinh doanh bên dưới để gửi yêu cầu xác thực tới Ban Quản trị.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-card" style="max-width:860px;">
            <form method="POST">
                <!-- KHỐI 1: THÔNG TIN THƯƠNG HIỆU -->
                <div style="border-bottom:1px solid var(--line); padding-bottom:20px; margin-bottom:20px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">1. Thông tin thương hiệu chung</h3>

                    <div class="form-group">
                        <label class="form-label" for="company_name">Tên công ty / Doanh nghiệp <span style="color:#DC2626;">*</span></label>
                        <input type="text" id="company_name" name="company_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($profile['company_name'] ?? $user['name']) ?>" required>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="industry">Lĩnh vực hoạt động</label>
                            <select id="industry" name="industry" class="form-control form-select">
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>" <?= (($profile['industry'] ?? '') === $cat['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="size">Quy mô nhân sự</label>
                            <select id="size" name="size" class="form-control form-select">
                                <option value="Dưới 20 nhân viên" <?= (($profile['size'] ?? '') === 'Dưới 20 nhân viên') ? 'selected' : '' ?>>Dưới 20 nhân viên</option>
                                <option value="20 - 50 nhân viên" <?= (($profile['size'] ?? '') === '20 - 50 nhân viên') ? 'selected' : '' ?>>20 - 50 nhân viên</option>
                                <option value="50 - 150 nhân viên" <?= (($profile['size'] ?? '') === '50 - 150 nhân viên') ? 'selected' : '' ?>>50 - 150 nhân viên</option>
                                <option value="150 - 500 nhân viên" <?= (($profile['size'] ?? '') === '150 - 500 nhân viên') ? 'selected' : '' ?>>150 - 500 nhân viên</option>
                                <option value="500+ nhân viên" <?= (($profile['size'] ?? '') === '500+ nhân viên') ? 'selected' : '' ?>>500+ nhân viên</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="website">Website chính thức</label>
                            <input type="url" id="website" name="website" class="form-control" maxlength="255" placeholder="https://..." value="<?= htmlspecialchars($profile['website'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="address">Địa chỉ trụ sở chính</label>
                            <input type="text" id="address" name="address" class="form-control" maxlength="255" placeholder="Số nhà, Phố, Quận/Huyện, Tỉnh/TP" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Giới thiệu về doanh nghiệp</label>
                        <textarea id="description" name="description" class="form-control form-textarea" style="min-height:120px;" placeholder="Lịch sử phát triển, văn hóa doanh nghiệp, giá trị cốt lõi..."><?= htmlspecialchars($profile['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- KHỐI 2: THÔNG TIN XÁC THỰC PHÁP LÝ -->
                <div style="border-bottom:1px solid var(--line); padding-bottom:20px; margin-bottom:20px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">2. Thông tin pháp lý & Liên hệ xác thực</h3>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="tax_code">Mã số thuế doanh nghiệp (MST)</label>
                            <input type="text" id="tax_code" name="tax_code" class="form-control" maxlength="50" placeholder="Ví dụ: 0101234567" value="<?= htmlspecialchars($profile['tax_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="license_document">Số Giấy phép ĐKKD / Liên kết tài liệu</label>
                            <input type="text" id="license_document" name="license_document" class="form-control" maxlength="255" placeholder="Số giấy phép hoặc link tài liệu" value="<?= htmlspecialchars($profile['license_document'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" for="contact_person">Người đại diện pháp lý / Nhân sự phụ trách</label>
                            <input type="text" id="contact_person" name="contact_person" class="form-control" maxlength="100" placeholder="Họ và tên người đại diện" value="<?= htmlspecialchars($profile['contact_person'] ?? $user['name']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contact_phone">Số điện thoại liên hệ trực tiếp</label>
                            <input type="tel" id="contact_phone" name="contact_phone" class="form-control" maxlength="50" placeholder="09xxxxxxxx" value="<?= htmlspecialchars($profile['contact_phone'] ?? $user['phone']) ?>">
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px;">
                    <button type="submit" class="btn btn-primary" style="padding:12px 28px;">Lưu hồ sơ doanh nghiệp →</button>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- MODAL PREVIEW TRANG CÔNG TY -->
<div class="modal-overlay" id="previewModal">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--line); padding-bottom:12px;">
            <h3 style="font-size:18px; font-weight:800; color:var(--ink);">Xem trước Trang Doanh nghiệp</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closePreviewModal()">✕</button>
        </div>

        <div style="text-align:center; padding:16px 0 20px;">
            <div style="width:64px; height:64px; border-radius:14px; background:#0B1F17; color:#99FF99; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; margin:0 auto 12px;">
                <?= mb_strtoupper(mb_substr($profile['company_name'] ?? $user['name'], 0, 1)) ?>
            </div>
            <h2 style="font-size:20px; font-weight:800; color:var(--ink);"><?= htmlspecialchars($profile['company_name'] ?? $user['name']) ?></h2>
            <div style="font-size:13px; color:var(--muted); margin-top:4px;">
                <?= htmlspecialchars($profile['industry'] ?? 'Chưa chọn ngành') ?> &middot; <?= htmlspecialchars($profile['size'] ?? 'Quy mô N/A') ?>
            </div>
            <?php if (!empty($profile['is_verified'])): ?>
                <span class="badge badge--active" style="margin-top:8px;">✓ Doanh nghiệp đã xác thực</span>
            <?php endif; ?>
        </div>

        <div style="font-size:13px; color:var(--text); line-height:1.7; background:#F8FAFC; padding:16px; border-radius:10px; margin-bottom:16px;">
            <strong>Giới thiệu:</strong>
            <p style="margin-top:6px;"><?= nl2br(htmlspecialchars($profile['description'] ?? 'Chưa có thông tin giới thiệu.')) ?></p>
        </div>

        <div style="font-size:13px; color:var(--muted);">
            <div><strong>Địa chỉ:</strong> <?= htmlspecialchars($profile['address'] ?? 'Chưa cập nhật') ?></div>
            <div><strong>Website:</strong> <?= htmlspecialchars($profile['website'] ?? 'Chưa cập nhật') ?></div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:20px;">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
function openPreviewModal() {
    document.getElementById('previewModal').classList.add('active');
}
function closePreviewModal() {
    document.getElementById('previewModal').classList.remove('active');
}
</script>

</body>
</html>

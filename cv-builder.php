<?php
/**
 * ============================================================
 * FILE: cv-builder.php
 * MÔ TẢ: Trình soạn thảo CV thời gian thực — Chuẩn Apple Design
 * ============================================================
 */

require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tạo hồ sơ CV.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode($_SERVER['PHP_SELF']));
    exit;
}
$user_id = (int)$_SESSION['user_id'];

$cv_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors  = [];
$cv      = null;

$fields = [
    'title'          => 'Hồ sơ nghề nghiệp',
    'full_name'      => '',
    'email'          => $_SESSION['user_email'] ?? '',
    'phone'          => '',
    'address'        => '',
    'summary'        => '',
    'education'      => '',
    'experience'     => '',
    'skills'         => '',
    'certifications' => '',
    'languages'      => '',
    'cv_link'        => '',
    'is_default'     => 0,
];

if ($cv_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND user_id = ?");
    $stmt->execute([$cv_id, $user_id]);
    $cv = $stmt->fetch();
    if (!$cv) {
        $_SESSION['flash_message'] = 'Hồ sơ CV không tồn tại hoặc không thuộc quyền sở hữu của bạn.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: my-cvs.php');
        exit;
    }
    foreach ($fields as $key => $_) {
        if (isset($cv[$key])) $fields[$key] = $cv[$key];
    }
}

if ($cv_id === 0) {
    $user_stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_row = $user_stmt->fetch();
    if ($user_row) {
        $fields['full_name'] = $user_row['name']  ?? '';
        $fields['email']     = $user_row['email'] ?? '';
        $fields['phone']     = $user_row['phone'] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $_) {
        if ($key === 'is_default') {
            $fields[$key] = isset($_POST['is_default']) ? 1 : 0;
        } else {
            $fields[$key] = trim($_POST[$key] ?? '');
        }
    }

    if (empty($fields['title']))     $errors[] = 'Tiêu đề CV không được để trống.';
    if (empty($fields['full_name'])) $errors[] = 'Họ và tên không được để trống.';
    if (empty($fields['email']))     $errors[] = 'Email không được để trống.';

    if (empty($errors)) {
        if ($fields['is_default']) {
            $pdo->prepare("UPDATE cvs SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        if ($cv_id > 0) {
            $upd = $pdo->prepare("
                UPDATE cvs SET
                    title = ?, full_name = ?, email = ?, phone = ?, address = ?,
                    summary = ?, education = ?, experience = ?, skills = ?,
                    certifications = ?, languages = ?, cv_link = ?, is_default = ?,
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $upd->execute([
                $fields['title'], $fields['full_name'], $fields['email'],
                $fields['phone'] ?: null, $fields['address'] ?: null,
                $fields['summary'] ?: null, $fields['education'] ?: null,
                $fields['experience'] ?: null, $fields['skills'] ?: null,
                $fields['certifications'] ?: null, $fields['languages'] ?: null,
                $fields['cv_link'] ?: null,
                $fields['is_default'], $cv_id, $user_id
            ]);
            $_SESSION['flash_message'] = 'Cập nhật hồ sơ CV thành công.';
        } else {
            $ins = $pdo->prepare("
                INSERT INTO cvs
                    (user_id, title, full_name, email, phone, address, summary,
                     education, experience, skills, certifications, languages, cv_link, is_default, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([
                $user_id,
                $fields['title'], $fields['full_name'], $fields['email'],
                $fields['phone'] ?: null, $fields['address'] ?: null,
                $fields['summary'] ?: null, $fields['education'] ?: null,
                $fields['experience'] ?: null, $fields['skills'] ?: null,
                $fields['certifications'] ?: null, $fields['languages'] ?: null,
                $fields['cv_link'] ?: null,
                $fields['is_default']
            ]);
            $_SESSION['flash_message'] = 'Tạo hồ sơ CV mới thành công.';
        }

        $_SESSION['flash_type'] = 'success';
        header('Location: my-cvs.php');
        exit;
    }
}

$page_title = $cv_id > 0 ? 'Chỉnh Sửa CV' : 'Tạo CV Mới';
require_once 'includes/header.php';
?>

<section class="section" style="padding-top:32px;">
    <div class="container">
        <!-- Header -->
        <div class="section-header" style="margin-bottom:28px;">
            <div>
                <h1 class="section-title"><?= $cv_id > 0 ? 'Chỉnh sửa hồ sơ CV' : 'Tạo hồ sơ CV trực tuyến' ?></h1>
                <p class="section-desc">Nội dung CV hiển thị song song theo thời gian thực ở khung bên trái khi bạn nhập liệu.</p>
            </div>
            <a href="my-cvs.php" class="btn btn-secondary btn-sm">← Danh sách CV</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash-bar flash--danger">
                <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <div class="cv-builder-layout">
            <!-- Cột trái: Live Preview -->
            <div>
                <div class="cv-preview-paper">
                    <div style="font-size:11px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); margin-bottom:16px;">
                        XEM TRƯỚC HỒ SƠ THỜI GIAN THỰC
                    </div>

                    <div class="cv-paper-header">
                        <div class="cv-paper-name" id="prev_full_name"><?= htmlspecialchars($fields['full_name'] ?: 'Họ và tên của bạn') ?></div>
                        <div class="cv-paper-title" id="prev_title"><?= htmlspecialchars($fields['title'] ?: 'Vị trí mong muốn') ?></div>
                        <div class="cv-paper-contact">
                            <span id="prev_email">Email: <?= htmlspecialchars($fields['email'] ?: 'email@domain.com') ?></span>
                            <span id="prev_phone"><?= !empty($fields['phone']) ? '&middot; SĐT: ' . htmlspecialchars($fields['phone']) : '' ?></span>
                            <span id="prev_address"><?= !empty($fields['address']) ? '&middot; ' . htmlspecialchars($fields['address']) : '' ?></span>
                        </div>
                        <div id="prev_link_wrap" style="margin-top:8px; display:<?= !empty($fields['cv_link']) ? 'block' : 'none' ?>;">
                            <span style="font-size:12px; font-weight:700;">Liên kết CV / Portfolio: </span>
                            <a href="<?= htmlspecialchars($fields['cv_link']) ?>" target="_blank" id="prev_link" style="font-size:12px; color:var(--ink); text-decoration:underline;">
                                <?= htmlspecialchars($fields['cv_link']) ?>
                            </a>
                        </div>
                    </div>

                    <div class="cv-paper-section">
                        <div class="cv-paper-section-title">Giới thiệu bản thân</div>
                        <div class="cv-paper-content" id="prev_summary"><?= htmlspecialchars($fields['summary'] ?: 'Tóm tắt ngắn gọn về năng lực chuyên môn và định hướng phát triển của bạn.') ?></div>
                    </div>

                    <div class="cv-paper-section">
                        <div class="cv-paper-section-title">Học vấn</div>
                        <div class="cv-paper-content" id="prev_education"><?= htmlspecialchars($fields['education'] ?: 'Trường Đại học / Cao đẳng, Chuyên ngành học...') ?></div>
                    </div>

                    <div class="cv-paper-section">
                        <div class="cv-paper-section-title">Kinh nghiệm thực tế</div>
                        <div class="cv-paper-content" id="prev_experience"><?= htmlspecialchars($fields['experience'] ?: 'Các vị trí từng đảm nhiệm, dự án đã tham gia...') ?></div>
                    </div>

                    <div class="cv-paper-section">
                        <div class="cv-paper-section-title">Kỹ năng chuyên môn</div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;" id="prev_skills">
                            <?php foreach (array_filter(array_map('trim', explode(',', $fields['skills']))) as $sk): ?>
                                <span class="chip"><?= htmlspecialchars($sk) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Form nhập liệu -->
            <div>
                <div class="form-card">
                    <form method="POST" id="cvForm">
                        <div class="form-group">
                            <label class="form-label" for="title">Tiêu đề CV / Vị trí hướng tới <span style="color:var(--status-danger-text);">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="Ví dụ: Lập trình viên Backend PHP / Chuyên viên Marketing" value="<?= htmlspecialchars($fields['title']) ?>" required oninput="updatePreview('prev_title', this.value)">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label" for="full_name">Họ và tên đầy đủ <span style="color:var(--status-danger-text);">*</span></label>
                                <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($fields['full_name']) ?>" required oninput="updatePreview('prev_full_name', this.value)">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Địa chỉ Email <span style="color:var(--status-danger-text);">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($fields['email']) ?>" required oninput="updatePreview('prev_email', 'Email: ' + this.value)">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label" for="phone">Số điện thoại</label>
                                <input type="text" id="phone" name="phone" class="form-control" placeholder="0912 345 678" value="<?= htmlspecialchars($fields['phone']) ?>" oninput="updatePreview('prev_phone', this.value ? '· SĐT: ' + this.value : '')">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="address">Địa chỉ / Khu vực</label>
                                <input type="text" id="address" name="address" class="form-control" placeholder="Hà Nội, Hồ Chí Minh..." value="<?= htmlspecialchars($fields['address']) ?>" oninput="updatePreview('prev_address', this.value ? '· ' + this.value : '')">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="summary">Tóm tắt bản thân</label>
                            <textarea id="summary" name="summary" class="form-control form-textarea" placeholder="Giới thiệu năng lực, điểm mạnh và mục tiêu nghề nghiệp..." oninput="updatePreview('prev_summary', this.value)"><?= htmlspecialchars($fields['summary']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="education">Học vấn & Bằng cấp</label>
                            <textarea id="education" name="education" class="form-control form-textarea" placeholder="Tên trường, Chuyên ngành, Điểm số hoặc niên khóa..." oninput="updatePreview('prev_education', this.value)"><?= htmlspecialchars($fields['education']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="experience">Kinh nghiệm làm việc & Dự án</label>
                            <textarea id="experience" name="experience" class="form-control form-textarea" style="min-height:140px;" placeholder="Tên công ty / Dự án, Vị trí đảm nhiệm, Kết quả đạt được..." oninput="updatePreview('prev_experience', this.value)"><?= htmlspecialchars($fields['experience']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="skills">Kỹ năng chuyên môn (cách nhau bằng dấu phẩy)</label>
                            <input type="text" id="skills" name="skills" class="form-control" placeholder="PHP, MySQL, JavaScript, Excel, Giao tiếp, Quản lý dự án..." value="<?= htmlspecialchars($fields['skills']) ?>" oninput="updateSkillsPreview(this.value)">
                            <div class="jf-suggestion-tags" id="skillQuickTags">
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'PHP / Laravel')">+ PHP / Laravel</button>
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'JavaScript / ReactJS')">+ JavaScript / ReactJS</button>
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'MySQL / SQL')">+ MySQL / SQL</button>
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'Digital Marketing')">+ Digital Marketing</button>
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'Kế toán tổng hợp')">+ Kế toán tổng hợp</button>
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'Giao tiếp & Đàm phán')">+ Giao tiếp & Đàm phán</button>
                                <button type="button" class="jf-suggestion-tag" onclick="appendCvTag('skills', 'Tin học văn phòng (Excel/Word)')">+ Tin học văn phòng</button>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label" for="certifications">Chứng chỉ / Giải thưởng</label>
                                <input type="text" id="certifications" name="certifications" class="form-control" placeholder="Chọn hoặc nhập chứng chỉ..." value="<?= htmlspecialchars($fields['certifications']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="languages">Ngoại ngữ</label>
                                <input type="text" id="languages" name="languages" class="form-control" placeholder="Chọn hoặc nhập ngoại ngữ..." value="<?= htmlspecialchars($fields['languages']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cv_link">Link CV / Portfolio trực tuyến (Google Drive, Notion, GitHub, Portfolio...)</label>
                            <input type="url" id="cv_link" name="cv_link" class="form-control" placeholder="https://drive.google.com/... hoặc https://github.com/..." value="<?= htmlspecialchars($fields['cv_link']) ?>" oninput="updateLinkPreview(this.value)">
                        </div>

                        <div class="form-group">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="is_default" value="1" <?= $fields['is_default'] ? 'checked' : '' ?>>
                                <span style="font-size:13px; font-weight:600;">Đặt làm CV mặc định khi ứng tuyển công việc</span>
                            </label>
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px; padding-top:20px; border-top:1px solid var(--line-subtle);">
                            <a href="my-cvs.php" class="btn btn-secondary">Hủy bỏ</a>
                            <button type="submit" class="btn btn-primary" style="padding:14px 28px;">Lưu hồ sơ CV →</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function updatePreview(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = value.trim() ? value : '...';
    }
}

function updateLinkPreview(value) {
    const wrap = document.getElementById('prev_link_wrap');
    const link = document.getElementById('prev_link');
    if (wrap && link) {
        if (value.trim()) {
            wrap.style.display = 'block';
            link.textContent = value.trim();
            link.href = value.trim();
        } else {
            wrap.style.display = 'none';
        }
    }
}

function updateSkillsPreview(value) {
    const container = document.getElementById('prev_skills');
    if (!container) return;
    container.innerHTML = '';
    const skills = value.split(',').map(s => s.trim()).filter(s => s.length > 0);
    skills.forEach(s => {
        const span = document.createElement('span');
        span.className = 'chip';
        span.textContent = s;
        container.appendChild(span);
    });
}

function appendCvTag(inputId, tag) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const parts = input.value.split(',').map(s => s.trim()).filter(Boolean);
    if (!parts.includes(tag)) {
        parts.push(tag);
        input.value = parts.join(', ');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof jfInitAutocomplete !== 'function') return;

    jfInitAutocomplete(document.getElementById('title'), [
        'Lập trình viên Backend PHP / Laravel',
        'Lập trình viên Frontend ReactJS',
        'Lập trình viên Fullstack',
        'Chuyên viên Thiết kế UI/UX',
        'Chuyên viên Marketing số',
        'Nhân viên Kinh doanh B2B',
        'Kế toán tổng hợp',
        'Chuyên viên Tuyển dụng nhân sự',
        'Nhân viên Điều phối Logistics',
        'Dược sĩ / Trình dược viên',
        'Giảng viên Tiếng Anh',
        'Kỹ sư Xây dựng'
    ]);

    jfInitAutocomplete(document.getElementById('address'), [
        'Quận 1, TP.HCM', 'Quận 7, TP.HCM', 'TP. Thủ Đức, TP.HCM', 'TP.HCM',
        'Cầu Giấy, Hà Nội', 'Hoàn Kiếm, Hà Nội', 'Nam Từ Liêm, Hà Nội', 'Hà Nội',
        'Hải Châu, Đà Nẵng', 'Đà Nẵng',
        'Thủ Dầu Một, Bình Dương', 'Biên Hòa, Đồng Nai',
        'Ninh Kiều, Cần Thơ', 'Hải Phòng', 'Làm việc từ xa'
    ]);

    jfInitAutocomplete(document.getElementById('skills'), [
        'PHP / Laravel', 'JavaScript / ReactJS', 'MySQL / PostgreSQL', 'HTML5 / CSS3',
        'Figma / UI-UX', 'Digital Marketing / SEO', 'Facebook Ads / Google Ads',
        'Kế toán tổng hợp / MISA', 'Phân tích tài chính', 'Bán hàng B2B / Đàm phán',
        'Tuyển dụng & Phỏng vấn', 'Quản lý chuỗi cung ứng', 'Tin học văn phòng (Excel/Word)',
        'Quản lý dự án Agile/Scrum', 'Giao tiếp & Thuyết trình'
    ], { multi: true });

    jfInitAutocomplete(document.getElementById('languages'), [
        'Tiếng Anh (IELTS 6.5+)', 'Tiếng Anh (TOEIC 750+)', 'Tiếng Anh (Giao tiếp tốt)',
        'Tiếng Nhật (JLPT N3)', 'Tiếng Nhật (JLPT N2)',
        'Tiếng Trung (HSK 4)', 'Tiếng Trung (HSK 5)',
        'Tiếng Hàn (TOPIK 4)', 'Tiếng Việt (Bản ngữ)'
    ], { multi: true });

    jfInitAutocomplete(document.getElementById('certifications'), [
        'Chứng chỉ TOEIC 800+', 'Chứng chỉ IELTS 7.0', 'Chứng chỉ AWS Cloud Practitioner',
        'Chứng chỉ Kế toán trưởng', 'Google Digital Marketing Certificate',
        'Chứng chỉ Hành nghề Dược', 'Chứng chỉ giảng dạy TESOL'
    ], { multi: true });
});
</script>

<?php require_once 'includes/footer.php'; ?>

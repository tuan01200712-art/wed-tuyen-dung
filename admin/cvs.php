<?php
/**
 * ============================================================
 * FILE: admin/cvs.php
 * MÔ TẢ: Quản trị toàn bộ Hồ sơ CV trực tuyến của ứng viên
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$page_title = 'Quản Lý Hồ Sơ CV Ứng Viên';

// Xử lý POST xóa CV
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cv_id = (int)($_POST['cv_id'] ?? 0);

    if ($cv_id > 0 && $action === 'delete') {
        $pdo->prepare("DELETE FROM cvs WHERE id = ?")->execute([$cv_id]);
        $_SESSION['flash_message'] = 'Đã xóa hồ sơ CV #' . $cv_id . ' khỏi hệ thống.';
        $_SESSION['flash_type'] = 'success';
        header('Location: cvs.php');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, u.name as account_name, u.email as account_email
    FROM cvs c
    JOIN users u ON u.id = c.user_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.full_name LIKE :search OR c.title LIKE :search OR c.email LIKE :search OR c.skills LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cvs = $stmt->fetchAll();
$total_cvs = count($cvs);
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
                <span class="section-eyebrow">Dữ liệu ứng viên</span>
                <h1 class="section-title">Danh sách hồ sơ CV trực tuyến</h1>
                <p class="section-desc">Tổng cộng <strong><?= $total_cvs ?></strong> hồ sơ CV được ứng viên khởi tạo trên hệ thống.</p>
            </div>
            <a href="../cv-builder.php" target="_blank" class="btn btn-secondary btn-sm">Mở công cụ tạo CV ↗</a>
        </div>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="flash-bar flash--<?= $_SESSION['flash_type'] ?? 'success' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Thanh tìm kiếm -->
        <div class="smart-search-card" style="margin-bottom:24px;">
            <form method="GET" class="search-form-row" style="grid-template-columns: 1fr auto;">
                <div class="search-field">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input" placeholder="Tìm kiếm theo họ tên, chức danh nghề nghiệp, kỹ năng..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <?php if (!empty($search)): ?>
                        <a href="cvs.php" class="btn btn-secondary">Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách CV -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Ứng viên & Chức danh</th>
                            <th>Thông tin liên hệ</th>
                            <th>Kỹ năng chính</th>
                            <th>Link đính kèm</th>
                            <th>Ngày lập</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cvs)): ?>
                            <?php foreach ($cvs as $i => $c): ?>
                                <tr>
                                    <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700; color:var(--ink);">
                                            <?= htmlspecialchars($c['full_name']) ?>
                                            <?php if (!empty($c['is_default'])): ?>
                                                <span class="badge badge--active" style="font-size:10px; padding:2px 6px;">Mặc định</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:13px; color:var(--text); font-weight:600;"><?= htmlspecialchars($c['title']) ?></div>
                                        <div style="font-size:11px; color:var(--muted);">Tài khoản: <?= htmlspecialchars($c['account_email']) ?></div>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <div>📧 <?= htmlspecialchars($c['email']) ?></div>
                                        <?php if (!empty($c['phone'])): ?>
                                            <div>📞 <?= htmlspecialchars($c['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $skills_arr = !empty($c['skills']) ? array_filter(array_map('trim', explode(',', $c['skills']))) : [];
                                        ?>
                                        <?php if (!empty($skills_arr)): ?>
                                            <div style="display:flex; flex-wrap:wrap; gap:4px; max-width:260px;">
                                                <?php foreach (array_slice($skills_arr, 0, 4) as $sk): ?>
                                                    <span class="chip" style="font-size:11px; padding:2px 8px;"><?= htmlspecialchars($sk) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($skills_arr) > 4): ?>
                                                    <span class="text-muted text-sm">+<?= count($skills_arr) - 4 ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">Chưa nhập</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['cv_link'])): ?>
                                            <a href="<?= htmlspecialchars($c['cv_link']) ?>" target="_blank" rel="noopener noreferrer" class="chip" style="color:var(--ink); font-weight:600; text-decoration:none;">
                                                🔗 Link ngoài ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted text-sm">
                                        <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                                            <a href="../cv-preview.php?id=<?= (int)$c['id'] ?>" target="_blank" class="btn btn-secondary btn-sm">Xem CV ↗</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hồ sơ CV này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="cv_id" value="<?= (int)$c['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px; color:var(--muted);">
                                    Không tìm thấy hồ sơ CV nào trong hệ thống.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>

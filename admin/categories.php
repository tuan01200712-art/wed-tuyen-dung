<?php
/**
 * ============================================================
 * FILE: admin/categories.php
 * MÔ TẢ: Quản trị Danh mục Ngành nghề & Địa điểm Tuyển dụng (CRUD)
 * ============================================================
 */

require_once '../config/database.php';
require_once '../includes/middleware-admin.php';

$admin_id = (int)$_SESSION['user_id'];
$page_title = 'Quản Lý Danh Mục Dữ Liệu';
$tab = $_GET['tab'] ?? 'categories'; // 'categories' hoặc 'locations'
$errors = [];

// Xử lý POST Thêm/Sửa/Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Quản lý Ngành nghề
    if ($tab === 'categories') {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? 'briefcase');
            $description = trim($_POST['description'] ?? '');
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            if (empty($name)) {
                $errors[] = 'Vui lòng nhập tên ngành nghề.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                    $stmt->execute([$name, $slug, $icon, $description]);
                    log_activity($pdo, $admin_id, 'CAT_CREATE', "Tạo ngành nghề: {$name}");
                    $_SESSION['flash_message'] = 'Đã thêm ngành nghề "' . htmlspecialchars($name) . '" thành công.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: categories.php?tab=categories');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Ngành nghề này đã tồn tại hoặc xảy ra lỗi: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
                log_activity($pdo, $admin_id, 'CAT_DELETE', "Xóa ngành nghề ID #{$id}");
                $_SESSION['flash_message'] = 'Đã xóa ngành nghề thành công.';
                $_SESSION['flash_type'] = 'success';
                header('Location: categories.php?tab=categories');
                exit;
            }
        }
    }

    // 2. Quản lý Địa điểm
    if ($tab === 'locations') {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? 'city');
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            if (empty($name)) {
                $errors[] = 'Vui lòng nhập tên địa điểm.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO locations (name, slug, type, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
                    $stmt->execute([$name, $slug, $type]);
                    log_activity($pdo, $admin_id, 'LOC_CREATE', "Tạo địa điểm: {$name}");
                    $_SESSION['flash_message'] = 'Đã thêm địa điểm "' . htmlspecialchars($name) . '" thành công.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: categories.php?tab=locations');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Địa điểm này đã tồn tại hoặc xảy ra lỗi: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM locations WHERE id = ?")->execute([$id]);
                log_activity($pdo, $admin_id, 'LOC_DELETE', "Xóa địa điểm ID #{$id}");
                $_SESSION['flash_message'] = 'Đã xóa địa điểm thành công.';
                $_SESSION['flash_type'] = 'success';
                header('Location: categories.php?tab=locations');
                exit;
            }
        }
    }
}

// Lấy danh sách hiển thị
$categories = [];
$locations = [];
try {
    $categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM jobs WHERE category = c.name) as job_count FROM categories c ORDER BY c.id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

try {
    $locations = $pdo->query("SELECT l.*, (SELECT COUNT(*) FROM jobs WHERE location = l.name) as job_count FROM locations l ORDER BY l.id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
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
        <div class="section-header" style="margin-bottom:24px;">
            <div>
                <h1 class="section-title">Quản lý Danh mục Dữ liệu</h1>
                <p class="section-desc">Thiết lập dữ liệu động cho toàn sàn, loại bỏ hoàn toàn việc hardcode trong mã nguồn.</p>
            </div>
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

        <!-- Tabs chuyển đổi giữa Ngành nghề và Địa điểm -->
        <div class="category-pills" style="margin-bottom:24px;">
            <a href="categories.php?tab=categories" class="category-pill <?= ($tab === 'categories') ? 'active' : '' ?>">
                Ngành nghề tuyển dụng (<?= count($categories) ?>)
            </a>
            <a href="categories.php?tab=locations" class="category-pill <?= ($tab === 'locations') ? 'active' : '' ?>">
                Địa điểm làm việc (<?= count($locations) ?>)
            </a>
        </div>

        <?php if ($tab === 'categories'): ?>
            <!-- TAB 1: NGÀNH NGHỀ -->
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:24px; align-items:start;">
                <!-- Form thêm mới ngành nghề -->
                <div class="form-card">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">+ Thêm ngành nghề mới</h3>
                    <form method="POST" action="categories.php?tab=categories">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label class="form-label" for="name">Tên ngành nghề <span style="color:var(--status-danger-text);">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="Ví dụ: Logistics & Chuỗi cung ứng" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="icon">Mã biểu tượng (Icon)</label>
                            <select id="icon" name="icon" class="form-control form-select">
                                <option value="briefcase">Briefcase (Cặp tài liệu)</option>
                                <option value="code">Code (Công nghệ)</option>
                                <option value="layout">Layout (Thiết kế)</option>
                                <option value="trending-up">Trending Up (Marketing)</option>
                                <option value="dollar-sign">Dollar Sign (Tài chính)</option>
                                <option value="users">Users (Nhân sự)</option>
                                <option value="database">Database (Dữ liệu)</option>
                                <option value="headphones">Headphones (CSKH)</option>
                                <option value="book-open">Book Open (Giáo dục)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">Mô tả ngắn</label>
                            <textarea id="description" name="description" class="form-control form-textarea" style="min-height:80px;" placeholder="Mô tả tóm tắt các vị trí liên quan..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Thêm ngành nghề →</button>
                    </form>
                </div>

                <!-- Bảng danh sách ngành nghề -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Tên ngành nghề</th>
                                    <th>Mô tả</th>
                                    <th>Số tin đăng</th>
                                    <th style="text-align:right;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $i => $cat): ?>
                                        <tr>
                                            <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                            <td>
                                                <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($cat['name']) ?></div>
                                                <div style="font-size:11px; color:var(--muted);"><?= htmlspecialchars($cat['slug']) ?></div>
                                            </td>
                                            <td class="text-muted text-sm">
                                                <?= htmlspecialchars($cat['description'] ?: '—') ?>
                                            </td>
                                            <td>
                                                <a href="jobs.php?category=<?= urlencode($cat['name']) ?>" style="font-weight:700; color:var(--ink); text-decoration:underline;">
                                                    <?= (int)$cat['job_count'] ?> tin →
                                                </a>
                                            </td>
                                            <td style="text-align:right;">
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ngành nghề này?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--muted);">Chưa có ngành nghề nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- TAB 2: ĐỊA ĐIỂM -->
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:24px; align-items:start;">
                <!-- Form thêm địa điểm -->
                <div class="form-card">
                    <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin-bottom:16px;">+ Thêm địa điểm mới</h3>
                    <form method="POST" action="categories.php?tab=locations">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label class="form-label" for="loc_name">Tên tỉnh/thành hoặc hình thức <span style="color:var(--status-danger-text);">*</span></label>
                            <input type="text" id="loc_name" name="name" class="form-control" placeholder="Ví dụ: Bình Dương hoặc Quốc tế" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="loc_type">Loại địa điểm</label>
                            <select id="loc_type" name="type" class="form-control form-select">
                                <option value="city">Thành phố / Tỉnh</option>
                                <option value="region">Khu vực</option>
                                <option value="remote">Làm việc từ xa</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Thêm địa điểm →</button>
                    </form>
                </div>

                <!-- Bảng danh sách địa điểm -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Địa điểm</th>
                                    <th>Phân loại</th>
                                    <th>Số tin đăng</th>
                                    <th style="text-align:right;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($locations)): ?>
                                    <?php foreach ($locations as $i => $loc): ?>
                                        <tr>
                                            <td class="text-muted" style="font-weight:700;"><?= $i + 1 ?></td>
                                            <td>
                                                <div style="font-weight:700; color:var(--ink);"><?= htmlspecialchars($loc['name']) ?></div>
                                            </td>
                                            <td>
                                                <span class="chip"><?= htmlspecialchars($loc['type']) ?></span>
                                            </td>
                                            <td>
                                                <span style="font-weight:700;"><?= (int)$loc['job_count'] ?> tin</span>
                                            </td>
                                            <td style="text-align:right;">
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa điểm này?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$loc['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--muted);">Chưa có địa điểm nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>

<?php
$admin_current = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
  <div class="admin-sidebar__logo">
    <a href="../index.php" class="logo" style="color:var(--admin-text);">
      <span class="logo-badge">JF</span>
      Jobfinder
    </a>
    <span class="admin-badge">ADMIN</span>
  </div>

  <nav class="admin-nav">
    <a href="dashboard.php" class="admin-nav-item <?= ($admin_current === 'dashboard.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
      Tổng quan
    </a>
    <a href="users.php" class="admin-nav-item <?= ($admin_current === 'users.php' || $admin_current === 'user-edit.php' || $admin_current === 'user-create.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Người dùng
    </a>
    <a href="jobs.php" class="admin-nav-item <?= ($admin_current === 'jobs.php' || $admin_current === 'job-edit.php' || $admin_current === 'job-create.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Tin tuyển dụng
    </a>
    <a href="applications.php" class="admin-nav-item <?= ($admin_current === 'applications.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Hồ sơ ứng tuyển
    </a>
    <a href="cvs.php" class="admin-nav-item <?= ($admin_current === 'cvs.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><polyline points="10 6 14 6"/><polyline points="10 10 14 10"/></svg>
      Hồ sơ CV
    </a>
    <a href="employers.php" class="admin-nav-item <?= ($admin_current === 'employers.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M19 21v-4"/><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v14"/><path d="M9 9h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
      Nhà tuyển dụng
    </a>
  </nav>

  <div class="admin-sidebar__footer">
    <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
    <a href="../logout.php" style="color:var(--admin-muted); font-weight:600;">Đăng xuất ✕</a>
  </div>
</aside>

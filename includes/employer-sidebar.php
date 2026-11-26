<?php
$emp_current = basename($_SERVER['PHP_SELF']);
?>
<aside class="employer-sidebar">
  <div class="admin-sidebar__logo">
    <a href="../index.php" class="logo" title="JobFinder">
      <img src="../assets/images/logo-white.svg" alt="JobFinder" class="logo-img" style="height:36px; max-width:146px;" onerror="this.src='../assets/images/logo.svg'">
    </a>
    <span class="admin-badge">NTD</span>
  </div>

  <nav class="admin-nav">
    <a href="dashboard.php" class="employer-nav-item <?= ($emp_current === 'dashboard.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
      <span>Tổng quan</span>
    </a>
    <a href="jobs.php" class="employer-nav-item <?= in_array($emp_current, ['jobs.php', 'job-create.php', 'job-edit.php']) ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      <span>Tin tuyển dụng</span>
    </a>
    <a href="applications.php" class="employer-nav-item <?= in_array($emp_current, ['applications.php', 'application-view.php']) ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      <span>Ứng viên ứng tuyển</span>
    </a>
    <a href="profile.php" class="employer-nav-item <?= ($emp_current === 'profile.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M19 21v-4"/><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v14"/><path d="M9 9h6"/><path d="M9 13h6"/></svg>
      <span>Hồ sơ công ty</span>
    </a>
  </nav>

  <div class="admin-sidebar__footer">
    <div class="sidebar-footer-user"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Nhà tuyển dụng') ?></div>
    <div class="sidebar-footer-actions">
      <a href="../index.php" class="sidebar-footer-btn">Về trang chủ</a>
      <a href="../logout.php" class="sidebar-footer-btn sidebar-footer-btn--danger">Đăng xuất</a>
    </div>
  </div>
</aside>

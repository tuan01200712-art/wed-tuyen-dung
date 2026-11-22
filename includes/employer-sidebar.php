<?php
$emp_current = basename($_SERVER['PHP_SELF']);
?>
<aside class="employer-sidebar">
  <div style="display:flex; align-items:center; justify-content:space-between; padding:0 12px 24px; border-bottom:1px solid var(--line); margin-bottom:20px;">
    <a href="../index.php" class="logo">
      <span class="logo-badge">JF</span>
      Jobfinder
    </a>
    <span class="badge badge--employer" style="font-size:10px;">DOANH NGHIỆP</span>
  </div>

  <nav class="admin-nav">
    <a href="dashboard.php" class="employer-nav-item <?= ($emp_current === 'dashboard.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
      Tổng quan
    </a>
    <a href="jobs.php" class="employer-nav-item <?= ($emp_current === 'jobs.php' || $emp_current === 'job-create.php' || $emp_current === 'job-edit.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Tin tuyển dụng
    </a>
    <a href="job-create.php" class="employer-nav-item <?= ($emp_current === 'job-create.php') ? 'active' : '' ?>" style="color:var(--ink); font-weight:700;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      + Đăng tin mới
    </a>
    <a href="applications.php" class="employer-nav-item <?= ($emp_current === 'applications.php' || $emp_current === 'application-view.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Hồ sơ ứng viên
    </a>
    <a href="profile.php" class="employer-nav-item <?= ($emp_current === 'profile.php') ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Hồ sơ công ty
    </a>
  </nav>

  <div style="padding-top:16px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--muted); margin-top:auto;">
    <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Employer') ?></span>
    <a href="../logout.php" style="font-weight:600; color:var(--ink);">Đăng xuất ✕</a>
  </div>
</aside>

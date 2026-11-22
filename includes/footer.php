<?php
/**
 * ============================================================
 * FILE: includes/footer.php
 * MÔ TẢ: Chân trang tối giản phong cách Apple
 * ============================================================
 */
?>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="index.php" class="logo">
                    <span class="logo-badge">JF</span>
                    <span>Jobfinder</span>
                </a>
                <p class="footer-brand-desc">
                    Hệ thống kết nối việc làm minh bạch và hồ sơ nghề nghiệp trực quan dành cho sinh viên, ứng viên và doanh nghiệp.
                </p>
            </div>

            <div>
                <div class="footer-title">Khám phá</div>
                <div class="footer-links">
                    <a href="jobs.php" class="footer-link">Tất cả việc làm</a>
                    <a href="jobs.php?category=Công+nghệ+thông+tin" class="footer-link">Việc làm Công nghệ số</a>
                    <a href="jobs.php?category=Thiết+kế+đồ+họa" class="footer-link">Thiết kế & Sáng tạo</a>
                    <a href="jobs.php?category=Marketing" class="footer-link">Tiếp thị & Truyền thông</a>
                </div>
            </div>

            <div>
                <div class="footer-title">Dành cho Ứng viên</div>
                <div class="footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="my-applications.php" class="footer-link">Hồ sơ đã ứng tuyển</a>
                        <a href="my-cvs.php" class="footer-link">Quản lý hồ sơ CV</a>
                        <a href="cv-builder.php" class="footer-link">Tạo CV trực tuyến</a>
                        <a href="profile.php" class="footer-link">Cài đặt tài khoản</a>
                    <?php else: ?>
                        <a href="login.php" class="footer-link">Đăng nhập tài khoản</a>
                        <a href="register.php" class="footer-link">Đăng ký ứng viên</a>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="footer-title">Nhà tuyển dụng & Quản trị</div>
                <div class="footer-links">
                    <a href="employer/dashboard.php" class="footer-link">Kênh Doanh nghiệp</a>
                    <a href="employer/job-create.php" class="footer-link">Đăng tin tuyển dụng</a>
                    <a href="admin/dashboard.php" class="footer-link">Cổng Quản trị Admin</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?php echo date('Y'); ?> <strong>JobFinder</strong>. Thiết kế chuẩn Apple & Web 2026.
            </div>
            <div style="display:flex; gap:16px;">
                <span>PHP PDO</span>
                <span>MySQL</span>
                <span>Vanilla HTML5/CSS3</span>
            </div>
        </div>
    </div>
</footer>

<script>
// Interactive User Dropdown Toggle
document.addEventListener('DOMContentLoaded', function () {
    const userBtn = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const mobileToggle = document.getElementById('mobileToggle');
    const navMenu = document.getElementById('navMenu');

    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });
    }

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', function () {
            navMenu.classList.toggle('active');
        });
    }
});
</script>

</body>
</html>

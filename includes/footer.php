<?php

/**
 * ============================================================
 * FILE: includes/footer.php
 * MÔ TẢ: Phần chân trang (Footer) và nạp Javascript
 * ============================================================
 */
?>
</main>
<!-- Kết thúc Main Content Wrapper -->

<!-- Footer -->
<footer class="footer">
    <div class="container footer-container">
        <!-- Cột 1: Thông tin thương hiệu -->
        <div class="footer-col footer-about">
            <a href="index.php" class="logo footer-logo">
                <span class="logo-icon"><i class="fa-solid fa-briefcase"></i></span>
                <span class="logo-text">Job<span>Finder</span></span>
            </a>
            <p class="footer-desc">
                Cổng thông tin tuyển dụng và tìm kiếm việc làm hàng đầu dành cho sinh viên và ứng viên chuyên nghiệp. Kết nối cơ hội, mở lối tương lai.
            </p>
            <div class="footer-socials">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                <a href="#" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
            </div>
        </div>

        <!-- Cột 2: Điều hướng nhanh -->
        <div class="footer-col">
            <h4 class="footer-title">Khám Phá</h4>
            <ul class="footer-links">
                <li><a href="index.php"><i class="fa-solid fa-angle-right"></i> Trang chủ</a></li>
                <li><a href="jobs.php"><i class="fa-solid fa-angle-right"></i> Tất cả việc làm</a></li>
                <li><a href="jobs.php?category=Công+nghệ+thông+tin"><i class="fa-solid fa-angle-right"></i> Việc làm IT</a></li>
                <li><a href="jobs.php?category=Marketing"><i class="fa-solid fa-angle-right"></i> Việc làm Marketing</a></li>
            </ul>
        </div>

        <!-- Cột 3: Dành cho ứng viên -->
        <div class="footer-col">
            <h4 class="footer-title">Dành Cho Ứng Viên</h4>
            <ul class="footer-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="my-applications.php"><i class="fa-solid fa-angle-right"></i> Việc làm đã ứng tuyển</a></li>
                    <li><a href="logout.php"><i class="fa-solid fa-angle-right"></i> Đăng xuất</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fa-solid fa-angle-right"></i> Đăng nhập tài khoản</a></li>
                    <li><a href="register.php"><i class="fa-solid fa-angle-right"></i> Đăng ký ứng viên mới</a></li>
                <?php endif; ?>
                <li><a href="jobs.php"><i class="fa-solid fa-angle-right"></i> Tìm kiếm theo ngành</a></li>
            </ul>
        </div>

        <!-- Cột 4: Thông tin liên hệ đồ án -->
        <div class="footer-col">
            <h4 class="footer-title">Liên Hệ</h4>
            <ul class="footer-contact">
                <li>
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Đồ án môn học: Lập trình ứng dụng Web</span>
                </li>
                <li>
                    <i class="fa-solid fa-envelope"></i>
                    <span>support@jobfinder.local</span>
                </li>
                <li>
                    <i class="fa-solid fa-phone"></i>
                    <span>+84 (028) 1234 5678</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Bản quyền -->
    <div class="footer-bottom">
        <div class="container footer-bottom-container">
            <p>&copy; <?php echo date('Y'); ?> <strong>JobFinder</strong> - Đồ án môn học Lập trình Web. Mọi quyền được bảo lưu.</p>
            <p class="footer-tech">Xây dựng bằng PHP, MySQL, PDO, HTML5 & CSS3.</p>
        </div>
    </div>
</footer>

<!-- Main JavaScript -->
<script src="assets/js/main.js"></script>
</body>

</html>
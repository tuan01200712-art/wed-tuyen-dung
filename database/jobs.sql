-- ============================================================
-- DATABASE: job_portal
-- DỰ ÁN: Cổng Tuyển Dụng & Vận Hành Quản Trị JobFinder (Hệ thống JobFinder)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `job_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `job_portal`;

-- Xóa bảng cũ theo thứ tự khóa ngoại an toàn
DROP TABLE IF EXISTS `interview_schedules`;
DROP TABLE IF EXISTS `application_tags`;
DROP TABLE IF EXISTS `application_notes`;
DROP TABLE IF EXISTS `saved_jobs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `cvs`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `campaigns`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `employer_profiles`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- 1. Bảng users (Tài khoản: Ứng viên, Nhà tuyển dụng, Quản trị viên)
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('candidate', 'employer', 'admin') NOT NULL DEFAULT 'candidate',
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `avatar` VARCHAR(255) NULL,
  `google_id` VARCHAR(255) NULL,
  `auth_provider` VARCHAR(20) NOT NULL DEFAULT 'local',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Bảng employer_profiles (Hồ sơ công ty & Xác thực pháp lý)
-- ------------------------------------------------------------
CREATE TABLE `employer_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `company_name` VARCHAR(150) NOT NULL,
  `company_logo` VARCHAR(255) NULL,
  `cover_image` VARCHAR(255) NULL,
  `website` VARCHAR(255) NULL,
  `industry` VARCHAR(100) NULL,
  `size` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `address` VARCHAR(255) NULL,
  `tax_code` VARCHAR(50) NULL,
  `license_document` VARCHAR(255) NULL,
  `contact_person` VARCHAR(100) NULL,
  `contact_phone` VARCHAR(50) NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Bảng categories (Danh mục ngành nghề tuyển dụng)
-- ------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `icon` VARCHAR(50) NULL DEFAULT 'briefcase',
  `description` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Bảng locations (Địa điểm làm việc)
-- ------------------------------------------------------------
CREATE TABLE `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `type` VARCHAR(50) DEFAULT 'city',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Bảng campaigns (Chiến dịch tuyển dụng)
-- ------------------------------------------------------------
CREATE TABLE `campaigns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employer_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `target_hires` INT NOT NULL DEFAULT 1,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `status` ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`employer_id`),
  CONSTRAINT `fk_camp_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Bảng cvs (Hồ sơ trực tuyến của ứng viên)
-- ------------------------------------------------------------
CREATE TABLE `cvs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL DEFAULT 'Hồ sơ nghề nghiệp',
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `address` VARCHAR(200) NULL,
  `summary` TEXT NULL,
  `education` TEXT NULL,
  `experience` TEXT NULL,
  `skills` TEXT NULL,
  `certifications` TEXT NULL,
  `languages` VARCHAR(255) NULL,
  `cv_link` VARCHAR(255) NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  CONSTRAINT `fk_cv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. Bảng jobs (Tin tuyển dụng hệ thống JobFinder)
-- ------------------------------------------------------------
CREATE TABLE `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employer_id` INT NULL,
  `campaign_id` INT NULL,
  `title` VARCHAR(150) NOT NULL,
  `company` VARCHAR(100) NOT NULL,
  `company_logo` VARCHAR(255) NULL,
  `location` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `salary` VARCHAR(100) NOT NULL,
  `salary_value` INT DEFAULT 0,
  `min_salary` INT NOT NULL DEFAULT 0,
  `max_salary` INT NOT NULL DEFAULT 0,
  `salary_type` VARCHAR(20) NOT NULL DEFAULT 'deal',
  `job_type` VARCHAR(50) NOT NULL DEFAULT 'Toàn thời gian',
  `quantity` INT NOT NULL DEFAULT 1,
  `level` VARCHAR(50) NOT NULL DEFAULT 'Nhân viên',
  `experience` VARCHAR(50) NOT NULL DEFAULT 'Không yêu cầu',
  `description` TEXT NOT NULL,
  `requirements` TEXT NOT NULL,
  `benefits` TEXT NULL,
  `education_level` VARCHAR(50) NOT NULL DEFAULT 'Không yêu cầu',
  `working_time` VARCHAR(150) NULL,
  `contact_name` VARCHAR(100) NULL,
  `contact_email` VARCHAR(100) NULL,
  `deadline` DATE NULL,
  `views` INT NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `reject_reason` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`employer_id`),
  INDEX (`campaign_id`),
  INDEX (`status`),
  CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_job_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. Bảng applications (Ứng tuyển & Candidate Pipeline 9 vòng)
-- ------------------------------------------------------------
CREATE TABLE `applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `cv_id` INT NULL,
  `cover_letter` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `rating` TINYINT NOT NULL DEFAULT 0,
  `stage_updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`job_id`),
  INDEX (`status`),
  CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_cv` FOREIGN KEY (`cv_id`) REFERENCES `cvs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uq_user_job` UNIQUE (`user_id`, `job_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. Bảng application_notes (Ghi chú nội bộ của Nhà tuyển dụng)
-- ------------------------------------------------------------
CREATE TABLE `application_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `employer_id` INT NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`application_id`),
  INDEX (`employer_id`),
  CONSTRAINT `fk_note_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_emp` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. Bảng application_tags (Gắn nhãn ứng viên)
-- ------------------------------------------------------------
CREATE TABLE `application_tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  `color` VARCHAR(20) NOT NULL DEFAULT '#16A36A',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`application_id`),
  CONSTRAINT `fk_tag_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. Bảng interview_schedules (Lịch phỏng vấn ứng viên)
-- ------------------------------------------------------------
CREATE TABLE `interview_schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `employer_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `candidate_id` INT NOT NULL,
  `interview_date` DATE NOT NULL,
  `interview_time` TIME NOT NULL,
  `interview_type` ENUM('online', 'offline') NOT NULL DEFAULT 'offline',
  `location_or_link` VARCHAR(255) NOT NULL,
  `interviewer` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`application_id`),
  INDEX (`employer_id`),
  INDEX (`candidate_id`),
  CONSTRAINT `fk_int_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_int_emp` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_int_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_int_cand` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. Bảng saved_jobs (Tin tuyển dụng đã lưu)
-- ------------------------------------------------------------
CREATE TABLE `saved_jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`user_id`, `job_id`),
  CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. Bảng notifications (Thông báo hệ thống đa vai trò)
-- ------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. Bảng activity_logs (Nhật ký hoạt động kiểm toán)
-- ------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- DỮ LIỆU KHỞI TẠO ĐẦY ĐỦ THỊ TRƯỜNG VIỆT NAM
-- Mật khẩu chuẩn cho các tài khoản mẫu: 123456
-- ============================================================

-- 1. Tài khoản mẫu (Admin, Nhà tuyển dụng, Ứng viên)
INSERT INTO `users` (`id`, `name`, `email`, `role`, `password`, `phone`, `is_active`, `created_at`) VALUES
(1, 'Quản trị viên Hệ thống', 'admin@jobfinder.vn', 'admin', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '0901234567', 1, NOW()),
(2, 'FPT Software HR', 'tuyendung@fpt.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02473007575', 1, NOW()),
(3, 'VNG Corporation HR', 'careers@vng.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02839623888', 1, NOW()),
(4, 'Nguyễn Văn An', 'an.nguyen@gmail.com', 'candidate', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '0912345678', 1, NOW()),
(5, 'Trần Thị Mai', 'mai.tran@gmail.com', 'candidate', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '0987654321', 1, NOW()),
(6, 'MoMo Tuyển Dụng', 'hr@momo.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02873065555', 1, NOW()),
(7, 'Viettel Digital HR', 'tuyendung@viettel.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02462660198', 1, NOW()),
(8, 'Shopee Việt Nam HR', 'tuyendung@shopee.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02873089999', 1, NOW()),
(9, 'Vinamilk Tuyển Dụng', 'hr@vinamilk.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02854155555', 1, NOW()),
(10, 'Techcombank HR', 'hr@techcombank.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02439446368', 1, NOW()),
(11, 'Vietcombank HR', 'tuyendung@vietcombank.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02439343137', 1, NOW()),
(12, 'Thế Giới Di Động HR', 'tuyendung@thegioididong.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02838125960', 1, NOW()),
(13, 'Masan Group HR', 'hr@masangroup.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02862563862', 1, NOW()),
(14, 'Vinpearl Tuyển Dụng', 'tuyendung@vinpearl.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02583598999', 1, NOW()),
(15, 'Gemadept Logistics HR', 'hr@gemadept.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02838236236', 1, NOW()),
(16, 'Dược Hậu Giang HR', 'tuyendung@dhgpharma.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02923891433', 1, NOW()),
(17, 'VUS Tuyển Dụng', 'hr@vus.edu.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02873083333', 1, NOW()),
(18, 'Coteccons HR', 'tuyendung@coteccons.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02835142255', 1, NOW()),
(19, 'Sun Group HR', 'hr@sungroup.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02363818999', 1, NOW()),
(20, 'PNJ Tuyển Dụng', 'tuyendung@pnj.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02839951703', 1, NOW()),
(21, 'Highlands Coffee HR', 'hr@highlandscoffee.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02835127355', 1, NOW());

-- 2. Hồ sơ 18 doanh nghiệp uy tín đa ngành
INSERT INTO `employer_profiles` (`id`, `user_id`, `company_name`, `company_logo`, `website`, `industry`, `size`, `description`, `address`, `tax_code`, `is_verified`, `created_at`) VALUES
(1, 2, 'FPT Software', 'assets/images/companies/fpt-software.svg', 'https://fptsoftware.com', 'Công nghệ thông tin', '10.000+ nhân viên', 'Doanh nghiệp dịch vụ công nghệ thông tin và chuyển đổi số hàng đầu Việt Nam.', 'Tòa nhà FPT, Phố Duy Tân, Cầu Giấy, Hà Nội', '0101234567', 1, NOW()),
(2, 3, 'VNG Corporation', 'assets/images/companies/vng.svg', 'https://vng.com.vn', 'Công nghệ thông tin', '3.000+ nhân viên', 'Tập đoàn công nghệ với hệ sinh thái Zalo, ZaloPay, VNG Cloud và Game Studios.', 'Z06 Đường số 13, Tân Thuận Đông, Quận 7, TP.HCM', '0309876543', 1, NOW()),
(3, 6, 'MoMo', 'assets/images/companies/momo.svg', 'https://momo.vn', 'Kế toán - Tài chính - Ngân hàng', '2.000+ nhân viên', 'Siêu ứng dụng thanh toán và nền tảng tài chính số phục vụ hơn 30 triệu người dùng.', '08 Hoàng Văn Thái, Tân Phú, Quận 7, TP.HCM', '0305288289', 1, NOW()),
(4, 7, 'Viettel Digital', 'assets/images/companies/viettel-digital.svg', 'https://viettel.com.vn', 'Công nghệ thông tin', '1.500+ nhân viên', 'Đơn vị tiên phong kiến tạo hệ sinh thái tài chính số và dữ liệu lớn của Tập đoàn Viettel.', 'Số 1 Trần Hữu Dực, Nam Từ Liêm, Hà Nội', '0100109106', 1, NOW()),
(5, 8, 'Shopee Việt Nam', 'assets/images/companies/shopee.svg', 'https://shopee.vn', 'Kinh doanh & Bán hàng', '5.000+ nhân viên', 'Nền tảng thương mại điện tử hàng đầu kết nối hàng triệu người mua và nhà bán hàng.', '67 Lê Lợi, Bến Nghé, Quận 1, TP.HCM', '0313667530', 1, NOW()),
(6, 9, 'Vinamilk', 'assets/images/companies/vinamilk.svg', 'https://vinamilk.com.vn', 'Marketing & Truyền thông', '8.000+ nhân viên', 'Thương hiệu sữa quốc gia hàng đầu Việt Nam với hệ thống nhà máy và trang trại chuẩn quốc tế.', '10 Tân Trào, Tân Phú, Quận 7, TP.HCM', '0300588569', 1, NOW()),
(7, 10, 'Techcombank', 'assets/images/companies/techcombank.svg', 'https://techcombank.com', 'Kế toán - Tài chính - Ngân hàng', '10.000+ nhân viên', 'Ngân hàng TMCP Kỹ Thương Việt Nam tiên phong chuyển đổi số ngành tài chính.', 'Số 6 Quang Trung, Hoàn Kiếm, Hà Nội', '0100230800', 1, NOW()),
(8, 11, 'Vietcombank', 'assets/images/companies/vietcombank.svg', 'https://vietcombank.com.vn', 'Kế toán - Tài chính - Ngân hàng', '20.000+ nhân viên', 'Ngân hàng TMCP Ngoại thương Việt Nam — định chế tài chính uy tín hàng đầu.', '198 Trần Quang Khải, Hoàn Kiếm, Hà Nội', '0100112437', 1, NOW()),
(9, 12, 'Thế Giới Di Động', 'assets/images/companies/the-gioi-di-dong.svg', 'https://mwg.vn', 'Kinh doanh & Bán hàng', '60.000+ nhân viên', 'Hệ thống bán lẻ thiết bị di động, điện máy và bách hóa hàng đầu Việt Nam.', 'Lô T2-1.2, Đường D1, Khu Công nghệ cao, TP. Thủ Đức, TP.HCM', '0306731335', 1, NOW()),
(10, 13, 'Masan Group', 'assets/images/companies/masan.svg', 'https://masangroup.com', 'Kinh doanh & Bán hàng', '30.000+ nhân viên', 'Tập đoàn hàng tiêu dùng và bán lẻ tích hợp hàng đầu Việt Nam.', '23 Lê Duẩn, Bến Nghé, Quận 1, TP.HCM', '0303576603', 1, NOW()),
(11, 14, 'Vinpearl', 'assets/images/companies/vinpearl.svg', 'https://vinpearl.com', 'Nhà hàng - Khách sạn - Du lịch', '10.000+ nhân viên', 'Hệ thống khách sạn, khu nghỉ dưỡng và vui chơi giải trí đẳng cấp 5 sao.', 'Đảo Hòn Tre, Vĩnh Nguyên, Nha Trang / VP TP.HCM', '4200456848', 1, NOW()),
(12, 15, 'Gemadept Logistics', 'assets/images/companies/gemadept.svg', 'https://gemadept.com.vn', 'Logistics & Xuất nhập khẩu', '3.500+ nhân viên', 'Doanh nghiệp khai thác cảng và chuỗi cung ứng logistics tích hợp hàng đầu.', '06 Lê Thánh Tôn, Bến Nghé, Quận 1, TP.HCM', '0301116791', 1, NOW()),
(13, 16, 'Dược Hậu Giang', 'assets/images/companies/duoc-hau-giang.svg', 'https://dhgpharma.com.vn', 'Y tế & Dược phẩm', '3.000+ nhân viên', 'Doanh nghiệp dược phẩm hàng đầu Việt Nam đạt chuẩn Japan-GMP.', '288 Bis Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ', '1800156801', 1, NOW()),
(14, 17, 'VUS Anh Văn Hội Việt Mỹ', 'assets/images/companies/vus.svg', 'https://vus.edu.vn', 'Giáo dục & Đào tạo', '2.500+ nhân viên', 'Hệ thống Anh văn Hội Việt Mỹ với hơn 80 cơ sở đào tạo trên toàn quốc.', '189 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '0302555123', 1, NOW()),
(15, 18, 'Coteccons', 'assets/images/companies/coteccons.svg', 'https://coteccons.vn', 'Kỹ thuật - Cơ khí - Xây dựng', '2.500+ nhân viên', 'Tổng thầu xây dựng hàng đầu Việt Nam với hàng loạt công trình biểu tượng.', '236/6 Điện Biên Phủ, Bình Thạnh, TP.HCM', '0303443233', 1, NOW()),
(16, 19, 'Sun Group', 'assets/images/companies/sun-group.svg', 'https://sungroup.com.vn', 'Nhà hàng - Khách sạn - Du lịch', '8.000+ nhân viên', 'Tập đoàn phát triển du lịch nghỉ dưỡng, vui chơi giải trí và bất động sản cao cấp.', 'Tòa nhà Sun City, Hải Châu, Đà Nẵng', '0400592108', 1, NOW()),
(17, 20, 'PNJ', 'assets/images/companies/pnj.svg', 'https://pnj.com.vn', 'Thiết kế & Kiến trúc', '7.000+ nhân viên', 'Công ty Cổ phần Vàng bạc Đá quý Phú Nhuận — thương hiệu trang sức hàng đầu.', '170E Phan Đăng Lưu, Phú Nhuận, TP.HCM', '0300521758', 1, NOW()),
(18, 21, 'Highlands Coffee', 'assets/images/companies/highlands-coffee.svg', 'https://highlandscoffee.com.vn', 'Nhà hàng - Khách sạn - Du lịch', '12.000+ nhân viên', 'Chuỗi thương hiệu cà phê và ẩm thực được yêu thích hàng đầu tại Việt Nam.', '135/37/50 Nguyễn Hữu Cảnh, Bình Thạnh, TP.HCM', '0309965814', 1, NOW());

-- 3. 12 Ngành nghề tuyển dụng
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'Công nghệ thông tin', 'cong-nghe-thong-tin', 'code', 'Lập trình viên, Kỹ sư phần mềm, DevOps, Kiểm thử, Dữ liệu'),
(2, 'Marketing & Truyền thông', 'marketing-truyen-thong', 'trending-up', 'Marketing số, SEO, Nội dung, Quản trị thương hiệu'),
(3, 'Kinh doanh & Bán hàng', 'kinh-doanh-ban-hang', 'shopping-bag', 'Kinh doanh B2B, Phát triển thị trường, Bán lẻ'),
(4, 'Kế toán - Tài chính - Ngân hàng', 'ke-toan-tai-chinh-ngan-hang', 'dollar-sign', 'Kế toán tổng hợp, Kiểm toán, Tín dụng, Phân tích tài chính'),
(5, 'Thiết kế & Kiến trúc', 'thiet-ke-kien-truc', 'layout', 'Thiết kế UI/UX, Đồ họa, Thiết kế sản phẩm, Kiến trúc'),
(6, 'Nhân sự & Hành chính', 'nhan-su-hanh-chinh', 'users', 'Tuyển dụng, Tiền lương phúc lợi, Hành chính văn phòng'),
(7, 'Logistics & Xuất nhập khẩu', 'logistics-xuat-nhap-khau', 'truck', 'Điều phối vận tải, Kho vận, Hải quan, Chuỗi cung ứng'),
(8, 'Y tế & Dược phẩm', 'y-te-duoc-pham', 'heart', 'Dược sĩ, Trình dược viên, Kiểm nghiệm chất lượng'),
(9, 'Giáo dục & Đào tạo', 'giao-duc-dao-tao', 'book-open', 'Giảng viên, Giáo viên ngoại ngữ, Tư vấn tuyển sinh'),
(10, 'Nhà hàng - Khách sạn - Du lịch', 'nha-hang-khach-san-du-lich', 'coffee', 'Quản lý cửa hàng, Lễ tân, Điều hành tour, F&B'),
(11, 'Kỹ thuật - Cơ khí - Xây dựng', 'ky-thuat-co-khi-xay-dung', 'tool', 'Kỹ sư xây dựng, Giám sát công trình, Cơ điện'),
(12, 'Pháp lý & Luật', 'phap-ly-luat', 'shield', 'Chuyên viên pháp chế, Luật sư nội bộ, Tuân thủ');

-- 4. Địa điểm tuyển dụng
INSERT INTO `locations` (`id`, `name`, `slug`, `type`) VALUES
(1, 'TP.HCM', 'tp-hcm', 'city'),
(2, 'Hà Nội', 'ha-noi', 'city'),
(3, 'Đà Nẵng', 'da-nang', 'city'),
(4, 'Bình Dương', 'binh-duong', 'city'),
(5, 'Đồng Nai', 'dong-nai', 'city'),
(6, 'Cần Thơ', 'can-tho', 'city'),
(7, 'Hải Phòng', 'hai-phong', 'city'),
(8, 'Làm việc từ xa', 'lam-viec-tu-xa', 'type');

-- 5. Chiến dịch tuyển dụng mẫu
INSERT INTO `campaigns` (`id`, `employer_id`, `title`, `description`, `target_hires`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 2, 'Tuyển dụng Kỹ sư Công nghệ 2026', 'Mở rộng đội ngũ phát triển phần mềm tại Hà Nội và Đà Nẵng.', 15, '2026-01-01', '2026-12-31', 'active', NOW()),
(2, 3, 'VNG Tuyển dụng Sản phẩm & Tăng trưởng 2026', 'Tìm kiếm nhân sự tài năng cho mảng web, nhân sự và truyền thông.', 10, '2026-02-01', '2026-12-31', 'active', NOW());

-- 6. Hồ sơ CV mẫu
INSERT INTO `cvs` (`id`, `user_id`, `title`, `full_name`, `email`, `phone`, `address`, `summary`, `education`, `experience`, `skills`, `certifications`, `languages`, `cv_link`, `is_default`) VALUES
(1, 4, 'Lập trình viên Backend PHP / Laravel', 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345678', 'Cầu Giấy, Hà Nội', 'Lập trình viên Backend với 2 năm kinh nghiệm phát triển ứng dụng web bằng PHP, Laravel và MySQL.', 'Đại học Bách Khoa Hà Nội — Công nghệ Thông tin (2020 - 2024)\nGPA: 3.45/4.0', 'Lập trình viên Backend — Công ty Phần mềm Minh Anh (2024 - Nay)\n• Xây dựng hệ thống quản lý đơn hàng và API kết nối đối tác vận chuyển.', 'PHP / Laravel, MySQL / SQL, JavaScript / ReactJS, Git', 'Chứng chỉ TOEIC 800+', 'Tiếng Anh (TOEIC 750+)', 'https://github.com/annguyen-dev', 1),
(2, 5, 'Chuyên viên Thiết kế UI/UX', 'Trần Thị Mai', 'mai.tran@gmail.com', '0987654321', 'Quận 1, TP.HCM', 'Chuyên viên thiết kế sản phẩm số có 2 năm kinh nghiệm xây dựng giao diện web và ứng dụng di động.', 'Đại học Kiến Trúc TP.HCM — Thiết kế Đồ họa (2019 - 2023)', 'Chuyên viên Thiết kế UI/UX — Studio Số (2023 - Nay)\n• Thiết kế giao diện cho 6 sản phẩm thương mại điện tử.', 'Figma / UI-UX, HTML5 / CSS3', 'Chứng chỉ Thiết kế Google UX', 'Tiếng Anh (Giao tiếp tốt)', 'https://behance.net/maitran-design', 1);

-- 7. 28 Tin tuyển dụng đa ngành nghề, đa tỉnh thành
INSERT INTO `jobs` (`id`, `employer_id`, `campaign_id`, `title`, `company`, `company_logo`, `location`, `category`, `salary`, `salary_value`, `min_salary`, `max_salary`, `salary_type`, `job_type`, `quantity`, `level`, `experience`, `description`, `requirements`, `benefits`, `deadline`, `views`, `is_featured`, `status`) VALUES
(1, 2, 1, 'Lập trình viên Backend PHP / Laravel', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Cầu Giấy, Hà Nội', 'Công nghệ thông tin', '18 - 28 triệu', 23, 19000000, 28000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Lập trình viên Backend PHP / Laravel tại FPT Software.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 28 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại FPT Software, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-31', 432, 1, 'active'),
(2, 3, 2, 'Lập trình viên Frontend ReactJS / TypeScript', 'VNG Corporation', 'assets/images/companies/vng.svg', 'Quận 7, TP.HCM', 'Công nghệ thông tin', '20 - 35 triệu', 28, 24000000, 33000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Lập trình viên Frontend ReactJS / TypeScript tại VNG Corporation.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (20 - 35 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại VNG Corporation, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-30', 518, 1, 'active'),
(3, 6, NULL, 'Chuyên viên Thiết kế Giao diện UI/UX', 'MoMo', 'assets/images/companies/momo.svg', 'Quận 7, TP.HCM', 'Thiết kế & Kiến trúc', '18 - 26 triệu', 22, 18000000, 27000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Thiết kế Giao diện UI/UX tại MoMo.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Thiết kế & Kiến trúc.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 26 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại MoMo, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-28', 389, 1, 'active'),
(4, 7, NULL, 'Kỹ sư Dữ liệu & Hệ thống Lớn', 'Viettel Digital', 'assets/images/companies/viettel-digital.svg', 'Nam Từ Liêm, Hà Nội', 'Công nghệ thông tin', '25 - 42 triệu', 33, 29000000, 38000000, 'range', 'Toàn thời gian', 2, 'Chuyên viên / Senior', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Kỹ sư Dữ liệu & Hệ thống Lớn tại Viettel Digital.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.
- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (25 - 42 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Viettel Digital, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-15', 310, 1, 'active'),
(5, 8, NULL, 'Chuyên viên Marketing Số (Performance)', 'Shopee Việt Nam', 'assets/images/companies/shopee.svg', 'Quận 1, TP.HCM', 'Marketing & Truyền thông', '15 - 22 triệu', 18, 14000000, 23000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Marketing Số (Performance) tại Shopee Việt Nam.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Marketing & Truyền thông.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (15 - 22 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Shopee Việt Nam, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-25', 295, 1, 'active'),
(6, 9, NULL, 'Chuyên viên Quản trị Thương hiệu', 'Vinamilk', 'assets/images/companies/vinamilk.svg', 'Quận 7, TP.HCM', 'Marketing & Truyền thông', '20 - 30 triệu', 25, 21000000, 30000000, 'range', 'Toàn thời gian', 1, 'Trưởng nhóm / Leader', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Quản trị Thương hiệu tại Vinamilk.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Marketing & Truyền thông.
- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (20 - 30 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Vinamilk, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-10', 240, 0, 'active'),
(7, 10, NULL, 'Chuyên viên Kế toán Tổng hợp', 'Techcombank', 'assets/images/companies/techcombank.svg', 'Hoàn Kiếm, Hà Nội', 'Kế toán - Tài chính - Ngân hàng', '16 - 24 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 1, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Kế toán Tổng hợp tại Techcombank.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kế toán - Tài chính - Ngân hàng.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 24 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Techcombank, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-20', 265, 0, 'active'),
(8, 11, NULL, 'Chuyên viên Quan hệ Khách hàng Doanh nghiệp', 'Vietcombank', 'assets/images/companies/vietcombank.svg', 'Hoàn Kiếm, Hà Nội', 'Kế toán - Tài chính - Ngân hàng', '18 - 28 triệu', 23, 19000000, 28000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Quan hệ Khách hàng Doanh nghiệp tại Vietcombank.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kế toán - Tài chính - Ngân hàng.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 28 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Vietcombank, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-18', 275, 1, 'active'),
(9, 12, NULL, 'Quản lý Cửa hàng Bán lẻ Công nghệ', 'Thế Giới Di Động', 'assets/images/companies/the-gioi-di-dong.svg', 'Biên Hòa, Đồng Nai', 'Kinh doanh & Bán hàng', '15 - 25 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 4, 'Quản lý', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Quản lý Cửa hàng Bán lẻ Công nghệ tại Thế Giới Di Động.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kinh doanh & Bán hàng.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (15 - 25 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Thế Giới Di Động, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-29', 210, 0, 'active'),
(10, 13, NULL, 'Nhân viên Phát triển Thị trường B2B', 'Masan Group', 'assets/images/companies/masan.svg', 'Thủ Dầu Một, Bình Dương', 'Kinh doanh & Bán hàng', '14 - 22 triệu', 18, 14000000, 23000000, 'range', 'Toàn thời gian', 5, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Nhân viên Phát triển Thị trường B2B tại Masan Group.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kinh doanh & Bán hàng.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (14 - 22 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Masan Group, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-05', 198, 0, 'active'),
(11, 14, NULL, 'Trưởng bộ phận Lễ tân Khách sạn 5 Sao', 'Vinpearl', 'assets/images/companies/vinpearl.svg', 'Hải Châu, Đà Nẵng', 'Nhà hàng - Khách sạn - Du lịch', '16 - 24 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 1, 'Trưởng nhóm / Leader', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Trưởng bộ phận Lễ tân Khách sạn 5 Sao tại Vinpearl.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhà hàng - Khách sạn - Du lịch.
- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 24 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Vinpearl, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-12', 185, 0, 'active'),
(12, 15, NULL, 'Chuyên viên Điều phối Logistics & Cảng biển', 'Gemadept Logistics', 'assets/images/companies/gemadept.svg', 'Hải Phòng', 'Logistics & Xuất nhập khẩu', '14 - 20 triệu', 17, 13000000, 22000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Điều phối Logistics & Cảng biển tại Gemadept Logistics.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Logistics & Xuất nhập khẩu.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (14 - 20 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Gemadept Logistics, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-26', 202, 0, 'active'),
(13, 15, NULL, 'Nhân viên Chứng từ Xuất nhập khẩu', 'Gemadept Logistics', 'assets/images/companies/gemadept.svg', 'Quận 1, TP.HCM', 'Logistics & Xuất nhập khẩu', '12 - 17 triệu', 14, 10000000, 19000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Nhân viên Chứng từ Xuất nhập khẩu tại Gemadept Logistics.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Logistics & Xuất nhập khẩu.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (12 - 17 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Gemadept Logistics, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-08', 175, 0, 'active'),
(14, 16, NULL, 'Dược sĩ Kiểm nghiệm Chất lượng (QC)', 'Dược Hậu Giang', 'assets/images/companies/duoc-hau-giang.svg', 'Ninh Kiều, Cần Thơ', 'Y tế & Dược phẩm', '12 - 18 triệu', 15, 11000000, 20000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Dược sĩ Kiểm nghiệm Chất lượng (QC) tại Dược Hậu Giang.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Y tế & Dược phẩm.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (12 - 18 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Dược Hậu Giang, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-20', 160, 0, 'active'),
(15, 16, NULL, 'Trình dược viên Kênh Nhà thuốc (ETC/OTC)', 'Dược Hậu Giang', 'assets/images/companies/duoc-hau-giang.svg', 'Quận 10, TP.HCM', 'Y tế & Dược phẩm', '14 - 24 triệu', 19, 15000000, 24000000, 'range', 'Toàn thời gian', 4, 'Nhân viên', 'Dưới 1 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Trình dược viên Kênh Nhà thuốc (ETC/OTC) tại Dược Hậu Giang.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Y tế & Dược phẩm.
- Kinh nghiệm yêu cầu: Dưới 1 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (14 - 24 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Dược Hậu Giang, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-15', 188, 0, 'active'),
(16, 17, NULL, 'Giảng viên Tiếng Anh Giao tiếp & IELTS', 'VUS Anh Văn Hội Việt Mỹ', 'assets/images/companies/vus.svg', 'Quận 1, TP.HCM', 'Giáo dục & Đào tạo', '22 - 38 triệu', 30, 26000000, 35000000, 'range', 'Toàn thời gian', 5, 'Chuyên viên / Senior', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Giảng viên Tiếng Anh Giao tiếp & IELTS tại VUS Anh Văn Hội Việt Mỹ.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Giáo dục & Đào tạo.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (22 - 38 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại VUS Anh Văn Hội Việt Mỹ, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-02-01', 290, 1, 'active'),
(17, 17, NULL, 'Chuyên viên Tư vấn Tuyển sinh Giáo dục', 'VUS Anh Văn Hội Việt Mỹ', 'assets/images/companies/vus.svg', 'Cầu Giấy, Hà Nội', 'Giáo dục & Đào tạo', '11 - 18 triệu', 14, 10000000, 19000000, 'range', 'Toàn thời gian', 3, 'Mới ra trường', 'Dưới 1 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Tư vấn Tuyển sinh Giáo dục tại VUS Anh Văn Hội Việt Mỹ.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Giáo dục & Đào tạo.
- Kinh nghiệm yêu cầu: Dưới 1 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (11 - 18 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại VUS Anh Văn Hội Việt Mỹ, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-10', 170, 0, 'active'),
(18, 18, NULL, 'Kỹ sư Giám sát Thi công Xây dựng', 'Coteccons', 'assets/images/companies/coteccons.svg', 'Bình Thạnh, TP.HCM', 'Kỹ thuật - Cơ khí - Xây dựng', '18 - 28 triệu', 23, 19000000, 28000000, 'range', 'Toàn thời gian', 4, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Kỹ sư Giám sát Thi công Xây dựng tại Coteccons.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kỹ thuật - Cơ khí - Xây dựng.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 28 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Coteccons, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-25', 220, 1, 'active'),
(19, 18, NULL, 'Kỹ sư Cơ điện (MEP) Công trình', 'Coteccons', 'assets/images/companies/coteccons.svg', 'Thủ Dầu Một, Bình Dương', 'Kỹ thuật - Cơ khí - Xây dựng', '17 - 26 triệu', 21, 17000000, 26000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Kỹ sư Cơ điện (MEP) Công trình tại Coteccons.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kỹ thuật - Cơ khí - Xây dựng.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (17 - 26 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Coteccons, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-22', 165, 0, 'active'),
(20, 19, NULL, 'Chuyên viên Điều hành Tour & Dịch vụ Du lịch', 'Sun Group', 'assets/images/companies/sun-group.svg', 'Hải Châu, Đà Nẵng', 'Nhà hàng - Khách sạn - Du lịch', '13 - 20 triệu', 16, 12000000, 21000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Điều hành Tour & Dịch vụ Du lịch tại Sun Group.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhà hàng - Khách sạn - Du lịch.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (13 - 20 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Sun Group, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-18', 195, 0, 'active'),
(21, 20, NULL, 'Chuyên viên Thiết kế Trang sức 3D', 'PNJ', 'assets/images/companies/pnj.svg', 'Phú Nhuận, TP.HCM', 'Thiết kế & Kiến trúc', '16 - 25 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Thiết kế Trang sức 3D tại PNJ.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Thiết kế & Kiến trúc.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 25 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại PNJ, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-14', 210, 0, 'active'),
(22, 21, NULL, 'Quản lý Cửa hàng Cà phê (Store Manager)', 'Highlands Coffee', 'assets/images/companies/highlands-coffee.svg', 'Quận 1, TP.HCM', 'Nhà hàng - Khách sạn - Du lịch', '13 - 19 triệu', 16, 12000000, 21000000, 'range', 'Toàn thời gian', 4, 'Quản lý', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Quản lý Cửa hàng Cà phê (Store Manager) tại Highlands Coffee.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhà hàng - Khách sạn - Du lịch.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (13 - 19 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Highlands Coffee, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-12', 245, 0, 'active'),
(23, 3, 2, 'Chuyên viên Tuyển dụng Nhân sự Công nghệ', 'VNG Corporation', 'assets/images/companies/vng.svg', 'Quận 7, TP.HCM', 'Nhân sự & Hành chính', '15 - 22 triệu', 18, 14000000, 23000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Tuyển dụng Nhân sự Công nghệ tại VNG Corporation.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhân sự & Hành chính.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (15 - 22 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại VNG Corporation, có lộ trình đào tạo và thăng tiến rõ ràng.', '2026-12-28', 198, 0, 'active'),
(24, 9, NULL, 'Chuyên viên Tiền lương & Phúc lợi (C&B)', 'Vinamilk', 'assets/images/companies/vinamilk.svg', 'Quận 7, TP.HCM', 'Nhân sự & Hành chính', '16 - 23 triệu', 19, 15000000, 24000000, 'range', 'Toàn thời gian', 1, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Tiền lương & Phúc lợi (C&B) tại Vinamilk.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhân sự & Hành chính.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 23 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Vinamilk, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-09', 172, 0, 'active'),
(25, 10, NULL, 'Chuyên viên Pháp chế Doanh nghiệp & Hợp đồng', 'Techcombank', 'assets/images/companies/techcombank.svg', 'Hoàn Kiếm, Hà Nội', 'Pháp lý & Luật', '20 - 32 triệu', 26, 22000000, 31000000, 'range', 'Toàn thời gian', 1, 'Chuyên viên / Senior', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Pháp chế Doanh nghiệp & Hợp đồng tại Techcombank.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Pháp lý & Luật.
- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (20 - 32 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại Techcombank, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-16', 184, 0, 'active'),
(26, 6, NULL, 'Chuyên viên Pháp lý Tuân thủ Fintech', 'MoMo', 'assets/images/companies/momo.svg', 'Quận 7, TP.HCM', 'Pháp lý & Luật', '22 - 35 triệu', 28, 24000000, 33000000, 'range', 'Toàn thời gian', 1, 'Chuyên viên / Senior', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Pháp lý Tuân thủ Fintech tại MoMo.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Pháp lý & Luật.
- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (22 - 35 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại MoMo, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-19', 166, 0, 'active'),
(27, 2, 1, 'Thực tập sinh Lập trình Web (PHP / JavaScript)', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Hải Châu, Đà Nẵng', 'Công nghệ thông tin', '5 - 8 triệu', 6, 2000000, 11000000, 'range', 'Thực tập', 6, 'Thực tập sinh', 'Không yêu cầu', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Thực tập sinh Lập trình Web (PHP / JavaScript) tại FPT Software.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.
- Kinh nghiệm yêu cầu: Không yêu cầu, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (5 - 8 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại FPT Software, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-02-15', 495, 1, 'active'),
(28, 3, 2, 'Chuyên viên Nội dung & SEO (Làm việc từ xa)', 'VNG Corporation', 'assets/images/companies/vng.svg', 'Làm việc từ xa', 'Marketing & Truyền thông', '12 - 18 triệu', 15, 11000000, 20000000, 'range', 'Làm việc từ xa', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Nội dung & SEO (Làm việc từ xa) tại VNG Corporation.
- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.
- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Marketing & Truyền thông.
- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.
- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (12 - 18 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.
- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.
- Môi trường làm việc chuyên nghiệp tại VNG Corporation, có lộ trình đào tạo và thăng tiến rõ ràng.', '2027-01-30', 312, 0, 'active'),
(29, 2, 1, 'Kỹ sư Kiểm thử Phần mềm (QA / Tester)', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Cầu Giấy, Hà Nội', 'Công nghệ thông tin', '14 - 22 triệu', 18, 14000000, 22000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Lập kế hoạch kiểm thử, viết kịch bản kiểm thử và thực hiện kiểm thử chức năng cho ứng dụng web/di động.\n- Ghi nhận lỗi, phối hợp với đội ngũ lập trình viên tái hiện và xác minh bản sửa lỗi.', '- Từ 1 năm kinh nghiệm kiểm thử phần mềm, hiểu rõ quy trình phát triển phần mềm Agile/Scrum.\n- Có kinh nghiệm kiểm thử API hoặc viết kịch bản tự động là lợi thế.', '- Lương tháng 13, thưởng hiệu suất dự án và bảo hiểm sức khỏe FPT Care.\n- Lộ trình phát triển lên Trưởng nhóm Kiểm thử.', '2027-02-20', 268, 1, 'active'),
(30, 2, 1, 'Kỹ sư Hạ tầng Đám mây (DevOps / Cloud)', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Quận 7, TP.HCM', 'Công nghệ thông tin', '26 - 42 triệu', 34, 26000000, 42000000, 'range', 'Toàn thời gian', 2, 'Chuyên viên / Senior', '3 - 5 năm', '- Thiết kế, triển khai và giám sát hệ thống hạ tầng Cloud (AWS/Azure) và quy trình CI/CD.\n- Tối ưu hóa hiệu năng, bảo mật và tính sẵn sàng cao cho các dịch vụ doanh nghiệp.', '- Từ 3 năm kinh nghiệm Linux, Docker, Kubernetes và công cụ tự động hóa Terraform/Ansible.\n- Giao tiếp tiếng Anh tốt trong môi trường dự án quốc tế.', '- Thu nhập cạnh tranh, hỗ trợ chi phí thi chứng chỉ AWS/Azure quốc tế.\n- Làm việc linh hoạt, chế độ đãi ngộ toàn diện.', '2027-02-28', 315, 1, 'active');

-- 8. Ứng tuyển mẫu (tài khoản tuyendung@fpt.com có sẵn 4 hồ sơ ứng viên)
INSERT INTO `applications` (`id`, `user_id`, `job_id`, `cv_id`, `cover_letter`, `status`, `rating`, `created_at`) VALUES
(1, 4, 1, 1, 'Kính gửi Bộ phận Tuyển dụng FPT Software, tôi có 2 năm kinh nghiệm PHP/Laravel và MySQL. Tôi mong muốn được tham gia phỏng vấn cho vị trí Lập trình viên Backend PHP / Laravel.', 'interview_scheduled', 4, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 4, 2, 1, 'Tôi quan tâm đến vị trí Lập trình viên Frontend ReactJS tại VNG Corporation.', 'viewed', 4, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 5, 3, 2, 'Kính gửi đội ngũ MoMo, tôi gửi kèm CV ứng tuyển vị trí Chuyên viên Thiết kế Giao diện UI/UX.', 'suitable', 5, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 5, 27, 2, 'Em là sinh viên mới tốt nghiệp mong muốn ứng tuyển Thực tập sinh Lập trình Web tại FPT Software Đà Nẵng.', 'new', 4, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5, 4, 29, 1, 'Tôi có kinh nghiệm viết kịch bản kiểm thử API và web application, xin ứng tuyển vị trí Kỹ sư Kiểm thử Phần mềm.', 'viewed', 4, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(6, 5, 30, 2, 'Tôi gửi hồ sơ ứng tuyển vị trí Kỹ sư Hạ tầng Đám mây tại FPT Software TP.HCM.', 'new', 3, NOW());

-- 9. Ghi chú nội bộ ứng viên
INSERT INTO `application_notes` (`application_id`, `employer_id`, `note`, `created_at`) VALUES
(1, 2, 'Ứng viên nắm vững Laravel và tối ưu index MySQL. Đã hẹn phỏng vấn vòng 1.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 6, 'Hồ sơ thiết kế sạch sẽ, có kinh nghiệm làm hệ thống giao diện.', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 10. Gắn nhãn ứng viên
INSERT INTO `application_tags` (`application_id`, `tag_name`, `color`) VALUES
(1, 'Ưu tiên', '#D97706'),
(1, 'Đúng chuyên môn', '#059669'),
(3, 'Hồ sơ nổi bật', '#2563EB');

-- 11. Lịch phỏng vấn mẫu
INSERT INTO `interview_schedules` (`application_id`, `employer_id`, `job_id`, `candidate_id`, `interview_date`, `interview_time`, `interview_type`, `location_or_link`, `interviewer`, `notes`, `status`) VALUES
(1, 2, 1, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:30:00', 'online', 'https://meet.google.com/fpt-backend-interview', 'Nguyễn Hoàng Long (Trưởng nhóm Kỹ thuật)', 'Phỏng vấn kỹ thuật PHP, Laravel & MySQL', 'confirmed');

-- 12. Lưu việc làm mẫu
INSERT INTO `saved_jobs` (`user_id`, `job_id`) VALUES
(4, 2), (4, 4), (5, 3);

-- 13. Thông báo mẫu
INSERT INTO `notifications` (`user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(4, 'Lịch phỏng vấn đã được xác nhận', 'FPT Software đã đặt lịch phỏng vấn vị trí Lập trình viên Backend PHP / Laravel lúc 14:30.', 'my-applications.php', 0, NOW()),
(2, 'Hồ sơ ứng tuyển mới', 'Ứng viên Nguyễn Văn An vừa ứng tuyển vị trí Lập trình viên Backend PHP / Laravel.', 'employer/applications.php', 0, NOW());

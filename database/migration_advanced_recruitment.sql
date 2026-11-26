-- ============================================================
-- MIGRATION: Nâng cấp JobFinder thành Hệ thống Quản trị Tuyển dụng Chuyên nghiệp (Hệ thống JobFinder)
-- Tương thích MySQL 5.7+ & MariaDB 10.3+ (An toàn, không mất dữ liệu cũ)
-- ============================================================

USE `job_portal`;

-- 1. Bổ sung các cột mới cho bảng `users`
ALTER TABLE `users` 
  ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- 2. Bổ sung các cột cho bảng `employer_profiles` (Xác thực pháp lý, ảnh bìa, liên hệ)
ALTER TABLE `employer_profiles` 
  ADD COLUMN `cover_image` VARCHAR(255) NULL AFTER `company_logo`,
  ADD COLUMN `tax_code` VARCHAR(50) NULL AFTER `address`,
  ADD COLUMN `license_document` VARCHAR(255) NULL AFTER `tax_code`,
  ADD COLUMN `contact_person` VARCHAR(100) NULL AFTER `license_document`,
  ADD COLUMN `contact_phone` VARCHAR(50) NULL AFTER `contact_person`,
  ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- 3. Tạo bảng danh mục ngành nghề (categories)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `icon` VARCHAR(50) NULL DEFAULT 'briefcase',
  `description` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tạo bảng địa điểm tuyển dụng (locations)
CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `type` VARCHAR(50) DEFAULT 'city',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tạo bảng chiến dịch tuyển dụng (campaigns)
CREATE TABLE IF NOT EXISTS `campaigns` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bổ sung các trường nâng cao cho bảng `jobs` (Hệ thống JobFinder)
ALTER TABLE `jobs`
  ADD COLUMN `campaign_id` INT NULL AFTER `employer_id`,
  ADD COLUMN `quantity` INT NOT NULL DEFAULT 1 AFTER `job_type`,
  ADD COLUMN `level` VARCHAR(50) NOT NULL DEFAULT 'Nhân viên' AFTER `quantity`,
  ADD COLUMN `min_salary` INT NOT NULL DEFAULT 0 AFTER `salary_value`,
  ADD COLUMN `max_salary` INT NOT NULL DEFAULT 0 AFTER `min_salary`,
  ADD COLUMN `salary_type` VARCHAR(20) NOT NULL DEFAULT 'deal' AFTER `max_salary`,
  ADD COLUMN `benefits` TEXT NULL AFTER `requirements`,
  ADD COLUMN `education_level` VARCHAR(50) NOT NULL DEFAULT 'Không yêu cầu' AFTER `benefits`,
  ADD COLUMN `working_time` VARCHAR(150) NULL AFTER `education_level`,
  ADD COLUMN `contact_name` VARCHAR(100) NULL AFTER `working_time`,
  ADD COLUMN `contact_email` VARCHAR(100) NULL AFTER `contact_name`,
  ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `views`,
  ADD COLUMN `reject_reason` TEXT NULL AFTER `status`,
  ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Tạo khóa ngoại cho campaign_id trong jobs (nếu chưa có)
ALTER TABLE `jobs` 
  ADD CONSTRAINT `fk_job_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL;

-- Cập nhật định nghĩa cột status trong jobs để hỗ trợ đầy đủ vòng đời tin JobFinder:
-- draft: bản nháp
-- pending: chờ admin kiểm duyệt
-- active: đang hiển thị tuyển dụng
-- rejected: bị admin từ chối (kèm lý do)
-- inactive: tạm dừng tin
-- expired: hết hạn ứng tuyển
ALTER TABLE `jobs` MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending';

-- 7. Bổ sung trường cho bảng `applications` (Candidate Pipeline & Đánh giá)
ALTER TABLE `applications`
  ADD COLUMN `rating` TINYINT NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN `stage_updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `rating`;

-- Cập nhật trạng thái applications sang các vòng quy trình tuyển dụng hệ thống JobFinder:
-- new: CV mới
-- viewed: Đã xem
-- suitable: Phù hợp
-- contacted: Đã liên hệ
-- interview_scheduled: Hẹn phỏng vấn
-- interviewed: Đã phỏng vấn
-- offered: Gửi đề nghị
-- hired: Đã tuyển dụng
-- rejected: Từ chối / Không phù hợp
ALTER TABLE `applications` MODIFY COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'new';

-- 8. Bảng ghi chú nội bộ ứng viên (application_notes - Chỉ NTD xem)
CREATE TABLE IF NOT EXISTS `application_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `employer_id` INT NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`application_id`),
  INDEX (`employer_id`),
  CONSTRAINT `fk_note_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng gắn nhãn ứng viên (application_tags: Tiềm năng, Ưu tiên, v.v.)
CREATE TABLE IF NOT EXISTS `application_tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  `color` VARCHAR(20) NOT NULL DEFAULT '#16A36A',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`application_id`),
  CONSTRAINT `fk_tag_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng lịch phỏng vấn (interview_schedules)
CREATE TABLE IF NOT EXISTS `interview_schedules` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Bảng lưu tin việc làm của ứng viên (saved_jobs)
CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`user_id`, `job_id`),
  CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Bảng thông báo đa vai trò (notifications)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Bảng nhật ký hoạt động quản trị & kiểm toán (activity_logs)
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NẠP DỮ LIỆU BAN ĐẦU CHO CATEGORIES & LOCATIONS (Nếu chưa có)
-- ------------------------------------------------------------
INSERT IGNORE INTO `categories` (`name`, `slug`, `icon`, `description`) VALUES
('Công nghệ thông tin', 'cong-nghe-thong-tin', 'code', 'Lập trình viên, DevOps, QA/QC, AI/Data Engineer'),
('Marketing & Truyền thông', 'marketing-truyen-thong', 'trending-up', 'Digital Marketing, SEO, Content, Brand Manager'),
('Thiết kế đồ họa & UI/UX', 'thiet-ke-do-hoa-ui-ux', 'layout', 'UI/UX Designer, Graphic Designer, 3D Artist'),
('Tài chính - Kế toán', 'tai-chinh-ke-toan', 'dollar-sign', 'Kế toán viên, Kiểm toán, Chuyên viên phân tích tài chính'),
('Kinh doanh & Bán hàng', 'kinh-doanh-ban-hang', 'shopping-bag', 'Sales B2B, Account Executive, Business Development'),
('Nhân sự & Tuyển dụng', 'nhan-su-tuyen-dung', 'users', 'HR Generalist, Talent Acquisition, C&B'),
('Ngân hàng & Dữ liệu', 'ngan-hang-du-lieu', 'database', 'Data Analyst, Data Scientist, Tín dụng ngân hàng'),
('Dịch vụ khách hàng', 'dich-vu-khach-hang', 'headphones', 'Customer Support, Chăm sóc khách hàng VIP'),
('Giáo dục & Đào tạo', 'giao-duc-dao-tao', 'book-open', 'Giảng viên công nghệ, Trợ giảng, Giáo viên ngoại ngữ'),
('Khác', 'khac', 'grid', 'Các ngành nghề khác');

INSERT IGNORE INTO `locations` (`name`, `slug`, `type`) VALUES
('Hà Nội', 'ha-noi', 'city'),
('TP. Hồ Chí Minh', 'tp-ho-chi-minh', 'city'),
('Đà Nẵng', 'da-nang', 'city'),
('Hải Phòng', 'hai-phong', 'city'),
('Cần Thơ', 'can-tho', 'city'),
('Làm từ xa (Remote)', 'remote', 'type'),
('Toàn quốc', 'toan-quoc', 'type');

-- Đảm bảo tất cả tin mẫu hiện có đều ở trạng thái 'active' nếu trước đó là active
UPDATE `jobs` SET `status` = 'active' WHERE `status` = 'active';

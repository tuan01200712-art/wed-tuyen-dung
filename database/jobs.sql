-- ============================================================
-- DATABASE: job_portal
-- DỰ ÁN: Cổng Tuyển Dụng JobFinder (Chuẩn Thiết Kế Apple & Web 2026)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `job_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `job_portal`;

-- Xóa bảng cũ theo thứ tự khóa ngoại
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `cvs`;
DROP TABLE IF EXISTS `employer_profiles`;
DROP TABLE IF EXISTS `jobs`;
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
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Bảng employer_profiles (Hồ sơ công ty của Nhà tuyển dụng)
-- ------------------------------------------------------------
CREATE TABLE `employer_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `company_name` VARCHAR(150) NOT NULL,
  `company_logo` VARCHAR(255) NULL,
  `website` VARCHAR(255) NULL,
  `industry` VARCHAR(100) NULL,
  `size` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `address` VARCHAR(255) NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Bảng cvs (Hồ sơ trực tuyến do ứng viên tự tạo)
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
  CONSTRAINT `fk_cv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Bảng jobs (Danh sách tin tuyển dụng)
-- ------------------------------------------------------------
CREATE TABLE `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employer_id` INT NULL,
  `title` VARCHAR(150) NOT NULL,
  `company` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `salary` VARCHAR(100) NOT NULL,
  `salary_value` INT DEFAULT 0,
  `job_type` VARCHAR(50) NOT NULL DEFAULT 'Toàn thời gian',
  `experience` VARCHAR(50) NOT NULL DEFAULT 'Không yêu cầu',
  `description` TEXT NOT NULL,
  `requirements` TEXT NOT NULL,
  `deadline` DATE NULL,
  `views` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Bảng applications (Ứng tuyển hồ sơ)
-- ------------------------------------------------------------
CREATE TABLE `applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `cv_id` INT NULL,
  `cover_letter` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Đã nộp hồ sơ',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_cv` FOREIGN KEY (`cv_id`) REFERENCES `cvs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uq_user_job` UNIQUE (`user_id`, `job_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- DỮ LIỆU KHỞI TẠO (Mật khẩu mẫu chung: 123456)
-- Hash bcrypt của '123456': $2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6
-- ============================================================

-- Tài khoản mẫu: 1 Admin, 2 Nhà tuyển dụng, 2 Ứng viên
INSERT INTO `users` (`id`, `name`, `email`, `role`, `password`, `phone`, `is_active`, `created_at`) VALUES
(1, 'Hệ Thống Quản Trị', 'admin@jobfinder.vn', 'admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6', '0901234567', 1, NOW()),
(2, 'FPT Software HR', 'tuyendung@fpt.com', 'employer', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6', '02473007575', 1, NOW()),
(3, 'VNG Corporation HR', 'careers@vng.com.vn', 'employer', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6', '02839623888', 1, NOW()),
(4, 'Nguyễn Văn An', 'an.nguyen@gmail.com', 'candidate', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6', '0912345678', 1, NOW()),
(5, 'Trần Thị Mai', 'mai.tran@gmail.com', 'candidate', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6', '0987654321', 1, NOW());

-- Hồ sơ công ty mẫu
INSERT INTO `employer_profiles` (`id`, `user_id`, `company_name`, `website`, `industry`, `size`, `description`, `address`, `is_verified`) VALUES
(1, 2, 'FPT Software', 'https://fptsoftware.com', 'Công nghệ thông tin', '500+ nhân viên', 'Tập đoàn công nghệ và dịch vụ phần mềm hàng đầu khu vực Châu Á.', 'Tòa nhà FPT, Phố Duy Tân, Cầu Giấy, Hà Nội', 1),
(2, 3, 'VNG Corporation', 'https://vng.com.vn', 'Công nghệ & Game', '500+ nhân viên', 'Kỳ lân công nghệ đầu tiên tại Việt Nam, kiến tạo các sản phẩm số dẫn đầu thị trường.', 'Z06 Đường số 13, Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh', 1);

-- CV mẫu của ứng viên
INSERT INTO `cvs` (`id`, `user_id`, `title`, `full_name`, `email`, `phone`, `address`, `summary`, `education`, `experience`, `skills`, `certifications`, `languages`, `cv_link`, `is_default`) VALUES
(1, 4, 'Fullstack Web Developer', 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345678', 'Cầu Giấy, Hà Nội', 
 'Lập trình viên đam mê kiến tạo trải nghiệm web hiện đại, hiệu năng cao và chuẩn mực UI/UX. Có 2 năm kinh nghiệm thực chiến với PHP, MySQL, JavaScript và các công nghệ web nền tảng.',
 'Đại học Bách Khoa Hà Nội\nChuyên ngành Công nghệ Thông tin (2020 - 2024)\nĐiểm GPA: 3.52/4.0',
 'Lập trình viên Frontend/Backend - Startup Lab (2023 - 2024)\n• Xây dựng và tối ưu hóa hệ thống cổng tuyển dụng và bảng điều khiển nội bộ.\n• Phối hợp với đội ngũ thiết kế hoàn thiện giao diện người dùng theo chuẩn Apple Design.',
 'PHP, MySQL, JavaScript (ES6+), CSS Grid/Flexbox, RESTful API, Git, Docker cơ bản',
 'Chứng chỉ AWS Certified Cloud Practitioner\nToeic 850', 'Tiếng Việt (Bản ngữ), Tiếng Anh (Thành thạo)', 'https://github.com/annguyen-dev', 1),

(2, 5, 'UI/UX Product Designer', 'Trần Thị Mai', 'mai.tran@gmail.com', '0987654321', 'Quận 1, TP. Hồ Chí Minh',
 'Nhà thiết kế sản phẩm tập trung vào hệ thống thiết kế tinh giản, dễ sử dụng và lấy trải nghiệm người dùng làm trọng tâm.',
 'Đại học Kiến Trúc TP. Hồ Chí Minh\nChuyên ngành Thiết kế Đồ họa (2020 - 2024)',
 'Junior UI/UX Designer - Tech Agency (2023 - Nay)\n• Thiết kế giao diện ứng dụng web & di động cho 5+ đối tác công nghệ.\n• Xây dựng hệ thống UI kit và hướng dẫn thương hiệu đồng nhất.',
 'Figma, Design Systems, Wireframing, Prototyping, HTML/CSS cơ bản, User Research',
 'Google UX Design Certificate', 'Tiếng Việt, Tiếng Anh', 'https://behance.net/maitran-design', 1);

-- 12 Công việc tuyển dụng mẫu
INSERT INTO `jobs` (`id`, `employer_id`, `title`, `company`, `location`, `category`, `salary`, `salary_value`, `job_type`, `experience`, `description`, `requirements`, `deadline`, `views`, `status`) VALUES
(1, 2, 'Senior PHP / Backend Engineer', 'FPT Software', 'Hà Nội', 'Công nghệ thông tin', '20 - 35 triệu', 27500000, 'Toàn thời gian', '2 - 3 năm', 
 'Tham gia phát triển hệ thống nền tảng dịch vụ quy mô lớn cho các đối tác quốc tế. Tối ưu hóa truy vấn cơ sở dữ liệu MySQL, xây dựng RESTful APIs ổn định và bảo mật cao.',
 '• Thành thạo PHP và MySQL (tối ưu index, query phức tạp)\n• Hiểu sâu về kiến trúc MVC, lập trình hướng đối tượng (OOP) và Clean Code\n• Có tinh thần trách nhiệm và tư duy giải quyết vấn đề độc lập', '2026-12-31', 342, 'active'),

(2, 2, 'Frontend Developer (JavaScript / Vue / React)', 'FPT Software', 'Hà Nội', 'Công nghệ thông tin', '15 - 25 triệu', 20000000, 'Toàn thời gian', '1 - 2 năm',
 'Xây dựng giao diện ứng dụng web hiện đại, mượt mà, tối ưu hóa trải nghiệm người dùng trên đa nền tảng thiết bị.',
 '• Thành thạo HTML5, CSS3 hiện đại, JavaScript ES6+\n• Có kinh nghiệm tối ưu hóa hiệu năng render và tương tác giao diện\n• Đam mê chuẩn thẩm mỹ cao cấp và chú trọng từng chi tiết vi tương tác', '2026-12-30', 289, 'active'),

(3, 3, 'Product Designer (UI/UX)', 'VNG Corporation', 'Hồ Chí Minh', 'Thiết kế đồ họa', '18 - 30 triệu', 24000000, 'Toàn thời gian', '2 - 3 năm',
 'Tham gia định hình ngôn ngữ thiết kế cho các sản phẩm số hàng chục triệu người dùng của VNG. Xây dựng prototype và kiểm thử trải nghiệm người dùng thực tế.',
 '• Thành thạo Figma và quy trình xây dựng Design System\n• Khả năng tư duy sản phẩm nhạy bén, loại bỏ sự phức tạp không cần thiết\n• Portfolio chứng minh năng lực thiết kế giao diện tinh tế', '2026-12-25', 412, 'active'),

(4, 3, 'Data Analyst / BI Specialist', 'VNG Corporation', 'Hồ Chí Minh', 'Ngân hàng - Dữ liệu', '18 - 28 triệu', 23000000, 'Toàn thời gian', '1 - 2 năm',
 'Thu thập, làm sạch và phân tích các chỉ số hành vi người dùng, trực quan hóa dữ liệu phục vụ ra quyết định kinh doanh.',
 '• Thành thạo SQL truy vấn dữ liệu lớn\n• Có kinh nghiệm làm việc với Tableau, Power BI hoặc Python\n• Tư duy logic và khả năng diễn giải dữ liệu thành hành động cụ thể', '2026-12-28', 195, 'active'),

(5, 2, 'DevOps / Cloud Infrastructure Engineer', 'FPT Software', 'Đà Nẵng', 'Công nghệ thông tin', '25 - 40 triệu', 32500000, 'Toàn thời gian', '3 - 5 năm',
 'Quản lý, vận hành và tự động hóa hạ tầng đám mây (AWS/GCP), thiết lập đường ống CI/CD ổn định, bảo mật.',
 '• Kinh nghiệm làm việc với Docker, Kubernetes, Terraform\n• Hiểu biết vững chắc về mạng, bảo mật Linux và giám sát hệ thống\n• Ưu tiên có chứng chỉ AWS Solution Architect', '2026-12-20', 160, 'active'),

(6, 3, 'Content & Growth Marketing Lead', 'VNG Corporation', 'Hồ Chí Minh', 'Marketing', '16 - 24 triệu', 20000000, 'Toàn thời gian', '1 - 2 năm',
 'Xây dựng chiến lược nội dung hướng đến giá trị thực, quản lý các kênh truyền thông số và tối ưu hóa chuyển đổi khách hàng tiềm năng.',
 '• Tư duy viết sắc sảo, ngắn gọn, trực diện, không sáo rỗng\n• Am hiểu các công cụ phân tích đo lường chỉ số tiếp thị số\n• Năng động, nhạy bén với xu hướng công nghệ', '2026-12-15', 220, 'active'),

(7, NULL, 'Kế Toán Tổng Hợp Doanh Nghiệp', 'VinFast Trading', 'Hà Nội', 'Tài chính - Kế toán', '12 - 18 triệu', 15000000, 'Toàn thời gian', '1 - 2 năm',
 'Kiểm soát chứng từ, lập báo cáo tài chính định kỳ và đối soát các luồng công nợ nội bộ theo quy định chuẩn.',
 '• Tốt nghiệp Đại học chuyên ngành Kế toán - Kiểm toán\n• Sử dụng thành thạo phần mềm kế toán MISA/SAP và Excel nâng cao\n• Trung thực, cẩn trọng và có tính kỷ luật cao', '2026-12-22', 140, 'active'),

(8, NULL, 'Chuyên Viên Tuyển Dụng & Phát Triển Nhân Tài', 'Viettel Post', 'Hà Nội', 'Nhân sự', '12 - 17 triệu', 14500000, 'Toàn thời gian', 'Dưới 1 năm',
 'Tìm kiếm, kết nối và phỏng vấn các ứng viên tiềm năng cho các bộ phận chuyên môn trong toàn hệ thống.',
 '• Giao tiếp tự tin, có khả năng lắng nghe và đánh giá ứng viên khách quan\n• Khả năng sắp xếp công việc khoa học\n• Sẵn sàng học hỏi và phát triển chuyên môn nhân sự', '2026-12-18', 178, 'active'),

(9, NULL, 'Chuyên Viên Kinh Doanh B2B Doanh Nghiệp', 'Tiki Corporation', 'Hồ Chí Minh', 'Kinh doanh - Bán hàng', '15 - 30 triệu', 22500000, 'Toàn thời gian', 'Không yêu cầu',
 'Tiếp cận các khách hàng doanh nghiệp, tư vấn giải pháp cung ứng và chăm sóc hệ thống đối tác chiến lược.',
 '• Đam mê kinh doanh, năng nổ và kiên trì\n• Kỹ năng đàm phán và thuyết phục hiệu quả\n• Tự chủ và hướng tới kết quả doanh số thực chất', '2026-12-31', 210, 'active'),

(10, NULL, 'Giảng Viên Lập Trình Web (Part-time / Full-time)', 'MindX Technology School', 'Hà Nội', 'Giáo dục - Đào tạo', '15 - 22 triệu', 18500000, 'Bán thời gian', '1 - 2 năm',
 'Giảng dạy kiến thức lập trình web nền tảng (HTML, CSS, JS, PHP) cho học sinh, sinh viên và người chuyển nghề.',
 '• Nắm chắc kiến thức công nghệ web căn bản\n• Kỹ năng truyền đạt dễ hiểu, kiên nhẫn và truyền cảm hứng\n• Ưu tiên người có kinh nghiệm làm dự án thực tế', '2026-12-25', 132, 'active'),

(11, NULL, 'Nhân Viên Hỗ Trợ Kỹ Thuật Khách Hàng (Customer Support)', 'MoMo Payment', 'Hồ Chí Minh', 'Dịch vụ khách hàng', '10 - 14 triệu', 12000000, 'Toàn thời gian', 'Không yêu cầu',
 'Tiếp nhận các phản hồi kỹ thuật từ khách hàng qua hệ thống Helpdesk, phối hợp đội ngũ kỹ thuật xử lý sự cố nhanh chóng.',
 '• Giọng nói chuẩn, thái độ phục vụ chuyên nghiệp, tận tâm\n• Khả năng chịu áp lực và giải quyết thắc mắc mạch lạc\n• Có hiểu biết căn bản về ứng dụng số', '2026-12-19', 115, 'active'),

(12, 2, 'Kỹ Sư An Toàn Thông Tin (Cybersecurity)', 'FPT Software', 'Hà Nội', 'Công nghệ thông tin', '22 - 38 triệu', 30000000, 'Toàn thời gian', '2 - 3 năm',
 'Đánh giá lỗ hổng bảo mật hệ thống web application, kiểm thử xâm nhập (Penetration Testing) và rà soát tuân thủ an toàn thông tin.',
 '• Hiểu biết sâu về OWASP Top 10, phân tích mã nguồn bảo mật\n• Kinh nghiệm sử dụng Burp Suite, Nmap, Wireshark\n• Có các chứng chỉ CEH, OSCP là lợi thế lớn', '2026-12-31', 190, 'active');

-- Dữ liệu ứng tuyển mẫu
INSERT INTO `applications` (`id`, `user_id`, `job_id`, `cv_id`, `cover_letter`, `status`, `created_at`) VALUES
(1, 4, 1, 1, 'Kính gửi Bộ phận Tuyển dụng FPT Software, tôi gửi hồ sơ ứng tuyển vị trí Senior PHP / Backend Engineer với mong muốn đóng góp năng lực lập trình và tối ưu hóa hệ thống cho các dự án quy mô lớn của quý công ty.', 'Đang xét duyệt', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 4, 2, 1, 'Tôi rất quan tâm đến vị trí Frontend Developer tại FPT Software. Với kinh nghiệm xây dựng giao diện hiện đại, tôi tự tin sẽ hoàn thành xuất sắc các mục tiêu sản phẩm.', 'Đã nộp hồ sơ', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 5, 3, 2, 'Kính gửi VNG Corporation, tôi xin ứng tuyển vị trí Product Designer. Tôi chia sẻ cùng tầm nhìn về các sản phẩm số tinh giản, lấy người dùng làm trọng tâm.', 'Phù hợp', DATE_SUB(NOW(), INTERVAL 3 DAY));

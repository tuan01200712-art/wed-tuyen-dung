-- ============================================================
-- DATABASE: job_portal
-- DỰ ÁN: Website Tuyển Dụng Việc Làm - JobFinder
-- ============================================================
-- Tạo cơ sở dữ liệu nếu chưa tồn tại
CREATE DATABASE IF NOT EXISTS `job_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `job_portal`;
-- ------------------------------------------------------------
-- 1. Bảng users (Người dùng / Ứng viên)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- ------------------------------------------------------------
-- 2. Bảng jobs (Danh sách công việc tuyển dụng)
-- ------------------------------------------------------------
CREATE TABLE `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
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
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- ------------------------------------------------------------
-- 3. Bảng applications (Ứng tuyển)
-- Mỗi người dùng chỉ ứng tuyển một lần cho cùng một công việc (UNIQUE user_id + job_id)
-- ------------------------------------------------------------
CREATE TABLE `applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `cover_letter` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Đã nộp hồ sơ',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `uq_user_job` UNIQUE (`user_id`, `job_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- ------------------------------------------------------------
-- DỮ LIỆU MẪU: Bảng users
-- Mật khẩu mẫu: 123456 (đã mã hóa bằng password_hash BCRYPT)
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`)
VALUES (
    1,
    'Nguyễn Văn An',
    'an.nguyen@gmail.com',
    '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6',
    NOW()
  ),
  (
    2,
    'Trần Thị Mai',
    'mai.tran@gmail.com',
    '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeT94eJ0TOnp3f5.c2W/0Q1e2m3n4o5p6',
    NOW()
  );
-- ------------------------------------------------------------
-- DỮ LIỆU MẪU: Bảng jobs (12 công việc đa dạng ngành nghề)
-- ------------------------------------------------------------
INSERT INTO `jobs` (
    `id`,
    `title`,
    `company`,
    `location`,
    `category`,
    `salary`,
    `salary_value`,
    `job_type`,
    `experience`,
    `description`,
    `requirements`,
    `status`,
    `created_at`
  )
VALUES (
    1,
    'Lập Trình Viên PHP / Laravel',
    'FPT Software',
    'Hà Nội',
    'Công nghệ thông tin',
    '15 - 25 triệu',
    20000000,
    'Toàn thời gian',
    '1 - 2 năm',
    '- Tham gia phát triển và duy trì các hệ thống web enterprise quy mô lớn.\n- Thiết kế kiến trúc CSDL MySQL và tối ưu hóa câu truy vấn SQL.\n- Phối hợp với team Frontend và Product Owner để hoàn thiện tính năng.\n- Viết unit test và tài liệu kỹ thuật dự án.',
    '- Tốt nghiệp ĐH/CĐ chuyên ngành CNTT hoặc liên quan.\n- Thành thạo PHP thuần, OOP, MVC, có kiến thức về Laravel framework.\n- Nắm vững MySQL, Git, RESTful API.\n- Khả năng đọc hiểu tài liệu tiếng Anh tốt.',
    'active',
    NOW()
  ),
  (
    2,
    'Frontend Developer (ReactJS / VueJS)',
    'VNG Corporation',
    'Hồ Chí Minh',
    'Công nghệ thông tin',
    '18 - 30 triệu',
    25000000,
    'Toàn thời gian',
    '2 - 3 năm',
    '- Xây dựng giao diện web ứng dụng hiện đại, mượt mà và tối ưu UX/UI.\n- Tích hợp API từ backend và quản lý state ứng dụng.\n- Đảm bảo tính tương thích đa nền tảng và tối ưu tốc độ tải trang.',
    '- Thành thạo HTML5, CSS3/SCSS, JavaScript ES6+.\n- Có kinh nghiệm thực chiến với ReactJS hoặc VueJS tối thiểu 1.5 năm.\n- Tư duy responsive design và clean code tốt.',
    'active',
    NOW()
  ),
  (
    3,
    'Chuyên Viên Digital Marketing',
    'Shopee Việt Nam',
    'Hồ Chí Minh',
    'Marketing',
    '12 - 20 triệu',
    16000000,
    'Toàn thời gian',
    '1 - 2 năm',
    '- Lập kế hoạch và thực thi các chiến dịch quảng cáo Facebook Ads, Google Ads, TikTok Ads.\n- Tối ưu chi phí CPA, CPL và tăng trưởng tỷ lệ chuyển đổi.\n- Phối hợp với đội ngũ nội dung và thiết kế để chuẩn bị tài nguyên truyền thông.',
    '- Có kinh nghiệm chạy quảng cáo số ngân sách từ 50tr/tháng trở lên.\n- Sử dụng thành thạo Google Analytics, Google Tag Manager.\n- Sáng tạo, nhạy bén với xu hướng thị trường.',
    'active',
    NOW()
  ),
  (
    4,
    'UI/UX Designer',
    'Tiki Tech Hub',
    'Hồ Chí Minh',
    'Thiết kế đồ họa',
    '15 - 22 triệu',
    18500000,
    'Toàn thời gian',
    '1 - 3 năm',
    '- Nghiên cứu hành vi người dùng, xây dựng User Flow, Wireframe và Prototype.\n- Thiết kế giao diện website và mobile app theo Design System của công ty.\n- Phối hợp chặt chẽ với team Developer trong quá trình hiện thực hóa thiết kế.',
    '- Thành thạo Figma, Adobe XD, Photoshop, Illustrator.\n- Có portfolio thể hiện các dự án web/app đã từng thực hiện.\n- Khả năng giao tiếp và bảo vệ quan điểm thiết kế tốt.',
    'active',
    NOW()
  ),
  (
    5,
    'Nhân Viên Kế Toán Tổng Hợp',
    'Tập Đoàn VinGroup',
    'Hà Nội',
    'Tài chính - Kế toán',
    '10 - 15 triệu',
    12500000,
    'Toàn thời gian',
    '2 năm',
    '- Hạch toán các nghiệp vụ kinh tế phát sinh vào phần mềm kế toán (MISA/Fast).\n- Lập báo cáo thuế định kỳ (VAT, TNCN, TNDN) và báo cáo tài chính năm.\n- Kiểm tra, đối chiếu số liệu công nợ khách hàng và nhà cung cấp.',
    '- Tốt nghiệp Đại học chuyên ngành Kế toán - Kiểm toán, Tài chính.\n- Nắm vững chế độ kế toán và quy định pháp luật về thuế hiện hành.\n- Cẩn thận, trung thực, chịu được áp lực cao.',
    'active',
    NOW()
  ),
  (
    6,
    'Chuyên Viên Tuyển Dụng & Đào Tạo',
    'Sun Asterisk',
    'Đà Nẵng',
    'Nhân sự',
    '10 - 16 triệu',
    13000000,
    'Toàn thời gian',
    '1 - 2 năm',
    '- Tiếp nhận nhu cầu tuyển dụng từ các phòng ban, đăng tin và tìm kiếm ứng viên.\n- Sàng lọc CV, liên hệ và trực tiếp phỏng vấn vòng 1.\n- Tổ chức các buổi hội nhập nhân viên mới và hỗ trợ hoạt động văn hóa nội bộ.',
    '- Có kinh nghiệm tuyển dụng IT hoặc headhunter là lợi thế lớn.\n- Kỹ năng giao tiếp, đàm phán và thuyết phục ứng viên xuất sắc.\n- Năng động, cầu tiến và có trách nhiệm cao trong công việc.',
    'active',
    NOW()
  ),
  (
    7,
    'Nhân Viên Kinh Doanh B2B',
    'MobiFone Plus',
    'Đà Nẵng',
    'Kinh doanh - Bán hàng',
    '8 - 18 triệu + Hoa hồng',
    13000000,
    'Toàn thời gian',
    'Không yêu cầu',
    '- Tìm kiếm và tiếp cận các khách hàng doanh nghiệp có nhu cầu sử dụng dịch vụ số.\n- Tư vấn giải pháp, đàm phán hợp đồng và chăm sóc khách hàng sau bán.\n- Đạt chỉ tiêu doanh số cá nhân và phối hợp cùng đội nhóm.',
    '- Đam mê kinh doanh, không ngại thử thách.\n- Kỹ năng giao tiếp và xử lý tình huống linh hoạt.\n- Chấp nhận sinh viên mới tốt nghiệp năng động, nhiệt tình.',
    'active',
    NOW()
  ),
  (
    8,
    'Data Analyst / Phân Tích Dữ Liệu',
    'Techcombank',
    'Hà Nội',
    'Ngân hàng - Dữ liệu',
    '18 - 28 triệu',
    23000000,
    'Toàn thời gian',
    '2 năm',
    '- Khai phá và phân tích tập dữ liệu khách hàng lớn để đưa ra khuyến nghị kinh doanh.\n- Xây dựng dashboard trực quan hóa dữ liệu bằng Power BI hoặc Tableau.\n- Viết các câu lệnh SQL nâng cao để trích xuất dữ liệu từ Data Warehouse.',
    '- Thành thạo SQL, Excel nâng cao, Power BI / Tableau.\n- Có hiểu biết về Python/R cho phân tích dữ liệu là điểm cộng.\n- Tư duy logic, phân tích vấn đề và trình bày mạch lạc.',
    'active',
    NOW()
  ),
  (
    9,
    'Kỹ Sư Kiểm Thử Phần Mềm (QC/Tester)',
    'KMS Technology',
    'Hồ Chí Minh',
    'Công nghệ thông tin',
    '12 - 18 triệu',
    15000000,
    'Toàn thời gian',
    '1 năm',
    '- Phân tích tài liệu yêu cầu (BRD, SRS) và viết Test Plan, Test Cases.\n- Thực hiện Manual Testing (Functional, Regression, Integration, API testing với Postman).\n- Ghi nhận và theo dõi lỗi trên hệ thống Jira cho đến khi hoàn tất sửa chữa.',
    '- Nắm chắc quy trình kiểm thử phần mềm (STLC, SDLC).\n- Biết sử dụng Postman kiểm thử RESTful API.\n- Tỉ mỉ, cẩn thận và có kỹ năng mô tả lỗi rõ ràng.',
    'active',
    NOW()
  ),
  (
    10,
    'Biên Tập Viên Nội Dung (Content Creator)',
    'Dentsu Redder',
    'Hồ Chí Minh',
    'Marketing',
    '9 - 14 triệu',
    11500000,
    'Toàn thời gian',
    '1 năm',
    '- Viết bài PR, bài chuẩn SEO website, kịch bản video ngắn cho TikTok/Reels.\n- Quản trị nội dung Fanpage, xây dựng ý tưởng thông điệp truyền thông sáng tạo.\n- Đo lường và đánh giá hiệu quả tương tác của các bài viết.',
    '- Khả năng hành văn lưu loát, giàu cảm xúc và linh hoạt phong cách.\n- Có kiến thức cơ bản về SEO và các nền tảng mạng xã hội.\n- Năng động, cập nhật xu hướng nhanh nhạy.',
    'active',
    NOW()
  ),
  (
    11,
    'Giáo Viên Tiếng Anh (IELTS / Giao Tiếp)',
    'VUS Miền Trung',
    'Đà Nẵng',
    'Giáo dục - Đào tạo',
    '12 - 22 triệu',
    17000000,
    'Bán thời gian',
    '1 năm',
    '- Giảng dạy các khóa tiếng Anh giao tiếp hoặc luyện thi chứng chỉ theo giáo trình chuẩn.\n- Theo dõi tiến độ học tập và tạo động lực học tập cho học viên.\n- Tham gia các buổi họp chuyên môn nâng cao chất lượng giảng dạy.',
    '- Tốt nghiệp Sư phạm Anh/Ngôn ngữ Anh hoặc chứng chỉ IELTS 7.0+ / TESOL / CELTA.\n- Phát âm chuẩn, phong thái tự tin, truyền cảm hứng.\n- Tâm huyết và yêu nghề dạy học.',
    'active',
    NOW()
  ),
  (
    12,
    'Chuyên Viên Chăm Sóc Khách Hàng',
    'Viettel Post',
    'Cần Thơ',
    'Dịch vụ khách hàng',
    '8 - 12 triệu',
    10000000,
    'Toàn thời gian',
    'Không yêu cầu',
    '- Tiếp nhận cuộc gọi và tin nhắn phản hồi từ khách hàng về đơn hàng và dịch vụ.\n- Phối hợp với các bưu cục để xử lý khiếu nại, sự cố giao nhận nhanh chóng.\n- Cập nhật thông tin và khảo sát mức độ hài lòng của khách hàng.',
    '- Giọng nói chuẩn, rõ ràng, không nói ngọng/nói lắp.\n- Kỹ năng lắng nghe và kiên nhẫn giải quyết vấn đề.\n- Ưu tiên ứng viên có kinh nghiệm trực tổng đài hoặc CSKH.',
    'active',
    NOW()
  );
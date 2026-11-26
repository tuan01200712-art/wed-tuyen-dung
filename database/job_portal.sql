-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th10 26, 2026 lúc 04:56 PM
-- Phiên bản máy phục vụ: 10.4.28-MariaDB
-- Phiên bản PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `job_portal`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 23, 'EMPLOYER_JOB_CREATE', 'Đăng tin tuyển dụng mới ID #31: Dev Web (Trạng thái: active)', '::1', '2026-11-26 12:00:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `cv_id` int(11) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `rating` tinyint(4) NOT NULL DEFAULT 0,
  `stage_updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `job_id`, `cv_id`, `cover_letter`, `status`, `rating`, `stage_updated_at`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 1, 'Kính gửi Bộ phận Tuyển dụng FPT Software, tôi có 2 năm kinh nghiệm PHP/Laravel và MySQL. Tôi mong muốn được tham gia phỏng vấn cho vị trí Lập trình viên Backend PHP / Laravel.', 'interview_scheduled', 4, '2026-11-26 11:53:43', '2026-11-23 11:53:43', NULL),
(2, 4, 2, 1, 'Tôi quan tâm đến vị trí Lập trình viên Frontend ReactJS tại VNG Corporation.', 'viewed', 4, '2026-11-26 11:53:43', '2026-11-25 11:53:43', NULL),
(3, 5, 3, 2, 'Kính gửi đội ngũ MoMo, tôi gửi kèm CV ứng tuyển vị trí Chuyên viên Thiết kế Giao diện UI/UX.', 'suitable', 5, '2026-11-26 11:53:43', '2026-11-24 11:53:43', NULL),
(4, 5, 27, 2, 'Em là sinh viên mới tốt nghiệp mong muốn ứng tuyển Thực tập sinh Lập trình Web tại FPT Software Đà Nẵng.', 'new', 4, '2026-11-26 11:53:43', '2026-11-25 23:53:43', NULL),
(5, 4, 29, 1, 'Tôi có kinh nghiệm viết kịch bản kiểm thử API và web application, xin ứng tuyển vị trí Kỹ sư Kiểm thử Phần mềm.', 'viewed', 4, '2026-11-26 11:53:43', '2026-11-26 05:53:43', NULL),
(6, 5, 30, 2, 'Tôi gửi hồ sơ ứng tuyển vị trí Kỹ sư Hạ tầng Đám mây tại FPT Software TP.HCM.', 'new', 3, '2026-11-26 11:53:43', '2026-11-26 11:53:43', NULL),
(7, 22, 1, 3, 'Nhanh nhẹn', 'new', 0, '2026-11-26 11:57:31', '2026-11-26 11:57:31', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `application_notes`
--

CREATE TABLE `application_notes` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `application_notes`
--

INSERT INTO `application_notes` (`id`, `application_id`, `employer_id`, `note`, `created_at`) VALUES
(1, 1, 2, 'Ứng viên nắm vững Laravel và tối ưu index MySQL. Đã hẹn phỏng vấn vòng 1.', '2026-11-24 11:53:43'),
(2, 3, 6, 'Hồ sơ thiết kế sạch sẽ, có kinh nghiệm làm hệ thống giao diện.', '2026-11-25 11:53:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `application_tags`
--

CREATE TABLE `application_tags` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#16A36A',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `application_tags`
--

INSERT INTO `application_tags` (`id`, `application_id`, `tag_name`, `color`, `created_at`) VALUES
(1, 1, 'Ưu tiên', '#D97706', '2026-11-26 11:53:43'),
(2, 1, 'Đúng chuyên môn', '#059669', '2026-11-26 11:53:43'),
(3, 3, 'Hồ sơ nổi bật', '#2563EB', '2026-11-26 11:53:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `target_hires` int(11) NOT NULL DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','paused','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `campaigns`
--

INSERT INTO `campaigns` (`id`, `employer_id`, `title`, `description`, `target_hires`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Tuyển dụng Kỹ sư Công nghệ 2026', 'Mở rộng đội ngũ phát triển phần mềm tại Hà Nội và Đà Nẵng.', 15, '2026-01-01', '2026-12-31', 'active', '2026-11-26 11:53:42', NULL),
(2, 3, 'VNG Tuyển dụng Sản phẩm & Tăng trưởng 2026', 'Tìm kiếm nhân sự tài năng cho mảng web, nhân sự và truyền thông.', 10, '2026-02-01', '2026-12-31', 'active', '2026-11-26 11:53:42', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `icon` varchar(50) DEFAULT 'briefcase',
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `is_active`, `created_at`) VALUES
(1, 'Công nghệ thông tin', 'cong-nghe-thong-tin', 'code', 'Lập trình viên, Kỹ sư phần mềm, DevOps, Kiểm thử, Dữ liệu', 1, '2026-11-26 11:53:42'),
(2, 'Marketing & Truyền thông', 'marketing-truyen-thong', 'trending-up', 'Marketing số, SEO, Nội dung, Quản trị thương hiệu', 1, '2026-11-26 11:53:42'),
(3, 'Kinh doanh & Bán hàng', 'kinh-doanh-ban-hang', 'shopping-bag', 'Kinh doanh B2B, Phát triển thị trường, Bán lẻ', 1, '2026-11-26 11:53:42'),
(4, 'Kế toán - Tài chính - Ngân hàng', 'ke-toan-tai-chinh-ngan-hang', 'dollar-sign', 'Kế toán tổng hợp, Kiểm toán, Tín dụng, Phân tích tài chính', 1, '2026-11-26 11:53:42'),
(5, 'Thiết kế & Kiến trúc', 'thiet-ke-kien-truc', 'layout', 'Thiết kế UI/UX, Đồ họa, Thiết kế sản phẩm, Kiến trúc', 1, '2026-11-26 11:53:42'),
(6, 'Nhân sự & Hành chính', 'nhan-su-hanh-chinh', 'users', 'Tuyển dụng, Tiền lương phúc lợi, Hành chính văn phòng', 1, '2026-11-26 11:53:42'),
(7, 'Logistics & Xuất nhập khẩu', 'logistics-xuat-nhap-khau', 'truck', 'Điều phối vận tải, Kho vận, Hải quan, Chuỗi cung ứng', 1, '2026-11-26 11:53:42'),
(8, 'Y tế & Dược phẩm', 'y-te-duoc-pham', 'heart', 'Dược sĩ, Trình dược viên, Kiểm nghiệm chất lượng', 1, '2026-11-26 11:53:42'),
(9, 'Giáo dục & Đào tạo', 'giao-duc-dao-tao', 'book-open', 'Giảng viên, Giáo viên ngoại ngữ, Tư vấn tuyển sinh', 1, '2026-11-26 11:53:42'),
(10, 'Nhà hàng - Khách sạn - Du lịch', 'nha-hang-khach-san-du-lich', 'coffee', 'Quản lý cửa hàng, Lễ tân, Điều hành tour, F&B', 1, '2026-11-26 11:53:42'),
(11, 'Kỹ thuật - Cơ khí - Xây dựng', 'ky-thuat-co-khi-xay-dung', 'tool', 'Kỹ sư xây dựng, Giám sát công trình, Cơ điện', 1, '2026-11-26 11:53:42'),
(12, 'Pháp lý & Luật', 'phap-ly-luat', 'shield', 'Chuyên viên pháp chế, Luật sư nội bộ, Tuân thủ', 1, '2026-11-26 11:53:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cvs`
--

CREATE TABLE `cvs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT 'Hồ sơ nghề nghiệp',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `cv_link` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cvs`
--

INSERT INTO `cvs` (`id`, `user_id`, `title`, `full_name`, `email`, `phone`, `address`, `summary`, `education`, `experience`, `skills`, `certifications`, `languages`, `cv_link`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 4, 'Lập trình viên Backend PHP / Laravel', 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345678', 'Cầu Giấy, Hà Nội', 'Lập trình viên Backend với 2 năm kinh nghiệm phát triển ứng dụng web bằng PHP, Laravel và MySQL.', 'Đại học Bách Khoa Hà Nội — Công nghệ Thông tin (2020 - 2024)\nGPA: 3.45/4.0', 'Lập trình viên Backend — Công ty Phần mềm Minh Anh (2024 - Nay)\n• Xây dựng hệ thống quản lý đơn hàng và API kết nối đối tác vận chuyển.', 'PHP / Laravel, MySQL / SQL, JavaScript / ReactJS, Git', 'Chứng chỉ TOEIC 800+', 'Tiếng Anh (TOEIC 750+)', 'https://github.com/annguyen-dev', 1, '2026-11-26 11:53:42', NULL),
(2, 5, 'Chuyên viên Thiết kế UI/UX', 'Trần Thị Mai', 'mai.tran@gmail.com', '0987654321', 'Quận 1, TP.HCM', 'Chuyên viên thiết kế sản phẩm số có 2 năm kinh nghiệm xây dựng giao diện web và ứng dụng di động.', 'Đại học Kiến Trúc TP.HCM — Thiết kế Đồ họa (2019 - 2023)', 'Chuyên viên Thiết kế UI/UX — Studio Số (2023 - Nay)\n• Thiết kế giao diện cho 6 sản phẩm thương mại điện tử.', 'Figma / UI-UX, HTML5 / CSS3', 'Chứng chỉ Thiết kế Google UX', 'Tiếng Anh (Giao tiếp tốt)', 'https://behance.net/maitran-design', 1, '2026-11-26 11:53:42', NULL),
(3, 22, 'Hồ sơ nghề nghiệp', 'Lê Thị Cẩm Linh', 'ltclinh23072007@gmail.com', '0389218044', 'TP.HCM', 'Nhiều kinh nghiệm, tham gia nhiều dự án, cuộc thi.', 'Trường Đại học Văn Lang, Khoá K31, GPA 2.85', 'Leader của các dự án, cuộc thi.', 'Giao tiếp & Đàm phán, Tin học văn phòng (Excel/Word), MySQL / SQL, PHP / Laravel', 'Chứng chỉ AWS Cloud Practitioner', 'Tiếng Anh (IELTS 6.5+)', 'https://github.com/ltclinh23072007-pixel', 1, '2026-11-26 11:57:07', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tax_code` varchar(50) DEFAULT NULL,
  `license_document` varchar(255) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `employer_profiles`
--

INSERT INTO `employer_profiles` (`id`, `user_id`, `company_name`, `company_logo`, `cover_image`, `website`, `industry`, `size`, `description`, `address`, `tax_code`, `license_document`, `contact_person`, `contact_phone`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 2, 'FPT Software', 'assets/images/companies/fpt-software.svg', NULL, 'https://fptsoftware.com', 'Công nghệ thông tin', '10.000+ nhân viên', 'Doanh nghiệp dịch vụ công nghệ thông tin và chuyển đổi số hàng đầu Việt Nam.', 'Tòa nhà FPT, Phố Duy Tân, Cầu Giấy, Hà Nội', '0101234567', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(2, 3, 'VNG Corporation', 'assets/images/companies/vng.svg', NULL, 'https://vng.com.vn', 'Công nghệ thông tin', '3.000+ nhân viên', 'Tập đoàn công nghệ với hệ sinh thái Zalo, ZaloPay, VNG Cloud và Game Studios.', 'Z06 Đường số 13, Tân Thuận Đông, Quận 7, TP.HCM', '0309876543', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(3, 6, 'MoMo', 'assets/images/companies/momo.svg', NULL, 'https://momo.vn', 'Kế toán - Tài chính - Ngân hàng', '2.000+ nhân viên', 'Siêu ứng dụng thanh toán và nền tảng tài chính số phục vụ hơn 30 triệu người dùng.', '08 Hoàng Văn Thái, Tân Phú, Quận 7, TP.HCM', '0305288289', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(4, 7, 'Viettel Digital', 'assets/images/companies/viettel-digital.svg', NULL, 'https://viettel.com.vn', 'Công nghệ thông tin', '1.500+ nhân viên', 'Đơn vị tiên phong kiến tạo hệ sinh thái tài chính số và dữ liệu lớn của Tập đoàn Viettel.', 'Số 1 Trần Hữu Dực, Nam Từ Liêm, Hà Nội', '0100109106', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(5, 8, 'Shopee Việt Nam', 'assets/images/companies/shopee.svg', NULL, 'https://shopee.vn', 'Kinh doanh & Bán hàng', '5.000+ nhân viên', 'Nền tảng thương mại điện tử hàng đầu kết nối hàng triệu người mua và nhà bán hàng.', '67 Lê Lợi, Bến Nghé, Quận 1, TP.HCM', '0313667530', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(6, 9, 'Vinamilk', 'assets/images/companies/vinamilk.svg', NULL, 'https://vinamilk.com.vn', 'Marketing & Truyền thông', '8.000+ nhân viên', 'Thương hiệu sữa quốc gia hàng đầu Việt Nam với hệ thống nhà máy và trang trại chuẩn quốc tế.', '10 Tân Trào, Tân Phú, Quận 7, TP.HCM', '0300588569', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(7, 10, 'Techcombank', 'assets/images/companies/techcombank.svg', NULL, 'https://techcombank.com', 'Kế toán - Tài chính - Ngân hàng', '10.000+ nhân viên', 'Ngân hàng TMCP Kỹ Thương Việt Nam tiên phong chuyển đổi số ngành tài chính.', 'Số 6 Quang Trung, Hoàn Kiếm, Hà Nội', '0100230800', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(8, 11, 'Vietcombank', 'assets/images/companies/vietcombank.svg', NULL, 'https://vietcombank.com.vn', 'Kế toán - Tài chính - Ngân hàng', '20.000+ nhân viên', 'Ngân hàng TMCP Ngoại thương Việt Nam — định chế tài chính uy tín hàng đầu.', '198 Trần Quang Khải, Hoàn Kiếm, Hà Nội', '0100112437', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(9, 12, 'Thế Giới Di Động', 'assets/images/companies/the-gioi-di-dong.svg', NULL, 'https://mwg.vn', 'Kinh doanh & Bán hàng', '60.000+ nhân viên', 'Hệ thống bán lẻ thiết bị di động, điện máy và bách hóa hàng đầu Việt Nam.', 'Lô T2-1.2, Đường D1, Khu Công nghệ cao, TP. Thủ Đức, TP.HCM', '0306731335', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(10, 13, 'Masan Group', 'assets/images/companies/masan.svg', NULL, 'https://masangroup.com', 'Kinh doanh & Bán hàng', '30.000+ nhân viên', 'Tập đoàn hàng tiêu dùng và bán lẻ tích hợp hàng đầu Việt Nam.', '23 Lê Duẩn, Bến Nghé, Quận 1, TP.HCM', '0303576603', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(11, 14, 'Vinpearl', 'assets/images/companies/vinpearl.svg', NULL, 'https://vinpearl.com', 'Nhà hàng - Khách sạn - Du lịch', '10.000+ nhân viên', 'Hệ thống khách sạn, khu nghỉ dưỡng và vui chơi giải trí đẳng cấp 5 sao.', 'Đảo Hòn Tre, Vĩnh Nguyên, Nha Trang / VP TP.HCM', '4200456848', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(12, 15, 'Gemadept Logistics', 'assets/images/companies/gemadept.svg', NULL, 'https://gemadept.com.vn', 'Logistics & Xuất nhập khẩu', '3.500+ nhân viên', 'Doanh nghiệp khai thác cảng và chuỗi cung ứng logistics tích hợp hàng đầu.', '06 Lê Thánh Tôn, Bến Nghé, Quận 1, TP.HCM', '0301116791', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(13, 16, 'Dược Hậu Giang', 'assets/images/companies/duoc-hau-giang.svg', NULL, 'https://dhgpharma.com.vn', 'Y tế & Dược phẩm', '3.000+ nhân viên', 'Doanh nghiệp dược phẩm hàng đầu Việt Nam đạt chuẩn Japan-GMP.', '288 Bis Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ', '1800156801', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(14, 17, 'VUS Anh Văn Hội Việt Mỹ', 'assets/images/companies/vus.svg', NULL, 'https://vus.edu.vn', 'Giáo dục & Đào tạo', '2.500+ nhân viên', 'Hệ thống Anh văn Hội Việt Mỹ với hơn 80 cơ sở đào tạo trên toàn quốc.', '189 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '0302555123', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(15, 18, 'Coteccons', 'assets/images/companies/coteccons.svg', NULL, 'https://coteccons.vn', 'Kỹ thuật - Cơ khí - Xây dựng', '2.500+ nhân viên', 'Tổng thầu xây dựng hàng đầu Việt Nam với hàng loạt công trình biểu tượng.', '236/6 Điện Biên Phủ, Bình Thạnh, TP.HCM', '0303443233', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(16, 19, 'Sun Group', 'assets/images/companies/sun-group.svg', NULL, 'https://sungroup.com.vn', 'Nhà hàng - Khách sạn - Du lịch', '8.000+ nhân viên', 'Tập đoàn phát triển du lịch nghỉ dưỡng, vui chơi giải trí và bất động sản cao cấp.', 'Tòa nhà Sun City, Hải Châu, Đà Nẵng', '0400592108', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(17, 20, 'PNJ', 'assets/images/companies/pnj.svg', NULL, 'https://pnj.com.vn', 'Thiết kế & Kiến trúc', '7.000+ nhân viên', 'Công ty Cổ phần Vàng bạc Đá quý Phú Nhuận — thương hiệu trang sức hàng đầu.', '170E Phan Đăng Lưu, Phú Nhuận, TP.HCM', '0300521758', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(18, 21, 'Highlands Coffee', 'assets/images/companies/highlands-coffee.svg', NULL, 'https://highlandscoffee.com.vn', 'Nhà hàng - Khách sạn - Du lịch', '12.000+ nhân viên', 'Chuỗi thương hiệu cà phê và ẩm thực được yêu thích hàng đầu tại Việt Nam.', '135/37/50 Nguyễn Hữu Cảnh, Bình Thạnh, TP.HCM', '0309965814', NULL, NULL, NULL, 1, '2026-11-26 11:53:42', NULL),
(19, 23, 'XiMi Tech', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-11-26 11:58:40', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `interview_schedules`
--

CREATE TABLE `interview_schedules` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `interview_date` date NOT NULL,
  `interview_time` time NOT NULL,
  `interview_type` enum('online','offline') NOT NULL DEFAULT 'offline',
  `location_or_link` varchar(255) NOT NULL,
  `interviewer` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `interview_schedules`
--

INSERT INTO `interview_schedules` (`id`, `application_id`, `employer_id`, `job_id`, `candidate_id`, `interview_date`, `interview_time`, `interview_type`, `location_or_link`, `interviewer`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 4, '2026-11-28', '14:30:00', 'online', 'https://meet.google.com/fpt-backend-interview', 'Nguyễn Hoàng Long (Trưởng nhóm Kỹ thuật)', 'Phỏng vấn kỹ thuật PHP, Laravel & MySQL', 'confirmed', '2026-11-26 11:53:43', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `company` varchar(100) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `salary` varchar(100) NOT NULL,
  `salary_value` int(11) DEFAULT 0,
  `min_salary` int(11) NOT NULL DEFAULT 0,
  `max_salary` int(11) NOT NULL DEFAULT 0,
  `salary_type` varchar(20) NOT NULL DEFAULT 'deal',
  `job_type` varchar(50) NOT NULL DEFAULT 'Toàn thời gian',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `level` varchar(50) NOT NULL DEFAULT 'Nhân viên',
  `experience` varchar(50) NOT NULL DEFAULT 'Không yêu cầu',
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `benefits` text DEFAULT NULL,
  `education_level` varchar(50) NOT NULL DEFAULT 'Không yêu cầu',
  `working_time` varchar(150) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `reject_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `jobs`
--

INSERT INTO `jobs` (`id`, `employer_id`, `campaign_id`, `title`, `company`, `company_logo`, `location`, `category`, `salary`, `salary_value`, `min_salary`, `max_salary`, `salary_type`, `job_type`, `quantity`, `level`, `experience`, `description`, `requirements`, `benefits`, `education_level`, `working_time`, `contact_name`, `contact_email`, `deadline`, `views`, `is_featured`, `status`, `reject_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Lập trình viên Backend PHP / Laravel', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Cầu Giấy, Hà Nội', 'Công nghệ thông tin', '18 - 28 triệu', 23, 19000000, 28000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Lập trình viên Backend PHP / Laravel tại FPT Software.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 28 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại FPT Software, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-31', 434, 1, 'active', NULL, '2026-11-26 11:53:43', '2026-11-26 11:57:18'),
(2, 3, 2, 'Lập trình viên Frontend ReactJS / TypeScript', 'VNG Corporation', 'assets/images/companies/vng.svg', 'Quận 7, TP.HCM', 'Công nghệ thông tin', '20 - 35 triệu', 28, 24000000, 33000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Lập trình viên Frontend ReactJS / TypeScript tại VNG Corporation.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (20 - 35 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại VNG Corporation, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-30', 518, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(3, 6, NULL, 'Chuyên viên Thiết kế Giao diện UI/UX', 'MoMo', 'assets/images/companies/momo.svg', 'Quận 7, TP.HCM', 'Thiết kế & Kiến trúc', '18 - 26 triệu', 22, 18000000, 27000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Thiết kế Giao diện UI/UX tại MoMo.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Thiết kế & Kiến trúc.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 26 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại MoMo, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-28', 389, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(4, 7, NULL, 'Kỹ sư Dữ liệu & Hệ thống Lớn', 'Viettel Digital', 'assets/images/companies/viettel-digital.svg', 'Nam Từ Liêm, Hà Nội', 'Công nghệ thông tin', '25 - 42 triệu', 33, 29000000, 38000000, 'range', 'Toàn thời gian', 2, 'Chuyên viên / Senior', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Kỹ sư Dữ liệu & Hệ thống Lớn tại Viettel Digital.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.\n- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (25 - 42 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Viettel Digital, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-15', 310, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(5, 8, NULL, 'Chuyên viên Marketing Số (Performance)', 'Shopee Việt Nam', 'assets/images/companies/shopee.svg', 'Quận 1, TP.HCM', 'Marketing & Truyền thông', '15 - 22 triệu', 18, 14000000, 23000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Marketing Số (Performance) tại Shopee Việt Nam.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Marketing & Truyền thông.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (15 - 22 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Shopee Việt Nam, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-25', 295, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(6, 9, NULL, 'Chuyên viên Quản trị Thương hiệu', 'Vinamilk', 'assets/images/companies/vinamilk.svg', 'Quận 7, TP.HCM', 'Marketing & Truyền thông', '20 - 30 triệu', 25, 21000000, 30000000, 'range', 'Toàn thời gian', 1, 'Trưởng nhóm / Leader', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Quản trị Thương hiệu tại Vinamilk.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Marketing & Truyền thông.\n- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (20 - 30 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Vinamilk, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-10', 240, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(7, 10, NULL, 'Chuyên viên Kế toán Tổng hợp', 'Techcombank', 'assets/images/companies/techcombank.svg', 'Hoàn Kiếm, Hà Nội', 'Kế toán - Tài chính - Ngân hàng', '16 - 24 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 1, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Kế toán Tổng hợp tại Techcombank.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kế toán - Tài chính - Ngân hàng.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 24 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Techcombank, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-20', 265, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(8, 11, NULL, 'Chuyên viên Quan hệ Khách hàng Doanh nghiệp', 'Vietcombank', 'assets/images/companies/vietcombank.svg', 'Hoàn Kiếm, Hà Nội', 'Kế toán - Tài chính - Ngân hàng', '18 - 28 triệu', 23, 19000000, 28000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Quan hệ Khách hàng Doanh nghiệp tại Vietcombank.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kế toán - Tài chính - Ngân hàng.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 28 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Vietcombank, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-18', 275, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(9, 12, NULL, 'Quản lý Cửa hàng Bán lẻ Công nghệ', 'Thế Giới Di Động', 'assets/images/companies/the-gioi-di-dong.svg', 'Biên Hòa, Đồng Nai', 'Kinh doanh & Bán hàng', '15 - 25 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 4, 'Quản lý', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Quản lý Cửa hàng Bán lẻ Công nghệ tại Thế Giới Di Động.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kinh doanh & Bán hàng.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (15 - 25 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Thế Giới Di Động, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-29', 210, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(10, 13, NULL, 'Nhân viên Phát triển Thị trường B2B', 'Masan Group', 'assets/images/companies/masan.svg', 'Thủ Dầu Một, Bình Dương', 'Kinh doanh & Bán hàng', '14 - 22 triệu', 18, 14000000, 23000000, 'range', 'Toàn thời gian', 5, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Nhân viên Phát triển Thị trường B2B tại Masan Group.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kinh doanh & Bán hàng.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (14 - 22 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Masan Group, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-05', 198, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(11, 14, NULL, 'Trưởng bộ phận Lễ tân Khách sạn 5 Sao', 'Vinpearl', 'assets/images/companies/vinpearl.svg', 'Hải Châu, Đà Nẵng', 'Nhà hàng - Khách sạn - Du lịch', '16 - 24 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 1, 'Trưởng nhóm / Leader', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Trưởng bộ phận Lễ tân Khách sạn 5 Sao tại Vinpearl.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhà hàng - Khách sạn - Du lịch.\n- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 24 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Vinpearl, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-12', 185, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(12, 15, NULL, 'Chuyên viên Điều phối Logistics & Cảng biển', 'Gemadept Logistics', 'assets/images/companies/gemadept.svg', 'Hải Phòng', 'Logistics & Xuất nhập khẩu', '14 - 20 triệu', 17, 13000000, 22000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Điều phối Logistics & Cảng biển tại Gemadept Logistics.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Logistics & Xuất nhập khẩu.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (14 - 20 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Gemadept Logistics, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-26', 202, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(13, 15, NULL, 'Nhân viên Chứng từ Xuất nhập khẩu', 'Gemadept Logistics', 'assets/images/companies/gemadept.svg', 'Quận 1, TP.HCM', 'Logistics & Xuất nhập khẩu', '12 - 17 triệu', 14, 10000000, 19000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Nhân viên Chứng từ Xuất nhập khẩu tại Gemadept Logistics.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Logistics & Xuất nhập khẩu.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (12 - 17 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Gemadept Logistics, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-08', 175, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(14, 16, NULL, 'Dược sĩ Kiểm nghiệm Chất lượng (QC)', 'Dược Hậu Giang', 'assets/images/companies/duoc-hau-giang.svg', 'Ninh Kiều, Cần Thơ', 'Y tế & Dược phẩm', '12 - 18 triệu', 15, 11000000, 20000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Dược sĩ Kiểm nghiệm Chất lượng (QC) tại Dược Hậu Giang.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Y tế & Dược phẩm.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (12 - 18 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Dược Hậu Giang, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-20', 160, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(15, 16, NULL, 'Trình dược viên Kênh Nhà thuốc (ETC/OTC)', 'Dược Hậu Giang', 'assets/images/companies/duoc-hau-giang.svg', 'Quận 10, TP.HCM', 'Y tế & Dược phẩm', '14 - 24 triệu', 19, 15000000, 24000000, 'range', 'Toàn thời gian', 4, 'Nhân viên', 'Dưới 1 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Trình dược viên Kênh Nhà thuốc (ETC/OTC) tại Dược Hậu Giang.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Y tế & Dược phẩm.\n- Kinh nghiệm yêu cầu: Dưới 1 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (14 - 24 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Dược Hậu Giang, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-15', 188, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(16, 17, NULL, 'Giảng viên Tiếng Anh Giao tiếp & IELTS', 'VUS Anh Văn Hội Việt Mỹ', 'assets/images/companies/vus.svg', 'Quận 1, TP.HCM', 'Giáo dục & Đào tạo', '22 - 38 triệu', 30, 26000000, 35000000, 'range', 'Toàn thời gian', 5, 'Chuyên viên / Senior', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Giảng viên Tiếng Anh Giao tiếp & IELTS tại VUS Anh Văn Hội Việt Mỹ.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Giáo dục & Đào tạo.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (22 - 38 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại VUS Anh Văn Hội Việt Mỹ, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-02-01', 290, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(17, 17, NULL, 'Chuyên viên Tư vấn Tuyển sinh Giáo dục', 'VUS Anh Văn Hội Việt Mỹ', 'assets/images/companies/vus.svg', 'Cầu Giấy, Hà Nội', 'Giáo dục & Đào tạo', '11 - 18 triệu', 14, 10000000, 19000000, 'range', 'Toàn thời gian', 3, 'Mới ra trường', 'Dưới 1 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Tư vấn Tuyển sinh Giáo dục tại VUS Anh Văn Hội Việt Mỹ.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Giáo dục & Đào tạo.\n- Kinh nghiệm yêu cầu: Dưới 1 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (11 - 18 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại VUS Anh Văn Hội Việt Mỹ, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-10', 170, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(18, 18, NULL, 'Kỹ sư Giám sát Thi công Xây dựng', 'Coteccons', 'assets/images/companies/coteccons.svg', 'Bình Thạnh, TP.HCM', 'Kỹ thuật - Cơ khí - Xây dựng', '18 - 28 triệu', 23, 19000000, 28000000, 'range', 'Toàn thời gian', 4, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Kỹ sư Giám sát Thi công Xây dựng tại Coteccons.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kỹ thuật - Cơ khí - Xây dựng.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (18 - 28 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Coteccons, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-25', 220, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(19, 18, NULL, 'Kỹ sư Cơ điện (MEP) Công trình', 'Coteccons', 'assets/images/companies/coteccons.svg', 'Thủ Dầu Một, Bình Dương', 'Kỹ thuật - Cơ khí - Xây dựng', '17 - 26 triệu', 21, 17000000, 26000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Kỹ sư Cơ điện (MEP) Công trình tại Coteccons.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Kỹ thuật - Cơ khí - Xây dựng.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (17 - 26 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Coteccons, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-22', 165, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(20, 19, NULL, 'Chuyên viên Điều hành Tour & Dịch vụ Du lịch', 'Sun Group', 'assets/images/companies/sun-group.svg', 'Hải Châu, Đà Nẵng', 'Nhà hàng - Khách sạn - Du lịch', '13 - 20 triệu', 16, 12000000, 21000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Điều hành Tour & Dịch vụ Du lịch tại Sun Group.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhà hàng - Khách sạn - Du lịch.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (13 - 20 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Sun Group, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-18', 195, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(21, 20, NULL, 'Chuyên viên Thiết kế Trang sức 3D', 'PNJ', 'assets/images/companies/pnj.svg', 'Phú Nhuận, TP.HCM', 'Thiết kế & Kiến trúc', '16 - 25 triệu', 20, 16000000, 25000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Thiết kế Trang sức 3D tại PNJ.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Thiết kế & Kiến trúc.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 25 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại PNJ, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-14', 210, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(22, 21, NULL, 'Quản lý Cửa hàng Cà phê (Store Manager)', 'Highlands Coffee', 'assets/images/companies/highlands-coffee.svg', 'Quận 1, TP.HCM', 'Nhà hàng - Khách sạn - Du lịch', '13 - 19 triệu', 16, 12000000, 21000000, 'range', 'Toàn thời gian', 4, 'Quản lý', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Quản lý Cửa hàng Cà phê (Store Manager) tại Highlands Coffee.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhà hàng - Khách sạn - Du lịch.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (13 - 19 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Highlands Coffee, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-12', 245, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(23, 3, 2, 'Chuyên viên Tuyển dụng Nhân sự Công nghệ', 'VNG Corporation', 'assets/images/companies/vng.svg', 'Quận 7, TP.HCM', 'Nhân sự & Hành chính', '15 - 22 triệu', 18, 14000000, 23000000, 'range', 'Toàn thời gian', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Tuyển dụng Nhân sự Công nghệ tại VNG Corporation.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhân sự & Hành chính.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (15 - 22 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại VNG Corporation, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2026-12-28', 198, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(24, 9, NULL, 'Chuyên viên Tiền lương & Phúc lợi (C&B)', 'Vinamilk', 'assets/images/companies/vinamilk.svg', 'Quận 7, TP.HCM', 'Nhân sự & Hành chính', '16 - 23 triệu', 19, 15000000, 24000000, 'range', 'Toàn thời gian', 1, 'Nhân viên', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Tiền lương & Phúc lợi (C&B) tại Vinamilk.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Nhân sự & Hành chính.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (16 - 23 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Vinamilk, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-09', 172, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(25, 10, NULL, 'Chuyên viên Pháp chế Doanh nghiệp & Hợp đồng', 'Techcombank', 'assets/images/companies/techcombank.svg', 'Hoàn Kiếm, Hà Nội', 'Pháp lý & Luật', '20 - 32 triệu', 26, 22000000, 31000000, 'range', 'Toàn thời gian', 1, 'Chuyên viên / Senior', '2 - 3 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Pháp chế Doanh nghiệp & Hợp đồng tại Techcombank.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Pháp lý & Luật.\n- Kinh nghiệm yêu cầu: 2 - 3 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (20 - 32 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại Techcombank, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-16', 184, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(26, 6, NULL, 'Chuyên viên Pháp lý Tuân thủ Fintech', 'MoMo', 'assets/images/companies/momo.svg', 'Quận 7, TP.HCM', 'Pháp lý & Luật', '22 - 35 triệu', 28, 24000000, 33000000, 'range', 'Toàn thời gian', 1, 'Chuyên viên / Senior', '3 - 5 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Pháp lý Tuân thủ Fintech tại MoMo.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Pháp lý & Luật.\n- Kinh nghiệm yêu cầu: 3 - 5 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (22 - 35 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại MoMo, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-19', 166, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(27, 2, 1, 'Thực tập sinh Lập trình Web (PHP / JavaScript)', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Hải Châu, Đà Nẵng', 'Công nghệ thông tin', '5 - 8 triệu', 6, 2000000, 11000000, 'range', 'Thực tập', 6, 'Thực tập sinh', 'Không yêu cầu', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Thực tập sinh Lập trình Web (PHP / JavaScript) tại FPT Software.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Công nghệ thông tin.\n- Kinh nghiệm yêu cầu: Không yêu cầu, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (5 - 8 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại FPT Software, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-02-15', 495, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(28, 3, 2, 'Chuyên viên Nội dung & SEO (Làm việc từ xa)', 'VNG Corporation', 'assets/images/companies/vng.svg', 'Làm việc từ xa', 'Marketing & Truyền thông', '12 - 18 triệu', 15, 11000000, 20000000, 'range', 'Làm việc từ xa', 2, 'Nhân viên', '1 - 2 năm', '- Thực hiện các nghiệp vụ chuyên môn cho vị trí Chuyên viên Nội dung & SEO (Làm việc từ xa) tại VNG Corporation.\n- Phối hợp cùng các bộ phận liên quan để hoàn thành chỉ tiêu và tiến độ công việc được giao.\n- Báo cáo kết quả công việc định kỳ và đề xuất giải pháp cải tiến quy trình.', '- Tốt nghiệp Cao đẳng/Đại học chuyên ngành phù hợp với lĩnh vực Marketing & Truyền thông.\n- Kinh nghiệm yêu cầu: 1 - 2 năm, có tinh thần trách nhiệm và khả năng làm việc nhóm tốt.\n- Thành thạo các công cụ chuyên môn phục vụ công việc.', '- Thu nhập hấp dẫn (12 - 18 triệu), thưởng tháng 13 và thưởng hiệu suất công việc.\n- Tham gia đầy đủ BHXH, BHYT, BHTN và gói bảo hiểm sức khỏe mở rộng.\n- Môi trường làm việc chuyên nghiệp tại VNG Corporation, có lộ trình đào tạo và thăng tiến rõ ràng.', 'Không yêu cầu', NULL, NULL, NULL, '2027-01-30', 312, 0, 'active', NULL, '2026-11-26 11:53:43', NULL),
(29, 2, 1, 'Kỹ sư Kiểm thử Phần mềm (QA / Tester)', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Cầu Giấy, Hà Nội', 'Công nghệ thông tin', '14 - 22 triệu', 18, 14000000, 22000000, 'range', 'Toàn thời gian', 3, 'Nhân viên', '1 - 2 năm', '- Lập kế hoạch kiểm thử, viết kịch bản kiểm thử và thực hiện kiểm thử chức năng cho ứng dụng web/di động.\n- Ghi nhận lỗi, phối hợp với đội ngũ lập trình viên tái hiện và xác minh bản sửa lỗi.', '- Từ 1 năm kinh nghiệm kiểm thử phần mềm, hiểu rõ quy trình phát triển phần mềm Agile/Scrum.\n- Có kinh nghiệm kiểm thử API hoặc viết kịch bản tự động là lợi thế.', '- Lương tháng 13, thưởng hiệu suất dự án và bảo hiểm sức khỏe FPT Care.\n- Lộ trình phát triển lên Trưởng nhóm Kiểm thử.', 'Không yêu cầu', NULL, NULL, NULL, '2027-02-20', 268, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(30, 2, 1, 'Kỹ sư Hạ tầng Đám mây (DevOps / Cloud)', 'FPT Software', 'assets/images/companies/fpt-software.svg', 'Quận 7, TP.HCM', 'Công nghệ thông tin', '26 - 42 triệu', 34, 26000000, 42000000, 'range', 'Toàn thời gian', 2, 'Chuyên viên / Senior', '3 - 5 năm', '- Thiết kế, triển khai và giám sát hệ thống hạ tầng Cloud (AWS/Azure) và quy trình CI/CD.\n- Tối ưu hóa hiệu năng, bảo mật và tính sẵn sàng cao cho các dịch vụ doanh nghiệp.', '- Từ 3 năm kinh nghiệm Linux, Docker, Kubernetes và công cụ tự động hóa Terraform/Ansible.\n- Giao tiếp tiếng Anh tốt trong môi trường dự án quốc tế.', '- Thu nhập cạnh tranh, hỗ trợ chi phí thi chứng chỉ AWS/Azure quốc tế.\n- Làm việc linh hoạt, chế độ đãi ngộ toàn diện.', 'Không yêu cầu', NULL, NULL, NULL, '2027-02-28', 315, 1, 'active', NULL, '2026-11-26 11:53:43', NULL),
(31, 23, NULL, 'Dev Web', 'XiMi Tech', NULL, 'Đồng Nai', 'Công nghệ thông tin', 'Thỏa thuận', 0, 0, 0, 'deal', 'Toàn thời gian', 1, 'Chuyên viên / Senior', '1 - 2 năm', 'Phát triển, xây dựng các dự án', 'Tốt nghiệp Cử nhân Công nghệ Thông tin', 'Nhiều phúc lợi xã hội', 'Đại học trở lên', 'Thứ 2 - Thứ 6 (8h30 - 17h30)', 'Ngô Tuệ Nghi', 'ntnghi23072007@gmail.com', '2027-01-31', 0, 0, 'active', NULL, '2026-11-26 12:00:34', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `type` varchar(50) DEFAULT 'city',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `locations`
--

INSERT INTO `locations` (`id`, `name`, `slug`, `type`, `is_active`, `created_at`) VALUES
(1, 'TP.HCM', 'tp-hcm', 'city', 1, '2026-11-26 11:53:42'),
(2, 'Hà Nội', 'ha-noi', 'city', 1, '2026-11-26 11:53:42'),
(3, 'Đà Nẵng', 'da-nang', 'city', 1, '2026-11-26 11:53:42'),
(4, 'Bình Dương', 'binh-duong', 'city', 1, '2026-11-26 11:53:42'),
(5, 'Đồng Nai', 'dong-nai', 'city', 1, '2026-11-26 11:53:42'),
(6, 'Cần Thơ', 'can-tho', 'city', 1, '2026-11-26 11:53:42'),
(7, 'Hải Phòng', 'hai-phong', 'city', 1, '2026-11-26 11:53:42'),
(8, 'Làm việc từ xa', 'lam-viec-tu-xa', 'type', 1, '2026-11-26 11:53:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 4, 'Lịch phỏng vấn đã được xác nhận', 'FPT Software đã đặt lịch phỏng vấn vị trí Lập trình viên Backend PHP / Laravel lúc 14:30.', 'my-applications.php', 0, '2026-11-26 11:53:43'),
(2, 2, 'Hồ sơ ứng tuyển mới', 'Ứng viên Nguyễn Văn An vừa ứng tuyển vị trí Lập trình viên Backend PHP / Laravel.', 'employer/applications.php', 0, '2026-11-26 11:53:43'),
(3, 2, 'Hồ sơ ứng tuyển mới', 'Ứng viên Lê Thị Cẩm Linh vừa ứng tuyển vị trí Lập trình viên Backend PHP / Laravel.', 'employer/applications.php', 0, '2026-11-26 11:57:31'),
(4, 1, 'Tin tuyển dụng mới vừa được đăng', 'Doanh nghiệp XiMi Tech vừa đăng tin \'Dev Web\'.', 'admin/jobs.php', 0, '2026-11-26 12:00:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `created_at`) VALUES
(1, 4, 2, '2026-11-26 11:53:43'),
(2, 4, 4, '2026-11-26 11:53:43'),
(3, 5, 3, '2026-11-26 11:53:43'),
(4, 22, 1, '2026-11-26 11:55:00'),
(5, 22, 3, '2026-11-26 11:55:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('candidate','employer','admin') NOT NULL DEFAULT 'candidate',
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `auth_provider` varchar(20) NOT NULL DEFAULT 'local',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role`, `password`, `phone`, `avatar`, `google_id`, `auth_provider`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Quản trị viên Hệ thống', 'admin@jobfinder.vn', 'admin', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '0901234567', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(2, 'FPT Software HR', 'tuyendung@fpt.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02473007575', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(3, 'VNG Corporation HR', 'careers@vng.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02839623888', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(4, 'Nguyễn Văn An', 'an.nguyen@gmail.com', 'candidate', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '0912345678', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(5, 'Trần Thị Mai', 'mai.tran@gmail.com', 'candidate', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '0987654321', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(6, 'MoMo Tuyển Dụng', 'hr@momo.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02873065555', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(7, 'Viettel Digital HR', 'tuyendung@viettel.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02462660198', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(8, 'Shopee Việt Nam HR', 'tuyendung@shopee.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02873089999', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(9, 'Vinamilk Tuyển Dụng', 'hr@vinamilk.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02854155555', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(10, 'Techcombank HR', 'hr@techcombank.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02439446368', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(11, 'Vietcombank HR', 'tuyendung@vietcombank.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02439343137', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(12, 'Thế Giới Di Động HR', 'tuyendung@thegioididong.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02838125960', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(13, 'Masan Group HR', 'hr@masangroup.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02862563862', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(14, 'Vinpearl Tuyển Dụng', 'tuyendung@vinpearl.com', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02583598999', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(15, 'Gemadept Logistics HR', 'hr@gemadept.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02838236236', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(16, 'Dược Hậu Giang HR', 'tuyendung@dhgpharma.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02923891433', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(17, 'VUS Tuyển Dụng', 'hr@vus.edu.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02873083333', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(18, 'Coteccons HR', 'tuyendung@coteccons.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02835142255', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(19, 'Sun Group HR', 'hr@sungroup.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02363818999', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(20, 'PNJ Tuyển Dụng', 'tuyendung@pnj.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02839951703', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(21, 'Highlands Coffee HR', 'hr@highlandscoffee.com.vn', 'employer', '$2y$10$DLBGh6ZLOJ1qNapr6JCTnOnZeJnLSy2ZYB5kFxw4m1ck5nsIahIua', '02835127355', NULL, NULL, 'local', 1, '2026-11-26 11:53:42', NULL),
(22, 'Lê Thị Cẩm Linh', 'ltclinh23072007@gmail.com', 'candidate', '$2y$10$R3AQQLphRf8xhtH3iK/KneuFIH5ATSv98hZbJ23/K5SBlz1WcMlGe', NULL, NULL, NULL, 'local', 1, '2026-11-26 11:54:56', NULL),
(23, 'Ngô Tuệ Nghi', 'ntnghi23072007@gmail.com', 'employer', '$2y$10$lIyB9PP0.WlTOCiHSZ3c0unsSNcx/GwHAG9ebdw5I08of5Tacaj3G', NULL, NULL, NULL, 'local', 1, '2026-11-26 11:58:40', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_job` (`user_id`,`job_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `status` (`status`),
  ADD KEY `fk_app_cv` (`cv_id`);

--
-- Chỉ mục cho bảng `application_notes`
--
ALTER TABLE `application_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Chỉ mục cho bảng `application_tags`
--
ALTER TABLE `application_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Chỉ mục cho bảng `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `cvs`
--
ALTER TABLE `cvs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `employer_id` (`employer_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `fk_int_job` (`job_id`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `status` (`status`);

--
-- Chỉ mục cho bảng `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`job_id`),
  ADD KEY `fk_saved_job` (`job_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `application_notes`
--
ALTER TABLE `application_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `application_tags`
--
ALTER TABLE `application_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `cvs`
--
ALTER TABLE `cvs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `employer_profiles`
--
ALTER TABLE `employer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `interview_schedules`
--
ALTER TABLE `interview_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_app_cv` FOREIGN KEY (`cv_id`) REFERENCES `cvs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_app_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `application_notes`
--
ALTER TABLE `application_notes`
  ADD CONSTRAINT `fk_note_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_note_emp` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `application_tags`
--
ALTER TABLE `application_tags`
  ADD CONSTRAINT `fk_tag_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `fk_camp_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cvs`
--
ALTER TABLE `cvs`
  ADD CONSTRAINT `fk_cv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD CONSTRAINT `fk_ep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD CONSTRAINT `fk_int_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_int_cand` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_int_emp` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_int_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_job_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `fk_saved_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

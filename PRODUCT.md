# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Sinh viên & người mới tốt nghiệp:** Nhóm người dùng trọng tâm. Họ cần tìm kiếm việc làm đầu tiên, vị trí thực tập hoặc việc làm bán thời gian/toàn thời gian phù hợp với chuyên ngành, tích lũy kinh nghiệm thực tế.
- **Nhà tuyển dụng / Doanh nghiệp:** Đăng tuyển vị trí việc làm, tiếp cận nguồn nhân lực trẻ, xem xét hồ sơ và thư giới thiệu (cover letter) của ứng viên, cập nhật trạng thái tuyển dụng.

## Product Purpose

JobFinder là nền tảng kết nối cơ hội việc làm giữa sinh viên, ứng viên trẻ và các doanh nghiệp, nhà tuyển dụng tại Việt Nam. Sản phẩm hướng tới trải nghiệm tuyển dụng tinh gọn, rõ ràng, giúp ứng viên dễ dàng tiếp cận thông tin công việc minh bạch và theo dõi tiến trình ứng tuyển một cách liền mạch.

## Positioning

Cổng kết nối việc làm hướng tới sinh viên và ứng viên trẻ với cấu trúc nội dung cô đọng, quy trình ứng tuyển tinh giản (tập trung vào thư giới thiệu và hồ sơ cá nhân phù hợp thực tế) và hệ thống phân loại việc làm rõ ràng theo ngành nghề và khu vực địa lý.

## Operating Context

- Môi trường triển khai: Ứng dụng web chạy trên nền tảng PHP & cơ sở dữ liệu MySQL (môi trường XAMPP / Apache cục bộ hoặc máy chủ web Linux tiêu chuẩn).
- Người dùng truy cập qua trình duyệt web trên máy tính hoặc thiết bị di động để tra cứu việc làm, nộp hồ sơ hoặc quản lý quy trình tuyển dụng.

## Capabilities and Constraints

- **Tính năng phía ứng viên (hiện hữu):**
  - Tra cứu và lọc danh sách việc làm theo từ khóa, ngành nghề và địa điểm.
  - Xem chi tiết công việc: mô tả, yêu cầu, mức lương, kinh nghiệm, chế độ làm việc.
  - Đăng ký và đăng nhập tài khoản ứng viên (xác thực mật khẩu mã hóa BCRYPT qua session PHP).
  - Nộp đơn ứng tuyển kèm Thư giới thiệu (Cover Letter) cho từng công việc; ngăn chặn việc nộp trùng lặp cùng một vị trí.
  - Theo dõi danh sách việc làm đã ứng tuyển cùng trạng thái duyệt hồ sơ theo thời gian thực.
- **Tính năng phía nhà tuyển dụng (kế hoạch mở rộng):**
  - Đăng tin tuyển dụng mới và quản lý các bài đăng.
  - Xem danh sách và hồ sơ ứng viên ứng tuyển vào từng tin tuyển dụng.
  - Cập nhật trạng thái ứng tuyển (Đã nhận hồ sơ, Đang xét duyệt, Phù hợp, Từ chối).
- **Ràng buộc kỹ thuật:**
  - Ngôn ngữ: PHP thuần (procedural / PDO) kết hợp MySQL database (`job_portal`).
  - Giao diện: HTML5, CSS3 hiện đại, Vanilla JavaScript (không phụ thuộc framework nặng).

## Brand Commitments

- Tên thương hiệu: **JobFinder** (viết tắt / logo mark: `(JF)`).
- Slogan / Thông điệp: *"Kết nối cơ hội, mở lối tương lai"*.
- Phong cách giao tiếp: Chuyên nghiệp, gần gũi, khích lệ người trẻ bắt đầu sự nghiệp.

## Evidence on Hand

- Cơ sở dữ liệu mẫu: Bảng `users`, `jobs`, `applications` đã có dữ liệu thử nghiệm tại [jobs.sql](file:///Applications/XAMPP/xamppfiles/htdocs/jobfinder_website/database/jobs.sql).
- Toàn bộ luồng ứng viên cơ bản đã hoạt động: tìm kiếm việc làm, đăng ký, đăng nhập, nộp đơn ứng tuyển, kiểm tra trạng thái hồ sơ.
- Vắng mặt: Hiện chưa có giao diện quản lý dành riêng cho Nhà tuyển dụng / Admin (mục tiêu phát triển tiếp theo).

## Product Principles

1. **Minh bạch & Rõ ràng:** Mọi thông tin tuyển dụng (mức lương, địa điểm, yêu cầu, hạn nộp) đều được hiển thị mạch lạc, không giấu thông tin.
2. **Thao tác Tinh gọn:** Rút ngắn tối đa số bước từ khi tìm thấy việc làm đến khi nộp hồ sơ thành công; không bắt buộc form nhập liệu rườm rà.
3. **Phản hồi Tức thì:** Ứng viên luôn nắm rõ tình trạng hồ sơ của mình (đã nộp, đang duyệt, kết quả) để chủ động trong kế hoạch nghề nghiệp.
4. **Trực quan & Dễ tiếp cận:** Giao diện tập trung vào tính khả dụng, bố cục dạng lưới chuẩn mực, dễ đọc trên cả máy tính lẫn điện thoại.

## Accessibility & Inclusion

- Ngôn ngữ giao diện tiếng Việt chuẩn hóa, cấu trúc thẻ ngữ nghĩa (Semantic HTML5) đầy đủ cho form và nút tương tác.
- Tương thích tốt với các kích thước màn hình phổ biến từ thiết bị di động đến máy tính để bàn.

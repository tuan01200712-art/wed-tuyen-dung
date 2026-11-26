<?php
/**
 * FILE: api/chat-support.php
 * API xử lý Chat Hỗ trợ trực tuyến — Kết nối MySQL (không cần API key ngoài)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không hợp lệ.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$message = trim($data['message'] ?? ($_POST['message'] ?? ''));

if ($message === '') {
    echo json_encode([
        'reply' => 'Bạn vui lòng nhập nội dung câu hỏi để tôi hỗ trợ nhé.',
        'jobs' => [],
        'quick_replies' => ['Tìm việc IT', 'Hướng dẫn tạo CV', 'Cách ứng tuyển', 'Đăng tin tuyển dụng']
    ]);
    exit;
}

/**
 * Chuẩn hóa chuỗi tiếng Việt: chữ thường, bỏ dấu để khớp cả có dấu và không dấu
 */
function vn_normalize($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $map = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    ];
    foreach ($map as $ascii => $pattern) {
        $str = preg_replace('/(' . $pattern . ')/u', $ascii, $str);
    }
    $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
    return preg_replace('/\s+/', ' ', trim($str));
}

function has_any($norm, array $keywords) {
    foreach ($keywords as $kw) {
        if (strpos($norm, $kw) !== false) {
            return true;
        }
    }
    return false;
}

$norm = vn_normalize($message);
$reply = '';
$jobs = [];
$quick_replies = [];

// 1. Lời chào / Cảm ơn / Tạm biệt (chỉ khớp khi câu ngắn, không chứa từ khóa tìm việc/hướng dẫn)
$is_short_greeting = mb_strlen($norm) <= 25 && has_any($norm, ['xin chao', 'chao ban', 'chao ad', 'chao', 'hello', 'hi ', 'alo']) && !has_any($norm, ['viec', 'cv', 'tin', 'luong', 'tai khoan']);
if ($is_short_greeting || $norm === 'hi' || $norm === 'hello' || $norm === 'alo') {
    $reply = 'Xin chào! Tôi là trợ lý hỗ trợ của <strong>JobFinder</strong>. Bạn đang cần tìm việc làm, tạo CV hay hỗ trợ đăng tin tuyển dụng?';
    $quick_replies = ['Tìm việc IT', 'Hướng dẫn tạo CV', 'Cách ứng tuyển', 'Đăng tin tuyển dụng'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

if (has_any($norm, ['cam on', 'thank', 'thanks', 'tuyet voi'])) {
    $reply = 'Rất vui được hỗ trợ bạn! Nếu cần tìm thêm việc làm hoặc hướng dẫn thao tác, bạn cứ nhắn cho tôi bất cứ lúc nào nhé.';
    $quick_replies = ['Tìm việc lương cao', 'Xem việc đã lưu', 'Theo dõi hồ sơ'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

if (has_any($norm, ['tam biet', 'bye', 'hen gap lai'])) {
    $reply = 'Tạm biệt bạn! Chúc bạn sớm tìm được công việc phù hợp trên JobFinder.';
    $quick_replies = ['Tìm việc làm mới nhất'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Hướng dẫn Ứng viên: Tạo CV
if (has_any($norm, ['tao cv', 'viet cv', 'sua cv', 'chinh sua cv', 'ho so cv', 'mau cv'])) {
    $reply = 'Để tạo hoặc chỉnh sửa CV trực tuyến trên JobFinder:<br>'
           . '1. Truy cập mục <strong>Tạo CV</strong> trên thanh menu.<br>'
           . '2. Điền thông tin cá nhân, học vấn, kinh nghiệm và chọn nhanh kỹ năng gợi ý.<br>'
           . '3. Bấm <strong>Lưu hồ sơ CV</strong> để đính kèm khi ứng tuyển.'
           . '<div class="chat-inline-actions">'
           . '<a href="cv-builder.php" class="chat-inline-link">Tạo CV ngay →</a>'
           . '<a href="my-cvs.php" class="chat-inline-link">Quản lý CV</a>'
           . '</div>';
    $quick_replies = ['Cách ứng tuyển', 'Tìm việc làm mới nhất', 'Lưu việc làm'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Hướng dẫn Ứng viên: Ứng tuyển & Theo dõi hồ sơ
if (has_any($norm, ['ung tuyen', 'nop ho so', 'nop don', 'theo doi ho so', 'trang thai ho so', 'lich phong van'])) {
    $reply = 'Để ứng tuyển và theo dõi tiến độ hồ sơ:<br>'
           . '1. Mở tin tuyển dụng phù hợp và bấm <strong>Ứng tuyển ngay</strong>.<br>'
           . '2. Chọn CV đã tạo (hoặc gửi kèm thư giới thiệu) rồi bấm gửi.<br>'
           . '3. Theo dõi trạng thái duyệt và lịch phỏng vấn tại mục <strong>Đã ứng tuyển</strong>.'
           . '<div class="chat-inline-actions">'
           . '<a href="my-applications.php" class="chat-inline-link">Xem hồ sơ đã ứng tuyển →</a>'
           . '<a href="jobs.php" class="chat-inline-link">Tìm việc làm</a>'
           . '</div>';
    $quick_replies = ['Hướng dẫn tạo CV', 'Lưu việc làm', 'Tìm việc lương cao'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Hướng dẫn Ứng viên: Lưu việc làm
if (has_any($norm, ['luu viec', 'viec da luu', 'yeu thich', 'trai tim'])) {
    $reply = 'Bạn có thể bấm vào biểu tượng <strong>trái tim</strong> trên mỗi thẻ việc làm hoặc trang chi tiết để lưu lại tin quan tâm, sau đó xem lại bất kỳ lúc nào tại trang Việc đã lưu.'
           . '<div class="chat-inline-actions">'
           . '<a href="saved-jobs.php" class="chat-inline-link">Xem việc đã lưu →</a>'
           . '</div>';
    $quick_replies = ['Tìm việc làm mới nhất', 'Cách ứng tuyển'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. Hướng dẫn Nhà tuyển dụng
if (has_any($norm, ['dang tin', 'nha tuyen dung', 'tuyen nhan su', 'tim ung vien', 'quan ly ung vien', 'kenh ntd', 'ntd'])) {
    $reply = 'Dành cho Nhà tuyển dụng:<br>'
           . '1. Đăng ký tài khoản với vai trò <strong>Nhà tuyển dụng</strong>.<br>'
           . '2. Vào <strong>Kênh Nhà tuyển dụng</strong> để cập nhật hồ sơ công ty và đăng tin tuyển dụng mới.<br>'
           . '3. Quản lý hồ sơ ứng viên nộp vào, ghi chú và đặt lịch phỏng vấn trực tiếp.'
           . '<div class="chat-inline-actions">'
           . '<a href="register.php?role=employer" class="chat-inline-link">Đăng ký Nhà tuyển dụng</a>'
           . '<a href="employer/dashboard.php" class="chat-inline-link">Kênh Nhà tuyển dụng →</a>'
           . '</div>';
    $quick_replies = ['Liên hệ hỗ trợ', 'Chính sách bảo mật'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 6. Tài khoản & Mật khẩu
if (has_any($norm, ['dang ky', 'dang nhap', 'mat khau', 'quen mat khau', 'doi mat khau', 'tai khoan'])) {
    $reply = 'Bạn có thể đăng nhập hoặc tạo tài khoản mới (Ứng viên / Nhà tuyển dụng) nhanh chóng:<br>'
           . '• Nếu quên mật khẩu hoặc tài khoản bị khóa, vui lòng liên hệ hỗ trợ qua email <strong>support@jobfinder.vn</strong> hoặc hotline <strong>1800 599 988</strong> để được cấp lại.'
           . '<div class="chat-inline-actions">'
           . '<a href="login.php" class="chat-inline-link">Đăng nhập</a>'
           . '<a href="register.php" class="chat-inline-link">Đăng ký tài khoản →</a>'
           . '</div>';
    $quick_replies = ['Liên hệ hỗ trợ', 'Hướng dẫn tạo CV'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 7. Bảo mật & Cookie
if (has_any($norm, ['bao mat', 'du lieu ca nhan', 'rieng tu', 'cookie', 'chinh sach'])) {
    $reply = 'JobFinder cam kết bảo vệ dữ liệu cá nhân của bạn theo quy định pháp luật. Mật khẩu được mã hóa an toàn và CV chỉ gửi tới nhà tuyển dụng khi bạn chủ động ứng tuyển.'
           . '<div class="chat-inline-actions">'
           . '<button type="button" class="chat-inline-link" onclick="openPrivacyModal()">Xem Chính sách</button>'
           . '<button type="button" class="chat-inline-link" onclick="openCookieSettings()">Cài đặt Cookie</button>'
           . '</div>';
    $quick_replies = ['Liên hệ hỗ trợ', 'Tìm việc làm mới nhất'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 8. Liên hệ / Hotline / Góp ý
if (has_any($norm, ['lien he', 'hotline', 'so dien thoai', 'email', 'gop y', 'gio lam viec', 'ho tro'])) {
    $reply = 'Thông tin hỗ trợ trực tiếp từ <strong>JobFinder</strong>:<br>'
           . '• Hotline: <strong>1800 599 988</strong><br>'
           . '• Email: <strong>support@jobfinder.vn</strong><br>'
           . '• Giờ làm việc: <strong>Thứ 2 – Thứ 6 (8:00 – 18:00)</strong>'
           . '<div class="chat-inline-actions">'
           . '<button type="button" class="chat-inline-link" onclick="openFeedbackModal()">Mở form Góp ý →</button>'
           . '</div>';
    $quick_replies = ['Tìm việc làm mới nhất', 'Hướng dẫn tạo CV'];
    echo json_encode(['reply' => $reply, 'jobs' => [], 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 9. Tìm việc làm trực tiếp từ MySQL
$search_triggers = [
    'tim viec', 'viec lam', 'tuyen dung', 'cong viec', 'luong cao', 'thuc tap', 'tu xa', 'remote',
    'it', 'cntt', 'phan mem', 'lap trinh', 'php', 'laravel', 'react', 'frontend', 'backend', 'du lieu',
    'marketing', 'seo', 'noi dung', 'thuong hieu',
    'kinh doanh', 'ban hang', 'sales', 'b2b',
    'ke toan', 'tai chinh', 'ngan hang',
    'thiet ke', 'ui', 'ux', 'kien truc', 'trang suc',
    'nhan su', 'tuyen dung', 'hanh chinh', 'c b',
    'logistics', 'xuat nhap khau', 'cang bien',
    'duoc', 'y te', 'trinh duoc',
    'giao duc', 'giang vien', 'tieng anh', 'tuyen sinh',
    'nha hang', 'khach san', 'du lich', 'le tan', 'ca phe',
    'ky thuat', 'co khi', 'xay dung', 'co dien',
    'phap ly', 'phap che', 'luat',
    'tphcm', 'tp hcm', 'ho chi minh', 'sai gon', 'ha noi', 'da nang', 'binh duong', 'dong nai', 'can tho', 'hai phong',
    'fpt', 'vng', 'momo', 'viettel', 'shopee', 'vinamilk', 'techcombank', 'vietcombank', 'the gioi di dong',
    'masan', 'vinpearl', 'gemadept', 'duoc hau giang', 'vus', 'coteccons', 'sun group', 'pnj', 'highlands'
];

if (has_any($norm, $search_triggers)) {
    // Bản đồ từ khóa không dấu -> cụm từ tìm kiếm SQL thực tế
    $keyword_map = [
        'tphcm' => 'TP.HCM', 'tp hcm' => 'TP.HCM', 'ho chi minh' => 'TP.HCM', 'sai gon' => 'TP.HCM',
        'ha noi' => 'Hà Nội', 'da nang' => 'Đà Nẵng', 'binh duong' => 'Bình Dương',
        'dong nai' => 'Đồng Nai', 'can tho' => 'Cần Thơ', 'hai phong' => 'Hải Phòng',
        'tu xa' => 'từ xa', 'remote' => 'từ xa',
        'it' => 'Công nghệ thông tin', 'cntt' => 'Công nghệ thông tin', 'phan mem' => 'Công nghệ thông tin',
        'lap trinh' => 'Lập trình', 'php' => 'PHP', 'laravel' => 'Laravel', 'react' => 'ReactJS',
        'marketing' => 'Marketing', 'seo' => 'SEO', 'ke toan' => 'Kế toán', 'tai chinh' => 'Tài chính',
        'ngan hang' => 'Ngân hàng', 'kinh doanh' => 'Kinh doanh', 'sales' => 'B2B',
        'thiet ke' => 'Thiết kế', 'ui' => 'UI/UX', 'nhan su' => 'Nhân sự',
        'logistics' => 'Logistics', 'xuat nhap khau' => 'Xuất nhập khẩu',
        'duoc' => 'Dược', 'y te' => 'Y tế', 'giao duc' => 'Giáo dục', 'tieng anh' => 'Tiếng Anh',
        'khach san' => 'Khách sạn', 'du lich' => 'Du lịch', 'xay dung' => 'Xây dựng',
        'phap ly' => 'Pháp', 'luat' => 'Pháp', 'thuc tap' => 'Thực tập',
        'fpt' => 'FPT', 'vng' => 'VNG', 'momo' => 'MoMo', 'viettel' => 'Viettel',
        'shopee' => 'Shopee', 'vinamilk' => 'Vinamilk', 'techcombank' => 'Techcombank',
        'vietcombank' => 'Vietcombank', 'masan' => 'Masan', 'vinpearl' => 'Vinpearl',
        'gemadept' => 'Gemadept', 'vus' => 'VUS', 'coteccons' => 'Coteccons',
        'sun group' => 'Sun Group', 'pnj' => 'PNJ', 'highlands' => 'Highlands'
    ];

    $terms = [];
    foreach ($keyword_map as $k => $v) {
        if (strpos($norm, $k) !== false) {
            $terms[] = $v;
        }
    }
    $terms = array_values(array_unique($terms));
    $high_salary = strpos($norm, 'luong cao') !== false;

    try {
        $where = " WHERE status = 'active'";
        $params = [];
        if (!empty($terms)) {
            $or_parts = [];
            foreach ($terms as $idx => $t) {
                $p = ":t{$idx}";
                $or_parts[] = "(title LIKE {$p} OR company LIKE {$p} OR category LIKE {$p} OR location LIKE {$p} OR job_type LIKE {$p})";
                $params[$p] = "%{$t}%";
            }
            $where .= " AND (" . implode(' OR ', $or_parts) . ")";
        }
        $order = $high_salary ? " ORDER BY salary_value DESC, created_at DESC" : " ORDER BY is_featured DESC, created_at DESC";
        $stmt = $pdo->prepare("SELECT id, title, company, salary, location FROM jobs" . $where . $order . " LIMIT 3");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (!empty($rows)) {
            foreach ($rows as $r) {
                $jobs[] = [
                    'id' => (int)$r['id'],
                    'title' => $r['title'],
                    'company' => $r['company'],
                    'salary' => $r['salary'],
                    'location' => $r['location'],
                    'url' => 'job-detail.php?id=' . (int)$r['id']
                ];
            }
            $reply = 'Tôi tìm thấy <strong>' . count($jobs) . '</strong> việc làm phù hợp đang tuyển trên hệ thống:';
            $quick_replies = ['Xem tất cả việc làm', 'Cách ứng tuyển', 'Hướng dẫn tạo CV'];
        } else {
            $reply = 'Hiện chưa có tin tuyển dụng khớp chính xác với yêu cầu này. Bạn có thể xem toàn bộ việc làm và dùng bộ lọc chi tiết nhé.'
                   . '<div class="chat-inline-actions"><a href="jobs.php" class="chat-inline-link">Mở trang Tìm việc →</a></div>';
            $quick_replies = ['Tìm việc IT', 'Tìm việc Marketing', 'Tìm việc lương cao'];
        }
    } catch (PDOException $e) {
        $reply = 'Bạn có thể khám phá và lọc việc làm mới nhất trực tiếp tại trang Tìm kiếm việc làm.'
               . '<div class="chat-inline-actions"><a href="jobs.php" class="chat-inline-link">Xem việc làm →</a></div>';
        $quick_replies = ['Hướng dẫn tạo CV', 'Liên hệ hỗ trợ'];
    }

    echo json_encode(['reply' => $reply, 'jobs' => $jobs, 'quick_replies' => $quick_replies], JSON_UNESCAPED_UNICODE);
    exit;
}

// 10. Câu hỏi ngoài kịch bản (Fallback thân thiện + 4 nút gợi ý)
$reply = 'Xin lỗi, tôi chưa hiểu rõ ý của bạn. Bạn có thể chọn nhanh một trong các chủ đề phổ biến dưới đây để tôi hỗ trợ ngay:';
$quick_replies = ['Tìm việc IT', 'Hướng dẫn tạo CV', 'Cách ứng tuyển', 'Đăng tin tuyển dụng'];

echo json_encode([
    'reply' => $reply,
    'jobs' => [],
    'quick_replies' => $quick_replies
], JSON_UNESCAPED_UNICODE);

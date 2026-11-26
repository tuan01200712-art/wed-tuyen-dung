<?php
/**
 * ============================================================
 * FILE: includes/functions.php
 * MÔ TẢ: Các hàm tiện ích cốt lõi cho hệ thống JobFinder (Hệ thống JobFinder)
 * ============================================================
 */

if (!function_exists('get_categories')) {
    /**
     * Lấy danh sách ngành nghề từ CSDL (kèm fallback an toàn)
     */
    function get_categories($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
            $cats = $stmt->fetchAll();
            if (!empty($cats)) {
                return $cats;
            }
        } catch (PDOException $e) {
            // Fallback nếu bảng chưa có
        }

        $default_names = [
            'Công nghệ thông tin', 'Marketing & Truyền thông', 'Kinh doanh & Bán hàng',
            'Kế toán - Tài chính - Ngân hàng', 'Thiết kế & Kiến trúc', 'Nhân sự & Hành chính',
            'Logistics & Xuất nhập khẩu', 'Y tế & Dược phẩm', 'Giáo dục & Đào tạo',
            'Nhà hàng - Khách sạn - Du lịch', 'Kỹ thuật - Cơ khí - Xây dựng', 'Pháp lý & Luật'
        ];
        $result = [];
        foreach ($default_names as $i => $name) {
            $result[] = ['id' => $i + 1, 'name' => $name, 'slug' => '', 'icon' => 'briefcase'];
        }
        return $result;
    }
}

if (!function_exists('get_locations')) {
    /**
     * Lấy danh sách địa điểm từ CSDL
     */
    function get_locations($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM locations WHERE is_active = 1 ORDER BY id ASC");
            $locs = $stmt->fetchAll();
            if (!empty($locs)) {
                return $locs;
            }
        } catch (PDOException $e) {
            // Fallback
        }

        $default_locs = ['TP.HCM', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai', 'Cần Thơ', 'Hải Phòng', 'Làm việc từ xa'];
        $result = [];
        foreach ($default_locs as $i => $name) {
            $result[] = ['id' => $i + 1, 'name' => $name];
        }
        return $result;
    }
}

if (!function_exists('get_application_stages')) {
    /**
     * Danh sách 9 vòng quy trình tuyển dụng hệ thống JobFinder
     */
    function get_application_stages() {
        return [
            'new'                 => ['name' => 'CV Mới',            'color' => '#3B82F6', 'bg' => '#EFF6FF'],
            'viewed'              => ['name' => 'Đã xem',            'color' => '#6366F1', 'bg' => '#EEF2FF'],
            'suitable'            => ['name' => 'Phù hợp',           'color' => '#059669', 'bg' => '#ECFDF5'],
            'contacted'           => ['name' => 'Đã liên hệ',        'color' => '#D97706', 'bg' => '#FFFBEB'],
            'interview_scheduled' => ['name' => 'Hẹn phỏng vấn',     'color' => '#8B5CF6', 'bg' => '#F5F3FF'],
            'interviewed'         => ['name' => 'Đã phỏng vấn',      'color' => '#4F46E5', 'bg' => '#EEF2FF'],
            'offered'             => ['name' => 'Gửi đề nghị',       'color' => '#0D9488', 'bg' => '#F0FDFA'],
            'hired'               => ['name' => 'Đã tuyển',          'color' => '#16A34A', 'bg' => '#DCFCE7'],
            'rejected'            => ['name' => 'Từ chối',           'color' => '#DC2626', 'bg' => '#FEF2F2'],
        ];
    }
}

if (!function_exists('get_application_stage_badge')) {
    /**
     * Trả về HTML badge cho trạng thái vòng tuyển dụng
     */
    function get_application_stage_badge($stage_code) {
        $stages = get_application_stages();
        // Ánh xạ trạng thái cũ tiếng Việt sang mã mới nếu có
        $legacy_map = [
            'Đã nộp hồ sơ' => 'new',
            'Đang xét duyệt' => 'viewed',
            'Phù hợp' => 'suitable',
            'Từ chối' => 'rejected'
        ];
        if (isset($legacy_map[$stage_code])) {
            $stage_code = $legacy_map[$stage_code];
        }

        if (isset($stages[$stage_code])) {
            $s = $stages[$stage_code];
            return '<span class="badge" style="background:' . $s['bg'] . '; color:' . $s['color'] . '; border:1px solid ' . $s['color'] . '33; font-weight:700;">' . htmlspecialchars($s['name']) . '</span>';
        }
        return '<span class="badge badge--pending">' . htmlspecialchars($stage_code) . '</span>';
    }
}

if (!function_exists('get_job_status_badge')) {
    /**
     * Trả về HTML badge cho vòng đời tin tuyển dụng
     */
    function get_job_status_badge($status) {
        switch ($status) {
            case 'active':
                return '<span class="badge badge--active">Đang hiển thị</span>';
            case 'pending':
                return '<span class="badge badge--pending" style="background:#FFFBEB; color:#D97706; border:1px solid #FDE68A;">Chờ duyệt</span>';
            case 'draft':
                return '<span class="badge badge--pending">Bản nháp</span>';
            case 'rejected':
                return '<span class="badge badge--danger">Bị từ chối</span>';
            case 'inactive':
                return '<span class="badge badge--danger" style="background:#F3F4F6; color:#4B5563; border:1px solid #E5E7EB;">Tạm dừng</span>';
            case 'expired':
                return '<span class="badge" style="background:#FEE2E2; color:#B91C1C; border:1px solid #FECACA;">Hết hạn</span>';
            default:
                return '<span class="badge">' . htmlspecialchars($status) . '</span>';
        }
    }
}

if (!function_exists('create_notification')) {
    /**
     * Tạo thông báo trong hệ thống cho người dùng
     */
    function create_notification($pdo, $user_id, $title, $message, $link = null) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            return $stmt->execute([(int)$user_id, $title, $message, $link]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('log_activity')) {
    /**
     * Ghi nhật ký thao tác
     */
    function log_activity($pdo, $user_id, $action, $details = '') {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([(int)$user_id, $action, $details, $ip]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('time_ago')) {
    /**
     * Định dạng mốc thời gian thân thiện (Time Ago)
     */
    function time_ago($datetime) {
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Vừa xong';
        } elseif ($diff < 3600) {
            return round($diff / 60) . ' phút trước';
        } elseif ($diff < 86400) {
            return round($diff / 3600) . ' giờ trước';
        } elseif ($diff < 2592000) {
            return round($diff / 86400) . ' ngày trước';
        } else {
            return date('d/m/Y', $timestamp);
        }
    }
}


if (!function_exists('get_company_logo_path')) {
    /**
     * Trả về đường dẫn logo riêng của từng doanh nghiệp
     */
    function get_company_logo_path($company_name, $db_logo = '') {
        if (!empty($db_logo)) {
            return $db_logo;
        }
        $map = [
            'FPT Software'       => 'assets/images/companies/fpt-software.svg',
            'VNG Corporation'    => 'assets/images/companies/vng.svg',
            'VNG'                => 'assets/images/companies/vng.svg',
            'MoMo'               => 'assets/images/companies/momo.svg',
            'Viettel Digital'    => 'assets/images/companies/viettel-digital.svg',
            'Shopee Việt Nam'    => 'assets/images/companies/shopee.svg',
            'Shopee Vietnam'     => 'assets/images/companies/shopee.svg',
            'Vinamilk'           => 'assets/images/companies/vinamilk.svg',
            'Techcombank'        => 'assets/images/companies/techcombank.svg',
            'Vietcombank'        => 'assets/images/companies/vietcombank.svg',
            'Thế Giới Di Động'   => 'assets/images/companies/the-gioi-di-dong.svg',
            'Masan Group'        => 'assets/images/companies/masan.svg',
            'Vinpearl'           => 'assets/images/companies/vinpearl.svg',
            'Gemadept Logistics' => 'assets/images/companies/gemadept.svg',
            'Dược Hậu Giang'     => 'assets/images/companies/duoc-hau-giang.svg',
            'VUS Anh Văn Hội Việt Mỹ' => 'assets/images/companies/vus.svg',
            'Coteccons'          => 'assets/images/companies/coteccons.svg',
            'Sun Group'          => 'assets/images/companies/sun-group.svg',
            'PNJ'                => 'assets/images/companies/pnj.svg',
            'Highlands Coffee'   => 'assets/images/companies/highlands-coffee.svg',
        ];
        foreach ($map as $key => $path) {
            if (mb_stripos($company_name, $key) !== false) {
                return $path;
            }
        }
        return '';
    }
}

if (!function_exists('render_company_logo')) {
    /**
     * Render thẻ logo công ty (SVG riêng hoặc fallback chữ viết tắt)
     */
    function render_company_logo($company_name, $db_logo = '', $prefix = '', $size_px = 44) {
        $logo = get_company_logo_path($company_name, $db_logo);
        $radius = $size_px >= 56 ? '12px' : '10px';
        if (!empty($logo)) {
            $src = htmlspecialchars($prefix . $logo);
            $alt = htmlspecialchars($company_name);
            return '<div class="company-logo-box" style="width:' . (int)$size_px . 'px; height:' . (int)$size_px . 'px; border-radius:' . $radius . ';"><img src="' . $src . '" alt="' . $alt . '"></div>';
        }
        $init = htmlspecialchars(mb_strtoupper(mb_substr($company_name, 0, 2)));
        return '<div class="job-card__avatar" style="width:' . (int)$size_px . 'px; height:' . (int)$size_px . 'px; border-radius:' . $radius . ';">' . $init . '</div>';
    }
}

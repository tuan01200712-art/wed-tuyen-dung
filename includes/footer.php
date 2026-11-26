<?php
/**
 * FILE: includes/footer.php
 * Footer 4 cột + Floating Widgets + Modals (ẩn mặc định) + Cookie Bar gọn sát đáy
 */
$_is_admin_page = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/employer/') !== false);
$_is_logged_in = isset($_SESSION['user_id']);
$_saved_jobs_count = 0;
if ($_is_logged_in && !$_is_admin_page && isset($pdo)) {
    try {
        $_sj_stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
        $_sj_stmt->execute([(int)$_SESSION['user_id']]);
        $_saved_jobs_count = (int)$_sj_stmt->fetchColumn();
    } catch (Exception $e) {
        $_saved_jobs_count = 0;
    }
}
?>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="index.php" class="logo" title="JobFinder">
                    <img src="assets/images/logo-white.svg" alt="JobFinder" class="logo-img" height="36" onerror="this.src='assets/images/logo.svg'">
                </a>
                <p class="footer-brand-desc">
                    Nền tảng tuyển dụng và tìm việc làm minh bạch tại Việt Nam. Kết nối trực tiếp ứng viên với doanh nghiệp đã xác thực.
                </p>
                <div class="footer-contact-row">
                    <div class="footer-contact-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.37 2 2 0 0 1 3.59 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.56a16 16 0 0 0 6.29 6.29l1.62-.83a2 2 0 0 1 2.11.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span>Hotline: 1800 599 988</span>
                    </div>
                    <div class="footer-contact-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <span>support@jobfinder.vn</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="footer-title">Dành cho Ứng viên</div>
                <div class="footer-links">
                    <a href="jobs.php" class="footer-link">Tìm việc làm</a>
                    <a href="cv-builder.php" class="footer-link">Tạo CV trực tuyến</a>
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'candidate'): ?>
                        <a href="my-applications.php" class="footer-link">Việc đã ứng tuyển</a>
                        <a href="saved-jobs.php" class="footer-link">Việc làm đã lưu</a>
                        <a href="my-cvs.php" class="footer-link">Quản lý hồ sơ</a>
                    <?php else: ?>
                        <a href="register.php" class="footer-link">Đăng ký ứng viên</a>
                        <a href="login.php" class="footer-link">Đăng nhập</a>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="footer-title">Nhà tuyển dụng</div>
                <div class="footer-links">
                    <a href="employer/job-create.php" class="footer-link">Đăng tin tuyển dụng</a>
                    <a href="employer/dashboard.php" class="footer-link">Bảng điều khiển NTD</a>
                    <a href="employer/applications.php" class="footer-link">Quản lý ứng viên</a>
                    <a href="register.php" class="footer-link">Đăng ký tài khoản NTD</a>
                    <a href="admin/dashboard.php" class="footer-link">Cổng quản trị</a>
                </div>
            </div>

            <div>
                <div class="footer-title">Pháp lý & Ngành nghề</div>
                <div class="footer-links">
                    <a href="#" class="footer-link" onclick="openPrivacyModal(); return false;">Chính sách Bảo vệ DLCN</a>
                    <a href="#" class="footer-link" onclick="openCookieSettings(); return false;">Cài đặt Cookie</a>
                    <a href="jobs.php?category=CNTT+-+Ph%E1%BA%A7n+m%E1%BB%81m" class="footer-link">Việc làm CNTT - Phần mềm</a>
                    <a href="jobs.php?category=Marketing" class="footer-link">Việc làm Marketing</a>
                    <a href="jobs.php?category=K%E1%BA%BF+to%C3%A1n+-+T%C3%A0i+ch%C3%ADnh" class="footer-link">Kế toán - Tài chính</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>&copy; <?php echo date('Y'); ?> <strong style="color:#CBD5E1;">JobFinder</strong>. Nền tảng tuyển dụng trực tuyến Việt Nam.</div>
            <div class="footer-bottom-links">
                <span class="footer-bottom-link" onclick="openCookieSettings()">Cài đặt Cookie</span>
                <span style="color:#334155;">·</span>
                <span class="footer-bottom-link" onclick="openPrivacyModal()">Bảo mật dữ liệu</span>
            </div>
        </div>
    </div>
</footer>

<?php if (!$_is_admin_page): ?>

<!-- FLOATING WIDGETS -->
<div class="floating-widgets" id="floatingWidgets">
    <?php if ($_is_logged_in): ?>
        <a href="saved-jobs.php" class="fw-btn" id="savedJobsBtn" title="Việc làm đã lưu">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
            <span class="fw-badge" id="savedBadge" style="<?php echo $_saved_jobs_count > 0 ? '' : 'display:none;'; ?>"><?php echo $_saved_jobs_count; ?></span>
        </a>
    <?php else: ?>
        <a href="login.php?redirect=<?php echo urlencode('saved-jobs.php'); ?>" class="fw-btn" id="savedJobsBtn" title="Đăng nhập để xem việc làm đã lưu" onclick="if (window.showToast) { showToast('Vui lòng đăng nhập để xem và lưu việc làm.', 'warning'); }">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
        </a>
    <?php endif; ?>

    <div class="fw-group">
        <button type="button" class="fw-group-btn" id="feedbackBtn" onclick="openFeedbackModal()" title="Góp ý">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span>Góp ý</span>
        </button>
        <div class="fw-group-sep"></div>
        <button type="button" class="fw-group-btn" id="supportBtn" onclick="toggleChat()" title="Hỗ trợ trực tuyến">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
            </svg>
            <span>Hỗ trợ</span>
        </button>
    </div>
</div>

<!-- CHAT SUPPORT PANEL (ẨN MẶC ĐỊNH) -->
<div class="chat-panel modal-hidden" id="chatPanel" style="display:none;">
    <div class="chat-header">
        <div class="chat-avatar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
            </svg>
        </div>
        <div class="chat-info">
            <div class="chat-name">Hỗ trợ JobFinder</div>
            <div class="chat-status">
                <span class="chat-status-dot"></span>
                <span>Trực tuyến · T2–T6 (8:00–18:00)</span>
            </div>
        </div>
        <button type="button" class="chat-refresh-btn" onclick="chatReset()" title="Làm mới hội thoại" aria-label="Làm mới hội thoại">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        </button>
        <button type="button" class="chat-close" onclick="toggleChat()" aria-label="Đóng khung chat">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <div class="chat-messages" id="chatMessages">
        <div class="chat-msg chat-msg--bot">
            Xin chào! Bạn cần hỗ trợ về tạo CV, ứng tuyển hay đăng tin tuyển dụng?
        </div>
    </div>

    <div class="chat-quick-btns" id="chatQuickBtns">
        <button type="button" class="chat-quick-btn" onclick="chatQuickAsk('Tìm việc IT')">Tìm việc IT</button>
        <button type="button" class="chat-quick-btn" onclick="chatQuickAsk('Hướng dẫn tạo CV')">Tạo CV</button>
        <button type="button" class="chat-quick-btn" onclick="chatQuickAsk('Cách ứng tuyển')">Ứng tuyển</button>
        <button type="button" class="chat-quick-btn" onclick="chatQuickAsk('Đăng tin tuyển dụng')">Đăng tin</button>
    </div>

    <div class="chat-input-row">
        <input type="text" class="chat-input" id="chatInput" placeholder="Nhập câu hỏi..." onkeydown="if(event.key==='Enter') chatSend()">
        <button type="button" class="chat-send" onclick="chatSend()" aria-label="Gửi">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>
</div>

<!-- FEEDBACK MODAL (ẨN MẶC ĐỊNH) -->
<div class="jf-modal-overlay modal-hidden" id="feedbackModal" style="display:none;">
    <div class="jf-modal">
        <div class="jf-modal-header">
            <div class="jf-modal-title">Gửi góp ý</div>
            <button type="button" class="jf-modal-close" onclick="closeFeedbackModal()" aria-label="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="jf-modal-body">
            <div class="form-group">
                <label class="form-label">Chủ đề góp ý</label>
                <select class="form-control" id="feedbackType">
                    <option value="bug">Lỗi kỹ thuật / Sự cố</option>
                    <option value="ui">Giao diện / Trải nghiệm</option>
                    <option value="feature">Đề xuất tính năng</option>
                    <option value="job">Báo cáo tin tuyển dụng</option>
                    <option value="other">Khác</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Nội dung chi tiết</label>
                <textarea class="form-control" id="feedbackText" rows="4" placeholder="Mô tả vấn đề hoặc góp ý của bạn..."></textarea>
            </div>
        </div>
        <div class="jf-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeFeedbackModal()">Hủy</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="submitFeedback()">Gửi góp ý</button>
        </div>
    </div>
</div>

<!-- COOKIE SETTINGS MODAL (ẨN MẶC ĐỊNH) -->
<div class="jf-modal-overlay modal-hidden" id="cookieModal" style="display:none;">
    <div class="jf-modal">
        <div class="jf-modal-header">
            <div class="jf-modal-title">Tùy chỉnh Cookie</div>
            <button type="button" class="jf-modal-close" onclick="closeCookieModal()" aria-label="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="jf-modal-body" style="padding-top:8px; padding-bottom:8px;">
            <div class="cookie-toggle-row">
                <div class="cookie-toggle-info">
                    <div class="cookie-toggle-name">Cookie thiết yếu</div>
                    <div class="cookie-toggle-desc">Duy trì đăng nhập, bảo mật phiên làm việc. Luôn bật.</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked disabled>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="cookie-toggle-row">
                <div class="cookie-toggle-info">
                    <div class="cookie-toggle-name">Phân tích hiệu suất</div>
                    <div class="cookie-toggle-desc">Giúp cải thiện tốc độ tải trang và chất lượng tìm kiếm.</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="cookieAnalytics" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="cookie-toggle-row">
                <div class="cookie-toggle-info">
                    <div class="cookie-toggle-name">Cá nhân hóa việc làm</div>
                    <div class="cookie-toggle-desc">Gợi ý công việc phù hợp theo ngành nghề bạn quan tâm.</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="cookiePersonal" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
        <div class="jf-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeCookieModal()">Hủy</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="saveCookiePrefs()">Lưu tùy chọn</button>
        </div>
    </div>
</div>

<!-- PRIVACY POLICY MODAL (ẨN MẶC ĐỊNH) -->
<div class="jf-modal-overlay modal-hidden" id="privacyModal" style="display:none;">
    <div class="jf-modal" style="max-width:560px;">
        <div class="jf-modal-header">
            <div class="jf-modal-title">Chính sách Bảo vệ Dữ liệu Cá nhân</div>
            <button type="button" class="jf-modal-close" onclick="closePrivacyModal()" aria-label="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="jf-modal-body" style="max-height:60vh; overflow-y:auto; font-size:13px; color:var(--ink-secondary); line-height:1.65;">
            <p style="margin-bottom:10px;"><strong>1. Mục đích thu thập dữ liệu:</strong> JobFinder chỉ thu thập thông tin tài khoản, hồ sơ CV và lịch sử ứng tuyển nhằm kết nối ứng viên với nhà tuyển dụng bạn chủ động nộp hồ sơ.</p>
            <p style="margin-bottom:10px;"><strong>2. Bảo mật & lưu trữ:</strong> Mật khẩu được mã hóa một chiều (bcrypt). Thông tin liên hệ của ứng viên chỉ hiển thị cho nhà tuyển dụng khi bạn thực hiện ứng tuyển.</p>
            <p style="margin-bottom:0;"><strong>3. Quyền của người dùng:</strong> Bạn có quyền xem, chỉnh sửa hoặc yêu cầu xóa hồ sơ CV và dữ liệu cá nhân bất kỳ lúc nào trong phần Cài đặt tài khoản.</p>
        </div>
        <div class="jf-modal-footer">
            <button type="button" class="btn btn-primary btn-sm" onclick="closePrivacyModal()">Đã hiểu</button>
        </div>
    </div>
</div>

<!-- COOKIE CONSENT BAR (CỐ ĐỊNH SÁT ĐÁY, 1 HÀNG NGANG) -->
<div class="cookie-bar modal-hidden" id="cookieBar" style="display:none;">
    <div class="cookie-bar-inner">
        <div class="cookie-bar-left">
            <div class="cookie-bar-title">Trải nghiệm và quyền riêng tư của bạn là ưu tiên của chúng tôi</div>
            <div class="cookie-bar-desc">
                JobFinder sử dụng cookie để đảm bảo tính năng thiết yếu và cải thiện trải nghiệm tìm việc.
                <span class="cookie-bar-link" onclick="openPrivacyModal()">Chính sách Bảo vệ Dữ liệu Cá nhân</span>
            </div>
        </div>
        <div class="cookie-bar-actions">
            <button type="button" class="cookie-btn-custom" onclick="openCookieSettings()">Tùy chỉnh cookie</button>
            <button type="button" class="cookie-btn-accept" onclick="acceptAllCookies()">Chấp nhận tất cả</button>
        </div>
    </div>
</div>

<?php endif; ?>

<script src="assets/js/main.js?v=<?php echo time(); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    ['userDropdown', 'notifDropdown'].forEach(function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const btn = el.querySelector('button');
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.querySelectorAll('.user-dropdown.open').forEach(function(o) { if (o !== el) o.classList.remove('open'); });
                el.classList.toggle('open');
            });
        }
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.user-dropdown.open').forEach(function(el) { el.classList.remove('open'); });
    });

    const toggle = document.getElementById('mobileToggle');
    const menu   = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            const open = menu.classList.toggle('active');
            toggle.setAttribute('aria-expanded', open);
        });
    }

    // Dọn dẹp mọi key localStorage lưu việc làm cũ (đồng bộ 100% theo MySQL)
    try {
        localStorage.removeItem('jf_saved_jobs');
        localStorage.removeItem('savedJobs');
    } catch (e) {}

    const cookiePref = localStorage.getItem('jf_cookie_prefs');
    const bar = document.getElementById('cookieBar');
    if (bar && !cookiePref) {
        bar.classList.remove('modal-hidden');
        bar.style.display = 'block';
    }

    <?php if (!empty($_SESSION['flash_message'])): ?>
    showToast(<?php echo json_encode($_SESSION['flash_message']); ?>, <?php echo json_encode($_SESSION['flash_type'] ?? 'success'); ?>);
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
});

function showModal(id, displayMode) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('modal-hidden');
    el.style.display = displayMode || 'flex';
}
function hideModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('modal-hidden');
    el.style.display = 'none';
}

function showToast(msg, type) {
    type = type || 'success';
    const container = document.getElementById('jfToastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'jf-toast jf-toast--' + type;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3500);
}

function updateSavedBadge(count) {
    const badge = document.getElementById('savedBadge');
    if (!badge || typeof count === 'undefined') return;
    const n = parseInt(count, 10) || 0;
    badge.textContent = n;
    badge.style.display = n > 0 ? 'flex' : 'none';
}

function toggleChat() {
    const panel = document.getElementById('chatPanel');
    if (!panel) return;
    const isHidden = panel.classList.contains('modal-hidden') || panel.style.display === 'none';
    if (isHidden) {
        showModal('chatPanel', 'flex');
        panel.classList.add('open');
        const input = document.getElementById('chatInput');
        if (input) setTimeout(function() { input.focus(); }, 60);
    } else {
        hideModal('chatPanel');
        panel.classList.remove('open');
    }
}

function openFeedbackModal()  { showModal('feedbackModal', 'flex'); }
function closeFeedbackModal() { hideModal('feedbackModal'); }
function submitFeedback() {
    const text = document.getElementById('feedbackText');
    if (!text || !text.value.trim()) { showToast('Vui lòng nhập nội dung góp ý.', 'warning'); return; }
    closeFeedbackModal();
    showToast('Cảm ơn! Góp ý của bạn đã được ghi nhận.', 'success');
    text.value = '';
}

function openCookieSettings() {
    hideModal('cookieBar');
    showModal('cookieModal', 'flex');
}
function closeCookieModal() { hideModal('cookieModal'); }
function acceptAllCookies() {
    localStorage.setItem('jf_cookie_prefs', JSON.stringify({essential:true, analytics:true, personal:true}));
    hideModal('cookieBar');
    showToast('Đã lưu tùy chọn cookie.', 'success');
}
function saveCookiePrefs() {
    const analytics = document.getElementById('cookieAnalytics');
    const personal  = document.getElementById('cookiePersonal');
    localStorage.setItem('jf_cookie_prefs', JSON.stringify({
        essential: true,
        analytics: analytics ? analytics.checked : true,
        personal:  personal  ? personal.checked  : true
    }));
    closeCookieModal();
    showToast('Đã lưu tùy chọn cookie.', 'success');
}
function openPrivacyModal()  { showModal('privacyModal', 'flex'); }
function closePrivacyModal() { hideModal('privacyModal'); }

// Close overlays on backdrop click
document.addEventListener('click', function(e) {
    ['feedbackModal', 'cookieModal', 'privacyModal'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el && e.target === el) hideModal(id);
    });
});
</script>

</body>
</html>

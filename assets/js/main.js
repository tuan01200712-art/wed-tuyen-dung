/**
 * FILE: assets/js/main.js
 * JobFinder — Global Interactive JS (Fintech/Professional)
 */

'use strict';

/* ============================================================
   1. NAVBAR — Scroll shadow & mobile menu
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 10) {
                navbar.style.boxShadow = '0 2px 12px rgba(15,23,42,0.08)';
            } else {
                navbar.style.boxShadow = 'none';
            }
        }, { passive: true });
    }

    /* ============================================================
       2. SAVE JOB BUTTON — Init saved state from localStorage
       ============================================================ */
    initSavedJobButtons();

    /* ============================================================
       3. FLASH MESSAGES — Auto-dismiss
       ============================================================ */
    document.querySelectorAll('.flash-bar').forEach(function (bar) {
        setTimeout(function () {
            bar.style.transition = 'opacity 0.4s';
            bar.style.opacity = '0';
            setTimeout(function () { bar.style.display = 'none'; }, 400);
        }, 4500);
    });

    /* ============================================================
       4. FORM VALIDATION — Visual feedback
       ============================================================ */
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let valid = true;
            form.querySelectorAll('[required]').forEach(function (field) {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--status-danger-text)';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            if (!valid) {
                e.preventDefault();
                showToast('Vui lòng điền đầy đủ các trường bắt buộc.', 'danger');
            }
        });
    });

    /* ============================================================
       5. TABLE ROW CLICK — Navigate on row click
       ============================================================ */
    document.querySelectorAll('tr[data-href]').forEach(function (row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function () {
            window.location.href = row.dataset.href;
        });
    });

    /* ============================================================
       6. CONFIRM DELETE — Prevent accidental deletion
       ============================================================ */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.dataset.confirm || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    /* ============================================================
       7. SMOOTH SCROLL for anchor links
       ============================================================ */
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const id = link.getAttribute('href').slice(1);
            const target = document.getElementById(id);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* ============================================================
       8. IMAGE LAZY LOAD — Fade in on intersect
       ============================================================ */
    if ('IntersectionObserver' in window) {
        const imgObs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    img.style.opacity = '1';
                    imgObs.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });

        document.querySelectorAll('img[data-src]').forEach(function (img) {
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.4s';
            imgObs.observe(img);
        });
    }

    /* ============================================================
       9. SECTION FADE IN — Animate sections on scroll
       ============================================================ */
    if ('IntersectionObserver' in window) {
        const sectionObs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('jf-visible');
                    sectionObs.unobserve(entry.target);
                }
            });
        }, { rootMargin: '-40px', threshold: 0.1 });

        document.querySelectorAll('.section, .stats-bar, .partners-strip, .support-strip').forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(16px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            sectionObs.observe(el);
        });
    }
});

/* Add visible class handler */
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = '.jf-visible { opacity: 1 !important; transform: translateY(0) !important; }';
    document.head.appendChild(style);
});

/* ============================================================
   10. GLOBAL HELPERS
   ============================================================ */

/** Dọn dẹp localStorage lưu tin cũ (dữ liệu đồng bộ trực tiếp từ MySQL) */
function initSavedJobButtons() {
    try {
        localStorage.removeItem('jf_saved_jobs');
        localStorage.removeItem('savedJobs');
    } catch (e) {}
}

/** Cập nhật số lượng việc làm đã lưu trên badge góc phải */
function updateSavedBadge(count) {
    const badge = document.getElementById('savedBadge');
    if (!badge || typeof count === 'undefined') return;
    const n = parseInt(count, 10) || 0;
    badge.textContent = n;
    badge.style.display = n > 0 ? 'flex' : 'none';
}

/** Show toast notification */
function showToast(message, type) {
    type = type || 'success';
    const container = document.getElementById('jfToastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'jf-toast jf-toast--' + type;
    toast.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> ' + message;
    if (type === 'danger' || type === 'warning') {
        toast.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ' + message;
    }
    container.appendChild(toast);
    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
    }, 3800);
}

/** Chuyển hướng đăng nhập nếu chưa đăng nhập khi bấm lưu tin */
function toggleSaveJob(jobId, btn) {
    if (typeof showToast === 'function') {
        showToast('Vui lòng đăng nhập để lưu việc làm.', 'warning');
    }
    setTimeout(function () {
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname.split('/').pop() + window.location.search);
    }, 650);
}

/** Copy text to clipboard */
function jfCopyText(text, label) {
    navigator.clipboard.writeText(text).then(function () {
        showToast((label || 'Đã sao chép') + ' vào bộ nhớ tạm.', 'success');
    }).catch(function () {
        showToast('Không thể sao chép. Vui lòng thử lại.', 'danger');
    });
}

/** Format number with comma */
function jfFormatNum(n) {
    return parseInt(n, 10).toLocaleString('vi-VN');
}

/* ============================================================
   11. PASSWORD VISIBILITY TOGGLE
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const wrapper = btn.closest('.password-wrapper');
            if (!wrapper) return;
            const input = wrapper.querySelector('input');
            if (!input) return;
            const show = input.getAttribute('type') === 'password';
            input.setAttribute('type', show ? 'text' : 'password');
            btn.classList.toggle('is-visible', show);
            btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        });
    });
});

/* ============================================================
   12. CUSTOM AUTOCOMPLETE DROPDOWN
   ============================================================ */
function jfInitAutocomplete(inputEl, items, options) {
    if (!inputEl || !Array.isArray(items) || !items.length) return;
    options = options || {};
    const multi = !!options.multi;
    const onSelect = typeof options.onSelect === 'function' ? options.onSelect : null;

    // Wrap input if not already wrapped
    let wrapper = inputEl.closest('.jf-autocomplete');
    if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.className = 'jf-autocomplete';
        inputEl.parentNode.insertBefore(wrapper, inputEl);
        wrapper.appendChild(inputEl);
    }

    const list = document.createElement('div');
    list.className = 'jf-autocomplete-list';
    wrapper.appendChild(list);

    function render(filterText) {
        const q = (filterText || '').trim().toLowerCase();
        const currentParts = multi ? inputEl.value.split(',').map(function(s) { return s.trim().toLowerCase(); }) : [];
        const activeToken = multi ? (inputEl.value.split(',').pop() || '').trim().toLowerCase() : q;

        const filtered = items.filter(function(item) {
            if (!activeToken) return true;
            return item.toLowerCase().indexOf(activeToken) !== -1;
        });

        list.innerHTML = '';
        if (!filtered.length) {
            list.classList.remove('open');
            return;
        }

        filtered.slice(0, 12).forEach(function(item) {
            const div = document.createElement('div');
            div.className = 'jf-autocomplete-item';
            if (multi && currentParts.indexOf(item.toLowerCase()) !== -1) {
                div.classList.add('active');
            }
            div.textContent = item;
            div.addEventListener('mousedown', function(e) {
                e.preventDefault();
                if (multi) {
                    const parts = inputEl.value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
                    // Replace last partial token if it's not already an exact item
                    if (parts.length && items.map(function(x){return x.toLowerCase();}).indexOf(parts[parts.length - 1].toLowerCase()) === -1) {
                        parts.pop();
                    }
                    if (parts.indexOf(item) === -1) {
                        parts.push(item);
                    }
                    inputEl.value = parts.join(', ');
                } else {
                    inputEl.value = item;
                }
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                if (onSelect) onSelect(item, inputEl.value);
                list.classList.remove('open');
            });
            list.appendChild(div);
        });
        list.classList.add('open');
    }

    inputEl.addEventListener('focus', function() { render(inputEl.value); });
    inputEl.addEventListener('click', function() { render(inputEl.value); });
    inputEl.addEventListener('input', function() { render(inputEl.value); });
    inputEl.addEventListener('blur', function() {
        setTimeout(function() { list.classList.remove('open'); }, 150);
    });
}

/* ============================================================
   13. LIVE CHAT SUPPORT (PHP + MYSQL + SESSIONSTORAGE)
   ============================================================ */
const JF_CHAT_STORAGE_KEY = 'jf_chat_history_v1';
const JF_CHAT_DEFAULT_HTML = '<div class="chat-msg chat-msg--bot">Xin chào! Bạn cần hỗ trợ tìm việc làm, tạo CV hay đăng tin tuyển dụng?</div>';

function jfSaveChatHistory() {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    try {
        sessionStorage.setItem(JF_CHAT_STORAGE_KEY, container.innerHTML);
    } catch (e) {}
}

function jfRestoreChatHistory() {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    try {
        const saved = sessionStorage.getItem(JF_CHAT_STORAGE_KEY);
        if (saved && saved.trim() !== '') {
            container.innerHTML = saved;
            container.scrollTop = container.scrollHeight;
        }
    } catch (e) {}
}

function chatReset() {
    const container = document.getElementById('chatMessages');
    if (container) {
        container.innerHTML = JF_CHAT_DEFAULT_HTML;
    }
    try {
        sessionStorage.removeItem(JF_CHAT_STORAGE_KEY);
    } catch (e) {}
    jfUpdateQuickReplies(['Tìm việc IT', 'Hướng dẫn tạo CV', 'Cách ứng tuyển', 'Đăng tin tuyển dụng']);
}

function jfUpdateQuickReplies(items) {
    const wrap = document.getElementById('chatQuickBtns');
    if (!wrap || !Array.isArray(items) || !items.length) return;
    wrap.innerHTML = '';
    items.forEach(function (label) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'chat-quick-btn';
        btn.textContent = label;
        btn.onclick = function () {
            if (label === 'Xem tất cả việc làm') {
                window.location.href = 'jobs.php';
                return;
            }
            if (label === 'Xem việc đã lưu') {
                window.location.href = 'saved-jobs.php';
                return;
            }
            if (label === 'Theo dõi hồ sơ') {
                window.location.href = 'my-applications.php';
                return;
            }
            chatQuickAsk(label);
        };
        wrap.appendChild(btn);
    });
}

function jfEscapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function addChatMsg(htmlOrText, type, jobs) {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'chat-msg chat-msg--' + type;
    if (type === 'user') {
        div.textContent = htmlOrText;
    } else {
        div.innerHTML = htmlOrText;
        if (Array.isArray(jobs) && jobs.length > 0) {
            const cards = document.createElement('div');
            cards.className = 'chat-job-cards';
            jobs.forEach(function (j) {
                const a = document.createElement('a');
                a.className = 'chat-job-card';
                a.href = j.url || ('job-detail.php?id=' + j.id);
                a.innerHTML =
                    '<div class="chat-job-card__title">' + jfEscapeHtml(j.title) + '</div>' +
                    '<div class="chat-job-card__meta">' +
                    '<span>' + jfEscapeHtml(j.company) + ' · ' + jfEscapeHtml(j.location) + '</span>' +
                    '<span class="chat-job-card__salary">' + jfEscapeHtml(j.salary) + '</span>' +
                    '</div>';
                cards.appendChild(a);
            });
            div.appendChild(cards);
        }
    }
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    jfSaveChatHistory();
}

function jfRequestChatReply(userMessage) {
    const container = document.getElementById('chatMessages');
    if (!container) return;

    const typing = document.createElement('div');
    typing.className = 'chat-typing';
    typing.textContent = 'JobFinder đang trả lời...';
    container.appendChild(typing);
    container.scrollTop = container.scrollHeight;

    const startTime = Date.now();
    fetch('api/chat-support.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userMessage })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        const elapsed = Date.now() - startTime;
        const delay = Math.max(0, 400 - elapsed);
        setTimeout(function () {
            if (typing.parentNode) typing.parentNode.removeChild(typing);
            addChatMsg(data.reply || 'Cảm ơn bạn đã liên hệ JobFinder.', 'bot', data.jobs || []);
            if (Array.isArray(data.quick_replies) && data.quick_replies.length) {
                jfUpdateQuickReplies(data.quick_replies);
            }
        }, delay);
    })
    .catch(function () {
        if (typing.parentNode) typing.parentNode.removeChild(typing);
        addChatMsg('Hiện kết nối tạm gián đoạn. Bạn có thể bấm vào <a href="jobs.php" class="chat-inline-link">Tìm việc làm</a> hoặc thử gửi lại câu hỏi nhé.', 'bot');
    });
}

function chatQuickAsk(q) {
    addChatMsg(q, 'user');
    jfRequestChatReply(q);
}

function chatSend() {
    const input = document.getElementById('chatInput');
    if (!input || !input.value.trim()) return;
    const msg = input.value.trim();
    input.value = '';
    addChatMsg(msg, 'user');
    jfRequestChatReply(msg);
}

document.addEventListener('DOMContentLoaded', function () {
    jfRestoreChatHistory();
});

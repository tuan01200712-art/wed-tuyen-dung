/**
 * ============================================================
 * FILE: assets/js/main.js
 * MÔ TẢ: Xử lý tương tác giao diện người dùng
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function () {
  // ----------------------------------------------------
  // 1. Mobile Menu Toggle
  // ----------------------------------------------------
  const mobileToggle = document.getElementById("mobileToggle");
  const navMenu = document.getElementById("navMenu");

  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener("click", function () {
      navMenu.classList.toggle("active");
      const icon = mobileToggle.querySelector("i");
      if (icon) {
        if (navMenu.classList.contains("active")) {
          icon.classList.remove("fa-bars");
          icon.classList.add("fa-xmark");
        } else {
          icon.classList.remove("fa-xmark");
          icon.classList.add("fa-bars");
        }
      }
    });
  }

  // ----------------------------------------------------
  // 2. User Dropdown Menu
  // ----------------------------------------------------
  const userBtn = document.getElementById("userBtn");
  const dropdownMenu = document.getElementById("dropdownMenu");

  if (userBtn && dropdownMenu) {
    userBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle("show");
    });

    // Bấm ra ngoài để đóng dropdown
    document.addEventListener("click", function (e) {
      if (!userBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove("show");
      }
    });
  }

  // ----------------------------------------------------
  // 3. Client-side Form Validation
  // ----------------------------------------------------
  // Kiểm tra form đăng ký
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
      const password = document.getElementById("password");
      const confirmPassword = document.getElementById("confirm_password");
      const name = document.getElementById("name");
      const email = document.getElementById("email");

      let errors = [];

      if (name && name.value.trim().length < 2) {
        errors.push("Họ và tên phải có ít nhất 2 ký tự.");
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email && !emailRegex.test(email.value.trim())) {
        errors.push("Địa chỉ email không đúng định dạng.");
      }

      if (password && password.value.length < 6) {
        errors.push("Mật khẩu phải có độ dài từ 6 ký tự trở lên.");
      }

      if (
        password &&
        confirmPassword &&
        password.value !== confirmPassword.value
      ) {
        errors.push("Mật khẩu và xác nhận mật khẩu không khớp nhau.");
      }

      if (errors.length > 0) {
        e.preventDefault();
        showClientAlert(errors.join("<br>"), "danger");
      }
    });
  }

  // Hiển thị alert động bằng JavaScript nếu có lỗi client
  function showClientAlert(message, type) {
    let alertBox = document.getElementById("clientAlert");
    if (!alertBox) {
      alertBox = document.createElement("div");
      alertBox.id = "clientAlert";
      alertBox.className = `alert alert-${type}`;
      const form = document.querySelector(".auth-form");
      if (form) {
        form.insertBefore(alertBox, form.firstChild);
      }
    }
    alertBox.innerHTML = `
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>${message}</div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
        `;
    alertBox.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  // ----------------------------------------------------
  // 4. Auto-dismiss Flash Alerts sau 5 giây
  // ----------------------------------------------------
  const flashAlerts = document.querySelectorAll(".flash-container .alert");
  flashAlerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = "opacity 0.5s ease, transform 0.5s ease";
      alert.style.opacity = "0";
      alert.style.transform = "translateY(-10px)";
      setTimeout(function () {
        alert.remove();
      }, 500);
    }, 5000);
  });
});

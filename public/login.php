<?php
require_once '../config/bootstrap.php'; 
include APP_ROOT . 'includes/header.php'; 
?>

<!-- Overlay pentru login -->
<div id="modalOverlayLogin" class="modal-overlay" style="display:block;"></div>

<!-- Modalul de login -->
<div id="loginModal" class="modal-login" style="display:flex;">
    <div class="modal-content-login">
        <form id="loginForm" method="POST" autocomplete="off">
            <div class="modal-header-login">
                <span class="modal-title-login"><?= lang('lang_login') ?></span>
                <span class="modal-close-login" id="closeLoginModal">&times;</span>
            </div>
            <div class="modal-body-login">
                <div class="modal-row-login">
                    <label class="modal-label-login"><?= lang('lang_login_username') ?></label>
                    <input class="modal-input-login" type="text" name="username" id="login-username" required>
                </div>
                <div class="modal-row-login">
                    <label class="modal-label-login"><?= lang('lang_login_password') ?></label>
                    <div class="password-wrapper">
                        <input class="modal-input-login" type="password" name="password" id="login-password" required>
                        <button type="button" id="login-btn-password" class="toggle-password" onclick="togglePasswordVisibility('login-password','login-btn-password')">👁️</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer-login">
                <button type="submit" class="modal-btn-register primary"><?= lang('lang_btn_login') ?? 'Login' ?></button>
            </div>
        </form>
    </div>
</div>

<style>
html, body {
  height: 100%;
  min-height: 100%;
  margin: 0;
  padding: 0;
}

</style>

<script>
const overlay = document.getElementById('modalOverlayLogin');
const modal = document.getElementById('loginModal');
const closeBtn = document.getElementById('closeLoginModal');

function closeLoginModal() {
    modal.style.display = "none";
    overlay.style.display = "none";
    window.location.href = "index.php";
}

if (closeBtn) closeBtn.onclick = closeLoginModal;
if (overlay) overlay.onclick = closeLoginModal;

// Blochează închiderea modalului la click pe fundal sau Escape
window.onclick = function(event) {
  if (event.target === modal) {
    // nu face nimic
  }
};
document.onkeydown = function(e) {
  if (e.key === "Escape") {
    e.preventDefault();
    return false;
  }
};

// Toggle password
function togglePasswordVisibility(inputId, btnId) {
  const input = document.getElementById(inputId);
  const btn = document.getElementById(btnId);
  if (input.type === "password") {
    input.type = "text";
    btn.textContent = "🙈";
  } else {
    input.type = "password";
    btn.textContent = "👁️";
  }
}

// AJAX login
document.getElementById('loginForm').onsubmit = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);
  // Adaugă aici gestionarea erorilor dacă ai nevoie
  const response = await fetch('api/bkd_login.php', {
    method: 'POST',
    body: data
  });
  const result = await response.json();
  if (result.success) {
    window.location.href = "index.php";
  } else {
    alert(result.error || "Eroare necunoscută.");
  }
};
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
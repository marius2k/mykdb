<?php
require_once '../config/bootstrap.php'; 
include APP_ROOT . 'includes/header.php'; 
?>

<!-- Overlay pentru login -->
<div id="modalOverlayLogin" class="modal-overlay" style="display:block;"></div>

<!-- Container cu Login/Register Slider -->
<div id="authContainer" class="auth-container" style="display:flex;">
    <div class="auth-box">
        <!-- Panou pentru Sign In -->
        <div class="form-container sign-in-container">
            <form id="loginForm" method="POST" autocomplete="off">
                <h1><?= lang('lang_login') ?? 'Sign In' ?></h1>
                <input type="text" name="username" id="login-username" placeholder="<?= lang('lang_login_username') ?? 'Username' ?>" required>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="login-password" placeholder="<?= lang('lang_login_password') ?? 'Password' ?>" required>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('login-password', this)">👁️</button>
                </div>
                <a href="#" class="forgot-password"><?= lang('lang_forgot_password') ?? 'Forgot your password?' ?></a>
                <button type="submit" class="auth-btn"><?= lang('lang_btn_login') ?? 'Sign In' ?></button>
            </form>
        </div>
        
        <!-- Panou pentru Sign Up -->
        <div class="form-container sign-up-container">
            <form id="registerForm" method="POST" autocomplete="off">
                <h1><?= lang('lang_reg_msg_top') ?? 'Create Account' ?></h1>
                <input type="text" name="first_name" id="first_name" placeholder="<?= lang('lang_reg_fname') ?? 'First Name' ?>" required>
                <input type="text" name="last_name" id="last_name" placeholder="<?= lang('lang_reg_lname') ?? 'Last Name' ?>" required>
                <input type="text" name="username" id="reg-username" placeholder="<?= lang('lang_reg_username') ?? 'Username' ?>" required>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="reg-password" placeholder="<?= lang('lang_reg_pass') ?? 'Password' ?>" required>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('reg-password', this)">👁️</button>
                </div>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="<?= lang('lang_reg_pass_confirm') ?? 'Confirm Password' ?>" required>
                <small id="password-match-msg" style="display: none;"></small>
                <button type="submit" class="auth-btn"><?= lang('lang_reg_btn_create') ?? 'Sign Up' ?></button>
            </form>
        </div>
        
        <!-- Panou overlay pentru slider -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1><?= lang('lang_welcome_back') ?? 'Welcome Back!' ?></h1>
                    <p><?= lang('lang_signin_msg') ?? 'To keep connected with us please login with your personal info' ?></p>
                    <button class="ghost-btn" id="signIn"><?= lang('lang_btn_login') ?? 'Sign In' ?></button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1><?= lang('lang_hello') ?? 'Hello, Friend!' ?></h1>
                    <p><?= lang('lang_signup_msg') ?? 'Enter your personal details and start journey with us' ?></p>
                    <button class="ghost-btn" id="signUp"><?= lang('lang_btn_signup') ?? 'Sign Up' ?></button>
                </div>
            </div>
        </div>
    </div>
    <span class="auth-close" id="closeAuthModal">&times;</span>
</div>

<script>
const overlay = document.getElementById('modalOverlayLogin');
const container = document.getElementById('authContainer');
const closeBtn = document.getElementById('closeAuthModal');
const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const authBox = document.querySelector('.auth-box');

// Toggle between Sign In and Sign Up
if (signUpButton) {
    signUpButton.addEventListener('click', () => {
        authBox.classList.add('right-panel-active');
    });
}

if (signInButton) {
    signInButton.addEventListener('click', () => {
        authBox.classList.remove('right-panel-active');
    });
}

function closeAuthModal() {
    container.style.display = "none";
    overlay.style.display = "none";
    window.location.href = "index.php";
}

if (closeBtn) closeBtn.onclick = closeAuthModal;
if (overlay) overlay.onclick = closeAuthModal;

// Toggle password visibility
function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === "password") {
    input.type = "text";
    btn.textContent = "🙈";
  } else {
    input.type = "password";
    btn.textContent = "👁️";
  }
}

// Password validation for register form
const password = document.getElementById('reg-password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');
const registerSubmitBtn = document.querySelector('#registerForm button[type="submit"]');

function validatePasswords() {
    if (confirmPassword.value.length === 0) {
        message.style.display = "none";
        if (registerSubmitBtn) registerSubmitBtn.disabled = false;
        return;
    }
    if (password.value !== confirmPassword.value) {
        message.style.display = "block";
        message.textContent = "<?= lang('lang_reg_pass_nok') ?? 'Passwords do not match' ?>";
        message.style.color = "red";
        if (registerSubmitBtn) registerSubmitBtn.disabled = true;
    } else {
        message.style.display = "block";
        message.textContent = "<?= lang('lang_reg_pass_ok') ?? 'Passwords match' ?> ✔️";
        message.style.color = "green";
        if (registerSubmitBtn) registerSubmitBtn.disabled = false;
    }
}

if (password) password.addEventListener('input', validatePasswords);
if (confirmPassword) confirmPassword.addEventListener('input', validatePasswords);

// AJAX login
document.getElementById('loginForm').onsubmit = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);
  const response = await fetch('api/bkd_login.php', {
    method: 'POST',
    body: data
  });
  const result = await response.json();
  if (result.success) {
    window.location.href = "index.php";
  } else {
    alert(result.error || "<?= lang('lang_error_unknown') ?? 'Unknown error' ?>");
  }
};

// AJAX register
document.getElementById('registerForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    const response = await fetch('api/bkd_register.php', {
        method: 'POST',
        body: data
    });
    const result = await response.json();
    if (result.success) {
        alert("<?= lang('lang_reg_success') ?? 'Account created successfully! You can now login.' ?>");
        authBox.classList.remove('right-panel-active');
        form.reset();
    } else {
        alert(result.errors ? result.errors.join('\n') : "<?= lang('lang_error_unknown') ?? 'Unknown error' ?>");
    }
};
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
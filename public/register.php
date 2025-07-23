<?php
require_once '../config/bootstrap.php';

$ops = ['register'];
if (!hasPermission($_SESSION['user']['id'], $ops)) {
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';
    $id = $_SESSION['user']['id'];
    echo "<script>
            alert('⚠️ Access Denied User ID: $id');
            window.location.href = '$referer';
        </script>";
    exit;
}
?>

<?php include APP_ROOT . 'includes/header.php'; ?>



<!-- Modalul de înregistrare, stil similar cu Categories -->
<div id="registerModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:400px;">
    <div class="modal-header">
      <span class="close" id="closeRegisterModal">&times;</span>
      <h2>📝 <?= lang('lang_reg_msg_top') ?></h2>
    </div>
    <div class="modal-body">
      <div id="register-errors" class="form-errors" style="display:none;"></div>
      <form id="registerForm" class="form-styled" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="first_name"><?= lang('lang_reg_fname') ?></label>
            <input type="text" name="first_name" id="first_name" required>
        </div>
        <div class="form-group">
            <label for="last_name"><?= lang('lang_reg_lname') ?></label>
            <input type="text" name="last_name" id="last_name" required>
        </div>
        <div class="form-group">
            <label for="username"><?= lang('lang_reg_username') ?></label>
            <input type="text" name="username" id="username" required>
        </div>
        <div class="form-group password-toggle-group">
            <label for="password"><?= lang('lang_reg_pass') ?></label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required>
                <button type="button" id="btn-password" class="toggle-password" onclick="togglePasswordVisibility('password','btn-password')">👁️</button>
            </div>
        </div>
        <div class="form-group password-toggle-group">
            <label for="confirm_password"><?= lang('lang_reg_pass_confirm') ?></label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <small id="password-match-msg" style="color: red; display: none;"><?= lang('lang_reg_pass_nomatch') ?></small>
        </div>
        <button type="submit" class="btn-primary full-width"><?= lang('lang_reg_btn_create') ?></button>
      </form>
    </div>
  </div>
</div>

<!-- Modal CSS (poți adapta după stilul tău de la Categories) -->
<style>
.modal {
  display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%;
  overflow: auto; background-color: rgba(0,0,0,0.4);
}
.modal-content {
  background: #fff; margin: 5% auto; border-radius: 8px; padding: 0; box-shadow: 0 2px 8px #0002;
  animation: fadeIn .2s;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal-header {
  padding: 1em 1.5em; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;
}
.modal-header h2 { margin: 0; font-size: 1.3em; }
.close {
  font-size: 1.5em; cursor: pointer; color: #888; transition: color .2s;
}
.close:hover { color: #d00; }
.modal-body { padding: 1.5em; }
.form-errors { background: #ffeaea; color: #b00; border-radius: 4px; padding: .5em 1em; margin-bottom: 1em; }
.form-styled .form-group { margin-bottom: 1em; }
.form-styled label { display: block; margin-bottom: .3em; }
.form-styled input[type="text"], .form-styled input[type="password"] {
  width: 100%; padding: .5em; border: 1px solid #ccc; border-radius: 4px;
}
.btn-primary.full-width { width: 100%; }
.password-wrapper { display: flex; align-items: center; }
.toggle-password { margin-left: .5em; background: none; border: none; cursor: pointer; font-size: 1.1em; }
</style>

<script>
// Modal logic
const modal = document.getElementById('registerModal');
const openBtn = document.getElementById('openRegisterModal');
const closeBtn = document.getElementById('closeRegisterModal');
if (openBtn) {
  openBtn.onclick = () => modal.style.display = "block";
}
if (closeBtn) {
  closeBtn.onclick = () => modal.style.display = "none";
}
window.onclick = (event) => { if (event.target == modal) modal.style.display = "none"; };

// Password validation
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');
const submitBtn = document.querySelector('#registerForm button[type="submit"]');
function validatePasswords() {
    if (confirmPassword.value.length === 0) {
        message.style.display = "none";
        submitBtn.disabled = false;
        return;
    }
    if (password.value !== confirmPassword.value) {
        message.style.display = "block";
        message.textContent = "<?= lang('lang_reg_pass_nok') ?>";
        message.style.color = "red";
        submitBtn.disabled = true;
    } else {
        message.style.display = "block";
        message.textContent = "<?= lang('lang_reg_pass_ok') ?> ✔️";
        message.style.color = "green";
        submitBtn.disabled = false;
    }
}
password.addEventListener('input', validatePasswords);
confirmPassword.addEventListener('input', validatePasswords);

// AJAX submit
document.getElementById('registerForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const errorsDiv = document.getElementById('register-errors');
    errorsDiv.style.display = "none";
    errorsDiv.innerHTML = "";

    const response = await fetch('api/bkd_register.php', {
        method: 'POST',
        body: data
    });
    const result = await response.json();
    if (result.success) {
        modal.style.display = "none";
        alert("Cont creat cu succes! Poți să te loghezi.");
        window.location.href = "login.php";
    } else {
        errorsDiv.style.display = "block";
        errorsDiv.innerHTML = result.errors.map(e => `<p>${e}</p>`).join('');
    }
};
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
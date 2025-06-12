<?php
//require_once '../config/config.php';
//require_once '../config/db.php';

require_once '../config/bootstrap.php';

$ops = ['register'];


if (!hasPermission($_SESSION['user']['id'],$ops)) {
    


    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';
    $id = $_SESSION['user']['id'];

    echo "<script>
            alert('⚠️ Access Denied User ID: $id');
            window.location.href = '$referer';
        </script>";
    exit;     
}



$errors = [];

$db = new Database();


$role = $db->fetchSingle("SELECT id FROM roles WHERE name = 'moderator'");

//echo "Role ID for moderator: " . $role['id'] . "<br>";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // get the role id for moderator - default role used at registration
    $role = $db->fetchSingle("SELECT id FROM roles WHERE name = 'moderator'");



    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['password'];
    //$role = $_POST['role'];
    $status = 'pending';
    //$confirm = $_POST['confirm'];

    $confirm_password = trim($_POST['confirm_password']);

    /*
    if ($password !== $confirm_password) {
        $errors[] = "Parolele nu coincid.";
    }
    */


    if (empty($username) || empty($password)) {
        $errors[] = 'Completează toate câmpurile.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Parolele nu se potrivesc.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Utilizatorul există deja.';
        } else {
            
            // by default, the user is registered as "moderator" with status "pending"

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, first_name, last_name, password, status, role_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $first_name, $last_name, $hash, $status, $role]);

            logActivity($db->lastInsertedId(), 'register_user', 'User registered: ' . $username);
            header('Location: login.php');
            exit;
        }
    }
}
?>


<?php include APP_ROOT . 'includes/header.php'; ?>

<!-- Simple HTML register form -->

<div class="form-card">
    <h2>📝 <?= lang('lang_reg_msg_top') ?></h2>

    <?php if (!empty($errors)): ?>
        <div> class="form-errors">
            <?php foreach ($errors as $e): ?>
                <p><?= escape($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-styled">
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
        <input type="hidden" name="role" value="moderator"> <!-- by defaul user is "moderator", user_id=3 -->
        <button type="submit" class="btn-primary full-width" ><?= lang('lang_reg_btn_create') ?></button>
    </form>
</div>
<script>

const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');
const submitBtn = document.querySelector('button[type="submit"]');

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


</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
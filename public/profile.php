<?php
require_once '../config/bootstrap.php'; 

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

echo "USER ID: " . $_SESSION['user']['id'] . "<br>";

$db = new Database();
$userId = $_SESSION['user']['id'];
$user = $db->fetchSingle("SELECT username, email, first_name, last_name, role FROM users WHERE id = ?", [$userId]);

if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $email     = trim($_POST['email']);
    $role     = trim($_POST['role'] ?? 'user');

    $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?", [
        $firstName, $lastName, $email, $userId
    ]);

    $_SESSION['flash'] = "Profil actualizat cu succes.";
    $_SESSION['user']['first_name'] = $firstName;
    $_SESSION['user']['last_name'] = $lastName;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['role'] = $role;

    header('Location: profile.php');
    exit;
}

// Procesare schimbare parolă
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Preia parola actuală
    $row = $db->fetchSingle("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!password_verify($currentPassword, $row['password'])) {
        $_SESSION['flash'] = "Parola actuală este incorectă.";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['flash'] = "Noua parolă și confirmarea nu se potrivesc.";
    } elseif (strlen($newPassword) < 6) {
        $_SESSION['flash'] = "Parola trebuie să aibă cel puțin 6 caractere.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId]);
        $_SESSION['flash'] = "Parola a fost actualizată cu succes.";
    }

    //$_SESSION['user']['first_name'] = $firstName;
    //$_SESSION['user']['last_name'] = $lastName;
    //$_SESSION['user']['email'] = $email;
    //$_SESSION['user']['role'] = $role;

    header("Location: profile.php");
    exit;
}

//$_SESSION['user']['first_name'] = $firstName;
//$_SESSION['user']['last_name'] = $lastName;
//$_SESSION['user']['email'] = $email;
//$_SESSION['user']['role'] = $role;

?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="container mt-4" style="max-width: 600px;">
    <h4>Modificare date profil</h4>
    
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-success"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
    <?php endif; ?>
   

    <form method="post" class="form-styled">
        <div class="form-group">
            <label>Prenume</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>">
        </div>

        <div class="form-group">
            <label>Nume</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <button type="submit" name="update_profile" class="btn btn-primary">Salvează modificările</button>
    </form>


    <hr>
    <h4>Schimbare parola</h4>
    <form method="post" class="form-styled">
        <div class="form-group password-toggle-group">
            <label for="current_password">Parola actuală</label>
            <div class="password-wrapper">    
                <input type="password" name="current_password" id="current_password" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">👁️</button>
            </div>
        </div>
        <div class="form-group password-toggle-group">
            <label for="new_password">Parolă nouă</label>
            <div class="password-wrapper">
                <input type="password" name="new_password" id="new_password" required>
            </div>  
        </div>
        <div class="form-group password-toggle-group">
            <div><label for="confirm_password">Confirmă noua parolă</label></div>
            <div class="password-wrapper">    
                <input type="password" name="confirm_password" class="confirm_password" id="confirm_password" required>
                <small id="password-match-msg" style="color: red; display: none;">Parolele nu coincid</small>
            </div>
        </div>

       
        <button type="submit" id="change_password" name="change_password" class="btn btn-primary">Actualizează parola</button>
    </form>

</div>
<script>


const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');
const submitBtn = document.querySelector('button[id="change_password"]');

function validatePasswords() {
    if (confirmPassword.value.length === 0) {
        message.style.display = "none";
        submitBtn.disabled = false;
        return;
    }

    if (newPassword.value !== confirmPassword.value) {
        message.style.display = "block";
        message.textContent = "Parolele nu coincid";
        message.style.color = "red";
        submitBtn.disabled = true;
    } else {
        message.style.display = "block";
        message.textContent = "Parolele coincid ✔️";
        message.style.color = "green";
        submitBtn.disabled = false;
    }
}

newPassword.addEventListener('input', validatePasswords);
confirmPassword.addEventListener('input', validatePasswords);
</script>


<?php include APP_ROOT . 'includes/footer.php'; ?>
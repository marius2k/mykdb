<?php
//require_once '../config/config.php';
//require_once '../config/db.php';

require_once '../config/bootstrap.php';

$errors = [];

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['password'];
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
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, first_name, last_name, password, role, status) VALUES (?, ?, ?, ?, 'user', 'pending')");
            $stmt->execute([$username, $first_name, $last_name, $hash]);
            header('Location: login.php');
            exit;
        }
    }
}
?>


<?php include APP_ROOT . 'includes/header.php'; ?>

<!-- Simple HTML register form -->

<div class="form-card">
    <h2>📝 Înregistrare cont nou</h2>

    <?php if (!empty($errors)): ?>
        <div> class="form-errors">
            <?php foreach ($errors as $e): ?>
                <p><?= escape($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-styled">
        <div class="form-group">
            <label for="first_name">Prenume:</label>
            <input type="text" name="first_name" id="first_name" required>
        </div>

        <div class="form-group">
            <label for="last_name">Nume:</label>
            <input type="text" name="last_name" id="last_name" required>
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
        </div>

        <div class="form-group password-toggle-group">
            <label for="password">Parolă:</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required>
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">👁️</button>
            </div>
        </div>

        <div class="form-group password-toggle-group">
            <label for="confirm_password">Confirmă parolă:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <small id="password-match-msg" style="color: red; display: none;">Parolele nu coincid</small>
        </div>

        <button type="submit" class="btn-primary full-width">Creează cont</button>
    </form>
</div>


<?php include APP_ROOT . 'includes/footer.php'; ?>
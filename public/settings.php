<?php
require_once '../config/bootstrap.php';


if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$db = new Database();

$userId = $_SESSION['user']['id'];
$settings = new UserSettings($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['lang'] ?? 'en';
    $theme = $_POST['theme']?? 'light';

    $settings->set('language', $lang, $userId);
    $settings->set('theme', $theme, $userId);
    
    // reload settings
    $currentSettings = $settings->getAll($userId);
    //$_SESSION['flash'] = 'Setările au fost salvate.';
    header("Location: settings.php");
    exit;
}

$currentSettings = $settings->getAll($userId);
$lang = $currentSettings['language'];
$theme = $currentSettings['theme'];
$_SESSION['settings'] = $currentSettings;



// Load user settings
     
//$db = new Database();
//$userSettings = new UserSettings($db);
//$_SESSION['settings'] = $userSettings->getAll($_SESSION['user']['id']);


//$currentSettings = $settings->getAll($userId);




?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="container">
    <h2>Setările mele</h2>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <form method="post" class="settings-form">
        <div class="form-group">
            <label for="lang">Limba preferată</label>
            <select name="lang" id="lang" class="form-control">
                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                <option value="ro" <?= $lang === 'ro' ? 'selected' : '' ?>>Română</option>
            </select>
        </div>

        <div class="form-group">
            <label for="theme">Tema interfeței</label>
            <select name="theme" id="theme" class="form-control">
                <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Luminoasă</option>
                <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Întunecată</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Salvează</button>
    </form>
</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>

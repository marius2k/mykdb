<?php


require_once '../config/bootstrap.php';
//require_once '../classes/UserSettings.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    http_response_code(403);
    exit;
}

$db = new Database();
$theme = $_POST['theme'] ?? null;
$lang = $_POST['lang'] ?? null;

$settings = new UserSettings($db);

if ($theme) {
    $settings->set('theme', $theme, $userId);
    $_SESSION['theme'] = $theme;
}

if ($lang) {
    $settings->set('language', $lang, $userId);
    $_SESSION['language'] = $lang;
}

// După ce am aplicat setările (ex: salvate în DB) ma intorc la pagina de unde am venit
$redirectTo = '/index.php'; // fallback implicit

if (!empty($_POST['redirect_back'])) {
    $url = filter_var($_POST['redirect_back'], FILTER_SANITIZE_URL);

    // Validare basică: trebuie să înceapă cu "/" ca să nu fie redirect extern
    if (strpos($url, '/') === 0) {
        $redirectTo = $url;
    }
}

header("Location: $redirectTo");
exit;



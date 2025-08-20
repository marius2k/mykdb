<?php


require_once '../config/bootstrap.php';
//require_once '../classes/UserSettings.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['user']['role'] === 'guest') {
    
    $_SESSION['settings']['theme'] = $_POST['theme'] ?? $_GET['theme'] ?? null;
    $_SESSION['settings']['language'] = $_POST['lang'] ?? $_GET['lang'] ?? null;
    
    //header("Location:".APP_URL. "public/index.php");
    //exit;
    
} else{


        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            http_response_code(403);
            exit;
        }

        $db = new Database();
        $theme = $_POST['theme'] ?? $_GET['theme'] ?? null;
        $lang = $_POST['lang'] ?? $_GET['lang'] ?? null;

        //echo "Tema selectat:".$theme;
        //echo "<br>Limba selectata:".$lang;


        $settings = new UserSettings($db);

        if ($theme) {
            $settings->set('theme', $theme, $userId);
            $_SESSION['settings']['theme'] = $theme;
        }

        if ($lang) {
            $settings->set('language', $lang, $userId);
            $_SESSION['settings']['language'] = $lang;
        }


}

// După ce am aplicat setările (ex: salvate în DB) ma intorc la pagina de unde am venit

$redirectTo = '/index.php'; // fallback implicit

if (!empty($_POST['redirect_back']) || !empty($_GET['redirect_back'])) {
    $url = filter_var($_POST['redirect_back'] ?? $_GET['redirect_back'], FILTER_SANITIZE_URL);

    // Validare basică: trebuie să înceapă cu "/" ca să nu fie redirect extern
    if (strpos($url, '/') === 0) {
          $redirectTo = $url;
    }
}

header("Location: $redirectTo");
exit;

?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//$currentPage = ""
//loadConfig();

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


// Load user settings


if (isset($_SESSION['user']) && !isset($_SESSION['settings'])) {
    $db = new Database();
    $userSettings = new UserSettings($db);
    $_SESSION['settings'] = $userSettings->getAll($_SESSION['user']['id']);
    echo "user: set; settings: not set";
}

$theme = $_SESSION['settings']['theme'] ?? 'light';
$lang = $_SESSION['settings']['language'] ?? 'en';

//echo "<body class='theme-$theme'>";
//echo "Settings->Theme: ".$theme;
//echo " Settings->Lang: ".$lang;

switch ($lang) {
    case 'ro':
        require_once APP_ROOT . 'assets/lang/ro.php';
        break;
    case 'en':
        require_once APP_ROOT . 'assets/lang/en.php';
        break;
    default:
        require_once APP_ROOT . 'assets/lang/en.php';
        break;
}

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL?>assets/css/style-<?=$theme?>.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.min.js"></script>

</head>
<body>


<div class="app-header">
<!-- 
<div style="background-image: url('<?= APP_URL ?>/assets/images/kdb_top.jpeg');background-size: cover; background-position: center; background-repeat: no-repeat; padding:10px;">    
-->
    <div style="width: 100%; display: flex; align-items: center; justify-content: space-between;">
        <div class="nav-app-name">🧠 <?= APP_NAME; ?></div>
        <div style="float: right;">

            <form id="user-settings-form" method="post" action="<?=APP_URL?>public/update_settings.php">
                <em class="settings-bar-text"><?=lang_select_theme?></em>
                <select name="theme" onchange="this.form.submit()" class="settings-dropdown">
                    <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Light</option>
                    <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Dark</option>
                </select>
                <em class="settings-bar-text"><?=lang_select_language?></em>
                <select name="lang" onchange="this.form.submit()" class="settings-dropdown">
                    <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>><?=lang_select_english?></option>
                    <option value="ro" <?= $lang === 'ro' ? 'selected' : '' ?>><?=lang_select_romanian?></option>
                    <!-- adaugă alte limbi dacă e cazul -->
                </select>
                <input type="hidden" name="redirect_back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            </form>

        </div>
        
    </div>
    
</div>

        <div class="topnav">
            <div style="float: left; width: 70%">
            <?php

                $role = $_SESSION['user']['role'] ?? null;
                $navbar = generateNavBar($role); 
                echo $navbar;
            ?>
            </div>
            
        </div>   
        


<main style="padding:20px;">

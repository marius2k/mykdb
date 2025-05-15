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


//echo "header.php: theme:" . $theme . "language: ".$lang;


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

            <!-- User Dropdown -->
            <?php if (is_logged_in()) { ?>
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="me-2 d-none d-md-block text-end">
                                    <div style="color: white; font-size: 15px;"><?= htmlspecialchars($_SESSION['user']['first_name']. " ".$_SESSION['user']['last_name']  ?? 'User') ?></div>
                                    <div style="color: gainsboro; font-size: 12px;"><?= ucfirst($_SESSION['user']['role'] ?? 'user') ?></div>
                                </div>
                                <?php
                                       if (isset($_SESSION['user']['profile_picture']) && $_SESSION['user']['profile_picture'] != '') {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/' . $_SESSION['user']['profile_picture'];
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        } else {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/default-profile.png';
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        }
                                ?>


                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                    <?php
                                        $aMenu = generateAvatarMenu($_SESSION['user']['role']);
                                        echo $aMenu;
                                    ?>
                            </ul>
            <?php } else { ?>


                                <a class="nav-link" href="<?php APP_URL ?>login.php">
                                    <i class="bi bi-box-arrow-in-right fs-4"></i>
                                    <div style="color: white; font-size: 15px;"><?=lang_login?></div>
                                </a>
            <?php } ?> 
           
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
            <div style="float: right; padding-right: 10px; padding-top: 5px; justify-content: space-between;">

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
        


<main style="padding:20px;">

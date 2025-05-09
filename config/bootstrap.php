<?php
// ✅ Global root path of the app
define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Load essentials
require_once APP_ROOT . 'config/config.php';
require_once APP_ROOT . 'config/db.php';
require_once APP_ROOT . 'classes/database.php';
require_once APP_ROOT . 'classes/usersettings.php';
require_once APP_ROOT . 'includes/functions.php';
require_once APP_ROOT . 'includes/auth.php';



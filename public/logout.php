<?php
//session_start();


require_once '../config/bootstrap.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logActivity($_SESSION['user']['id'] ?? null, 'logout', 'User logged out');
session_destroy();

header('Location: login.php');
exit;

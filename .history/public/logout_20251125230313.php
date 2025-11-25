<?php
//session_start();


require_once '../config/bootstrap.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set user as offline before logging out
if (isset($_SESSION['user']['id'])) {
    $db = new Database();
    $db->query("UPDATE users SET online = 0 WHERE id = ?", [$_SESSION['user']['id']]);
}

logActivity($_SESSION['user']['id'] ?? null, 'logout', 'User logged out');
session_destroy();

header('Location: index.php');
exit;

<?php
require_once '../../config/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
    exit;
}

$db = new Database();


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
logActivity($_GET['id'], 'user_restored', 'User restored: ' . $_SESSION['user']['username']);
header('Location: users.php');
exit;
?>
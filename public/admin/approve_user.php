<?php
require_once '../../config/bootstrap.php';
//require_admin();

$ops = ['approve_user'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}


$db = new Database();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logActivity($_GET['id'], 'user_approved', 'User approved: ' . $_SESSION['user']['username']);
}

header('Location: users.php');
exit;

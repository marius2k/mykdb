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

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
    exit;
}


$db = new Database();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logActivity($_GET['id'], 'user_approved', 'User approved: ' . $_SESSION['user']['username']);
}

// creez o inregistrate in tabela user_configuration folosita pentru sistemul de notificari

$userId = getUserNameById($_GET['id']);

 // Creare înregistrare în user_configuration
    $stmt = $db->prepare("
        INSERT INTO user_configuration (user_id, last_processed_activity_log_id)
        VALUES (:user_id, NULL)
    ");

    $stmt->execute([':user_id' => $userId]);


header('Location: users.php');
exit;

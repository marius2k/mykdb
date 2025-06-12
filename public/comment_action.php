<?php
require_once '../config/bootstrap.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : ('view_comment.php?id=' . $commentId);


$action = $_POST['action'] ?? '';
$validActions = ['approve', 'reject', 'delete'];

if (!in_array($action, $validActions, true)) {
    $_SESSION['message'] = 'Acțiune invalidă!';
    header('Location: ' . $redirect);    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['message'] = 'Token CSRF invalid!';
    header('Location: ' . $redirect);
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['message'] = 'ID comentariu invalid!';
    header('Location: ' . $redirect);
    exit;
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    $_SESSION['message'] = 'ID user invalid!';
    header('Location: ' . $redirect);
    exit;
}

$lang = $_SESSION['settings']['language'] ?? 'en';



//echo " Settings->Lang: ".$lang;

$langFile = APP_ROOT . "assets/lang/{$lang}.php";
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include APP_ROOT . "assets/lang/en.php";
}


$commentId = intval($_POST['id']);
$userId = intval($_POST['user_id']);
$userRole = getUserRoleById($userId);
$db = new Database();

try {
    if ($action === 'approve') {
        $sql = "UPDATE article_comments SET status = 'approved' WHERE id = :id";
        $stmt = $db->getPdo()->prepare($sql);
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['message'] = 'Comentariul a fost aprobat cu succes!';
        $op=lang('lang_com_op_approved');
        $msg=lang('lang_com_approved');

        if ($userRole <> 'guest') {
            sendNotification($userId,$op, $msg, 'info');
        }

    } elseif ($action === 'reject') {
        $sql = "UPDATE article_comments SET status = 'rejected' WHERE id = :id";
        $stmt = $db->getPdo()->prepare($sql);
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['message'] = 'Comentariul a fost respins!';
        
        if ($userRole <> 'guest') {
            sendNotification($userId,lang('lang_com_op_rejected'), lang('lang_com_rejected'), 'info');
        }

    } elseif ($action === 'delete') {
        $sql = "DELETE FROM article_comments WHERE id = :id";
        $stmt = $db->getPdo()->prepare($sql);
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['message'] = 'Comentariul a fost șters!';
        
        if ($userRole <> 'guest') {
            sendNotification($userId,lang('lang_com_op_deleted'), lang('lang_com_deleted'), 'info');
        }
        
    }
} catch (PDOException $e) {
    $_SESSION['message'] = 'Eroare la procesarea acțiunii: ' . htmlspecialchars($e->getMessage());
}

//$redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : ('view_comment.php?id=' . $commentId);
header('Location: ' . $redirect);
exit;

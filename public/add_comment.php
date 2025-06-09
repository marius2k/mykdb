<?php
require_once '../config/bootstrap.php';

header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);



if (!is_logged_in()) {
  echo "<script>alert('Autentificare necesară.'); history.back();</script>";
  exit;
}


$ops=['add_comment'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}

/*
if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
    exit;
}
*/


$articleId = (int)$_POST['article_id'];
$content = trim($_POST['content']);
$userId = $_SESSION['user']['id'];

if ($content === '') {
  echo "<script>alert('Comentariul nu poate fi gol.'); history.back();</script>";
  exit;
}


//debuging AJAX request

file_put_contents('debug_comments.log', json_encode([
  'POST' => $_POST,
  'RAW' => file_get_contents('php://input'),
  'SESSION' => $_SESSION ?? 'no session'
]) . PHP_EOL, FILE_APPEND);



$db = new Database();

$db->query("INSERT INTO article_comments (article_id, user_id, content) VALUES (?, ?, ?)", [
  $articleId, $userId, $content
]);

echo json_encode(['status' => 'ok']);
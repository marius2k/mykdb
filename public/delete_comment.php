<?php
require_once '../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['message'] = 'Token CSRF invalid!';
    header('Location: view_comment.php?id=' . ($_POST['id'] ?? ''));
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['message'] = 'ID comentariu invalid!';
    header('Location: view_comment.php');
    exit;
}

$commentId = intval($_POST['id']);
$db = new Database();

try {
    $sql = "DELETE FROM article_comments WHERE id = :id";
    $stmt = $db->getPdo()->prepare($sql);
    $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
    $stmt->execute();
    $_SESSION['message'] = 'Comentariul a fost șters!';
} catch (PDOException $e) {
    $_SESSION['message'] = 'Eroare la ștergerea comentariului: ' . htmlspecialchars($e->getMessage());
}

$redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : ('view_comment.php?id=' . $commentId);
header('Location: ' . $redirect);
exit;

?>

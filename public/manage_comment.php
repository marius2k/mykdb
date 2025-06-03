<?php
require_once '../config/bootstrap.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? 0;
$action = $_POST['action'] ?? '';
$commentId = (int)($_POST['comment_id'] ?? 0);

if (!$userId || !$commentId || !in_array($action, ['disable', 'delete'])) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
  exit;
}

$db = new Database();

// Verifică permisiunile
$requiredPermission = $action === 'delete' ? 'delete_comment' : 'reject_comment';
if (!hasPermission($userId, [$requiredPermission])) {
  echo json_encode(['status' => 'error', 'message' => 'Access denied']);
  exit;
}

if ($action === 'disable') {
    
    $stmt=$db->prepare("UPDATE article_comments SET status = 'rejected' WHERE id = ?");
    $stmt->execute( [$commentId]);

} elseif ($action === 'delete') {
    
    $stmt=$db->prepare("DELETE FROM article_comments WHERE id = ?");
    $stmt->execute([$commentId]);
}

echo json_encode(['status' => 'ok']);
?>
<?php

require_once '../config/bootstrap.php';


header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$db=new Database();


$log = $db->fetchSingle("SELECT * FROM activity_log WHERE id = ?", [$id]);

if (!$log) {
  echo json_encode(['status' => 'error', 'message' => 'Log inexistent']);
  exit;
}


$isOwner = ($log['user_id'] == $userId);
$isAdmin = hasAllPermission($userId, ['delete_all_logs', 'archive_all_logs']);
$canDelete = $isOwner || hasPermission($userId, ['delete_all_logs']);
$canArchive = $isOwner || hasPermission($userId, ['archive_all_logs']);

if ($action === 'delete' && $canDelete) {
  $db->query("DELETE FROM activity_log WHERE id = ?", [$id]);
  echo json_encode(['status' => 'ok']);
} elseif ($action === 'archive' && $canArchive) {
  $db->query("UPDATE activity_log SET archived = 1 WHERE id = ?", [$id]);
  echo json_encode(['status' => 'ok']);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Acces interzis']);
}

?>
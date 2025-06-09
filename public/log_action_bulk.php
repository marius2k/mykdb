<?php

require_once '../config/bootstrap.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$data = json_decode(file_get_contents('php://input'), true);

$logIds = $data['log_ids'] ?? [];
$action = $data['action'] ?? '';

if (!in_array($action, ['archive', 'delete']) || !is_array($logIds)) {
  echo json_encode(['status' => 'error', 'message' => 'Cerere invalidă']);
  exit;
}

$isAdmin = hasPermission($userId, ['delete_all_logs', 'archive_all_logs']);
$canDeleteOwn = hasPermission($userId, ['delete_log']);
$canArchiveOwn = hasPermission($userId, ['archive_log']);

$affected = 0;
$db = new Database();

foreach ($logIds as $logId) {
  $log = $db->fetchSingle("SELECT * FROM activity_log WHERE id = ?", [$logId]);

  if (!$log) continue;

  $isOwner = ($log['user_id'] == $userId);

  if ($action === 'delete' && ($isAdmin || ($isOwner && $canDeleteOwn))) {
    $db->query("DELETE FROM activity_log WHERE id = ?", [$logId]);
    $affected++;
  } elseif ($action === 'archive' && ($isAdmin || ($isOwner && $canArchiveOwn))) {
    $db->query("UPDATE activity_log SET archived = 1 WHERE id = ?", [$logId]);
    $affected++;
  }
}

echo json_encode([
  'status' => 'ok',
  'message' => "S-au procesat $affected log(uri)."
]);
?>
<?php
require_once '../config/bootstrap.php';



ini_set('display_errors', 1);
error_reporting(E_ALL);

$userId = (int)($_POST['user_id'] ?? 0);
$roleId = (int)($_POST['role_id'] ?? 0);

// VALIDARE simplă
if (!$userId || !$roleId) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Parametri lipsă']);
  exit;
}

$db = new Database();

// Verifică dacă userul există
$userExists = $db->fetchSingle("SELECT COUNT(*) FROM users WHERE id = ?", [$userId]);
if (!$userExists) {
  http_response_code(404);
  echo json_encode(['status' => 'error', 'message' => 'User inexistent']);
  exit;
}

// Update
$stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
$stmt->execute([$roleId, $userId]);

$newLabel = $db->fetchSingle("SELECT label FROM roles WHERE id = ?", [$roleId]);

echo json_encode([
  'status' => 'ok',
  'new_role_label' => $newLabel
]);
?>
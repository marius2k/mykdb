<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (empty($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user']['id'];
$notifId = (int)($_POST['id'] ?? 0);

if (!$notifId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$db = new Database();
$notification = $db->fetchSingle("SELECT * FROM notifications WHERE id = ? AND user_id = ?", [$notifId, $userId]);
if (!$notification) {
    echo json_encode(['success' => false, 'error' => 'Notificarea nu există sau nu îți aparține']);
    exit;
}

$db->query("DELETE FROM notifications WHERE id = ?", [$notifId]);
echo json_encode(['success' => true]);
?>
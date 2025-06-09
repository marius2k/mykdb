
<?php
require_once '../config/bootstrap.php';
header('Content-Type: application/json');

// Verificăm login
if (empty($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$db = new Database();

$userId = $_SESSION['user']['id'];
$action = $_POST['action'] ?? '';
$notifId = (int)($_POST['id'] ?? 0);

// Validări simple
if (!$notifId || !in_array($action, ['mark_read', 'delete'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Verificăm dacă notificarea aparține userului (sau dacă e admin/superadmin)
$notification = $db->fetchSingle("SELECT * FROM notifications WHERE id = ? AND user_id = ?", [$notifId, $userId]);

if (!$notification) {
    echo json_encode(['status' => 'error', 'message' => 'Notificarea nu există sau nu îți aparține']);
    exit;
}

// Executăm acțiunea
if ($action === 'mark_read') {
    $db->query("UPDATE notifications SET is_read = 1 WHERE id = ?", [$notifId]);
} elseif ($action === 'delete') {
    $db->query("DELETE FROM notifications WHERE id = ?", [$notifId]);
}

// Recăutăm câte notificări necitite are userul
$countUnread = $db->fetchSingle("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
$remaining = $countUnread['total'] ?? 0;

echo json_encode([
    'status' => 'ok',
    'remaining' => $remaining
]);
?>
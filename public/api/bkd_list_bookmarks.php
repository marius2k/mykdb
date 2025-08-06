<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_list_bookmarks.php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$db = new Database();
$rows = $db->fetchAll("
    SELECT a.id, a.title, a.created_at, a.updated_at, c.icon, c.name AS category
    FROM user_bookmarks b
    JOIN articles a ON b.article_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
", [$userId]);

echo json_encode(['success' => true, 'bookmarks' => $rows]);
?>
<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_toggle_bookmark.php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? null;
$articleId = intval($_POST['article_id'] ?? 0);

if (!$userId || !$articleId) {
    echo json_encode(['success' => false, 'error' => 'Date invalide']);
    exit;
}

$db = new Database();
$exists = $db->fetchSingle("SELECT id FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
if ($exists) {
    $db->query("DELETE FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
    echo json_encode(['success' => true, 'bookmarked' => false]);
} else {
    $db->query("INSERT INTO user_bookmarks (user_id, article_id) VALUES (?, ?)", [$userId, $articleId]);
    echo json_encode(['success' => true, 'bookmarked' => true]);
}
?>
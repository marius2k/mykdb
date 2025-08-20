<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (empty($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

//$userId = $_SESSION['user']['id'];
$commentId = (int)($_POST['id'] ?? 0);

if (!$commentId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$db = new Database();
$comment = $db->fetchSingle("SELECT * FROM article_comments WHERE id = ?", [$commentId]);
if (!$comment) {
    echo json_encode(['success' => false, 'error' => 'Comment does not exist']);
    exit;
}

// get article title for gamification
$articleTitle = $db->fetchSingle("SELECT title FROM articles WHERE id = ?", [$comment['article_id']]);

$db->query("UPDATE article_comments SET status = 'approved' WHERE id = ?", [$commentId]);

// add gamification points
awardCommentAdded($_SESSION['user']['id'], $commentId, $articleTitle);
echo json_encode(['success' => true]);
?>
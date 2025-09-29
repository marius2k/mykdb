<?php

require_once '../config/bootstrap.php';

$userId = $_SESSION['user']['id'] ?? 0;
$articleId = (int)($_GET['article_id'] ?? 0);

if (!$userId || !$articleId) {
    die("Parametri lipsă");
}

$db = new Database();

$article = $db->fetchSingle("SELECT * FROM articles WHERE id = ? AND user_id = ?", [$articleId, $userId]);

// Verificări de securitate
if (!$article) {
    die("Articol inexistent sau nu ai permisiune.");
}

if ($article['status'] !== 'draft') {
    die("Doar articolele în draft pot fi submise pentru aprobare.");
}

// Update status la pending
$stmt = $db->prepare("UPDATE articles SET status = 'pending' WHERE id = ?");
$stmt->execute([$articleId]);

// Log activitatea
logActivity($userId, 'submit_article', 'User ' . $_SESSION['user']['username'] . ' submitted article: ' . $article['title']);

// Trimite notificări către moderatori și admini
sendNotificationToRole('moderator', 'info', 'Article <a href="view_article.php?id=' . $articleId . '&version=' . $version . '">' . truncateText($article['title'], 30) . '</a> has been submitted for approval.');
sendNotificationToRole('admin', 'info', 'Article <a href="view_article.php?id=' . $articleId . '&version=' . $version . '">' . truncateText($article['title'], 30) . '</a> has been submitted for approval.');

header("Location: dashboard.php");
exit;
?>
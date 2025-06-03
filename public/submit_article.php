<?php

require_once '../config/bootstrap.php';

$userId = $_SESSION['user']['id'] ?? 0;
$articleId = (int)($_GET['article_id'] ?? 0);


echo "User ID: $userId, Article ID: $articleId<br>";
if (!$userId || !$articleId) {
  die("Parametri lipsă");
}

$db = new Database();

$article = $db->fetchSingle("SELECT * FROM articles WHERE id = ? AND user_id = ?", [$articleId, $userId]);

/*
if (!$article || $article['status'] !== 'draft') {
  die("Articol invalid sau nu ai permisiune.");
}
*/


$stmt = $db->prepare("UPDATE articles SET status = 'pending' WHERE id = ?");
$stmt->execute([$articleId]);

header("Location: dashboard.php");
exit;
?>
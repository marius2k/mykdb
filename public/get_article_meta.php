<?php

require_once '../config/bootstrap.php';

header('Content-Type: application/json');

$aid = (int) ($_GET['aid'] ?? 0);
if (!$aid) {
  echo json_encode(['error' => 'Invalid article ID']); exit;
}

$db = new Database();

// views (direct din articole)
$article = $db->fetchSingle("SELECT views FROM articles WHERE id = ?", [$aid]);

// comments
$comments = $db->fetchAll("SELECT COUNT(*) AS total FROM article_comments WHERE article_id = ? AND status = 'approved'", [$aid]);

echo json_encode([
  'views' => (int) $article['views'],
  'comments' => (int) $comments['total'],
]);

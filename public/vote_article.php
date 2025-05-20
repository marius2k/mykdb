<?php

require_once '../config/bootstrap.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
  echo json_encode(['status' => 'error', 'message' => 'Autentificare necesară.']);
  exit;
}

$aid = (int) ($_POST['aid'] ?? 0);
$vote = ($_POST['vote'] ?? '') === 'dislike' ? 'dislike' : 'like';
$userId = $_SESSION['user']['id'] ?? 0;

if (!$aid || !$userId) {
  echo json_encode(['status' => 'error', 'message' => 'Date invalide.']);
  exit;
}

$db = new Database();

// Verifică vot anterior
$existing = $db->fetchSingle("SELECT id FROM article_likes WHERE article_id = ? AND user_id = ?", [$aid, $userId]);

if ($existing) {
  $db->query("UPDATE article_likes SET vote_type = ? WHERE article_id = ? AND user_id = ?", [$vote, $aid, $userId]);
} else {
  $db->query("INSERT INTO article_likes (article_id, user_id, vote_type) VALUES (?, ?, ?)", [$aid, $userId, $vote]);
}

// Returnăm numerele actualizate
$votes = getArticleLikesDislikes($aid);
echo json_encode([
  'status' => 'ok',
  'likes' => $votes['like'],
  'dislikes' => $votes['dislike']
]);
?>
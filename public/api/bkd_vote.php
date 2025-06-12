<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

$userId = $_SESSION['user']['id'] ?? null;
$type = $_POST['type'] ?? ''; // 'article' sau 'comment'
$id = (int)($_POST['id'] ?? 0);
$vote = $_POST['vote'] ?? ''; // 'like' sau 'dislike'

if (!$userId || !$id || !in_array($type, ['article', 'comment']) || !in_array($vote, ['like', 'dislike'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Date lipsă sau invalide']);
    exit;
}

$db = new Database();

if ($type === 'article') {
    // Șterge votul anterior dacă există
    $db->query("DELETE FROM article_likes WHERE article_id = ? AND user_id = ?", [$id, $userId]);
    // Adaugă votul nou
    $db->query("INSERT INTO article_likes (article_id, user_id, vote_type) VALUES (?, ?, ?)", [$id, $userId, $vote]);
    // Returnează numărul de like/dislike
    $likes = $db->fetchSingle("SELECT COUNT(*) as cnt FROM article_likes WHERE article_id = ? AND vote_type = 'like'", [$id])['cnt'];
    $dislikes = $db->fetchSingle("SELECT COUNT(*) as cnt FROM article_likes WHERE article_id = ? AND vote_type = 'dislike'", [$id])['cnt'];
    echo json_encode(['success' => true, 'likes' => $likes, 'dislikes' => $dislikes]);
} else {
    // Pentru comentarii
    $db->query("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?", [$id, $userId]);
    $db->query("INSERT INTO comment_likes (comment_id, user_id, vote_type) VALUES (?, ?, ?)", [$id, $userId, $vote]);
    $likes = $db->fetchSingle("SELECT COUNT(*) as cnt FROM comment_likes WHERE comment_id = ? AND vote_type = 'like'", [$id])['cnt'];
    $dislikes = $db->fetchSingle("SELECT COUNT(*) as cnt FROM comment_likes WHERE comment_id = ? AND vote_type = 'dislike'", [$id])['cnt'];
    echo json_encode(['success' => true, 'likes' => $likes, 'dislikes' => $dislikes]);
}
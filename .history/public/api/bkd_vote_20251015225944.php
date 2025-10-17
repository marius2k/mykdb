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
    
    // Track article vote in analytics
    require_once 'bkd_user_analytics.php';
    $analyticsData = [
        'user_id' => $userId,
        'article_id' => $id,
        'action_type' => 'vote',
        'value' => ($vote === 'like') ? 1 : -1,
        'vote_type' => $vote,
        'target_type' => 'article'
    ];
    trackUserActionDirect($analyticsData);
    
    echo json_encode(['success' => true, 'likes' => $likes, 'dislikes' => $dislikes]);
} else {
    // Pentru comentarii
    $db->query("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?", [$id, $userId]);
    $db->query("INSERT INTO comment_likes (comment_id, user_id, vote_type) VALUES (?, ?, ?)", [$id, $userId, $vote]);
    $likes = $db->fetchSingle("SELECT COUNT(*) as cnt FROM comment_likes WHERE comment_id = ? AND vote_type = 'like'", [$id])['cnt'];
    $dislikes = $db->fetchSingle("SELECT COUNT(*) as cnt FROM comment_likes WHERE comment_id = ? AND vote_type = 'dislike'", [$id])['cnt'];
    
    // Get article ID for comment
    $articleId = $db->fetchSingle("SELECT article_id FROM article_comments WHERE id = ?", [$id])['article_id'] ?? null;
    
    // Track comment vote in analytics
    if ($articleId) {
        require_once 'bkd_user_analytics.php';
        $analyticsData = [
            'user_id' => $userId,
            'article_id' => $articleId,
            'action_type' => 'vote',
            'action_value' => ($vote === 'like') ? 1 : -1,
            'action_details' => json_encode([
                'type' => 'comment',
                'comment_id' => $id,
                'vote_type' => $vote
            ])
        ];
        trackUserAction($analyticsData);
    }
    
    echo json_encode(['success' => true, 'likes' => $likes, 'dislikes' => $dislikes]);
}
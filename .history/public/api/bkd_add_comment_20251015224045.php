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
$articleId = (int)($_POST['article_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$userId || !$articleId || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Date lipsă']);
    exit;
}

$db = new Database();
$stmt = $db->prepare("INSERT INTO article_comments (article_id, user_id, content, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->execute([$articleId, $userId, $content]);

// Get the new comment ID
$commentId = $db->lastInsertId();

// Track comment creation in analytics
require_once 'bkd_user_analytics.php';
$analyticsData = [
    'user_id' => $userId,
    'article_id' => $articleId,
    'action_type' => 'comment',
    'action_value' => 1,
    'action_details' => json_encode([
        'comment_id' => $commentId,
        'status' => 'pending'
    ])
];
trackUserAction($analyticsData);

echo json_encode(['success' => true, 'message' => 'Comentariul a fost trimis pentru aprobare.']);
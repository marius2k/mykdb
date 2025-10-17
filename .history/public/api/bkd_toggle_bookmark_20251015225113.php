<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_toggle_bookmark.php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? null;
$articleId = intval($_POST['article_id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? '';

if (!$userId || !$articleId) {
    echo json_encode(['success' => false, 'error' => 'Date invalide']);
    exit;
}

// Check CSRF token if it's provided
if ($csrfToken && (!isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$db = new Database();
$exists = $db->fetchSingle("SELECT id FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
if ($exists) {
    $db->query("DELETE FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
    
    // Track bookmark removal in analytics
    require_once 'bkd_user_analytics.php';
    $analyticsData = [
        'user_id' => $userId,
        'article_id' => $articleId,
        'action_type' => 'bookmark',
        'action_value' => 0,
        'action_details' => json_encode(['action' => 'unbookmarked'])
    ];
    trackUserAction($analyticsData);
    
    echo json_encode(['success' => true, 'bookmarked' => false]);
} else {
    $db->query("INSERT INTO user_bookmarks (user_id, article_id) VALUES (?, ?)", [$userId, $articleId]);
    
    // Track bookmark addition in analytics
    require_once 'bkd_user_analytics.php';
    $analyticsData = [
        'user_id' => $userId,
        'article_id' => $articleId,
        'action_type' => 'bookmark',
        'action_value' => 1,
        'action_details' => json_encode(['action' => 'bookmarked'])
    ];
    trackUserAction($analyticsData);
    
    echo json_encode(['success' => true, 'bookmarked' => true]);
}
?>
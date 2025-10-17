<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_toggle_bookmark.php
require_once '../../config/bootstrap.php';

// Set proper headers for error handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Wrap everything in try/catch to ensure proper JSON responses for all errors
try {
    $userId = $_SESSION['user']['id'] ?? null;
    $articleId = intval($_POST['article_id'] ?? 0);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$userId) {
        throw new Exception('User not logged in');
    }
    
    if (!$articleId) {
        throw new Exception('Invalid article ID');
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
        'value' => 0 // No action_details field in the table
    ];
    trackUserActionDirect($analyticsData);
    
    echo json_encode(['success' => true, 'bookmarked' => false]);
} else {
    $db->query("INSERT INTO user_bookmarks (user_id, article_id) VALUES (?, ?)", [$userId, $articleId]);
    
    // Track bookmark addition in analytics
    require_once 'bkd_user_analytics.php';
    $analyticsData = [
        'user_id' => $userId,
        'article_id' => $articleId,
        'action_type' => 'bookmark',
        'value' => 1 // No action_details field in the table
    ];
    trackUserActionDirect($analyticsData);
    
    echo json_encode(['success' => true, 'bookmarked' => true]);
}
?>
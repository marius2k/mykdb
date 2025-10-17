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
        throw new Exception('Invalid CSRF token');
    }

    $db = new Database();
    
    // Check if the article exists first
    $article = $db->fetchSingle("SELECT id FROM articles WHERE id=?", [$articleId]);
    if (!$article) {
        throw new Exception('Article not found');
    }
    
    $exists = $db->fetchSingle("SELECT id FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
    if ($exists) {
        $db->query("DELETE FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
        
        // Track bookmark removal in analytics
        require_once 'bkd_user_analytics.php';
        $analyticsData = [
            'user_id' => $userId,
            'article_id' => $articleId,
            'action_type' => 'bookmark',
            'value' => 0
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
            'value' => 1
        ];
        trackUserActionDirect($analyticsData);
        
        echo json_encode(['success' => true, 'bookmarked' => true]);
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Bookmark toggle error: " . $e->getMessage());
    
    // Return a user-friendly error
    http_response_code(400); // Bad request
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>

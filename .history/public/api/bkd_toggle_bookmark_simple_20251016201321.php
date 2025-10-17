<?php
// Simple bookmark toggle API - simplified version
require_once '../../config/bootstrap.php';

// Set proper headers
header('Content-Type: application/json');

// Basic error handling
try {
    // Get user and article IDs
    $userId = $_SESSION['user']['id'] ?? null;
    $articleId = $_POST['article_id'] ?? '';
    
    // Simple validation
    if (!$userId) {
        throw new Exception('User not logged in');
    }
    
    if (empty($articleId)) {
        throw new Exception('Missing article ID');
    }
    
    // Initialize database connection
    $db = new Database();
    
    // Don't validate if article exists - just use the provided ID
    // Check if bookmark exists
    $exists = $db->fetchSingle("SELECT id FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
    
    // Toggle bookmark status
    if ($exists) {
        // Remove bookmark
        $db->query("DELETE FROM user_bookmarks WHERE user_id=? AND article_id=?", [$userId, $articleId]);
        echo json_encode(['success' => true, 'bookmarked' => false]);
    } else {
        // Add bookmark
        $db->query("INSERT INTO user_bookmarks (user_id, article_id) VALUES (?, ?)", [$userId, $articleId]);
        echo json_encode(['success' => true, 'bookmarked' => true]);
    }
} catch (Exception $e) {
    // Log error for debugging
    error_log("Bookmark toggle error: " . $e->getMessage());
    
    // Return error message
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
<?php
/**
 * Search Analytics API
 * 
 * Handles tracking and analysis of user search behavior
 */
require_once '../../config/bootstrap.php';
require_once APP_ROOT . '/includes/functions.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

try {
    // Basic validation
    if (!$data || !isset($data['action'])) {
        throw new Exception('Invalid request data');
    }
    
    // Get user ID from session if available
    $userId = $_SESSION['user']['id'] ?? null;
    
    // Override with user ID from request if available (for compatibility with front-end)
    if (isset($data['data']['user_id']) && $data['data']['user_id']) {
        $userId = $data['data']['user_id'];
    }
    
    // Initialize database connection
    $db = new Database();
    
    // Handle different action types
    switch ($data['action']) {
        case 'track_search':
            trackSearchQuery($db, $userId, $data['data']);
            break;
            
        case 'track_result_click':
            trackSearchResultClick($db, $userId, $data['data']);
            break;
            
        default:
            throw new Exception('Unknown action type: ' . $data['action']);
    }
    
    // Return success
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Search analytics error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Track a search query
 * 
 * @param Database $db Database connection
 * @param int|null $userId User ID (null for anonymous users)
 * @param array $data Search data
 */
function trackSearchQuery($db, $userId, $data) {
    // Validate required fields
    if (!isset($data['query']) || empty($data['query'])) {
        throw new Exception('Search query is required');
    }
    
    // Prepare search data
    $sessionId = $data['session_id'] ?? session_id();
    $searchId = $data['search_id'] ?? null;
    $abandoned = isset($data['abandoned']) && $data['abandoned'] ? 1 : 0;
    
    // Insert search query record
    $db->insert('search_queries', [
        'user_id' => $userId,
        'query' => $data['query'],
        'results_count' => $data['results_count'] ?? 0,
        'search_id' => $searchId,
        'session_id' => $sessionId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'abandoned' => $abandoned,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Also record in user_activity_analytics for unified reporting
    if ($userId) {
        require_once APP_ROOT . '/public/api/bkd_user_analytics.php';
        $analyticsData = [
            'user_id' => $userId,
            'action_type' => 'search',
            'value' => $data['result_count'] ?? 0,
            'search_query' => $data['query']
        ];
        trackUserActionDirect($analyticsData);
    }
}

/**
 * Track when user clicks on a search result
 * 
 * @param Database $db Database connection
 * @param int|null $userId User ID (null for anonymous users)
 * @param array $data Click data
 */
function trackSearchResultClick($db, $userId, $data) {
    // Validate required fields
    if (!isset($data['result_url']) || empty($data['result_url'])) {
        throw new Exception('Result URL is required');
    }
    
    // Get article ID from URL if possible
    $articleId = extractArticleIdFromUrl($data['result_url']);
    
    // Insert search click record
    $db->insert('search_result_clicks', [
        'user_id' => $userId,
        'search_id' => $data['search_id'] ?? null,
        'query' => $data['query'] ?? null,
        'result_url' => $data['result_url'],
        'result_title' => $data['result_title'] ?? null,
        'position' => $data['position'] ?? null,
        'time_to_click' => $data['time_to_click'] ?? null,
        'article_id' => $articleId,
        'session_id' => session_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Also record in user_activity_analytics for unified reporting
    if ($userId && $articleId) {
        require_once 'bkd_user_analytics.php';
        $analyticsData = [
            'user_id' => $userId,
            'article_id' => $articleId,
            'action_type' => 'search_click',
            'value' => $data['position'] ?? 0,
            'search_query' => $data['query'] ?? null
        ];
        trackUserActionDirect($analyticsData);
    }
}

/**
 * Extract article ID from a URL
 * 
 * @param string $url URL to extract from
 * @return int|null Article ID or null if not found
 */
function extractArticleIdFromUrl($url) {
    // Extract article ID from URL patterns like:
    // /view_article.php?id=123
    // /articles/123
    
    if (preg_match('/[?&]id=([^&]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/\/articles\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    
    return null;
}
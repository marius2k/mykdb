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
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Get user ID from session if available
    $userId = $_SESSION['user']['id'] ?? null;
    
    // Override with user ID from request if available
    if (isset($data['user_id']) && $data['user_id']) {
        $userId = $data['user_id'];
    }
    
    // Initialize database connection
    $db = new Database();
    
    // Determine action type from different possible fields
    $actionType = $data['action_type'] ?? $data['action'] ?? null;
    
    if (!$actionType) {
        throw new Exception('Missing action type');
    }
    
    // Handle different action types
    switch ($actionType) {
        case 'search_query':
        case 'track_search':
            trackSearchQuery($db, $userId, $data);
            break;
            
        case 'search_result_click':
        case 'track_result_click':
            trackSearchResultClick($db, $userId, $data);
            break;
            
        default:
            throw new Exception('Unknown action type: ' . $actionType);
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
    $sessionId = $data['session_id'] ?? (function_exists('session_id') ? session_id() : null);
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
    if (!isset($data['article_id']) && !isset($data['search_id'])) {
        throw new Exception('Either article ID or search ID is required');
    }
    
    $sessionId = $data['session_id'] ?? (function_exists('session_id') ? session_id() : null);
    $articleId = $data['article_id'] ?? null;
    
    // Insert search click record
    $db->insert('search_results_clicks', [
        'user_id' => $userId,
        'search_query_id' => $data['search_id'] ?? null,
        'article_id' => $articleId,
        'position' => $data['position'] ?? null,
        'session_id' => $sessionId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Also record in user_activity_analytics for unified reporting
    if ($userId && $articleId) {
        if (file_exists(APP_ROOT . '/public/api/bkd_user_analytics.php')) {
            require_once APP_ROOT . '/public/api/bkd_user_analytics.php';
            if (function_exists('trackUserActionDirect')) {
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
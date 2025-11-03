<?php
/**
 * Search Analytics API
 * 
 * Handles tracking and analysis of user search behavior
 */

// Start output buffering to catch any unexpected output
ob_start();

require_once '../../config/bootstrap.php';
require_once APP_ROOT . '/includes/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean any output that might have been generated
ob_clean();

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize database connection
$db = new Database();

// Handle GET requests for analytics data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetRequest($db);
    exit;
}

// Handle POST requests for tracking
// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Temporarily disable error_log output to stdout
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Basic validation
    if (!$data) {
        throw new Exception('Invalid request data - no JSON data received');
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
        throw new Exception('Missing action type. Received data: ' . json_encode($data));
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
    ob_clean(); // Clear any output buffer content
    echo json_encode(['success' => true]);
    exit;
    
} catch (Exception $e) {
    // Log error for debugging with full trace
    error_log('Search analytics error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Clear output buffer and return error response
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
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
    
    // Use result_count or results_count (support both)
    $resultCount = $data['result_count'] ?? $data['results_count'] ?? 0;
    
    $insertData = [
        'user_id' => $userId,
        'query' => $data['query'],
        'result_count' => $resultCount,
        'search_id' => $searchId,
        'session_id' => $sessionId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        // Insert search query record
        $insertId = $db->insert('search_queries', $insertData);
        
        if ($insertId === false) {
            throw new Exception('Database insert returned false - check database logs');
        }
        
    } catch (Exception $e) {
        throw new Exception('Failed to insert search query: ' . $e->getMessage());
    }
    
    // Also record in user_activity_analytics for unified reporting
    if ($userId) {
        try {
            if (file_exists(APP_ROOT . '/public/api/bkd_user_analytics.php')) {
                require_once APP_ROOT . '/public/api/bkd_user_analytics.php';
                if (function_exists('trackUserActionDirect')) {
                    $analyticsData = [
                        'user_id' => $userId,
                        'action_type' => 'search',
                        'action' => 'search',
                        'value' => $resultCount,
                        'search_query' => $data['query']
                    ];
                    trackUserActionDirect($analyticsData);
                }
            }
        } catch (Exception $e) {
            // Don't fail the whole operation if user analytics fails
        }
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
    $sessionId = $data['session_id'] ?? (function_exists('session_id') ? session_id() : null);
    $searchId = $data['search_id'] ?? null;
    
    // Try to extract article ID from URL if not provided directly
    $articleId = $data['article_id'] ?? null;
    if (!$articleId && isset($data['result_url'])) {
        $articleId = extractArticleIdFromUrl($data['result_url']);
    }
    
    // Insert search click record
    $insertData = [
        'user_id' => $userId,
        'search_id' => $searchId,
        'query' => $data['query'] ?? null,
        'result_url' => $data['result_url'] ?? null,
        'result_title' => $data['result_title'] ?? null,
        'article_id' => $articleId,
        'position' => $data['position'] ?? null,
        'time_to_click' => $data['time_to_click'] ?? null,
        'session_id' => $sessionId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('search_result_clicks', $insertData);
    
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
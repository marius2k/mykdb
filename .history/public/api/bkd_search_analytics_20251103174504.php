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

/**
 * Handle GET requests for analytics data
 * 
 * @param Database $db Database connection
 */
function handleGetRequest($db) {
    // Debug: Log session status
    error_log("Search Analytics API - Session status: " . (isset($_SESSION['user']) ? "User logged in as " . $_SESSION['user']['role'] : "No user session"));
    
    // Check permissions
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'editor', 'moderator', 'superadmin'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => 'Unauthorized',
            'debug' => [
                'session_exists' => isset($_SESSION['user']),
                'session_id' => session_id()
            ]
        ]);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get_dashboard_data':
                getDashboardData($db);
                break;
                
            default:
                throw new Exception('Invalid action: ' . htmlspecialchars($action));
        }
    } catch (Exception $e) {
        error_log("Search Analytics API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Get all dashboard data for search analytics
 * 
 * @param Database $db Database connection
 */
function getDashboardData($db) {
    // Date range filtering
    $period = isset($_GET['period']) && is_numeric($_GET['period']) ? (int)$_GET['period'] : 7;
    
    try {
        // 1. Total metrics for the period
        $totalMetrics = $db->fetchSingle("
            SELECT 
                COUNT(*) as total_searches,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT query) as unique_queries,
                SUM(result_count) as total_results,
                AVG(result_count) as avg_results
            FROM search_queries
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ", [$period]);
        
        // 2. Top searches
        $topSearches = $db->fetchAll("
            SELECT 
                query, 
                COUNT(*) as search_count, 
                SUM(result_count) as total_results,
                AVG(result_count) as avg_results,
                COUNT(DISTINCT user_id) as unique_users,
                MAX(created_at) as last_search
            FROM search_queries
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY query
            ORDER BY search_count DESC
            LIMIT 20
        ", [$period]);
        
        // 3. Zero result searches
        $zeroResults = $db->fetchAll("
            SELECT 
                query, 
                COUNT(*) as count, 
                MAX(created_at) as last_search,
                COUNT(DISTINCT user_id) as unique_users
            FROM search_queries
            WHERE result_count = 0 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY query
            ORDER BY count DESC
            LIMIT 20
        ", [$period]);
        
        // 4. Click-through rates
        $ctrData = $db->fetchAll("
            SELECT 
                sq.query,
                COUNT(DISTINCT sq.id) as total_searches,
                COUNT(DISTINCT src.id) as total_clicks,
                ROUND(COUNT(DISTINCT src.id) / COUNT(DISTINCT sq.id) * 100, 2) as ctr
            FROM search_queries sq
            LEFT JOIN search_result_clicks src ON sq.search_id = src.search_id
            WHERE sq.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY sq.query
            HAVING total_searches >= 3
            ORDER BY ctr DESC
            LIMIT 20
        ", [$period]);
        
        // 5. Daily search trends
        $trends = $db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as search_count,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(result_count) as total_results
            FROM search_queries
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$period]);
        
        // 6. Most clicked articles from search
        $topClickedArticles = $db->fetchAll("
            SELECT 
                src.article_id,
                a.title,
                COUNT(*) as click_count,
                AVG(src.position) as avg_position,
                COUNT(DISTINCT src.user_id) as unique_users
            FROM search_result_clicks src
            LEFT JOIN articles a ON src.article_id = a.id
            WHERE src.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND src.article_id IS NOT NULL
            GROUP BY src.article_id, a.title
            ORDER BY click_count DESC
            LIMIT 20
        ", [$period]);
        
        // 7. Click position distribution
        $positionStats = $db->fetchAll("
            SELECT 
                position,
                COUNT(*) as clicks
            FROM search_result_clicks
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND position IS NOT NULL
            AND position <= 10
            GROUP BY position
            ORDER BY position ASC
        ", [$period]);
        
        // Return all data
        echo json_encode([
            'success' => true,
            'data' => [
                'total_metrics' => $totalMetrics ?: [],
                'top_searches' => $topSearches ?: [],
                'zero_results' => $zeroResults ?: [],
                'ctr_data' => $ctrData ?: [],
                'trends' => $trends ?: [],
                'top_clicked_articles' => $topClickedArticles ?: [],
                'position_stats' => $positionStats ?: []
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to fetch dashboard data: ' . $e->getMessage());
    }
}
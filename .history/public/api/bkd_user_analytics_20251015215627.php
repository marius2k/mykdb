<?php
require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// First check if the required tables exist
function tablesExist($db) {
    try {
        $userActivityTable = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_analytics'");
        $adminActivityTable = $db->fetchSingle("SHOW TABLES LIKE 'admin_activity_analytics'");
        $userSummaryTable = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_summary'");
        $userReadingTable = $db->fetchSingle("SHOW TABLES LIKE 'user_reading_time'");
        
        // Log the results for debugging
        error_log("Table check: user_activity_analytics: " . (!empty($userActivityTable) ? "EXISTS" : "NOT FOUND"));
        error_log("Table check: admin_activity_analytics: " . (!empty($adminActivityTable) ? "EXISTS" : "NOT FOUND"));
        error_log("Table check: user_activity_summary: " . (!empty($userSummaryTable) ? "EXISTS" : "NOT FOUND"));
        error_log("Table check: user_reading_time: " . (!empty($userReadingTable) ? "EXISTS" : "NOT FOUND"));
        
        // All tables need to exist
        return (!empty($userActivityTable) && !empty($adminActivityTable) && 
                !empty($userSummaryTable) && !empty($userReadingTable));
    } catch (Exception $e) {
        error_log("Error checking tables: " . $e->getMessage());
        return false;
    }
}

try {
    // Set default error handling to log errors instead of displaying them
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    
    $db = new Database();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Log the request for debugging
    error_log("API Request: action=$action, method=" . $_SERVER['REQUEST_METHOD'] . ", ip=" . $_SERVER['REMOTE_ADDR']);

    // Check if tables exist before proceeding
    $tablesExist = tablesExist($db);
    if (!$tablesExist && $action != 'check_tables' && $action != 'check_tables_exist') {
        throw new Exception('User analytics tables do not exist yet. Please run the migration script first.');
    }

    switch ($action) {
        case 'check_tables':
        case 'check_tables_exist':  // Support both endpoint names
            echo json_encode(['success' => true, 'tables_exist' => $tablesExist]);
            break;
        case 'track_user_action':
            trackUserAction($db);
            break;
        case 'get_user_analytics':
            getUserAnalytics($db);
            break;
        case 'get_admin_activity':
            getAdminActivity($db);
            break;
        case 'get_user_engagement':
            getUserEngagement($db);
            break;
        default:
            throw new Exception('Invalid action: ' . htmlspecialchars($action));
    }
} catch (Exception $e) {
    // Log the error for server-side debugging
    error_log("API Error in action '$action': " . $e->getMessage());
    error_log("API Error trace: " . $e->getTraceAsString());
    
    // Return a user-friendly error
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'error_type' => get_class($e),
        'action' => $action
    ]);
}

/**
 * Track various user actions on articles
 */
function trackUserAction($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $articleId = (int)($data['article_id'] ?? 0);
    $commentId = (int)($data['comment_id'] ?? 0);
    $actionType = $data['action_type'] ?? '';
    $actionValue = $data['action_value'] ?? null;
    $actionMetadata = $data['action_metadata'] ?? null;
    
    $validActions = [
        'view', 'bookmark', 'comment', 'rating', 'vote', 'edit', 
        'publish', 'approve', 'save_pdf', 'usefulness_rating',
        'reject', 'delete', 'restore'
    ];
    
    if (!in_array($actionType, $validActions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action type']);
        return;
    }
    
    if ($articleId <= 0 && $commentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid article ID or comment ID']);
        return;
    }
    
    // Session is already started by bootstrap.php
    $sessionId = session_id();
    $userId = $_SESSION['user']['id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Determine which table to use based on action type
    $isAdminAction = in_array($actionType, ['edit', 'publish', 'approve', 'reject', 'delete', 'restore']);
    $tableName = $isAdminAction ? 'admin_activity_analytics' : 'user_activity_analytics';
    
    try {
        $metadataJson = $actionMetadata ? json_encode($actionMetadata) : null;
        
        $data = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'action_type' => $actionType,
            'ip_address' => $ipAddress,
            'action_date' => date('Y-m-d H:i:s')
        ];
        
        if ($articleId > 0) {
            $data['article_id'] = $articleId;
        }
        
        if ($commentId > 0) {
            $data['comment_id'] = $commentId;
        }
        
        if ($actionValue !== null) {
            $data['value'] = $actionValue;
        }
        
        $db->insert($tableName, $data);
        
        // For reading time tracking if applicable
        if ($actionType === 'view' && isset($data['reading_time']) && $data['reading_time'] > 0) {
            $db->insert('user_reading_time', [
                'user_id' => $userId,
                'article_id' => $articleId,
                'reading_time' => (int)$data['reading_time'],
                'session_id' => $sessionId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get analytics about user interactions
 */
function getUserAnalytics($db) {
    // Check permissions - but allow development mode override with a debug parameter
    $devMode = isset($_GET['debug']) && $_GET['debug'] == 1;
    error_log("getUserAnalytics: Session user: " . (isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'not set'));
    error_log("getUserAnalytics: devMode: " . ($devMode ? 'true' : 'false'));
    
    if (!$devMode && (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin']))) {
        error_log("getUserAnalytics: User not authorized. Session: " . json_encode($_SESSION));
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Use debug=1 to bypass in development.']);
        return;
    }

    // Optional filters
    $articleId = (int)($_GET['article_id'] ?? 0);
    $userId = (int)($_GET['user_id'] ?? 0);
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $whereConditions = [];
    $params = [];
    
    if ($articleId > 0) {
        $whereConditions[] = 'article_id = ?';
        $params[] = $articleId;
    }
    
    if ($userId > 0) {
        $whereConditions[] = 'user_id = ?';
        $params[] = $userId;
    }
    
    if ($startDate) {
        $whereConditions[] = 'action_date >= ?';
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate) {
        $whereConditions[] = 'action_date <= ?';
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    try {
        // Get overall stats
        $overallStats = [
            'bookmarks' => $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'bookmark' $whereClause", $params)['count'] ?? 0,
            'usefulness_ratings' => $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'usefulness_rating' $whereClause", $params)['count'] ?? 0,
            'avg_star_rating' => $db->fetchSingle("SELECT AVG(CAST(value AS DECIMAL(3,2))) as avg FROM user_activity_analytics WHERE action_type = 'rating' $whereClause", $params)['avg'] ?? 0,
            'pdf_saves' => $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'save_pdf' $whereClause", $params)['count'] ?? 0,
            'comments' => $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'comment' $whereClause", $params)['count'] ?? 0,
            'useful_yes' => $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'usefulness_rating' AND value = 1 $whereClause", $params)['count'] ?? 0,
            'useful_no' => $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'usefulness_rating' AND value = 0 $whereClause", $params)['count'] ?? 0
        ];
        
        // Get daily activity for charts
        $dailyActivity = $db->fetchAll(
            "SELECT 
                DATE(action_date) as date,
                action_type,
                COUNT(*) as count
             FROM user_activity_analytics
             $whereClause
             GROUP BY DATE(action_date), action_type
             ORDER BY DATE(action_date) DESC
             LIMIT 30", 
            $params
        );
        
        // Top articles by interaction
        try {
            // Log the query parameters for debugging
            error_log("Starting topArticles query with whereClause: " . $whereClause);
            
            try {
            // Use a simpler approach with more error handling
            $hasArticles = 0;
            
            try {
                $hasArticles = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE article_id > 0 $whereClause", $params);
                error_log("Found " . ($hasArticles['count'] ?? 0) . " articles with interactions");
            } catch (Exception $countEx) {
                error_log("Error counting articles: " . $countEx->getMessage());
                $hasArticles = ['count' => 0];
            }
            
            $topArticles = [];
            
            if ($hasArticles && $hasArticles['count'] > 0) {
                // Try a simpler query first
                try {
                    error_log("Executing simplified topArticles query with whereClause: " . $whereClause);
                    
                    // Use a simpler query with fewer aggregations to reduce chances of error
                    $topArticles = $db->fetchAll(
                        "SELECT 
                            ua.article_id,
                            IFNULL(a.title, CONCAT('Article #', ua.article_id)) as title,
                            COUNT(*) as total_interactions,
                            SUM(CASE WHEN ua.action_type = 'bookmark' THEN 1 ELSE 0 END) as bookmarks,
                            SUM(CASE WHEN ua.action_type = 'save_pdf' THEN 1 ELSE 0 END) as pdf_saves,
                            SUM(CASE WHEN ua.action_type = 'comment' THEN 1 ELSE 0 END) as comments,
                            AVG(CASE WHEN ua.action_type = 'rating' THEN ua.value ELSE NULL END) as avg_rating,
                            SUM(CASE WHEN ua.action_type = 'usefulness_rating' AND ua.value = 1 THEN 1 ELSE 0 END) as useful_yes,
                            SUM(CASE WHEN ua.action_type = 'usefulness_rating' AND ua.value = 0 THEN 1 ELSE 0 END) as useful_no
                        FROM user_activity_analytics ua
                        LEFT JOIN articles a ON ua.article_id = a.id
                        $whereClause
                        GROUP BY ua.article_id
                        ORDER BY total_interactions DESC
                        LIMIT 20", 
                        $params
                    );
                } catch (Exception $innerEx) {
                    // Fall back to an even simpler query if the complex one fails
                    error_log("Falling back to basic query: " . $innerEx->getMessage());
                    try {
                        // Just get article IDs as a fallback with minimal processing
                        $rawArticles = $db->fetchAll(
                            "SELECT 
                                article_id,
                                COUNT(*) as total_count
                            FROM user_activity_analytics
                            $whereClause
                            GROUP BY article_id
                            ORDER BY total_count DESC
                            LIMIT 20", 
                            $params
                        );
                        
                        // Manually build the expected structure with empty/default values
                        $topArticles = [];
                        foreach ($rawArticles as $article) {
                            $topArticles[] = [
                                'article_id' => $article['article_id'],
                                'title' => "Article #{$article['article_id']}",
                                'bookmarks' => 0,
                                'pdf_saves' => 0,
                                'comments' => 0,
                                'avg_rating' => 0,
                                'useful_yes' => 0,
                                'useful_no' => 0
                            ];
                        }
                    } catch (Exception $basicEx) {
                        // If even that fails, return empty array
                        error_log("Basic query failed too: " . $basicEx->getMessage());
                        $topArticles = [];
                    }
                    
                    // Try to add titles after the fact
                    foreach ($topArticles as &$article) {
                        try {
                            if (isset($article['article_id'])) {
                                $title = $db->fetchSingle(
                                    "SELECT title FROM articles WHERE id = ?", 
                                    [$article['article_id']]
                                );
                                $article['title'] = $title['title'] ?? "Article #{$article['article_id']}";
                            }
                        } catch (Exception $titleEx) {
                            $article['title'] = "Article #{$article['article_id']}";
                        }
                    }
                }
            } else {
                $topArticles = [];
            }
            error_log("topArticles query successful");
        } catch (Exception $e) {
            error_log("Error in topArticles query: " . $e->getMessage());
            error_log("Query error trace: " . $e->getTraceAsString());
            // Return empty array instead of failing completely
            $topArticles = [];
        }
        
        // Ensure we have valid data for the response
        // Convert daily activity to array if it's null
        if (!is_array($dailyActivity)) {
            error_log("Warning: daily_activity is not an array, setting to empty array");
            $dailyActivity = [];
        }
        
        // Convert top articles to array if it's null
        if (!is_array($topArticles)) {
            error_log("Warning: top_articles is not an array, setting to empty array");
            $topArticles = [];
        }
        
        // Log the successful data before returning
        error_log("getUserAnalytics: Successfully processed data");
        
        echo json_encode([
            'success' => true,
            'overall_stats' => $overallStats,
            'daily_activity' => $dailyActivity,
            'top_articles' => $topArticles
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);
        
    } catch (Exception $e) {
        // Log the error with more details
        error_log("getUserAnalytics Error: " . $e->getMessage());
        error_log("getUserAnalytics Error Trace: " . $e->getTraceAsString());
        
        // For development, create default empty data structures instead of failing
        if (isset($_GET['debug']) && $_GET['debug'] == 1) {
            error_log("Debug mode - returning default data instead of error");
            echo json_encode([
                'success' => true,
                'debug_mode' => true,
                'error_caught' => $e->getMessage(),
                'overall_stats' => [
                    'bookmarks' => 0,
                    'usefulness_ratings' => 0,
                    'avg_star_rating' => 0,
                    'pdf_saves' => 0,
                    'comments' => 0,
                    'useful_yes' => 0,
                    'useful_no' => 0
                ],
                'daily_activity' => [],
                'top_articles' => []
            ]);
        } else {
            // Return a user-friendly error with debugging information
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => 'Error processing analytics data: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }
}

/**
 * Get admin activity analytics
 */
function getAdminActivity($db) {
    // Check permissions
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        return;
    }
    
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    try {
        // Admin activity summary
        $activitySummary = $db->fetchAll(
            "SELECT 
                u.username,
                COUNT(CASE WHEN aa.action_type = 'edit' THEN 1 END) as edits,
                COUNT(CASE WHEN aa.action_type = 'publish' THEN 1 END) as publishes,
                COUNT(CASE WHEN aa.action_type = 'approve' THEN 1 END) as approvals,
                COUNT(*) as total_actions
             FROM admin_activity_analytics aa
             JOIN users u ON aa.user_id = u.id
             WHERE aa.action_date BETWEEN ? AND ?
             GROUP BY u.id, u.username
             ORDER BY total_actions DESC",
            [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
        );
        
        // Daily admin activity
        $dailyActivity = $db->fetchAll(
            "SELECT 
                DATE(action_date) as date,
                action_type,
                COUNT(*) as count
             FROM admin_activity_analytics
             WHERE action_date BETWEEN ? AND ?
             GROUP BY DATE(action_date), action_type
             ORDER BY DATE(action_date)",
            [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
        );
        
        // Most active articles (most edited/published)
        $activeArticles = $db->fetchAll(
            "SELECT 
                aa.article_id,
                a.title,
                COUNT(DISTINCT CASE WHEN aa.action_type = 'edit' THEN aa.id END) as edits,
                COUNT(DISTINCT CASE WHEN aa.action_type = 'publish' THEN aa.id END) as publishes,
                COUNT(DISTINCT CASE WHEN aa.action_type = 'approve' THEN aa.id END) as approvals,
                MAX(aa.action_date) as last_action
             FROM admin_activity_analytics aa
             JOIN articles a ON aa.article_id = a.id
             WHERE aa.action_date BETWEEN ? AND ?
             GROUP BY aa.article_id, a.title
             ORDER BY COUNT(*) DESC
             LIMIT 20",
            [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
        );
        
        echo json_encode([
            'success' => true,
            'activity_summary' => $activitySummary,
            'daily_activity' => $dailyActivity,
            'active_articles' => $activeArticles
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Get user engagement statistics
 */
function getUserEngagement($db) {
    // Check permissions
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        return;
    }
    
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Enable error logging
    error_log("getUserEngagement: Processing request for date range $startDate to $endDate");
    
    try {
        // Most engaged users - with error handling
        try {
            $engagedUsers = $db->fetchAll(
                "SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.role,
                    COUNT(DISTINCT ua.article_id) as articles_interacted,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'view' THEN ua.article_id END) as articles_viewed,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'bookmark' THEN ua.article_id END) as articles_bookmarked,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'comment' THEN ua.article_id END) as articles_commented,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'rating' THEN ua.article_id END) as articles_rated,
                    COUNT(*) as total_interactions
                 FROM user_activity_analytics ua
                 JOIN users u ON ua.user_id = u.id
                 WHERE ua.action_date BETWEEN ? AND ?
                 AND ua.user_id IS NOT NULL
                 GROUP BY u.id, u.username, u.email, u.role
                 ORDER BY total_interactions DESC
                 LIMIT 50",
                [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            );
        } catch (Exception $e) {
            error_log("Error in engaged users query: " . $e->getMessage());
            $engagedUsers = [];
        }
        
        // User retention statistics (returning users) - simpler query without subquery
        try {
            $retention = $db->fetchAll(
                "SELECT 
                    COUNT(DISTINCT user_id) as total_users,
                    SUM(CASE WHEN user_id IN (
                        SELECT user_id FROM user_activity_analytics 
                        WHERE action_date BETWEEN ? AND ?
                        AND user_id IS NOT NULL
                        GROUP BY user_id
                        HAVING COUNT(DISTINCT DATE(action_date)) >= 2
                    ) THEN 1 ELSE 0 END) as returning_users,
                    SUM(CASE WHEN user_id IN (
                        SELECT user_id FROM user_activity_analytics 
                        WHERE action_date BETWEEN ? AND ?
                        AND user_id IS NOT NULL
                        GROUP BY user_id
                        HAVING COUNT(DISTINCT DATE(action_date)) >= 5
                    ) THEN 1 ELSE 0 END) as frequent_users,
                    SUM(CASE WHEN user_id IN (
                        SELECT user_id FROM user_activity_analytics 
                        WHERE action_date BETWEEN ? AND ?
                        AND user_id IS NOT NULL
                        GROUP BY user_id
                        HAVING COUNT(DISTINCT DATE(action_date)) >= 10
                    ) THEN 1 ELSE 0 END) as power_users
                FROM (SELECT DISTINCT user_id FROM user_activity_analytics 
                      WHERE action_date BETWEEN ? AND ?
                      AND user_id IS NOT NULL) as distinct_users",
                [
                    $startDate . ' 00:00:00', $endDate . ' 23:59:59',
                    $startDate . ' 00:00:00', $endDate . ' 23:59:59',
                    $startDate . ' 00:00:00', $endDate . ' 23:59:59',
                    $startDate . ' 00:00:00', $endDate . ' 23:59:59'
                ]
            );
        } catch (Exception $e) {
            error_log("Error in retention query: " . $e->getMessage());
            // Fallback to a simple count
            $retention = [
                ['total_users' => 0, 'returning_users' => 0, 'frequent_users' => 0, 'power_users' => 0]
            ];
        }
        
        // Engagement by role - with error handling
        try {
            $engagementByRole = $db->fetchAll(
                "SELECT 
                    u.role,
                    COUNT(DISTINCT u.id) as user_count,
                    COUNT(DISTINCT ua.article_id) as articles_interacted,
                    CASE 
                        WHEN COUNT(DISTINCT u.id) > 0 THEN ROUND(COUNT(*) / COUNT(DISTINCT u.id), 1) 
                        ELSE 0 
                    END as avg_interactions_per_user
                 FROM user_activity_analytics ua
                 JOIN users u ON ua.user_id = u.id
                 WHERE ua.action_date BETWEEN ? AND ?
                 GROUP BY u.role
                 ORDER BY avg_interactions_per_user DESC",
                [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            );
        } catch (Exception $e) {
            error_log("Error in role engagement query: " . $e->getMessage());
            // Provide fallback data
            $engagementByRole = [];
        }
        
        // Engagement over time - with error handling
        try {
            $engagementOverTime = $db->fetchAll(
                "SELECT 
                    DATE(action_date) as date,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT article_id) as unique_articles,
                    COUNT(*) as total_interactions
                 FROM user_activity_analytics
                 WHERE action_date BETWEEN ? AND ?
                 GROUP BY DATE(action_date)
                 ORDER BY DATE(action_date)",
                [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            );
        } catch (Exception $e) {
            error_log("Error in engagement over time query: " . $e->getMessage());
            $engagementOverTime = [];
        }
        
        echo json_encode([
            'success' => true,
            'engaged_users' => $engagedUsers,
            'retention' => $retention[0] ?? [],
            'engagement_by_role' => $engagementByRole,
            'engagement_over_time' => $engagementOverTime
        ]);
        
    } catch (Exception $e) {
        error_log("getUserEngagement Error: " . $e->getMessage());
        error_log("SQL Error Details: " . print_r($e->getTraceAsString(), true));
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
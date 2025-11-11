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
        // Prevent PHP errors from being output
        $previousErrorReporting = error_reporting(0);
        
        $userActivityTable = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_analytics'");
        $adminActivityTable = $db->fetchSingle("SHOW TABLES LIKE 'admin_activity_analytics'");
        $userSummaryTable = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_summary'");
        $userReadingTable = $db->fetchSingle("SHOW TABLES LIKE 'user_reading_time'");
        
        // Restore error reporting
        error_reporting($previousErrorReporting);
        
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

// Only execute the main API logic if this file is called directly (not included)
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    try {
        // Make sure we never output PHP errors directly - always JSON
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        
        // Set up a custom error handler that converts all PHP errors to exceptions
        set_error_handler(function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        
        $db = new Database();
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        // Log the request for debugging
        error_log("API Request: action=$action, method=" . $_SERVER['REQUEST_METHOD'] . ", ip=" . $_SERVER['REMOTE_ADDR']);

        // Check if tables exist before proceeding
        $tablesExist = tablesExist($db);
        // We'll now just log if tables don't exist instead of throwing an exception
        if (!$tablesExist && $action != 'check_tables' && $action != 'check_tables_exist') {
            error_log('Warning: User analytics tables not detected, but proceeding with the request.');
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
            case 'search_behavior':
                getSearchBehavior($db);
                break;
            default:
                throw new Exception('Invalid action: ' . htmlspecialchars($action));
        }
    } catch (Throwable $e) {
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
    // Check permissions
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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
    $andClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';
    
    try {
        // Add individual error handling for each query to better diagnose issues
        $overallStats = [];
        
        try {
            // Bookmarks count
            $bookmarks = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'bookmark' $andClause", $params);
            $overallStats['bookmarks'] = $bookmarks['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting bookmarks count: " . $e->getMessage());
            $overallStats['bookmarks'] = 0;
        }
        
        try {
            // Usefulness ratings count
            $usefulnessRatings = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'usefulness_rating' $andClause", $params);
            $overallStats['usefulness_ratings'] = $usefulnessRatings['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting usefulness ratings count: " . $e->getMessage());
            $overallStats['usefulness_ratings'] = 0;
        }
        
        try {
            // Average star rating
            $avgRating = $db->fetchSingle("SELECT AVG(CAST(value AS DECIMAL(3,2))) as avg FROM user_activity_analytics WHERE action_type = 'rating' $andClause", $params);
            $overallStats['avg_star_rating'] = $avgRating['avg'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting avg star rating: " . $e->getMessage());
            $overallStats['avg_star_rating'] = 0;
        }
        
        try {
            // PDF saves count
            $pdfSaves = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'save_pdf' $andClause", $params);
            $overallStats['pdf_saves'] = $pdfSaves['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting pdf saves count: " . $e->getMessage());
            $overallStats['pdf_saves'] = 0;
        }
        
        try {
            // Comments count
            $comments = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'comment' $andClause", $params);
            $overallStats['comments'] = $comments['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting comments count: " . $e->getMessage());
            $overallStats['comments'] = 0;
        }
        
        try {
            // Useful yes count
            $usefulYes = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'usefulness_rating' AND value = 1 $andClause", $params);
            $overallStats['useful_yes'] = $usefulYes['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting useful yes count: " . $e->getMessage());
            $overallStats['useful_yes'] = 0;
        }
        
        try {
            // Useful no count
            $usefulNo = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE action_type = 'usefulness_rating' AND value = 0 $andClause", $params);
            $overallStats['useful_no'] = $usefulNo['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting useful no count: " . $e->getMessage());
            $overallStats['useful_no'] = 0;
        }
        
        // Get daily activity for charts
        $dailyActivity = [];
        try {
            error_log("Executing daily activity query with whereClause: " . $whereClause);
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
            error_log("Daily activity query successful: " . count($dailyActivity) . " rows returned");
        } catch (Exception $e) {
            error_log("Error executing daily activity query: " . $e->getMessage());
            error_log("Query trace: " . $e->getTraceAsString());
            // Return an empty array instead of failing
            $dailyActivity = [];
        }
        
        // Top articles by interaction
        $topArticles = []; // Initialize with empty array in case of failure
        try {
            // Log the query parameters for debugging
            error_log("Starting topArticles query with whereClause: " . $whereClause);
            
            // First check if we have any articles with interactions - use a try/catch for this query too
            try {
                $hasArticles = $db->fetchSingle("SELECT COUNT(*) as count FROM user_activity_analytics WHERE article_id > 0 $andClause", $params);
                error_log("Article count check successful: " . ($hasArticles['count'] ?? 0) . " articles found");
            } catch (Exception $countEx) {
                error_log("Error checking article count: " . $countEx->getMessage());
                // Set to an empty array with count 0 to skip the next section
                $hasArticles = ['count' => 0];
            }
            
            if ($hasArticles && $hasArticles['count'] > 0) {
                // Check for MySQL version to ensure proper syntax
                try {
                    $mysqlVersion = $db->fetchSingle("SELECT version() as version")['version'] ?? '';
                    error_log("MySQL version: " . $mysqlVersion);
                    
                    // Use proper string concatenation for the database version
                    // MySQL uses CONCAT() while some others use || operator
                    $concatOperator = "CONCAT('Article #', ua.article_id)";
                    
                    // Modify the query to handle articles that might not exist anymore
                    // Use a more compatible query with simpler CASE statements
                    // Add WHERE clause to filter out NULL article_ids
                    $whereClauseWithArticle = $whereClause ? $whereClause . ' AND ua.article_id IS NOT NULL' : 'WHERE ua.article_id IS NOT NULL';
                    
                    $topArticles = $db->fetchAll(
                        "SELECT 
                            ua.article_id,
                            IFNULL(a.title, $concatOperator) as title,
                            SUM(IF(ua.action_type = 'bookmark', 1, 0)) as bookmarks,
                            SUM(IF(ua.action_type = 'save_pdf', 1, 0)) as pdf_saves,
                            SUM(IF(ua.action_type = 'comment', 1, 0)) as comments,
                            AVG(IF(ua.action_type = 'rating', ua.value, NULL)) as avg_rating,
                            SUM(IF(ua.action_type = 'usefulness_rating' AND ua.value = 1, 1, 0)) as useful_yes,
                            SUM(IF(ua.action_type = 'usefulness_rating' AND ua.value = 0, 1, 0)) as useful_no
                        FROM user_activity_analytics ua
                        LEFT JOIN articles a ON ua.article_id = a.id
                        $whereClauseWithArticle
                        GROUP BY ua.article_id
                        ORDER BY COUNT(*) DESC
                        LIMIT 20", 
                        $params
                    );
                    error_log("Top articles query successful: " . count($topArticles) . " articles found");
                } catch (Exception $innerEx) {
                    // Fall back to a simpler query if the complex one fails
                    error_log("Falling back to simpler query: " . $innerEx->getMessage());
                    // Simplest possible query as a fallback
                    try {
                        $topArticles = $db->fetchAll(
                            "SELECT 
                                article_id,
                                '' as title,
                                COUNT(*) as total_interactions,
                                0 as bookmarks,
                                0 as pdf_saves,
                                0 as comments,
                                0 as avg_rating,
                                0 as useful_yes,
                                0 as useful_no
                            FROM user_activity_analytics
                            $whereClause
                            GROUP BY article_id
                            ORDER BY COUNT(*) DESC
                            LIMIT 20", 
                            $params
                        );
                        error_log("Fallback query successful: " . count($topArticles) . " articles found");
                    } catch (Exception $fallbackEx) {
                        error_log("Even fallback query failed: " . $fallbackEx->getMessage());
                        // Last resort - return empty array
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
        http_response_code(500);
        
        // Return a more user-friendly error message
        echo json_encode([
            'success' => false, 
            'error' => 'Database query failed',
            'error_details' => $e->getMessage(),
            'empty_data' => true,
            // Include empty datasets to prevent frontend errors
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
                    r.name as role,
                    COUNT(DISTINCT ua.article_id) as articles_interacted,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'view' THEN ua.article_id END) as articles_viewed,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'bookmark' THEN ua.article_id END) as articles_bookmarked,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'comment' THEN ua.article_id END) as articles_commented,
                    COUNT(DISTINCT CASE WHEN ua.action_type = 'rating' THEN ua.article_id END) as articles_rated,
                    COUNT(*) as total_interactions
                 FROM user_activity_analytics ua
                 JOIN users u ON ua.user_id = u.id
                 LEFT JOIN roles r ON u.role_id = r.id
                 WHERE ua.action_date BETWEEN ? AND ?
                 AND ua.user_id IS NOT NULL
                 GROUP BY u.id, u.username, u.email, r.name
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
                    r.name as role,
                    COUNT(DISTINCT u.id) as user_count,
                    COUNT(DISTINCT ua.article_id) as articles_interacted,
                    CASE 
                        WHEN COUNT(DISTINCT u.id) > 0 THEN ROUND(COUNT(*) / COUNT(DISTINCT u.id), 1) 
                        ELSE 0 
                    END as avg_interactions_per_user
                 FROM user_activity_analytics ua
                 JOIN users u ON ua.user_id = u.id
                 LEFT JOIN roles r ON u.role_id = r.id
                 WHERE ua.action_date BETWEEN ? AND ?
                 GROUP BY r.name
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

/**
 * Direct tracking for user actions when called from other API endpoints
 * This function handles the case where analytics tracking is done directly
 * rather than through an API endpoint
 * 
 * @param array $data Analytics data to track
 * @return bool Success status
 */
function trackUserActionDirect($data) {
    // Simple validation
    if (!isset($data['action_type']) || !isset($data['user_id'])) {
        error_log("Invalid data for trackUserActionDirect: missing required fields - " . json_encode($data));
        return false;
    }
    
    // Ignore the action parameter if present - it's only needed for the API endpoint switch
    // but not for direct tracking
    if (isset($data['action'])) {
        unset($data['action']);
    }
    
    try {
        $db = new Database();
        
        // Make sure we only use fields that exist in the table
        $validFields = [
            'user_id', 
            'action_type', 
            'article_id', 
            'comment_id', 
            'value', 
            'action_date', 
            'session_id', 
            'ip_address',
            'vote_type',
            'target_type',
            'status',
            'approved_by'
        ];
        
        $cleanData = [];
        foreach ($validFields as $field) {
            if (isset($data[$field])) {
                $cleanData[$field] = $data[$field];
            } else if ($field === 'action_type' && isset($data['action_type'])) {
                $cleanData[$field] = $data['action_type'];
            }
        }
        
        // Rename action_value to value if present
        if (!isset($cleanData['value']) && isset($data['action_value'])) {
            $cleanData['value'] = $data['action_value'];
        }
        
        // Determine which table to use based on action type
        $actionType = $cleanData['action_type'] ?? '';
        $isAdminAction = in_array($actionType, ['edit', 'publish', 'approve', 'reject', 'delete', 'restore']);
        $tableName = $isAdminAction ? 'admin_activity_analytics' : 'user_activity_analytics';
        
        // Add session and timestamp if not provided
        if (!isset($cleanData['session_id'])) {
            $cleanData['session_id'] = session_id();
        }
        
        if (!isset($cleanData['action_date'])) {
            $cleanData['action_date'] = date('Y-m-d H:i:s');
        }
        
        if (!isset($cleanData['ip_address'])) {
            $cleanData['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // Log the query we're about to execute
        error_log("Analytics tracking: " . json_encode($cleanData));
        
        // Insert into database
        $db->insert($tableName, $cleanData);
        
        return true;
    } catch (Exception $e) {
        error_log("Error in trackUserActionDirect call: " . $e->getMessage());
        error_log("Data: " . json_encode($data));
        return false;
    }
}

/**
 * Get search behavior data including queries and clicked articles
 */
function getSearchBehavior($db) {
    // Check permissions
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin', 'editor', 'moderator'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        return;
    }
    
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $roleFilter = $_GET['role'] ?? 'all';
    
    // Enable error logging
    error_log("getSearchBehavior: Processing request for date range $startDate to $endDate, role: $roleFilter");
    
    try {
        // Build WHERE clause for role filtering
        $roleCondition = '';
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($roleFilter !== 'all') {
            $roleCondition = ' AND r.name = ?';
            $params[] = $roleFilter;
        }
        
        // Query to get search queries and their clicked results
        $searchBehavior = $db->fetchAll(
            "SELECT 
                sq.created_at as action_date,
                COALESCE(u.username, CONCAT('Guest-', SUBSTRING(sq.session_id, 1, 8))) as username,
                r.name as role,
                sq.query,
                sq.result_count,
                src.article_id as article_id,
                a.title as article_title,
                src.position,
                src.time_to_click
             FROM search_queries sq
             LEFT JOIN users u ON sq.user_id = u.id
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN search_result_clicks src ON sq.id = src.search_id
             LEFT JOIN articles a ON src.article_id = a.id
             WHERE sq.created_at BETWEEN ? AND ?
             $roleCondition
             ORDER BY sq.created_at DESC
             LIMIT 500",
            $params
        );
        
        // Format the data for the frontend
        $formattedData = [];
        foreach ($searchBehavior as $row) {
            $formattedData[] = [
                'action_date' => $row['action_date'],
                'username' => $row['username'],
                'role' => $row['role'] ?? 'Guest',
                'query' => $row['query'] ?? 'N/A',
                'result_count' => (int)($row['result_count'] ?? 0),
                'article_id' => (int)($row['article_id'] ?? 0),
                'article_title' => $row['article_title'] ?? null,
                'position' => (int)($row['position'] ?? 0),
                'time_to_click' => (int)($row['time_to_click'] ?? 0)
            ];
        }
        
        error_log("Search behavior query returned " . count($formattedData) . " results");
        
        echo json_encode([
            'success' => true,
            'data' => $formattedData
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getSearchBehavior: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to retrieve search behavior data: ' . $e->getMessage()
        ]);
    }
}

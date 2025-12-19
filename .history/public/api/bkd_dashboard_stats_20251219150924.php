<?php
require_once '../../config/bootstrap.php';
require_login();

header('Content-Type: application/json');

$db = new Database();
$userId = $_SESSION['user']['id'];

try {
    // Calculate stats
    
    // 1. Total Articles
    $totalArticlesStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
    $totalArticles = $totalArticlesStmt->fetchColumn();
    
    // Articles trend (compare this week vs last week)
    $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
    $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
    $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
    
    $thisWeekArticlesStmt = $db->query("
        SELECT COUNT(DISTINCT article_id) 
        FROM admin_activity_analytics 
        WHERE action_type = 'publish' 
        AND DATE(action_date) >= ?
    ", [$thisWeekStart]);
    $thisWeekArticles = $thisWeekArticlesStmt->fetchColumn();
    
    $lastWeekArticlesStmt = $db->query("
        SELECT COUNT(DISTINCT article_id) 
        FROM admin_activity_analytics 
        WHERE action_type = 'publish' 
        AND DATE(action_date) >= ? 
        AND DATE(action_date) <= ?
    ", [$lastWeekStart, $lastWeekEnd]);
    $lastWeekArticles = $lastWeekArticlesStmt->fetchColumn();
    
    $articlesTrend = 0;
    if ($lastWeekArticles > 0) {
        $articlesTrend = round((($thisWeekArticles - $lastWeekArticles) / $lastWeekArticles) * 100);
    }
    
    // 2. Active Users (logged in last 7 days)
    $activeUsersStmt = $db->query("
        SELECT COUNT(DISTINCT user_id) 
        FROM activity_log 
        WHERE action_type = 'login_success' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $activeUsers = $activeUsersStmt->fetchColumn();
    
    // 3. Comments This Week
    $commentsThisWeekStmt = $db->query("
        SELECT COUNT(*) 
        FROM user_activity_analytics 
        WHERE action_type = 'comment' 
        AND action_date >= ?
    ", [$thisWeekStart]);
    $commentsThisWeek = $commentsThisWeekStmt->fetchColumn();
    
    $commentsLastWeekStmt = $db->query("
        SELECT COUNT(*) 
        FROM user_activity_analytics 
        WHERE action_type = 'comment' 
        AND action_date >= ? 
        AND action_date <= ?
    ", [$lastWeekStart, $lastWeekEnd]);
    $commentsLastWeek = $commentsLastWeekStmt->fetchColumn();
    
    $commentsComparison = $commentsThisWeek - $commentsLastWeek;
    
    // 4. Views This Week
    $viewsThisWeekStmt = $db->query("
        SELECT COUNT(*) 
        FROM user_activity_analytics 
        WHERE action_type = 'view' 
        AND action_date >= ?
    ", [$thisWeekStart]);
    $viewsThisWeek = $viewsThisWeekStmt->fetchColumn();
    
    // Get Recent Activity (last 20 activities)
    $activitiesStmt = $db->query("
        SELECT 
            u.username,
            uaa.action_type,
            uaa.action_date,
            a.title as article_title,
            uaa.article_id
        FROM user_activity_analytics uaa
        LEFT JOIN users u ON uaa.user_id = u.id
        LEFT JOIN articles a ON uaa.article_id = a.id
        WHERE uaa.action_type IN ('view', 'comment', 'bookmark', 'rating', 'publish', 'approve', 'edit')
        ORDER BY uaa.action_date DESC
        LIMIT 20
    ");
    
    $activities = [];
    while ($row = $activitiesStmt->fetch()) {
        $icon = '📄';
        $message = '';
        
        switch ($row['action_type']) {
            case 'view':
                $icon = '👁️';
                $message = 'viewed "' . htmlspecialchars($row['article_title']) . '"';
                break;
            case 'comment':
                $icon = '💬';
                $message = 'commented on "' . htmlspecialchars($row['article_title']) . '"';
                break;
            case 'bookmark':
                $icon = '⭐';
                $message = 'bookmarked "' . htmlspecialchars($row['article_title']) . '"';
                break;
            case 'rating':
                $icon = '⭐';
                $message = 'rated "' . htmlspecialchars($row['article_title']) . '"';
                break;
            case 'publish':
                $icon = '🚀';
                $message = 'published "' . htmlspecialchars($row['article_title']) . '"';
                break;
            case 'approve':
                $icon = '✅';
                $message = 'approved "' . htmlspecialchars($row['article_title']) . '"';
                break;
            case 'edit':
                $icon = '✏️';
                $message = 'edited "' . htmlspecialchars($row['article_title']) . '"';
                break;
        }
        
        // Calculate time ago
        $actionTime = strtotime($row['action_date']);
        $now = time();
        $diff = $now - $actionTime;
        
        if ($diff < 60) {
            $timeAgo = 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            $timeAgo = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            $timeAgo = date('M d, Y', $actionTime);
        }
        
        $activities[] = [
            'username' => $row['username'] ?? 'Unknown',
            'icon' => $icon,
            'message' => $message,
            'timeAgo' => $timeAgo,
            'timestamp' => $row['action_date']
        ];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'metrics' => [
            [
                'topText' => 'Published',
                'counter' => (int)$publishedCount,
                'bottomText' => 'Articles This Month',
                'color' => '#770286ff'
            ],
            [
                'topText' => 'In Draft',
                'counter' => (int)$draftsCount,
                'bottomText' => 'Articles This Month',
                'color' => '#d60303ff'
            ],
            [
                'topText' => 'Pending',
                'counter' => (int)$pendingCount,
                'bottomText' => 'Articles This Month',
                'color' => '#02860dff'
            ],
            [
                'topText' => 'Approved',
                'counter' => (int)$approvedCount,
                'bottomText' => 'Articles This Month',
                'color' => '#029affff'
            ],
            [
                'topText' => 'Total',
                'counter' => (int)$totalArticles,
                'bottomText' => 'Articles',
                'color' => '#04709bcc'
            ],
            [
                'topText' => 'Total',
                'counter' => (int)$totalUsers,
                'bottomText' => 'Users',
                'color' => '#04709bcc'
            ],
            [
                'topText' => 'Online',
                'counter' => (int)$onlineUsers,
                'bottomText' => 'Users Now',
                'color' => '#10b981'
            ],
            [
                'topText' => 'Active',
                'counter' => (int)$activeUsers,
                'bottomText' => 'Last 7 Days',
                'color' => '#3b82f6'
            ]
        ],
        'activities' => $activities
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch dashboard stats',
        'message' => $e->getMessage()
    ]);
}

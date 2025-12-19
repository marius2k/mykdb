<?php
require_once '../../config/bootstrap.php';
require_login();

header('Content-Type: application/json');

$db = new Database();
$userId = $_SESSION['user']['id'];

try {
    // Calculate stats
    
    
    // 1. Total Articles
    $totalArticlesStmt = $db->query("SELECT COUNT(*) FROM articles");
    $totalArticles = $totalArticlesStmt->fetchColumn();
    
    // 2. Total Users
    $totalUsersStmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $totalUsersStmt->fetchColumn();
    
    // 3. Online Users
    $onlineUsersStmt = $db->query("SELECT COUNT(*) FROM users WHERE online = 1");
    $onlineUsers = $onlineUsersStmt->fetchColumn();
    
    // 4. Active Users (logged in last 7 days)
    $activeUsersStmt = $db->query("
        SELECT COUNT(DISTINCT user_id) 
        FROM activity_log 
        WHERE action_type = 'login_success' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $activeUsers = $activeUsersStmt->fetchColumn();
    
    // 5. Monthly Stats
    $monthStart = date('Y-m-01');
    
    // Published this month
    $publishedStmt = $db->query("
        SELECT COUNT(DISTINCT article_id) 
        FROM admin_activity_analytics 
        WHERE action_type = 'publish' 
        AND DATE(action_date) >= ?
    ", [$monthStart]);
    $publishedCount = $publishedStmt->fetchColumn();
    
    // Approved this month
    $approvedStmt = $db->query("
        SELECT COUNT(DISTINCT article_id) 
        FROM admin_activity_analytics 
        WHERE action_type = 'approve' 
        AND DATE(action_date) >= ?
    ", [$monthStart]);
    $approvedCount = $approvedStmt->fetchColumn();
    
    // Drafts this month
    $draftsStmt = $db->query("SELECT COUNT(*) FROM article_versions WHERE status = 'draft' AND created_at >= ?", [$monthStart]);
    $draftsCount = $draftsStmt->fetchColumn();
    
    // Pending this month
    $pendingStmt = $db->query("SELECT COUNT(*) FROM article_versions WHERE status = 'pending' AND created_at >= ?", [$monthStart]);
    $pendingCount = $pendingStmt->fetchColumn();
    
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
        LIMIT 10
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
    
    // Prepare stats items for ScrollablePanel
    $statsItems = [
        [
            'label' => 'Total Articles',
            'value' => (int)$totalArticles,
            'icon' => '📚',
            'color' => '#04709bcc'
        ],
        [
            'label' => 'Total Users',
            'value' => (int)$totalUsers,
            'icon' => '👥',
            'color' => '#04709bcc'
        ],
        [
            'label' => 'Active Users',
            'value' => (int)$activeUsers,
            'icon' => '🟢',
            'color' => '#10b981'
        ],
        [
            'label' => 'Published (This Month)',
            'value' => (int)$publishedCount,
            'icon' => '🚀',
            'color' => '#770286ff'
        ],
        [
            'label' => 'Approved (This Month)',
            'value' => (int)$approvedCount,
            'icon' => '✅',
            'color' => '#029affff'
        ],
        [
            'label' => 'Drafts (This Month)',
            'value' => (int)$draftsCount,
            'icon' => '📝',
            'color' => '#d60303ff'
        ],
        [
            'label' => 'Pending (This Month)',
            'value' => (int)$pendingCount,
            'icon' => '⏳',
            'color' => '#02860dff'
        ]
    ];
    
    // Get Articles in Pending
    $pendingArticlesStmt = $db->query("
        SELECT 
            av.article_id,
            a.title,
            u.username as author,
            av.version,
            av.created_at
        FROM article_versions av
        JOIN articles a ON av.article_id = a.id
        JOIN users u ON a.author = u.id
        WHERE av.status = 'pending'
        ORDER BY av.created_at DESC
        LIMIT 10
    ");
    
    $pendingArticles = [];
    while ($row = $pendingArticlesStmt->fetch()) {
        $pendingArticles[] = [
            'article_id' => $row['article_id'],
            'title' => htmlspecialchars($row['title']),
            'author' => htmlspecialchars($row['author']),
            'version' => $row['version'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get Articles in Draft
    $draftArticlesStmt = $db->query("
        SELECT 
            av.article_id,
            a.title,
            u.username as author,
            av.version,
            av.created_at
        FROM article_versions av
        JOIN articles a ON av.article_id = a.id
        JOIN users u ON a.author = u.id
        WHERE av.status = 'draft'
        ORDER BY av.created_at DESC
        LIMIT 10
    ");
    
    $draftArticles = [];
    while ($row = $draftArticlesStmt->fetch()) {
        $draftArticles[] = [
            'article_id' => $row['article_id'],
            'title' => htmlspecialchars($row['title']),
            'author' => htmlspecialchars($row['author']),
            'version' => $row['version'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'metrics' => [
            
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
        'statsItems' => $statsItems,
        'activities' => $activities,
        'pendingArticles' => $pendingArticles,
        'draftArticles' => $draftArticles
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

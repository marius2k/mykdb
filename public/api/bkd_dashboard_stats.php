<?php
require_once '../../config/bootstrap.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$monthStart = date('Y-m-01');

try {
    // Published this month
    $publishedStmt = $db->query("
        SELECT COUNT(DISTINCT article_id) 
        FROM admin_activity_analytics 
        WHERE action_type = 'publish' 
        AND DATE(action_date) >= ?
    ", [$monthStart]);
    $published = $publishedStmt->fetchColumn();

    // Drafts this month
    $draftsStmt = $db->query("
        SELECT COUNT(*) 
        FROM article_versions 
        WHERE status = 'draft' 
        AND created_at >= ?
    ", [$monthStart]);
    $drafts = $draftsStmt->fetchColumn();

    // Pending this month
    $pendingStmt = $db->query("
        SELECT COUNT(*) 
        FROM article_versions 
        WHERE status = 'pending' 
        AND created_at >= ?
    ", [$monthStart]);
    $pending = $pendingStmt->fetchColumn();

    // Approved this month
    $approvedStmt = $db->query("
        SELECT COUNT(DISTINCT article_id) 
        FROM admin_activity_analytics 
        WHERE action_type = 'approve' 
        AND DATE(action_date) >= ?
    ", [$monthStart]);
    $approved = $approvedStmt->fetchColumn();

    // Total articles
    $totalArticlesStmt = $db->query("SELECT COUNT(*) FROM articles");
    $totalArticles = $totalArticlesStmt->fetchColumn();

    // Total users
    $totalUsersStmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $totalUsersStmt->fetchColumn();

    // Online users
    $onlineUsersStmt = $db->query("SELECT COUNT(*) FROM users WHERE online = 1");
    $onlineUsers = $onlineUsersStmt->fetchColumn();

    echo json_encode([
        'published' => (int)$published,
        'drafts' => (int)$drafts,
        'pending' => (int)$pending,
        'approved' => (int)$approved,
        'totalArticles' => (int)$totalArticles,
        'totalUsers' => (int)$totalUsers,
        'onlineUsers' => (int)$onlineUsers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Failed to fetch statistics',
        'message' => $e->getMessage()
    ]);
}

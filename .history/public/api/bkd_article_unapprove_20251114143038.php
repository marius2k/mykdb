<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['approve_article']; // Same permission as approve since it's the reverse action
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

/**
 * Track admin activity for analytics
 */
function trackAdminActivity($articleId, $actionType, $userId = null) {
    if ($userId === null && isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
    }
    
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $db = new Database();
        $db->insert('admin_activity_analytics', [
            'user_id' => $userId,
            'article_id' => $articleId,
            'session_id' => $sessionId,
            'action_type' => $actionType,
            'ip_address' => $ipAddress,
            'action_date' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Error tracking admin activity: " . $e->getMessage());
    }
}

// Helper function to validate action permissions
function canPerformAction($action, $userRole, $articleStatus, $authorId, $currentUserId, $isOnlineVersion = false) {
    $isOwner = (int)$authorId === (int)$currentUserId;
    $status = strtolower(trim($articleStatus));
    
    switch ($userRole) {
        case 'contributor':
            // Contributors cannot unapprove articles
            return false;
            
        case 'editor':
        case 'moderator':
        case 'admin':
        case 'superadmin':
            // Only these roles can unapprove
            // Can only unapprove if status is 'approved' and NOT online
            return $status === 'approved' && !$isOnlineVersion;
            
        default:
            return false;
    }
}

// Helper function to get article version with permission check
function getArticleWithPermissionCheck($articleId, $action, $version = null) {
    global $db;
    
    $userRole = $_SESSION['user']['role'] ?? '';
    $currentUserId = $_SESSION['user']['id'] ?? 0;
    
    if ($version) {
        // Get specific version data from article_versions
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id,
                   u.username,
                   c.name as category
            FROM article_versions av 
            LEFT JOIN users u ON av.author_id = u.id
            LEFT JOIN categories c ON av.category_id = c.id
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
        
        if (!$articleData) {
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
    } else {
        // Get latest version
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id,
                   u.username,
                   c.name as category
            FROM article_versions av 
            LEFT JOIN users u ON av.author_id = u.id
            LEFT JOIN categories c ON av.category_id = c.id
            WHERE av.article_id = ? 
            ORDER BY av.is_online DESC, av.version_number DESC
            LIMIT 1
        ", [$articleId]);
        
        if (!$articleData) {
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
    }
    
    // Check permissions
    $statusForPermission = $articleData['status'];
    
    if (!canPerformAction($action, $userRole, $statusForPermission, $articleData['owner_id'], $currentUserId, $isOnlineVersion)) {
        return false;
    }
    
    return $articleData;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articleId = (int)($_POST['article_id'] ?? 0);
    $version = (int)($_POST['version'] ?? 1);
    $reason = trim($_POST['reason'] ?? '');
    
    // Validate inputs
    if (!$articleId || !$version) {
        echo json_encode(['error' => 'ID articol și versiune sunt obligatorii']);
        exit;
    }
    
    if (empty($reason)) {
        echo json_encode(['error' => 'Motivul pentru returnarea articolului este obligatoriu']);
        exit;
    }
    
    // Check unapprove permission
    $versionData = getArticleWithPermissionCheck($articleId, 'unapprove', $version);
    if ($versionData === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Nu aveți permisiunea să returnați această versiune la starea Draft']);
        exit;
    }
    if ($versionData === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Versiunea nu a fost găsită']);
        exit;
    }
    
    // Additional check: only approved versions can be unapproved
    if ($versionData['status'] !== 'approved') {
        echo json_encode(['error' => 'Doar versiunile aprobate pot fi returnate la starea Draft']);
        exit;
    }
    
    // Additional check: cannot unapprove online versions
    if ($versionData['is_online'] == 1) {
        echo json_encode(['error' => 'Nu poți returna o versiune publicată (online). Dezactivează mai întâi articolul.']);
        exit;
    }
    
    $user_id = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];

    try {
        $db->beginTransaction();
        
        // Prepare the note message
        $noteMessage = "\n[Returnat de " . $username . "]: " . $reason;
        
        // Update version status from 'approved' to 'draft'
        $db->query("UPDATE article_versions SET status = 'draft', updated_at = NOW(), change_note = CONCAT(COALESCE(change_note, ''), ?) WHERE article_id = ? AND version_number = ?", [$noteMessage, $articleId, $version]);
        
        // Track admin activity
        trackAdminActivity($articleId, 'unapprove_article', $user_id);
        
        // Log the activity
        logActivity($user_id, 'unapprove_article', 
            'User '. $username .' returned version '. $version .' of article ID '. $articleId .' to draft status. Reason: ' . $reason);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Versiunea a fost returnată la starea Draft cu succes'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error in unapprove action: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Eroare la returnarea versiunii: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>

<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['approve_article'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

// Helper function to validate action permissions
function canPerformAction($action, $userRole, $articleStatus, $authorId, $currentUserId, $isOnlineVersion = false) {
    $isOwner = (int)$authorId === (int)$currentUserId;
    $status = strtolower(trim($articleStatus));
    
    switch ($userRole) {
        case 'contributor':
            switch ($action) {
                case 'view':
                    return $status === 'approved' || $status === 'disabled' || ($isOwner && ($status === 'draft' || $status === 'pending'));
                case 'edit':
                    // Contributor: doar propriile draft + versiuni online disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled' && $isOwner;
                    }
                    return $status === 'draft' && $isOwner;
                case 'approve':
                case 'publish':
                case 'disable':
                case 'restore':
                    return false;
                case 'delete':
                    return $status === 'draft' && $isOwner;
                default:
                    return false;
            }
            
        case 'editor':
            switch ($action) {
                case 'view':
                    return true;
                case 'edit':
                    // Editor: draft, pending + versiuni online disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled';
                    }
                    return $status === 'draft' || $status === 'pending' || $status === 'approved';
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'approved' && $isOnlineVersion;
                case 'restore':
                    return $status === 'disabled' && $isOnlineVersion;
                case 'delete':
                    return $status === 'draft' || $status === 'pending';
                default:
                    return false;
            }
            
        case 'moderator':
            switch ($action) {
                case 'view':
                    return true;
                case 'edit':
                    // Moderator: draft, pending + versiuni online disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled';
                    }
                    return $status === 'draft' || $status === 'pending';
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'approved' && $isOnlineVersion;
                case 'restore':
                    return $status === 'disabled' && $isOnlineVersion;
                case 'delete':
                    return $status === 'draft' || $status === 'pending';
                default:
                    return false;
            }
            
        case 'admin':
        case 'superadmin':
            switch ($action) {
                case 'view':
                    return true;
                case 'edit':
                    // Admin/Superadmin: toate versiunile + versiuni online doar dacă sunt disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled';
                    }
                    return true;
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'approved' && $isOnlineVersion;
                case 'restore':
                    return $status === 'disabled' && $isOnlineVersion;
                case 'delete':
                    return true;
                default:
                    return false;
            }
            
        default:
            return false;
    }
}

// Helper function to get article data with permission check
function getArticleWithPermissionCheck($articleId, $action, $version = null) {
    global $db;
    
    $userRole = $_SESSION['user']['role'] ?? '';
    $currentUserId = $_SESSION['user']['id'] ?? 0;
    
    if ($version) {
        // Get specific version data
        $articleData = $db->fetchSingle("
            SELECT av.*, a.user_id as owner_id 
            FROM article_versions av 
            JOIN articles a ON av.article_id = a.id 
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
        
        if (!$articleData) {
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
        
    } else {
        // Get main article data
        $articleData = $db->fetchSingle("SELECT * FROM articles WHERE id = ?", [$articleId]);
        
        if (!$articleData) {
            return null;
        }
        
        $articleData['owner_id'] = $articleData['user_id'];
        $isOnlineVersion = true; // Main article is always considered online
    }
    
    // Check permissions
    if (!canPerformAction($action, $userRole, $articleData['status'], $articleData['owner_id'], $currentUserId, $isOnlineVersion)) {
        return false; // Permission denied
    }
    
    return $articleData;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;

}else{

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 1);
        
        // Check approve permission
        $versionData = getArticleWithPermissionCheck($articleId, 'approve', $version);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să aprobați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        // Additional check: only pending versions can be approved
        if ($versionData['status'] !== 'pending') {
            echo json_encode(['error' => 'Doar versiunile în așteptare pot fi aprobate']);
            exit;
        }
        
        $publishAt = $_POST['publish_at'] ?? null;
        $user_id = $_SESSION['user']['id'];

        // Actualizează statusul versiunii la 'approved'
        $db->query("UPDATE article_versions SET status = 'approved', updated_at = NOW() WHERE article_id = ? AND version_number = ?", [$articleId, $version]);

        // Actualizează și articolul principal dacă această versiune este online
        $isOnline = $db->fetchSingle("SELECT is_online FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
        if ($isOnline && $isOnline['is_online'] == 1) {
            $db->query("UPDATE articles SET status = 'approved', publish_at = ?, updated_at = NOW() WHERE id = ?", [$publishAt, $articleId]);
        }

        logActivity($user_id, 'approve_article', 'User '. $_SESSION['user']['username'].' approved version '. $version .' of article ID '. $articleId);
        echo json_encode(['success' => true]);
        exit;
    }












}


?>
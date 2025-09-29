<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['edit_article','disable_article','enable_article','create_article','approve_article','restore_article','publish_article'];
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
        // Get specific version data from article_versions only
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id
            FROM article_versions av 
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
        
        if (!$articleData) {
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
        
    } else {
        // Get the online version or latest version if no online exists
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id
            FROM article_versions av 
            WHERE av.article_id = ? 
            AND (av.is_online = 1 OR av.article_id NOT IN (
                SELECT DISTINCT article_id FROM article_versions WHERE is_online = 1
            ))
            ORDER BY av.is_online DESC, av.version_number DESC
            LIMIT 1
        ", [$articleId]);
        
        if (!$articleData) {
            // Fallback: try to get from articles table if exists
            $articleData = $db->fetchSingle("SELECT *, user_id as owner_id FROM articles WHERE id = ?", [$articleId]);
            if (!$articleData) {
                return null;
            }
            $isOnlineVersion = true;
        } else {
            $isOnlineVersion = (int)$articleData['is_online'] === 1;
        }
    }
    
    // Check permissions
    if (!canPerformAction($action, $userRole, $articleData['status'], $articleData['owner_id'], $currentUserId, $isOnlineVersion)) {
        return false; // Permission denied
    }
    
    return $articleData;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Creare articol nou
    if ($action === 'add_article') {
        // Contributors and above can create articles
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, ['contributor', 'editor', 'moderator', 'admin', 'superadmin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să creați articole']);
            exit;
        }
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
        $user_id = $_SESSION['user']['id'];
        $created_at = date('Y-m-d H:i:s');
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        $change_note = trim($_POST['change_note'] ?? '');

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;   
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        // Inserează articolul în tabelul articles
        //$stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at, created_at, updated_at, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        //$stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at, $created_at, $created_at]);
        //$aid = $db->lastInsertedId();

        // Salvează versiunea inițială în article_versions (v1) - nu este online încă
        $initial_note = $change_note ?: 'Versiune inițială';
        $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, 1, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)",
            [$aid, $title, $clean_content, $category_id, $user_id, $status, $initial_note]);

        // Salvează tagurile
        if ($aid && $tagList) {
            foreach ($tagList as $tagName) {
                $tagStmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                $tagStmt->execute([$tagName]);
                $tagId = $tagStmt->fetchColumn();
                if (!$tagId) {
                    $db->query("INSERT INTO tags (name) VALUES (?)", [$tagName]);
                    $tagId = $db->lastInsertedId();
                }
                $db->query("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)", [$aid, $tagId]);
            }
        }

        logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created article: '. $title);
        echo json_encode(['success' => true, 'article_id' => $aid]);
        exit;
    }
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Acțiune necunoscută']);
        exit;
    }

}

?>
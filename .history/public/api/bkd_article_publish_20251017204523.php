<?php
// Publish article
//
//

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['publish_article'];
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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'publish') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);
        
        // Check publish permission
        $versionData = getArticleWithPermissionCheck2($articleId, 'publish', $version);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să publicați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        // Additional checks
        if ($versionData['status'] !== 'approved') {
            echo json_encode(['success' => false, 'error' => 'Doar versiunile aprobate pot fi publicate']);
            exit;
        }
        
        if ($versionData['is_online'] == 1) {
            echo json_encode(['success' => false, 'error' => 'Această versiune este deja publicată']);
            exit;
        }
        
        


        $user_id = $_SESSION['user']['id'];
        
        try {
            $db->beginTransaction();
            
            // 1. Get the version to be published from article_versions
            
            $version_data = $db->fetchSingle("SELECT * FROM article_versions WHERE id = ?", [$version]);
            
            $version_data = $db->fetchSingle("
                SELECT av.*, u.username 
                FROM article_versions av 
                LEFT JOIN users u ON av.author_id = u.id 
                WHERE av.article_id = ? AND av.version_number = ?
            ", [$articleId, $version]);
            
            if (!$version_data) {
                throw new Exception('Versiunea specificată nu a fost găsită');
            }
            
            // 2. Check if version status is 'approved'
            if ($version_data['status'] !== 'approved') {
                throw new Exception('Doar versiunile aprobate pot fi publicate');
            }
            
            // 3. Check if this version is already online
            if ($version_data['is_online'] == 1) {
                throw new Exception('Această versiune este deja publicată');
            }
            
            // 4. Check if an online version exists in articles table
            $existing_article = $db->fetchSingle("SELECT id FROM articles WHERE id = ?", [$articleId]);
            
            // 5. Update all versions to set is_online = 0 (remove online status from all versions)
            $db->query("UPDATE article_versions SET is_online = 0 WHERE article_id = ?", [$articleId]);
            
            // 6. Set the selected version as online in article_versions
            $db->query("UPDATE article_versions SET is_online = 1 WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
            
            // 7. Copy/Update the data in articles table
            if ($existing_article) {
                // Update existing article
                $db->query("
                    UPDATE articles SET 
                        title = ?, 
                        content = ?, 
                        category_id = ?, 
                        status = 'published',
                        version = ?,
                        user_id = ?,
                        publish_at = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ", [
                    $version_data['title'],
                    $version_data['content'],
                    $version_data['category_id'],
                    $version,
                    $version_data['author_id'],
                    $version_data['publish_at'] ?? date('Y-m-d H:i:s'),
                    $articleId
                ]);
            } else {
                // Create new article entry
                $db->query("
                    INSERT INTO articles (
                        id, title, content, category_id, 
                        status, version, user_id, publish_at, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'published', ?, ?, ?, NOW(), NOW())
                ", [
                    $articleId,
                    $version_data['title'],
                    $version_data['content'],
                    $version_data['category_id'],
                    $version,
                    $version_data['author_id'],
                    $version_data['publish_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
            
            // 8. Log the publish action
            logActivity($user_id, 'publish_article', 'User '. $_SESSION['user']['username'].' published version '. $version .' of article ID '. $articleId);
            
            // Track admin activity for analytics
            trackAdminActivity($articleId, 'publish', $user_id);
            
            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Versiunea {$version} a fost publicată cu succes"
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}


?>
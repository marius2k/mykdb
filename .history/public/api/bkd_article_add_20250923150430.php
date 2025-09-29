<?php

//creation of new article

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['create_article'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}


$db = new Database();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

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

        try {
            $db->beginTransaction();
            
            // Inserează articolul în tabelul articles
            $db->insert("articles", [
                "title" => $title,
                "content" => $clean_content,
                "category_id" => $category_id,
                "user_id" => $user_id,
                "status" => 'disabled',
                "publish_at" => $publish_at,
                "created_at" => $created_at,
                "updated_at" => $created_at,
                "version" => 1
            ]);
            
            // get the article id of last inserted article;

            $aid = $db->lastInsertedId();

            
            // Save initial version in article_versions

            $initial_note = $change_note ?: 'First version';

            $version = $db->insert("article_versions", [
                "article_id" => $aid,
                "version_number" => 1,
                "title" => $title,
                "content" => $clean_content,
                "category_id" => $category_id,
                "author_id" => $user_id,
                "status" => $status,
                "is_online" => 0,
                "created_at" => $created_at,
                "updated_at" => $created_at,
                "change_note" => $initial_note
            ]);
            
            
            
            // get the version id of created version (article_versions.id)
            $version_id = $db->lastInsertedId();
            
            //Save tags 

            if ($tagList) {
                foreach ($tagList as $tagName) {
                    $tagStmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                    $tagStmt->execute([$tagName]);
                    $tagId = $tagStmt->fetchColumn();
                    if (!$tagId) {
                        $insertTagStmt = $db->prepare("INSERT INTO tags (name) VALUES (?)");
                        $insertTagStmt->execute([$tagName]);
                        $tagId = $db->lastInsertedId();
                    }
                    
                    $insertArticleTagStmt = $db->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                    $insertArticleTagStmt->execute([$aid, $tagId]);
                }
            }

            $db->commit();
            
            logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created article: '. $title);
            echo json_encode(['success' => true, 'version_id' => $version_id]);
        
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error creating article: " . $e->getMessage());
            echo json_encode(['error' => 'Eroare la crearea articolului: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Acțiune invalidă']);
    }
}
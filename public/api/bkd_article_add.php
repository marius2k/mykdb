<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_article_add.php

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
        $publish_at = null;
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
            
            // Debug: Log înainte de insert
            error_log("bkd_article_add: About to insert into articles table");
            
            // Inserează articolul în tabelul articles
            $aid = $db->insert("articles", [
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
            
            // Debug: Verifică dacă insert-ul a reușit
            if (!$aid) {
                error_log("bkd_article_add: Failed to insert into articles table");
                throw new Exception('Failed to create article in articles table');
            }
            
            error_log("bkd_article_add: Successfully inserted into articles, ID: " . $aid);
            
            // Verifică dacă articolul există în tabela articles
            $articleExists = $db->fetchSingle("SELECT id FROM articles WHERE id = ?", [$aid]);
            if (!$articleExists) {
                error_log("bkd_article_add: Article not found after insert, ID: " . $aid);
                throw new Exception('Article not found after insert');
            }
            
            error_log("bkd_article_add: Article confirmed in database, ID: " . $aid);
            
            // Save initial version in article_versions
            $initial_note = $change_note ?: 'First version';

            error_log("bkd_article_add: About to insert into article_versions with article_id: " . $aid);
            
            $version_id = $db->insert("article_versions", [
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
            
            // Debug: Verifică dacă insert-ul în article_versions a reușit
            if (!$version_id) {
                error_log("bkd_article_add: Failed to insert into article_versions table");
                throw new Exception('Failed to create version in article_versions table');
            }
            
            error_log("bkd_article_add: Successfully inserted into article_versions, version_id: " . $version_id);
            
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
            
            error_log("bkd_article_add: Transaction committed successfully");
            
            logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created article: '. $title);
            echo json_encode(['success' => true, 'article_id' => $aid, 'version_id' => $version_id]);
        
        } catch (Exception $e) {
            $db->rollBack();
            error_log("bkd_article_add: Error creating article: " . $e->getMessage());
            error_log("bkd_article_add: Exception trace: " . $e->getTraceAsString());
            echo json_encode(['error' => 'Eroare la crearea articolului: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Acțiune invalidă']);
    }
}
?>
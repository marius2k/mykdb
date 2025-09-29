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

        try {
            $db->beginTransaction();
            
            // 1. Generează un nou article_id (găsește următorul ID disponibil)
            $nextIdStmt = $db->prepare("SELECT COALESCE(MAX(article_id), 0) + 1 as next_id FROM article_versions");
            $nextIdStmt->execute();
            $nextIdResult = $nextIdStmt->fetch();
            $article_id = $nextIdResult['next_id'];
            
            // 2. Salvează DOAR în article_versions (v1) - nu este online încă
            // NU se populează încă tabela articles
            $initial_note = $change_note ?: 'Versiune inițială';
            $stmt = $db->prepare("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, 1, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)");
            $stmt->execute([$article_id, $title, $clean_content, $category_id, $user_id, $status, $initial_note]);

            // 3. Salvează tagurile folosind article_id generat
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
                    $insertArticleTagStmt->execute([$article_id, $tagId]);
                }
            }

            $db->commit();
            
            logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created article: '. $title);
            echo json_encode(['success' => true, 'article_id' => $article_id]);
        
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
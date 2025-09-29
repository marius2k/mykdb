<?php
// Editare articol

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['edit_article','edit_own_article'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied!!!']);
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


    if ($action === 'edit_article') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $baseVersion = (int)($_POST['base_version'] ?? 1);
        $isEditingOnlineVersion = (int)($_POST['is_editing_online_version'] ?? 0);
        
        // Check edit permission for this specific version
        $versionData = getArticleWithPermissionCheck2($articleId, 'edit', $baseVersion);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să editați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
        $user_id = $_SESSION['user']['id'];
        $updated_at = date('Y-m-d H:i:s');
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        $change_note = trim($_POST['change_note'] ?? '');

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        if ($isEditingOnlineVersion === 1) {
            // REGULA 5: Editarea versiunii online generează o versiune nouă
            
            // Obține ultima versiune
            $lastVersion = $db->fetchSingle("SELECT MAX(version_number) as v FROM article_versions WHERE article_id = ?", [$articleId]);
            $nextVersion = ($lastVersion && $lastVersion['v']) ? $lastVersion['v'] + 1 : 1;
            
            // Creează nota de modificare
            if ($change_note) {
                $edit_note = $change_note;
            } else {
                $edit_note = "Modificare bazată pe versiunea ONLINE {$baseVersion} → versiunea {$nextVersion}";
            }
            
            // Creează noua versiune în article_versions (NU este online)
            $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)",
                [$articleId, $nextVersion, $title, $clean_content, $category_id, $user_id, $status, $edit_note]);

            // NU actualizează tabelul articles - versiunea online rămâne neschimbată
            // Doar dacă se aprobă noua versiune, aceasta va deveni online
            
            logActivity($user_id, 'edit_online_version', 'User '. $_SESSION['user']['username'].' edited ONLINE version '. $baseVersion .' of article ID '. $articleId .', created new version '. $nextVersion);
            
            echo json_encode([
                'success' => true, 
                'was_editing_online_version' => 1,
                'base_version' => $baseVersion,
                'new_version' => $nextVersion,
                'message' => 'A fost creată o versiune nouă bazată pe versiunea online'
            ]);
            
        } else {
            // REGULA 1: Editarea unei versiuni care NU este online
            // Regula 4: Editarea unei versiuni aprobate o scoate din statusul Approved
            
            // Actualizează versiunea existentă în article_versions
            $db->query("UPDATE article_versions SET title = ?, content = ?, category_id = ?, status = ?, updated_at = NOW(), change_note = ? WHERE article_id = ? AND version_number = ?",
                [$title, $clean_content, $category_id, $status, $change_note ?: "Modificare versiune {$baseVersion}", $articleId, $baseVersion]);
            
            logActivity($user_id, 'edit_version', 'User '. $_SESSION['user']['username'].' edited NON-ONLINE version '. $baseVersion .' of article ID '. $articleId);
            
            echo json_encode([
                'success' => true, 
                'was_editing_online_version' => 0,
                'base_version' => $baseVersion,
                'message' => 'Versiunea a fost actualizată'
            ]);
        }

        // Actualizează tagurile (pentru ambele cazuri)
        $db->query("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
        if ($articleId && $tagList) {
            foreach ($tagList as $tagName) {
                $tagStmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                $tagStmt->execute([$tagName]);
                $tagId = $tagStmt->fetchColumn();
                if (!$tagId) {
                    $db->query("INSERT INTO tags (name) VALUES (?)", [$tagName]);
                    $tagId = $db->lastInsertedId();
                }
                $db->query("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)", [$articleId, $tagId]);
            }
        }

        exit;
    }

}

?>
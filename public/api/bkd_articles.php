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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_versions') {
    $articleId = (int)($_GET['id'] ?? 0);
    $versions = $db->fetchAll("SELECT version_number, status, change_note, created_at, is_online FROM article_versions WHERE article_id = ? ORDER BY version_number DESC", [$articleId]);
    echo json_encode(['success' => true, 'versions' => $versions]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_version_history') {
    $articleId = (int)($_GET['id'] ?? 0);
    $history = $db->fetchAll("
        SELECT av.version_number, av.status, av.change_note, av.created_at, av.updated_at, av.is_online, u.username as author 
        FROM article_versions av 
        LEFT JOIN users u ON av.author_id = u.id 
        WHERE av.article_id = ? 
        ORDER BY av.version_number DESC
    ", [$articleId]);
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_version') {
    $articleId = (int)($_GET['id'] ?? 0);
    $version = (int)($_GET['version'] ?? 1);
    $ver = $db->fetchSingle("SELECT title, content, category_id, status, created_at, updated_at, change_note, is_online FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
    if (!$ver) {
        echo json_encode(['error' => 'Versiune inexistentă']);
        exit;
    }
    echo json_encode(['success' => true] + $ver);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_article') {
    $articleId = (int)($_GET['id'] ?? 0);
    $article = $db->fetchSingle("SELECT * FROM articles WHERE id = ?", [$articleId]);
    if (!$article) {
        echo json_encode(['error' => 'Articol inexistent']);
        exit;
    }
    // Încarcă tagurile articolului
    $tags = $db->fetchAll("SELECT t.name FROM tags t
        JOIN article_tags at ON at.tag_id = t.id
        WHERE at.article_id = ?", [$articleId]);
    $article['tags'] = array_column($tags, 'name');
    echo json_encode(['success' => true, 'article' => $article]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';

    $where = '';
    $params = [];
    if ($searchValue) {
        $where = "WHERE a.title LIKE :search1 OR u.username LIKE :search2 OR c.name LIKE :search3";
        $params = [
            ':search1' => "%$searchValue%",
            ':search2' => "%$searchValue%",
            ':search3' => "%$searchValue%"
        ];
    }

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles");
    $totalArticles = $totalStmt->fetchColumn();

    $filteredStmt = $db->prepare("
        SELECT COUNT(*) FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        $where
    ");
    foreach ($params as $k => $v){
        $filteredStmt->bindValue($k, $v);
    }
    $filteredStmt->execute();
    $filtered = $filteredStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT a.id as article_id, a.title, a.status, a.publish_at, a.updated_at, a.user_id, a.version as current_version,
               u.username, c.name as category,
               GROUP_CONCAT(DISTINCT av.version_number ORDER BY av.version_number DESC) as versions_list
        FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN article_versions av ON a.id = av.article_id
        $where
        GROUP BY a.id
        ORDER BY a.updated_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rownum = $start + 1;
    foreach ($articles as &$a) {
        $a['rownum'] = $rownum++;
        $a['versions'] = [];
        if ($a['versions_list']) {
            foreach (explode(',', $a['versions_list']) as $v) {
                $a['versions'][] = ['version_number' => (int)$v];
            }
        }
        unset($a['versions_list']);
    }

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalArticles,
        "recordsFiltered" => $filtered,
        "data" => $articles
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Creare articol nou
    if ($action === 'add_article') {
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
        $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at, created_at, updated_at, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at, $created_at, $created_at]);
        $aid = $db->lastInsertedId();

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

    // Editare articol
    if ($action === 'edit_article') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $baseVersion = (int)($_POST['base_version'] ?? 1);
        $isEditingOnlineVersion = (int)($_POST['is_editing_online_version'] ?? 0);
        
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

    // Schimbarea datei de publicare
    if ($action === 'publish_at') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $publishAt = $_POST['publish_at'] ?? null;
        
        // Actualizează în tabelul articles
        $db->query("UPDATE articles SET publish_at = ?, updated_at = NOW() WHERE id = ?", [$publishAt, $articleId]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // Aprobat
    if ($action === 'approve') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 1);
        $publishAt = $_POST['publish_at'] ?? null;
        $user_id = $_SESSION['user']['id'];

        // Verifică dacă versiunea există și are statusul 'pending'
        $versionData = $db->fetchSingle("SELECT status FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
        
        if (!$versionData) {
            echo json_encode(['error' => 'Versiunea specificată nu există']);
            exit;
        }
        
        if ($versionData['status'] !== 'pending') {
            echo json_encode(['error' => 'Doar versiunile în așteptare pot fi aprobate']);
            exit;
        }

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

    // Publish action - NEW
    if ($action === 'publish') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);
        $user_id = $_SESSION['user']['id'];
        $user_role = $_SESSION['user']['role'] ?? '';
        
        // Validate inputs
        if (!$articleId || !$version) {
            echo json_encode(['success' => false, 'error' => 'ID articol și versiune sunt obligatorii']);
            exit;
        }
        
        // Check permissions - only editor, moderator, admin, superadmin can publish
        $allowed_roles = ['editor', 'moderator', 'admin', 'superadmin'];
        if (!in_array($user_role, $allowed_roles)) {
            echo json_encode(['success' => false, 'error' => 'Nu aveți permisiunea să publicați articole']);
            exit;
        }
        
        try {
            $db->beginTransaction();
            
            // 1. Get the version to be published from article_versions
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
                        status = 'approved',
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
                    ) VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, NOW(), NOW())
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

    // Restore version
    if ($action === 'restore') {
        $articleId = (int)($_POST['id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);
        $user_id = $_SESSION['user']['id'];

        if (!$articleId || !$version) {
            echo json_encode(['success' => false, 'error' => 'ID și versiune sunt obligatorii']);
            exit;
        }

        // Verifică dacă versiunea există și este aprobată
        $versionData = $db->fetchSingle("SELECT * FROM article_versions WHERE article_id = ? AND version_number = ? AND status = 'approved'", [$articleId, $version]);
        
        if (!$versionData) {
            echo json_encode(['success' => false, 'error' => 'Versiunea nu există sau nu este aprobată']);
            exit;
        }

        // Verifică dacă versiunea nu este deja online
        if ($versionData['is_online'] == 1) {
            echo json_encode(['success' => false, 'error' => 'Această versiune este deja online']);
            exit;
        }

        try {
            $db->beginTransaction();
            
            // Marchează toate versiunile ca nefiind online
            $db->query("UPDATE article_versions SET is_online = 0 WHERE article_id = ?", [$articleId]);
            
            // Marchează versiunea selectată ca fiind online
            $db->query("UPDATE article_versions SET is_online = 1 WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
            
            // Actualizează articolul principal cu datele din versiunea restaurată
            $db->query("UPDATE articles SET title = ?, content = ?, category_id = ?, version = ?, updated_at = NOW() WHERE id = ?", 
                [$versionData['title'], $versionData['content'], $versionData['category_id'], $version, $articleId]);
            
            $db->commit();
            
            logActivity($user_id, 'restore_article', 'User '. $_SESSION['user']['username'].' restored version '. $version .' of article ID '. $articleId);
            echo json_encode(['success' => true, 'message' => 'Versiunea a fost restaurată cu succes']);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Eroare la restaurarea versiunii: ' . $e->getMessage()]);
        }
        exit;
    }

    // Disable/Enable article
    if ($action === 'disable' || $action === 'enable') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $user_id = $_SESSION['user']['id'];
        $newStatus = $action === 'disable' ? 'disabled' : 'approved';
        
        $db->query("UPDATE articles SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $articleId]);
        
        logActivity($user_id, $action.'_article', 'User '. $_SESSION['user']['username'].' '.$action.'d article ID '. $articleId);
        echo json_encode(['success' => true]);
        exit;
    }

    // Delete article
    if ($action === 'delete') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $user_id = $_SESSION['user']['id'];
        
        try {
            $db->beginTransaction();
            
            // Șterge tagurile articolului
            $db->query("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
            
            // Șterge toate versiunile articolului
            $db->query("DELETE FROM article_versions WHERE article_id = ?", [$articleId]);
            
            // Șterge articolul principal
            $db->query("DELETE FROM articles WHERE id = ?", [$articleId]);
            
            $db->commit();
            
            logActivity($user_id, 'delete_article', 'User '. $_SESSION['user']['username'].' deleted article ID '. $articleId);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Eroare la ștergerea articolului: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['error' => 'Acțiune necunoscută']);
}
?>
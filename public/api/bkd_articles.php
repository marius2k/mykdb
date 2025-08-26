<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_articles.php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['edit_article','disable_article','enable_article','create_article','approve_article','restore_article'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_versions') {
    $articleId = (int)($_GET['id'] ?? 0);
    $versions = $db->fetchAll("SELECT version_number, status, change_note, created_at FROM article_versions WHERE article_id = ? ORDER BY version_number DESC", [$articleId]);
    echo json_encode(['success' => true, 'versions' => $versions]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_version_history') {
    $articleId = (int)($_GET['id'] ?? 0);
    $history = $db->fetchAll("
        SELECT av.version_number, av.status, av.change_note, av.created_at, av.updated_at, u.username as author 
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
    $ver = $db->fetchSingle("SELECT title, content, category_id, status, created_at, updated_at, change_note FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
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
        $params =[
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
    $filteredStmt->execute($params);
    $filtered = $filteredStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT a.*, u.username, c.name AS category
        FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        $where
        ORDER BY a.created_at DESC
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
        $a['article_id'] = $a['id'] ?? 0;
        $a['title'] = $a['title'] ?? '';
        $a['username'] = $a['username'] ?? '';
        $a['category'] = $a['category'] ?? '';
        $a['status'] = $a['status'] ?? '';
        $a['publish_at'] = $a['publish_at'] ?? '';
        $a['versions'] = $db->fetchAll("SELECT version_number, status FROM article_versions WHERE article_id = ? ORDER BY version_number DESC", [$a['id']]) ?? [];
        $a['current_version'] = $a['version'] ?? 1; // Folosește câmpul version din articles
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
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Creare articol
    if ($action === 'add_article') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $user_id = $_SESSION['user']['id'];
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        $change_note = trim($_POST['change_note'] ?? '');

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at]);
        $aid = $db->lastInsertedId();

        // VoA: Salvează versiunea inițială (v1) în article_versions
        $initial_note = $change_note ?: 'Versiune inițială';
        $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, created_at, updated_at, change_note) VALUES (?, 1, ?, ?, ?, ?, ?, NOW(), NOW(), ?)",
            [$aid, $title, $clean_content, $category_id, $user_id, $status, $initial_note]);

        // Salvează tagurile
        if ($aid && $tagList) {
            foreach ($tagList as $tagName) {
                if (!$tagName) continue;
                $tag = $db->fetchSingle("SELECT id FROM tags WHERE name = ?", [$tagName]);
                if (!$tag) {
                    $db->query("INSERT INTO tags (name) VALUES (?)", [$tagName]);
                    $tagId = $db->lastInsertedId();
                } else {
                    $tagId = $tag['id'];
                }
                $db->query("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)", [$aid, $tagId]);
            }
        }

        logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created the article:'. $title);
        sendNotificationToRole('moderator', 'info','Article <a href="view_article.php?id='. $aid.'">'. $title.'</a> has been submitted for approval.');
        sendNotificationToRole('admin', 'info', 'Article <a href="view_article.php?id='. $aid.'">'. $title.'</a> has been submitted for approval.');
        //awardArticlePublished($user_id, $aid, $title);
        echo json_encode(['success' => true]);
        exit;
    }

    // Editare articol
    if ($action === 'edit_article') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $baseVersion = (int)($_POST['base_version'] ?? 1);
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

        $stmt = $db->prepare("UPDATE articles SET title=?, content=?, category_id=?, publish_at=?, status=?, updated_at=? WHERE id=?");
        $stmt->execute([$title, $clean_content, $category_id, $publish_at, $status, $updated_at, $articleId]);

        // VoA: Salvează o versiune nouă la editare
        $lastVersion = $db->fetchSingle("SELECT MAX(version_number) as v FROM article_versions WHERE article_id = ?", [$articleId]);
        $nextVersion = ($lastVersion && $lastVersion['v']) ? $lastVersion['v'] + 1 : 1;
        
        // Creează nota de modificare cu informații despre versiunea de bază
        if ($change_note) {
            $edit_note = $change_note;
        } else {
            $edit_note = "Modificare bazată pe versiunea {$baseVersion} → versiunea {$nextVersion}";
        }
        
        $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, created_at, updated_at, change_note) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)",
            [$articleId, $nextVersion, $title, $clean_content, $category_id, $user_id, $status, $edit_note]);

        // Salvează tagurile (șterge vechile și adaugă noile)
        $db->query("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
        if ($articleId && $tagList) {
            foreach ($tagList as $tagName) {
                if (!$tagName) continue;
                $tag = $db->fetchSingle("SELECT id FROM tags WHERE name = ?", [$tagName]);
                if (!$tag) {
                    $db->query("INSERT INTO tags (name) VALUES (?)", [$tagName]);
                    $tagId = $db->lastInsertedId();
                } else {
                    $tagId = $tag['id'];
                }
                $db->query("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)", [$articleId, $tagId]);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // Acțiuni pe articol
    $articleId = (int)($_POST['article_id'] ?? 0);

    if ($action === 'publish_at') {
        $publishAt = $_POST['publish_at'] ?? null;
        if ($publishAt && !strtotime($publishAt)) {
            echo json_encode(['error' => '⚠️ Dată invalidă.']);
            exit;
        }
        $db->query("UPDATE articles SET publish_at = ? WHERE id = ?", [$publishAt ?: null, $articleId]);
        $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
        sendNotification($article['user_id'], 'Article Publication','Your article <a href="view_article.php?id='.$articleId.'">'. truncateText($article['title'],30). '</a> is published at ' .$publishAt,'info');
        awardArticlePublished($_SESSION['user']['id'], $articleId, $article['title']);
        echo json_encode(['success' => true]);
        exit;   
    }

    if ($action === 'approve') {
        // Primește versiunea specificată din frontend
        $versionToApprove = $_POST['version'] ?? null;
        
        if (!$versionToApprove) {
            echo json_encode(['error' => 'Versiunea nu a fost specificată.']);
            exit;
        }
        
        // Găsește versiunea specifică care trebuie aprobată
        $versionData = $db->fetchSingle("SELECT version_number, title, content, category_id, author_id FROM article_versions WHERE article_id = ? AND version_number = ? AND status = 'pending'", [$articleId, $versionToApprove]);
        
        if (!$versionData) {
            echo json_encode(['error' => 'Versiunea specificată nu există sau nu este în status pending.']);
            exit;
        }
        
        // Verifică dacă articolul există deja în tabela articles
        $existingArticle = $db->fetchSingle("SELECT id FROM articles WHERE id = ?", [$articleId]);
        
        if ($existingArticle) {
            // Articolul există - UPDATE cu datele din versiunea aprobată
            $db->query("UPDATE articles SET title = ?, content = ?, category_id = ?, version = ?, status = 'approved', updated_at = NOW() WHERE id = ?", 
                [$versionData['title'], $versionData['content'], $versionData['category_id'], $versionData['version_number'], $articleId]);
        } else {
            // Articolul nu există - INSERT în tabela articles cu datele din versiune
            $db->query("INSERT INTO articles (id, title, content, category_id, user_id, version, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())", 
                [$articleId, $versionData['title'], $versionData['content'], $versionData['category_id'], $versionData['author_id'], $versionData['version_number']]);
        }
        
        // Marchează versiunea specifică ca aprobată în article_versions
        $db->query("UPDATE article_versions SET status = 'approved', updated_at = NOW() WHERE article_id = ? AND version_number = ?", [$articleId, $versionToApprove]);
        
        logActivity($_SESSION['user']['id'], 'article_approved', 'User ' .$_SESSION['user']['username'] .' approved version ' . $versionToApprove . ' of article ID ' . $articleId);
        $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
        sendNotification($article['user_id'], 'Article Approved','Your article <a href="view_article.php?id='.$articleId.'">'. truncateText($article['title'],30). '</a> has been approved.','info');
        awardArticlePublished($_SESSION['user']['id'], $articleId, $article['title']);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'restore') {
        // Verifică că utilizatorul este moderator
        if ($_SESSION['role'] !== 'moderator' && $_SESSION['role'] !== 'admin') {
            echo json_encode(['error' => 'Nu aveți permisiuni pentru a restaura versiuni.']);
            exit;
        }

        $versionToRestore = $_POST['version'] ?? null;
        
        if (!$versionToRestore) {
            echo json_encode(['error' => 'Versiunea nu a fost specificată.']);
            exit;
        }
        
        // Verifică că versiunea este approved și nu este versiunea online
        $versionData = $db->fetchSingle("SELECT version_number, title, content, category_id, author_id FROM article_versions WHERE article_id = ? AND version_number = ? AND status = 'approved'", [$articleId, $versionToRestore]);
        
        if (!$versionData) {
            echo json_encode(['error' => 'Versiunea specificată nu există sau nu este approved.']);
            exit;
        }
        
        // Verifică că nu este versiunea online curentă
        $currentArticle = $db->fetchSingle("SELECT version FROM articles WHERE id = ?", [$articleId]);
        if ($currentArticle['version'] == $versionToRestore) {
            echo json_encode(['error' => 'Nu poți restaura versiunea care este deja online.']);
            exit;
        }
        
        // Obține tag-urile existente din articolul curent
        $currentTags = $db->fetchSingle("SELECT tags FROM articles WHERE id = ?", [$articleId]);
        
        // Restaurează versiunea în tabelul articles (păstrează tag-urile existente)
        $db->query("UPDATE articles SET title = ?, content = ?, category_id = ?, version = ?, updated_at = NOW() WHERE id = ?", 
                   [$versionData['title'], $versionData['content'], $versionData['category_id'], $versionToRestore, $articleId]);
        
        // Log activity
        logActivity($_SESSION['user']['id'], 'article_restored', 'User ' .$_SESSION['user']['username'] .' restored version ' . $versionToRestore . ' of article ID ' . $articleId);
        
        // Trimite notificare autorului
        $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
        sendNotification($article['user_id'], 'Article Restored','Your article <a href="view_article.php?id='.$articleId.'">'. truncateText($article['title'],30). '</a> has been restored to version ' . $versionToRestore . '.','info');
        
        echo json_encode(['success' => true, 'message' => 'Versiunea a fost restaurată cu succes.']);
        exit;
    }

    if ($action === 'disable') {
        $newDate = date('Y-m-d H:i:s');
        $db->query("UPDATE articles SET status='pending', publish_at = ? WHERE id=?", [$newDate, $articleId]);
        logActivity($_SESSION['user']['id'], 'article_disabled', 'User ' .$_SESSION['user']['username'] .' disabled an article');
        $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
        sendNotification($article['user_id'], 'Article Disabled','Your article <a href="view_article.php?id='.$articleId.'">'. truncateText($article['title'],30). '</a> has been disabled.','warning');
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_articles.php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['edit_article','disable_article','enable_article','create_article','approve_article'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

// GET: articol pentru editare (cu taguri)
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

// GET: listare articole (ex. pentru DataTables)
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
    }

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalArticles,
        "recordsFiltered" => $filtered,
        "data" => $articles
    ]);
    exit;
}

// POST: creare/editare articol și acțiuni
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

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at]);
        $aid = $db->lastInsertedId();

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
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
        $user_id = $_SESSION['user']['id'];
        $updated_at = date('Y-m-d H:i:s');
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $tagList = array_filter(array_map('trim', explode(',', $tags)));

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        $stmt = $db->prepare("UPDATE articles SET title=?, content=?, category_id=?, publish_at=?, status=?, updated_at=? WHERE id=?");
        $stmt->execute([$title, $clean_content, $category_id, $publish_at, $status, $updated_at, $articleId]);

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
        $db->query("UPDATE articles SET status = 'approved' WHERE id = ?", [$articleId]);
        logActivity($_SESSION['user']['id'], 'article_approved', 'User ' .$_SESSION['user']['username'] .' approved an article');
        $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
        sendNotification($article['user_id'], 'Article Approved','Your article <a href="article.php?id='.$articleId.'">'. truncateText($article['title'],30). '</a> has been approved.','info');
        awardArticlePublished($_SESSION['user']['id'], $articleId, $article['title']);
        echo json_encode(['success' => true]);
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
<?php

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Paginare
    $perPage = 5;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $perPage;

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles");
    $totalArticles = $totalStmt->fetchColumn();
    $totalPages = ceil($totalArticles / $perPage);

    $stmt = $db->prepare("
        SELECT a.*, u.username, c.name AS category
        FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();

    echo json_encode([
        'articles' => $articles,
        'page' => $page,
        'totalPages' => $totalPages,
        'user_id' => $_SESSION['user']['id'],
        'role' => $_SESSION['user']['role']
    ]);
    exit;
}

// POST: creare articol sau acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'add_article') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $user_id = $_SESSION['user']['id'];
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at]);
        $aid = $db->lastInsertedId();

        logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created the article:'. $title);
        sendNotificationToRole('moderator', 'info','Article <a href="view_article.php?id='. $aid.'">'. $title.'</a> has been submitted for approval.');
        sendNotificationToRole('admin', 'info', 'Article <a href="view_article.php?id='. $aid.'">'. $title.'</a> has been submitted for approval.');

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
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'approve') {
        $db->query("UPDATE articles SET status = 'approved' WHERE id = ?", [$articleId]);
        logActivity($_SESSION['user']['id'], 'article_approved', 'User ' .$_SESSION['user']['username'] .' approved an article');
        $article = $db->fetchSingle("SELECT user_id, title FROM articles WHERE id = ?", [$articleId]);
        sendNotification($article['user_id'], 'Article Approved','Your article <a href="article.php?id='.$articleId.'">'. truncateText($article['title'],30). '</a> has been approved.','info');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'disable') {
        $newDate = date('Y-m-d H:i:s');
        $db->query("UPDATE articles SET status='pending', publish_at = ? WHERE id=?", [$newDate, $articleId]);
        logActivity($_SESSION['user']['id'], 'article_disabled', 'User ' .$_SESSION['user']['username'] .' disabled an article');
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
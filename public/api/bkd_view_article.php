<?php

require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';


$lang = $_SESSION['settings']['language'] ?? 'ro';

//require_once APP_ROOT . 'assets/lang/.$lang.php'; // sau en.php

$langFile = APP_ROOT . "assets/lang/{$lang}.php";
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include APP_ROOT . "assets/lang/en.php";
}



header('Content-Type: application/json');
/*
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
}
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid article id']);
    exit;
}


$id = (int)$_GET['id'];
$db = new Database();

// Increment view counter
//$db->query("UPDATE articles SET views = views + 1 WHERE id = ?", [$id]);
if (isset($_GET['id'])) {
    logArticleView((int)$_GET['id']);
}

// Fetch article + voturi articol
$stmt = $db->prepare("
    SELECT a.*, u.username, c.name AS category, c.icon,
        (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id AND vote_type = 'like') AS likes,
        (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id AND vote_type = 'dislike') AS dislikes
    FROM articles a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    echo json_encode(['error' => 'Articol inexistent sau neaprobat.']);
    exit;
}

// Fetch tags pentru acest articol
$tags = $db->fetchAll("
    SELECT t.name 
    FROM tags t
    JOIN article_tags at ON t.id = at.tag_id 
    WHERE at.article_id = ?
", [$id]);

$isBookmarked = false;
if (!empty($_SESSION['user']['id'])) {
    $isBookmarked = is_article_bookmarked($id, $_SESSION['user']['id']);
}


// Fetch comments + voturi comentarii
$comments = $db->fetchAll("
    SELECT c.id, c.content, c.created_at, u.username,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id AND vote_type = 'like') AS likes,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id AND vote_type = 'dislike') AS dislikes
    FROM article_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC
", [$article['id']]);

$article['comments'] = $comments;
$article['is_bookmarked'] = $isBookmarked;
$article['tags'] = array_column($tags, 'name');

echo json_encode($article);
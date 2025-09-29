<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid article id']);
    exit;
}

$id = (int)$_GET['id'];
$version = (int)($_GET['version'] ?? 1);
$isOnline = (int)($_GET['isonline'] ?? 0);
$db = new Database();

// Increment view counter
if (isset($_GET['id'])) {
    logArticleView((int)$_GET['id']);
}

$article = null;

if ($isOnline == 1) {
    // Pentru versiuni online, încearcă mai întâi în articles
    $stmt = $db->prepare("
        SELECT a.*, u.username, c.name AS category, c.icon,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id AND vote_type = 'like') AS likes,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id AND vote_type = 'dislike') AS dislikes,
            0 as is_version, a.version as version_number
        FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id = ? AND a.status = 'approved'
    ");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        // Fallback: caută versiunea online în article_versions
        $stmt = $db->prepare("
            SELECT av.*, av.author_id as user_id, u.username, c.name AS category, c.icon,
                av.article_id as id,
                (SELECT COUNT(*) FROM article_likes WHERE article_id = av.article_id AND vote_type = 'like') AS likes,
                (SELECT COUNT(*) FROM article_likes WHERE article_id = av.article_id AND vote_type = 'dislike') AS dislikes,
                1 as is_version
            FROM article_versions av
            JOIN users u ON av.author_id = u.id
            LEFT JOIN categories c ON av.category_id = c.id
            WHERE av.article_id = ? AND av.version_number = ? AND av.is_online = 1
        ");
        $stmt->execute([$id, $version]);
        $article = $stmt->fetch();
    }
} else {
    // Pentru versiuni non-online, caută DOAR în article_versions
    $stmt = $db->prepare("
        SELECT av.*, av.author_id as user_id, u.username, c.name AS category, c.icon,
            av.article_id as id,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = av.article_id AND vote_type = 'like') AS likes,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = av.article_id AND vote_type = 'dislike') AS dislikes,
            1 as is_version
        FROM article_versions av
        JOIN users u ON av.author_id = u.id
        LEFT JOIN categories c ON av.category_id = c.id
        WHERE av.article_id = ? AND av.version_number = ?
    ");
    $stmt->execute([$id, $version]);
    $article = $stmt->fetch();
}

if (!$article) {
    http_response_code(404);
    echo json_encode(['error' => 'Articol inexistent sau neaprobat.']);
    exit;
}

// Fetch tags
$tags = $db->fetchAll("
    SELECT t.name 
    FROM tags t
    JOIN article_tags at ON t.id = at.tag_id 
    WHERE at.article_id = ?
", [$id]);

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

// Check if bookmarked
$isBookmarked = false;
if (!empty($_SESSION['user']['id'])) {
    $bookmark = $db->fetchSingle("SELECT id FROM bookmarks WHERE user_id = ? AND article_id = ?", [$_SESSION['user']['id'], $id]);
    $isBookmarked = !empty($bookmark);
}

$article['comments'] = $comments;
$article['is_bookmarked'] = $isBookmarked;
$article['tags'] = array_column($tags, 'name');
$article['is_online'] = $isOnline;
$article['change_note'] = $article['change_note'] ?? null;

echo json_encode($article);
?>
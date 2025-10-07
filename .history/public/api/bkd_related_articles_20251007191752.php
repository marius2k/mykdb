<?php
/**
 * API pentru obținerea articolelor relacionate
 * Folosește multiple algoritme pentru a găsi articole similare
 */

require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid article id']);
    exit;
}

$currentArticleId = (int)$_GET['id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;

$db = new Database();

// Obține informațiile articolului curent
$currentArticle = $db->fetchSingle("
    SELECT id, title, content, category_id, user_id 
    FROM articles 
    WHERE id = ? AND status = 'approved'
", [$currentArticleId]);

if (!$currentArticle) {
    http_response_code(404);
    echo json_encode(['error' => 'Article not found']);
    exit;
}

// Obține tag-urile articolului curent
$currentTags = $db->fetchAll("
    SELECT t.id 
    FROM tags t
    JOIN article_tags at ON t.id = at.tag_id 
    WHERE at.article_id = ?
", [$currentArticleId]);
$currentTagIds = array_column($currentTags, 'id');

$relatedArticles = [];
$articleScores = [];

// 1. SIMILITUDINE PE BAZA TAG-URILOR (Cel mai important)
if (!empty($currentTagIds)) {
    $tagPlaceholders = implode(',', array_fill(0, count($currentTagIds), '?'));
    $tagMatches = $db->fetchAll("
        SELECT 
            a.id, a.title, a.views, a.created_at,
            c.name as category_name, c.icon as category_icon,
            u.username,
            COUNT(at.tag_id) as common_tags
        FROM articles a
        JOIN article_tags at ON a.id = at.article_id
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.user_id = u.id
        WHERE at.tag_id IN ($tagPlaceholders)
            AND a.id != ?
            AND a.status = 'published'
        GROUP BY a.id, a.title, a.views, a.created_at, c.name, c.icon, u.username
        ORDER BY common_tags DESC, a.views DESC
        LIMIT ?
    ", array_merge($currentTagIds, [$currentArticleId, $limit * 2]));

    foreach ($tagMatches as $article) {
        $score = $article['common_tags'] * 10; // 10 puncte per tag comun
        $articleScores[$article['id']] = $score;
        $relatedArticles[$article['id']] = $article;
    }
}

// 2. SIMILITUDINE PE CATEGORIA (Important)
$categoryMatches = $db->fetchAll("
    SELECT 
        a.id, a.title, a.views, a.created_at,
        c.name as category_name, c.icon as category_icon,
        u.username
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN users u ON a.user_id = u.id
    WHERE a.category_id = ?
        AND a.id != ?
        AND a.status = 'published'
    ORDER BY a.views DESC, a.created_at DESC
    LIMIT ?
", [$currentArticle['category_id'], $currentArticleId, $limit]);

foreach ($categoryMatches as $article) {
    if (!isset($articleScores[$article['id']])) {
        $articleScores[$article['id']] = 0;
        $relatedArticles[$article['id']] = $article;
    }
    $articleScores[$article['id']] += 5; // 5 puncte pentru aceeași categorie
}

// 3. ACELAȘI AUTOR (Bonus)
$authorMatches = $db->fetchAll("
    SELECT 
        a.id, a.title, a.views, a.created_at,
        c.name as category_name, c.icon as category_icon,
        u.username
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN users u ON a.user_id = u.id
    WHERE a.user_id = ?
        AND a.id != ?
        AND a.status = 'published'
    ORDER BY a.views DESC, a.created_at DESC
    LIMIT ?
", [$currentArticle['user_id'], $currentArticleId, $limit]);

foreach ($authorMatches as $article) {
    if (!isset($articleScores[$article['id']])) {
        $articleScores[$article['id']] = 0;
        $relatedArticles[$article['id']] = $article;
    }
    $articleScores[$article['id']] += 3; // 3 puncte pentru același autor
}

// 4. POPULARE ȘI RECENTE (Fallback)
if (count($relatedArticles) < $limit) {
    $popularArticles = $db->fetchAll("
        SELECT 
            a.id, a.title, a.views, a.created_at,
            c.name as category_name, c.icon as category_icon,
            u.username
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.user_id = u.id
        WHERE a.id != ?
            AND a.status = 'published'
        ORDER BY a.views DESC, a.created_at DESC
        LIMIT ?
    ", [$currentArticleId, $limit * 2]);

    foreach ($popularArticles as $article) {
        if (!isset($articleScores[$article['id']])) {
            $articleScores[$article['id']] = 1; // 1 punct pentru popularitate
            $relatedArticles[$article['id']] = $article;
        }
    }
}

// Sortează după scor și limitează rezultatele
arsort($articleScores);
$sortedRelatedArticles = [];

$count = 0;
foreach ($articleScores as $articleId => $score) {
    if ($count >= $limit) break;
    
    $article = $relatedArticles[$articleId];
    $article['relevance_score'] = $score;
    
    // Adaugă tag-urile pentru fiecare articol relacionat
    $tags = $db->fetchAll("
        SELECT t.name 
        FROM tags t
        JOIN article_tags at ON t.id = at.tag_id 
        WHERE at.article_id = ?
    ", [$articleId]);
    $article['tags'] = array_column($tags, 'name');
    
    $sortedRelatedArticles[] = $article;
    $count++;
}

echo json_encode([
    'related_articles' => $sortedRelatedArticles,
    'total_found' => count($relatedArticles),
    'current_article_id' => $currentArticleId
]);

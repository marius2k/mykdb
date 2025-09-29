<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

// Preia tag-ul din GET
$tag = trim($_GET['tag'] ?? '');

if (empty($tag)) {
    echo json_encode(['error' => 'Tag necesar pentru filtrare.']);
    exit;
}

$db = new Database();

try {
    // Query pentru articole cu tag-ul specificat, doar cele aprobate
    $sql = "
        SELECT DISTINCT a.*, 
               u.username, 
               c.name as category_name, 
               c.icon as category_icon,
               (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id AND vote_type = 'like') AS likes,
               (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id AND vote_type = 'dislike') AS dislikes,
               (SELECT COUNT(*) FROM article_views WHERE article_id = a.id) AS views
        FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        JOIN article_tags at ON a.id = at.article_id
        JOIN tags t ON at.tag_id = t.id
        WHERE t.name = ? AND a.status = 'approved'
        ORDER BY a.created_at DESC
    ";
    
    $articles = $db->fetchAll($sql, [$tag]);
    
    // Pentru fiecare articol, obține toate tagurile
    foreach ($articles as &$article) {
        $tags = $db->fetchAll("
            SELECT t.name 
            FROM tags t
            JOIN article_tags at ON t.id = at.tag_id 
            WHERE at.article_id = ?
        ", [$article['id']]);
        
        $article['tags'] = array_column($tags, 'name');
    }
    
    echo json_encode([
        'success' => true,
        'articles' => $articles,
        'tag' => $tag,
        'count' => count($articles)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Eroare la căutarea articolelor: ' . $e->getMessage()]);
}
?>

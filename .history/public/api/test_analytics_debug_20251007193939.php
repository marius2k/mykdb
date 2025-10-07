<?php
require_once '../../config/bootstrap.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Test basic query first
    echo "Testing basic articles query...\n";
    $articles = $db->fetchAll("SELECT id, title FROM articles WHERE status = 'published' LIMIT 5");
    echo "Basic query successful. Found " . count($articles) . " articles.\n";
    
    // Test with subqueries
    echo "Testing with subqueries...\n";
    $topArticles = $db->fetchAll("
        SELECT 
            a.id,
            a.title,
            (SELECT COUNT(*) FROM article_views WHERE article_id = a.id) as total_views,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes
        FROM articles a
        WHERE a.status = 'published'
        ORDER BY total_views DESC
        LIMIT 5
    ");
    echo "Subquery successful. Found " . count($topArticles) . " articles.\n";
    print_r($topArticles);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Analytics test error: " . $e->getMessage());
}
?>
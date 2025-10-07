<?php
include_once '../config/bootstrap.php';

// Get article ID and version from URL parameters
$articleId = (int)($_GET['id'] ?? 0);
$version = (int)($_GET['version'] ?? 1);
$isOnline = (int)($_GET['isonline'] ?? 0);

echo "URL Parameters:<br>";
echo "articleId: " . $articleId . "<br>";
echo "version: " . $version . "<br>";
echo "isOnline: " . $isOnline . "<br><br>";

if (!$articleId) {
    echo "No article ID provided<br>";
    exit;
}

// Check if article exists
$db = new Database();
$article = null;

try {
    if ($isOnline == 1) {
        $article = $db->fetchSingle("
            SELECT a.*, u.username, c.name as category_name, c.icon as category_icon,
                   'articles' as source_table, 0 as is_version, a.version as version_number
            FROM articles a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.id = ? AND a.status = 'published'
        ", [$articleId]);
    }
    
    echo "Database Query Result:<br>";
    if ($article) {
        echo "Article found: " . $article['title'] . "<br>";
        echo "Status: " . $article['status'] . "<br>";
    } else {
        echo "Article NOT found or not published<br>";
    }
    
    echo "<br>JavaScript Conditions:<br>";
    echo "isOnline == 1: " . ($isOnline == 1 ? 'true' : 'false') . "<br>";
    echo "article exists: " . ($article ? 'true' : 'false') . "<br>";
    echo "Should initialize tracker: " . (($isOnline == 1 && $article) ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>

<script>
console.log('Test page loaded');
console.log('articleId from PHP:', <?= $articleId ?>);
console.log('isOnline from PHP:', <?= $isOnline ?>);
console.log('article exists from PHP:', <?= $article ? 'true' : 'false' ?>);
</script>
<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$db = new Database();

// GET: toate tagurile existente sau căutare
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    
    if ($search) {
        // Căutare tags care încep cu termenul de căutare
        $tags = $db->fetchAll(
            "SELECT name FROM tags WHERE name LIKE ? ORDER BY name LIMIT 10", 
            [$search . '%']
        );
    } else {
        // Toate tagurile, ordonate după frecvența de utilizare
        $tags = $db->fetchAll("
            SELECT t.name, COUNT(at.tag_id) as usage_count 
            FROM tags t 
            LEFT JOIN article_tags at ON t.id = at.tag_id 
            GROUP BY t.id, t.name 
            ORDER BY usage_count DESC, t.name ASC
            LIMIT 50
        ");
    }
    
    echo json_encode([
        'success' => true,
        'tags' => array_column($tags, 'name')
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>

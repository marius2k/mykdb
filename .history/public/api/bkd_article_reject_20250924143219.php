<?php

// endpoint for rejecting an article
// reject could be done for articles in pending or approved status
// after the reject action the article status will be set to draft again    


require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user']['id'];

// Verifică că este POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verifică CSRF token

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}



$articleId = (int)($_POST['article_id'] ?? 0);
$version = (int)($_POST['version'] ?? 1);

if (!$articleId) {
    echo json_encode(['error' => 'Article ID missing']);
    exit;
}

$db = new Database();

try {
    $db->beginTransaction();
    
    // Verifică că articolul si versiunea exista
    $article = $db->fetchSingle("SELECT * FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);

    if (!$article) {
        throw new Exception('Articol inexistent sau nu ai permisiune.');
    }
    
    if ($article['status'] !== 'pending' && $article['status'] !== 'approved') {
        throw new Exception('Doar articolele în pending sau approved pot fi respinse');
    }
    
    // Update status la draft

    $db->query("UPDATE article_versions SET status = 'draft' WHERE article_id = ? AND version_number = ?", [$articleId, $version]);

       
    // Log activitatea
    logActivity($userId, 'reject_article', 'User ' . $_SESSION['user']['username'] . ' rejected article: ' . $article['title']);

    // send notification to the user who created the article
    
    $authorId = getArticleAuthorId($articleId,'article_versions');
    sendNotificationToUser($authorId, 'info', 'Article <a href="view_article.php?id=' . $articleId . '">' . truncateText($article['title'], 30) . '</a> has been rejected.');

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Article rejected successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
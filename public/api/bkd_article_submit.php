<?php

// endpoint for submitting article to approval

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
    
    if ($article['status'] !== 'draft') {
        throw new Exception('Doar articolele în draft pot fi trimise pentru aprobat');
    }
    
    // Update status la pending
    //$db->query("UPDATE article_versions SET status = 'pending' WHERE article_id = ?", [$articleId]);
    $db->query("UPDATE article_versions SET status = 'pending' WHERE article_id = ? AND version_number = ?", [$articleId, $version]);

    // Dacă e o versiune specifică, update-ează și versiunea
    //if ($version > 1) {
    //    $db->query("UPDATE article_versions SET status = 'pending' WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
    //}
    
    // Log activitatea
    logActivity($userId, 'submit_article', 'User ' . $_SESSION['user']['username'] . ' submitted article: ' . $article['title']);
    
    // Trimite notificări către moderatori și admini
    sendNotificationToRole('moderator', 'info', 'Article <a href="view_article.php?id=' . $articleId . '&version=' . $version . '">' . truncateText($article['title'], 30) . '</a> has been submitted for approval.');
    sendNotificationToRole('admin', 'info', 'Article <a href="view_article.php?id=' . $articleId . '&version=' . $version . '">' . truncateText($article['title'], 30) . '</a> has been submitted for approval.');

    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Article submitted for approval successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
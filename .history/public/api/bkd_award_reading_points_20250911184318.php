<?php
require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/gamification_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

// Verifică CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$articleId = (int)($_POST['article_id'] ?? 0);
$userId = $_SESSION['user']['id'];

if (!$articleId) {
    echo json_encode(['success' => false, 'error' => 'Invalid article ID']);
    exit;
}

try {
    $db = new Database();
    
    // Verifică dacă request-ul vine din zona de administrare
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, '/admin/') !== false) {
        echo json_encode(['success' => true, 'message' => 'No points for admin views']);
        exit;
    }
    
    // Verifică dacă articolul există ȘI este online (publicat)
    // Mai întâi caută în articles (articole publicate)
    $article = $db->fetchSingle("SELECT title FROM articles WHERE id = ? AND status = 'approved'", [$articleId]);
    
    if (!$article) {
        // Dacă nu există în articles, caută în article_versions doar dacă is_online = 1
        $article = $db->fetchSingle("
            SELECT title 
            FROM article_versions 
            WHERE article_id = ? AND is_online = 1 AND status = 'approved'
            ORDER BY version_number DESC 
            LIMIT 1
        ", [$articleId]);
    }
    
    if (!$article) {
        // Nu acordă puncte pentru articole nepublicate sau neaprobate
        echo json_encode(['success' => true, 'message' => 'No points for unpublished articles']);
        exit;
    }
    
    // Verifică dacă utilizatorul a primit deja puncte pentru acest articol
    $alreadyAwarded = $db->fetchSingle(
        "SELECT id FROM points_history WHERE user_id = ? AND action = 'article_read' AND related_id = ?",
        [$userId, $articleId]
    );
    
    if ($alreadyAwarded) {
        echo json_encode(['success' => true, 'message' => 'Already awarded']);
        exit;
    }
    
    // Acordă puncte doar pentru articole online, aprobate și accesate din zona publică
    $success = awardArticleRead($userId, $articleId, $article['title']);
    
    echo json_encode(['success' => $success]);
    
} catch (Exception $e) {
    error_log("Error awarding reading points: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
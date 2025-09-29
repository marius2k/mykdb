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
    
    // Verifică dacă articolul există
    $article = $db->fetchSingle("SELECT title FROM articles WHERE id = ?", [$articleId]);
    if (!$article) {
        echo json_encode(['success' => false, 'error' => 'Article not found']);
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
    
    // Acordă puncte
    $success = awardArticleRead($userId, $articleId, $article['title']);
    
    echo json_encode(['success' => $success]);
    
} catch (Exception $e) {
    error_log("Error awarding reading points: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
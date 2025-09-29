<?php
require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';

// Activează erorile temporar pentru debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$lang = $_SESSION['settings']['language'] ?? 'ro';
$langFile = APP_ROOT . "assets/lang/{$lang}.php";
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include APP_ROOT . "assets/lang/en.php";
}

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid article id']);
        exit;
    }

    $id = (int)$_GET['id'];
    $version = (int)($_GET['version'] ?? 1);
    $isOnline = (int)($_GET['isonline'] ?? 0);
    
    error_log("bkd_view_article.php - Params: id=$id, version=$version, isOnline=$isOnline");
    
    // Testează conexiunea la baza de date
    $db = new Database();
    $testQuery = $db->query("SELECT 1 as test");
    $testResult = $testQuery->fetch();
    error_log("bkd_view_article.php - Database test result: " . json_encode($testResult));
    
    if (!$testResult) {
        throw new Exception("Database connection failed");
    }

    // Verifică dacă articolul există în article_versions
    $checkStmt = $db->prepare("SELECT article_id, version_number, status, is_online FROM article_versions WHERE article_id = ? AND version_number = ?");
    $checkStmt->execute([$id, $version]);
    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("bkd_view_article.php - Check result: " . json_encode($checkResult));
    
    if (!$checkResult) {
        echo json_encode(['error' => 'Article version not found in database', 'debug' => "id=$id, version=$version"]);
        exit;
    }

    // Increment view counter - comentat temporar
    /*
    try {
        if (isset($_GET['id']) && function_exists('logArticleView')) {
            logArticleView((int)$_GET['id']);
        }
    } catch (Exception $e) {
        error_log("Error in logArticleView: " . $e->getMessage());
    }
    */

    $article = null;

    // Simplificat pentru debug - caută doar în article_versions
    error_log("bkd_view_article.php - Looking for NON-ONLINE version in article_versions");
    
    $stmt = $db->prepare("
        SELECT av.*, av.author_id as user_id, u.username, c.name AS category, c.icon,
            av.article_id as id, 0 as views, av.publish_at,
            0 AS likes, 0 AS dislikes, 1 as is_version
        FROM article_versions av
        JOIN users u ON av.author_id = u.id
        LEFT JOIN categories c ON av.category_id = c.id
        WHERE av.article_id = ? AND av.version_number = ?
    ");
    
    error_log("bkd_view_article.php - Executing query with params: [$id, $version]");
    $stmt->execute([$id, $version]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("bkd_view_article.php - Query result: " . json_encode($article));

    if (!$article) {
        echo json_encode(['error' => 'Article not found after query', 'debug' => "id=$id, version=$version"]);
        exit;
    }

    // Construiește un răspuns minimal pentru testare
    $response = [
        'id' => $article['id'],
        'title' => $article['title'] ?? 'No title',
        'content' => $article['content'] ?? 'No content',
        'status' => $article['status'] ?? 'unknown',
        'username' => $article['username'] ?? 'Unknown user',
        'category' => $article['category'] ?? 'No category',
        'icon' => $article['icon'] ?? null,
        'created_at' => $article['created_at'] ?? '',
        'updated_at' => $article['updated_at'] ?? '',
        'likes' => 0,
        'dislikes' => 0,
        'is_version' => 1,
        'version_number' => (int)($article['version_number'] ?? 1),
        'change_note' => $article['change_note'] ?? null,
        'is_online' => $isOnline,
        'comments' => [],
        'is_bookmarked' => false,
        'tags' => []
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("bkd_view_article.php - Exception: " . $e->getMessage());
    error_log("bkd_view_article.php - Exception trace: " . $e->getTraceAsString());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log("bkd_view_article.php - Fatal error: " . $e->getMessage());
    error_log("bkd_view_article.php - Fatal error trace: " . $e->getTraceAsString());
    echo json_encode(['error' => 'System error: ' . $e->getMessage()]);
}
?>
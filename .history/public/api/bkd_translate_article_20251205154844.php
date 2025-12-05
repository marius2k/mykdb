<?php
/**
 * Translate Article API Endpoint
 * 
 * Translates article content on-the-fly using LibreTranslate
 * 
 * Request (POST JSON):
 * {
 *   "article_id": 123,
 *   "target_lang": "ro"
 * }
 * 
 * Response (JSON):
 * {
 *   "success": true,
 *   "title": "Translated title",
 *   "content": "Translated content",
 *   "source_lang": "en",
 *   "target_lang": "ro"
 * }
 */

// Set JSON header first to ensure proper response format
header('Content-Type: application/json');

// Enable error logging to file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_translate_errors.log');

// Log script start
error_log("=== Translation API called at " . date('Y-m-d H:i:s') . " ===");

try {
    error_log("Loading bootstrap and translator...");
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../classes/translator.php';
    error_log("Bootstrap and translator loaded successfully");
} catch (Exception $e) {
    error_log("Bootstrap error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server configuration error: ' . $e->getMessage()
    ]);
    exit;
}

// Check if user is logged in
error_log("Checking session...");
if (!isset($_SESSION['user'])) {
    error_log("No user session found");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
error_log("User session found: " . json_encode($_SESSION['user']));

// Get POST data
error_log("Reading POST data...");
$input = json_decode(file_get_contents('php://input'), true);
error_log("POST data: " . json_encode($input));

if (!isset($input['article_id']) || !isset($input['target_lang'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$articleId = (int)$input['article_id'];
$targetLang = $input['target_lang'];
$sourceLang = $input['source_lang'] ?? 'auto';

// Validate language codes (2-letter ISO codes)
if (!preg_match('/^[a-z]{2}$/', $targetLang)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid target language']);
    exit;
}

try {
    $db = new Database();
    
    // Fetch article
    $article = $db->fetchOne(
        "SELECT id, title, content FROM articles WHERE id = ? AND status = 'published'",
        [$articleId]
    );
    
    if (!$article) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article not found']);
        exit;
    }
    
    // Initialize translator
    $translator = new Translator();
    
    // Check if translation service is available
    if (!$translator->isAvailable()) {
        http_response_code(503);
        echo json_encode([
            'success' => false, 
            'message' => 'Translation service is starting up. Please wait a few minutes and try again. This is normal on first startup as language models are being downloaded.'
        ]);
        exit;
    }
    
    // Translate article
    $translated = $translator->translateArticle($article, $targetLang, $sourceLang);
    
    if ($translated === false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Translation failed. Please try again.'
        ]);
        exit;
    }
    
    // Return translated content
    echo json_encode([
        'success' => true,
        'title' => $translated['title'],
        'content' => $translated['content'],
        'source_lang' => $translated['source_lang'],
        'target_lang' => $translated['target_lang']
    ]);
    
} catch (Exception $e) {
    error_log("Translation error: " . $e->getMessage());
    error_log("Translation error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during translation',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

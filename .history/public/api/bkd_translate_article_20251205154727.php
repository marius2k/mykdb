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

// Suppress errors from being output (they would break JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../classes/translator.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server configuration error: ' . $e->getMessage()
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

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

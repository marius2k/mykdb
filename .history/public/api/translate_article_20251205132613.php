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

require_once '../config/bootstrap.php';
require_once '../classes/translator.php';

header('Content-Type: application/json');

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
            'message' => 'Translation service temporarily unavailable'
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during translation'
    ]);
}

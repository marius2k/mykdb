<?php
/**
 * PDF Download Tracker API
 * 
 * Tracks when users download article PDFs
 */
require_once '../../config/bootstrap.php';
require_once APP_ROOT . '/includes/functions.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Basic validation
    $articleId = isset($_POST['article_id']) ? trim($_POST['article_id']) : null;
    
    if (!$articleId) {
        throw new Exception('Article ID is required');
    }
    
    // Get user ID from session if available
    $userId = $_SESSION['user']['id'] ?? null;
    
    // Initialize database connection
    $db = new Database();
    
    // Record the PDF download
    $downloadData = [
        'user_id' => $userId,
        'article_id' => $articleId,
        'file_type' => 'pdf',
        'session_id' => session_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'download_date' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('file_downloads', $downloadData);
    
    // Also record in user_activity_analytics for unified reporting
    if ($userId) {
        require_once 'bkd_user_analytics.php';
        $analyticsData = [
            'user_id' => $userId,
            'article_id' => $articleId,
            'action_type' => 'save_pdf',
            'value' => 1
        ];
        trackUserActionDirect($analyticsData);
    }
    
    // Return success
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('PDF download tracker error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
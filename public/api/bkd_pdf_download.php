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

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

try {
    // Support both JSON and POST data formats
    if ($data && isset($data['article_id'])) {
        $articleId = trim($data['article_id']);
        $fileType = $data['file_type'] ?? 'pdf';
        $fileName = $data['file_name'] ?? null;
    } else {
        $articleId = isset($_POST['article_id']) ? trim($_POST['article_id']) : null;
        $fileType = isset($_POST['file_type']) ? trim($_POST['file_type']) : 'pdf';
        $fileName = isset($_POST['file_name']) ? trim($_POST['file_name']) : null;
    }
    
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
        'file_type' => $fileType,
        'file_name' => $fileName,
        'session_id' => function_exists('session_id') ? session_id() : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'download_date' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('file_downloads', $downloadData);
    
    // Also record in user_activity_analytics for unified reporting
    if ($userId && file_exists(APP_ROOT . '/public/api/bkd_user_analytics.php')) {
        require_once APP_ROOT . '/public/api/bkd_user_analytics.php';
        if (function_exists('trackUserActionDirect')) {
            $analyticsData = [
                'user_id' => $userId,
                'article_id' => $articleId,
                'action_type' => 'save_pdf',
                'value' => 1
            ];
            trackUserActionDirect($analyticsData);
        }
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
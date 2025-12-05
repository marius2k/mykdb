<?php
require_once '../../vendor/autoload.php';
require_once '../../config/bootstrap.php';

// Set headers to avoid mixed content warnings
header('Content-Security-Policy: upgrade-insecure-requests');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');

use Dompdf\Dompdf;

// Support both GET (old method) and POST (new method with current page content)
$isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPostRequest) {
    // New method: receive current page content (possibly translated)
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data || !isset($data['article_id']) || !isset($data['title']) || !isset($data['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $article_id = intval($data['article_id']);
    $title = $data['title'];
    $content = $data['content'];
    $author = $data['author'] ?? '';
    $category = $data['category'] ?? '';
    $created_at = $data['created_at'] ?? '';
    $updated_at = $data['updated_at'] ?? '';
    $language = $data['language'] ?? '';  // Track if translated
    
} else {
    // Old method: GET request, fetch from database
    $article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($article_id <= 0) {
        http_response_code(400);
        echo 'ID articol invalid.';
        exit;
    }

    // Fetch article from database
    $sql = 'SELECT a.title, a.content, a.created_at, a.updated_at, c.name AS category, u.first_name, u.last_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        http_response_code(404);
        echo 'Articolul nu a fost găsit.';
        exit;
    }

    $title = $article['title'];
    $content = $article['content'];
    $author = trim($article['first_name'] . ' ' . $article['last_name']);
    $category = $article['category'] ?? 'Fără categorie';
    $created_at = $article['created_at'];
    $updated_at = $article['updated_at'];
    $language = '';
}

$html = '<html><head><meta charset="UTF-8">'
    . '<style>body { font-family: DejaVu Sans, sans-serif; }</style>'
    . '</head><body>';
$html .= '<h1>' . htmlspecialchars($article['title']) . '</h1>';
$html .= '<p><strong>Autor:</strong> ' . htmlspecialchars($author) . '</p>';
$html .= '<p><strong>Categorie:</strong> ' . htmlspecialchars($category) . '</p>';
$html .= '<p><strong>Data publicării:</strong> ' . htmlspecialchars($article['created_at']) . '</p>';
$html .= '<p><strong>Data actualizării:</strong> ' . htmlspecialchars($article['updated_at']) . '</p>';
$html .= '<hr>';
$html .= '<div>' . $article['content'] . '</div>';
$html .= '</body></html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Track the PDF download
$userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
$fileName = 'article_' . $article_id . '.pdf';
try {
    // Initialize database connection
    $db = new Database();
    
    // Record the PDF download
    $downloadData = [
        'user_id' => $userId,
        'article_id' => $article_id,
        'file_type' => 'pdf',
        'file_name' => $fileName,
        'session_id' => session_id(),
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
                'article_id' => $article_id,
                'action_type' => 'save_pdf',
                'value' => 1
            ];
            trackUserActionDirect($analyticsData);
            
        }
    }


} catch (Exception $e) {
    // Log error but continue with download
    error_log('Error tracking PDF download: ' . $e->getMessage());
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
echo $dompdf->output();
exit;

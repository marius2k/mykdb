<?php
require_once '../../vendor/autoload.php';
require_once '../../config/bootstrap.php';
//require_once '../../includes/functions.php';
//require_once '../../config/db.php';

use Dompdf\Dompdf;

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($article_id <= 0) {
    http_response_code(400);
    echo 'ID articol invalid.';
    exit;
}

// Preia articolul cu join la categorie și autor
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

$author = trim($article['first_name'] . ' ' . $article['last_name']);
$category = $article['category'] ?? 'Fără categorie';

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

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="articol_' . $article_id . '.pdf"');
echo $dompdf->output();
exit;

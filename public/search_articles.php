<?php
require_once '../config/bootstrap.php';


header('Content-Type: application/json');

$search = strtolower(trim($_GET['q'] ?? ''));

if (!$search) {
    echo json_encode([]);
    exit;
}

$like = '%' . $search . '%';

$db = new Database();

$stmt = $db->prepare("
    SELECT a.title, a.content, a.created_at, u.username, c.name AS category
    FROM articles a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'approved' AND (LOWER(a.content) LIKE ? OR LOWER(a.title) LIKE ?)
    ORDER BY a.created_at DESC
    LIMIT 10
");
//$stmt->execute(['%' . $search . '%']);
$stmt->execute([$like, $like]);
$results = $stmt->fetchAll();

echo json_encode($results);

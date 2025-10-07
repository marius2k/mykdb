<?php
require_once '../../config/bootstrap.php';

header('Content-Type: application/json');

$response = [
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'user_authenticated' => isset($_SESSION['user']),
    'user_role' => $_SESSION['user']['role'] ?? 'none',
    'has_access' => false
];

if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'moderator', 'superadmin'])) {
    $response['has_access'] = true;
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
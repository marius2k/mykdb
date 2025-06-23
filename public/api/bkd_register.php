<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$errors = [];
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $roleRow = $db->fetchSingle("SELECT id FROM roles WHERE name = 'moderator'");
    $role = $roleRow['id'] ?? null;

    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $status = 'pending';

    if (empty($username) || empty($password) || empty($first_name) || empty($last_name)) {
        $errors[] = 'Completează toate câmpurile.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Parolele nu se potrivesc.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Utilizatorul există deja.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, first_name, last_name, password, status, role_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $first_name, $last_name, $hash, $status, $role]);
        logActivity($db->lastInsertedId(), 'register_user', 'User registered: ' . $username);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'errors' => $errors]);

?>
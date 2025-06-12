<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$ops = ['edit_user','disable_user','enable_user','delete_user','modify_user','approve_user'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $perPage = 5;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $perPage;

    $totalStmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $totalStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);

    $stmt = $db->prepare("
        SELECT u.*, r.name AS role_name, r.label AS role_label
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

    // Toate rolurile pentru dropdown
    $roles = $db->fetchAll("SELECT id, label FROM roles ORDER BY label");

    echo json_encode([
        'users' => $users,
        'roles' => $roles,
        'page' => $page,
        'totalPages' => $totalPages
    ]);
    exit;
}

// POST pentru acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
    }


    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if (!$userId || !in_array($action, ['disable', 'enable', 'change_role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date lipsă sau acțiune invalidă']);
        exit;
    }

    if ($action === 'disable') {
        if ($_SESSION['user']['id'] === $userId) {
            echo json_encode(['error' => 'Nu poți dezactiva propriul cont!']);
            exit;
        }
        $db->query("UPDATE users SET status = 'disabled' WHERE id = ?", [$userId]);
        logActivity($userId, 'user_disabled', 'User disabled: ' . $_SESSION['user']['username']);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'enable') {
        $db->query("UPDATE users SET status = 'active' WHERE id = ?", [$userId]);
        logActivity($userId, 'user_enabled', 'User enabled: ' . $_SESSION['user']['username']);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'change_role') {
        $roleId = (int)($_POST['role_id'] ?? 0);
        $db->query("UPDATE users SET role_id = ? WHERE id = ?", [$roleId, $userId]);
        $role = $db->fetchSingle("SELECT label FROM roles WHERE id = ?", [$roleId]);
        echo json_encode(['success' => true, 'new_role_label' => $role['label']]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
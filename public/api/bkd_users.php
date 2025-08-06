<?php
// filepath: /home/marius/work/projects/mykdb/public/api/bkd_users.php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

require_login();
$db = new Database();

// Restricționează accesul doar pentru admin, superadmin si moderator
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'superadmin','moderator'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Helper: return JSON and exit
function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Acțiuni AJAX: enable, disable, change_role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        json_response(['success' => false, 'error' => 'CSRF invalid!']);
    }
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'disable') {
        $stmt = $db->prepare("UPDATE users SET status='disabled' WHERE id=:id");
        $stmt->execute([':id' => $userId]);
        json_response(['success' => true]);
    } elseif (($action === 'enable') || ($action === 'approve')) {
        $stmt = $db->prepare("UPDATE users SET status='active' WHERE id=:id");
        $stmt->execute([':id' => $userId]);
        json_response(['success' => true]);
    } elseif ($action === 'change_role' && isset($_POST['role_id'])) {
        $roleId = (int)$_POST['role_id'];
        if (!$roleId || !$userId) {
            json_response(['success' => false, 'error' => 'ID invalid!']);
        }
        // Verifică dacă role_id există în tabela roles
        $check = $db->prepare("SELECT COUNT(*) FROM roles WHERE id = :id");
        $check->execute([':id' => $roleId]);
        if (!$check->fetchColumn()) {
            json_response(['success' => false, 'error' => 'Rol invalid!']);
        }
        $stmt = $db->prepare("UPDATE users SET role_id=:role_id WHERE id=:id");
        $stmt->execute([':role_id' => $roleId, ':id' => $userId]);
        json_response(['success' => true]);
    }
    json_response(['success' => false, 'error' => 'Acțiune necunoscută!']);
}

// DataTables server-side
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$orderCol = $_GET['order'][0]['column'] ?? 1;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

// Coloane pentru sortare (trebuie să corespundă cu coloanele din DataTables)
$columns = [
    0 => 'u.id',
    1 => 'u.username',
    2 => 'u.email',
    3 => 'r.name',
    4 => 'u.status',
    5 => 'u.created_at'
];

// Filtre custom
$where = [];
$params = [];

// Filtru rol (din dropdown)
if (!empty($_GET['role'])) {
    $where[] = 'r.name = :role';
    $params[':role'] = $_GET['role'];
}

// Filtru căutare globală
if ($search) {
    $where[] = '(u.username LIKE :search1 OR u.email LIKE :search2 OR r.name LIKE :search3 OR r.label LIKE :search4)';
    $params=[
            ':search1' => "%$search%",
            ':search2' => "%$search%",
            ':search3' => "%$search%",
            ':search4' => "%$search%"
    ];
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total fără filtru
$totalRecords = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Total cu filtru
$stmt = $db->prepare("SELECT COUNT(*) FROM users u LEFT JOIN roles r ON u.role_id = r.id $whereSql");


foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute($params);
$recordsFiltered = $stmt->fetchColumn();

// Query date paginată
$orderBy = $columns[$orderCol] ?? 'u.id';
$orderDir = ($orderDir === 'asc') ? 'ASC' : 'DESC';

$sql = "
    SELECT u.id, u.username, u.email, u.role_id, u.created_at, u.status, r.name AS role, r.label AS role_label
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    $whereSql
    ORDER BY $orderBy $orderDir
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $length, PDO::PARAM_INT);
$stmt->bindValue(':offset', $start, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Returnează datele în format DataTables
json_response([
    'draw' => $draw,
    'recordsTotal' => (int)$totalRecords,
    'recordsFiltered' => (int)$recordsFiltered,
    'data' => $users
]);
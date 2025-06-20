<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

require_login();
$db = new Database();

// Helper: return JSON and exit
function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Return users for filters
if (isset($_GET['users'])) {
    $users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);;
    json_response(['users' => $users]);
}

// Return user info for JS (admin, superadmin, current user)
if (isset($_GET['userinfo'])) {
    $isSuperAdmin = ($_SESSION['user']['role'] ?? '') === 'superadmin';
    $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
    $userId = $_SESSION['user']['id'] ?? 0;
    
    // debugging    
    //error_log('isAdmin: ' . var_export($isAdmin, true));
    //error_log('isSuperAdmin: ' . var_export($isSuperAdmin, true));

    json_response(['isAdmin' => $isAdmin, 'userId' => $userId, 'isSuperAdmin' => $isSuperAdmin]);
}

// Bulk actions: archive/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['log_ids'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        json_response(['success' => false, 'error' => 'CSRF invalid!']);
    }
    $ids = array_map('intval', $_POST['log_ids']);
    if (!count($ids)) json_response(['success' => false, 'error' => 'Nicio selecție!']);
    $action = $_POST['action'];
    if ($action === 'archive') {
        $stmt = $db->prepare("UPDATE activity_log SET archived=1 WHERE id IN (" . implode(',', $ids) . ")");
        $stmt->execute();
        json_response(['success' => true]);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM activity_log WHERE id IN (" . implode(',', $ids) . ")");
        $stmt->execute();
        json_response(['success' => true]);
    }
    json_response(['success' => false, 'error' => 'Acțiune necunoscută!']);
}

// DataTables server-side
// Parametri DataTables
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$orderCol = $_GET['order'][0]['column'] ?? 1;
$orderDir = $_GET['order'][0]['dir'] ?? 'desc';

// Coloane pentru sortare (trebuie să corespundă cu coloanele din DataTables)
$columns = [
    0 => 'id',
    1 => 'username',
    2 => 'action_type',
    3 => 'user_agent',
    4 => 'details',
    5 => 'created_at',
    6 => 'id'
];

// Filtre custom
$where = [];
$params = [];

// Determină rolul și user_id-ul curent
$role = $_SESSION['user']['role'] ?? '';
$currentUserId = $_SESSION['user']['id'] ?? 0;

// Restricționează accesul la loguri pentru non-admin/superadmin
if (!in_array($role, ['admin', 'superadmin'])) {
    $where[] = 'l.user_id = :current_user_id';
    $params[':current_user_id'] = $currentUserId;
}

// Filtru user
if (!empty($_GET['user_id'])) {
    $where[] = 'l.user_id = :user_id';
    $params[':user_id'] = (int)$_GET['user_id'];
}

// Filtru dată start
if (!empty($_GET['start_date'])) {
    $where[] = 'l.created_at >= :start_date';
    $params[':start_date'] = $_GET['start_date'];
}

// Filtru dată end
if (!empty($_GET['end_date'])) {
    $where[] = 'l.created_at <= :end_date';
    $params[':end_date'] = $_GET['end_date'];
}

// Filtru căutare globală
if ($search) {
    $where[] = '(u.username LIKE :search1 OR l.action_type LIKE :search2 OR l.details LIKE :search3 OR l.user_agent LIKE :search4)';
    
    $params [':search1']= "%$search%";
    $params [':search2']= "%$search%";
    $params [':search3']= "%$search%";
    $params [':search4']= "%$search%";
    
}

$whereSql = $where ? 'WHERE l.archived = 0 AND ' . implode(' AND ', $where) : 'WHERE l.archived = 0';

// debugging    
    //error_log('Where clause: ' . var_export($whereSql, true));
    //error_log('isSuperAdmin: ' . var_export($isSuperAdmin, true));
    //error_log('params: ' . var_export($params, true));


// Total fără filtru
$totalRecords = $db->query("SELECT COUNT(*) FROM activity_log WHERE archived = 0")->fetchColumn();



// Total cu filtru
$stmt = $db->prepare("
    SELECT COUNT(*) FROM activity_log l
    LEFT JOIN users u ON l.user_id = u.id
    $whereSql
");

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute($params);
$recordsFiltered = $stmt->fetchColumn();

    //error_log('records filtered: ' . var_export($recordsFiltered, true));


// Query date paginată
$orderBy = $columns[$orderCol] ?? 'created_at';
$orderDir = ($orderDir === 'asc') ? 'ASC' : 'DESC';

$sql = "
    SELECT l.*, u.username 
    FROM activity_log l
    LEFT JOIN users u ON l.user_id = u.id
    $whereSql
    ORDER BY $orderBy $orderDir
    LIMIT :limit OFFSET :offset
";
    //debugging
    //error_log('sql: ' . var_export($sql, true));
    //error_log('search: ' . var_export($search, true));


$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $length, PDO::PARAM_INT);
$stmt->bindValue(':offset', $start, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Completează cu user_id pentru acțiuni
foreach ($logs as &$log) {
    $log['user_id'] = $log['user_id'] ?? null;
}
unset($log);

// Returnează datele în format DataTables
json_response([
    'draw' => $draw,
    'recordsTotal' => (int)$totalRecords,
    'recordsFiltered' => (int)$recordsFiltered,
    'logs' => $logs
]);
?>
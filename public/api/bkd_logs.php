<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

require_login();

$userId = $_SESSION['user']['id'];
$isAdmin = ($_SESSION['user']['role'] === 'admin') || ($_SESSION['user']['role'] === 'superadmin');

$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$filterUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$start = isset($_GET['start_date']) && !empty($_GET['start_date']) ? date('Y-m-d H:i:s', strtotime($_GET['start_date'])) : '2000-01-01 00:00:00';
$end = isset($_GET['end_date']) && !empty($_GET['end_date']) ? date('Y-m-d H:i:s', strtotime($_GET['end_date'])) : date('Y-m-d H:i:s');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Filtrare și paginare
    if (!$isAdmin) {
        $sql = "SELECT l.*, u.username
                FROM activity_log l
                JOIN users u ON l.user_id = u.id
                WHERE l.archived = 0 AND l.user_id = :uid AND l.created_at BETWEEN :startd AND :endd
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $stmt->bindValue(':endd', $end, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = :uid AND archived = 0 AND created_at BETWEEN :startd AND :endd");
        $totalStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $totalStmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $totalStmt->bindValue(':endd', $end, PDO::PARAM_STR);
        $totalStmt->execute();
        $totalRows = $totalStmt->fetchColumn();
    } else {
        $where = "WHERE l.archived = 0 AND l.created_at BETWEEN :startd AND :endd";
        if ($filterUserId) {
            $where .= " AND l.user_id = :filterUserId";
        }
        $sql = "SELECT l.*, u.username
                FROM activity_log l
                JOIN users u ON l.user_id = u.id
                $where
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $stmt->bindValue(':endd', $end, PDO::PARAM_STR);
        if ($filterUserId) $stmt->bindValue(':filterUserId', $filterUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) FROM activity_log l $where";
        $totalStmt = $db->prepare($countSql);
        $totalStmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $totalStmt->bindValue(':endd', $end, PDO::PARAM_STR);
        if ($filterUserId) $totalStmt->bindValue(':filterUserId', $filterUserId, PDO::PARAM_INT);
        $totalStmt->execute();
        $totalRows = $totalStmt->fetchColumn();
    }

    $totalPages = ceil($totalRows / $perPage);

    // Pentru dropdown useri (doar la GET)
    $users = $db->fetchAll("SELECT id, username FROM users ORDER BY username");

    echo json_encode([
        'logs' => $logs,
        'users' => $users,
        'page' => $page,
        'totalPages' => $totalPages,
        'isAdmin' => $isAdmin,
        'userId' => $userId
    ]);
    exit;
}

// POST pentru acțiuni (archive/delete, bulk)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $logIds = isset($_POST['log_ids']) ? $_POST['log_ids'] : [];
    if (!is_array($logIds)) $logIds = [$logIds];

    if (!in_array($action, ['archive', 'delete'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Acțiune invalidă']);
        exit;
    }

    foreach ($logIds as $logId) {
        $logId = (int)$logId;
        // Verifică permisiunea pentru fiecare log
        $log = $db->fetchSingle("SELECT * FROM activity_log WHERE id = ?", [$logId]);
        if (!$log) continue;
        if (!$isAdmin && $log['user_id'] != $userId) continue;

        if ($action === 'archive') {
            $db->query("UPDATE activity_log SET archived = 1 WHERE id = ?", [$logId]);
        } elseif ($action === 'delete') {
            $db->query("DELETE FROM activity_log WHERE id = ?", [$logId]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
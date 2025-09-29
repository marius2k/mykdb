<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$ops = ['approve_comment','edit_comment','delete_comment','reject_comment'];
if (!hasPermission($_SESSION['user']['id'], $ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'view') {
    $commentId = (int)($_GET['id'] ?? 0);
    $comment = $db->fetchSingle("
        SELECT c.*, u.username, a.title AS article_title
        FROM article_comments c
        JOIN users u ON u.id = c.user_id
        JOIN articles a ON a.id = c.article_id
        WHERE c.id = ?
    ", [$commentId]);
    if (!$comment) {
        echo json_encode(['error' => 'Comentariu inexistent']);
        exit;
    }
    echo json_encode(['success' => true, 'comment' => $comment]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // DataTables params
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';

    $where = '';
    $params = [];
    if ($searchValue) {
        $where = "WHERE c.content LIKE :search1 OR u.username LIKE :search2 OR a.title LIKE :search3";
        $params=[
            ':search1' => "%$searchValue%",
            ':search2' => "%$searchValue%",
            ':search3' => "%$searchValue%"
        ];
    }

    // Total comments
    $totalStmt = $db->query("SELECT COUNT(*) FROM article_comments");
    $totalComments = $totalStmt->fetchColumn();

    // Total filtrat
    $filteredStmt = $db->prepare("
        SELECT COUNT(*) FROM article_comments c
        JOIN users u ON u.id = c.user_id
        JOIN articles a ON a.id = c.article_id
        $where
    ");
    foreach ($params as $k => $v) $filteredStmt->bindValue($k, $v);
    $filteredStmt->execute($params);
    $filtered = $filteredStmt->fetchColumn();

    // Date paginare
    $stmt = $db->prepare("
        SELECT c.id, c.content, c.created_at, c.status, u.username, a.title AS article_title, a.id AS article_id
        FROM article_comments c
        JOIN users u ON u.id = c.user_id
        JOIN articles a ON a.id = c.article_id
        $where
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Adaugă rownum pentru indexare
    $rownum = $start + 1;
    foreach ($comments as &$c) {
        $c['rownum'] = $rownum++;
    }

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalComments,
        "recordsFiltered" => $filtered,
        "data" => $comments
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
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if (!$commentId || !in_array($action, ['approve', 'reject', 'delete'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date lipsă sau acțiune invalidă']);
        exit;
    }

    // Verifică statusul curent al comentariului
    $comment = $db->fetchSingle("SELECT status, user_id, article_id FROM article_comments WHERE id = ?", [$commentId]);
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['error' => 'Comentariu inexistent']);
        exit;
    }

    if ($action === 'approve') {
        if ($comment['status'] === 'approved') {
            echo json_encode(['error' => 'Comentariul este deja aprobat']);
            exit;
        }
        $db->query("UPDATE article_comments SET status = 'approved' WHERE id = ?", [$commentId]);

        // get article title 
        $articleData = $db->fetchSingle("SELECT title FROM articles WHERE id = ?", [$comment['article_id']]);
        $articleTitle = $articleData ? $articleData['title'] : 'Unknown Article';
        
        // Award points for comment approval (only if gamification helpers are available)
        awardCommentAdded($comment['user_id'], $commentId, $articleTitle);
        

        sendNotificationToUser($comment['user_id'], 'info', 'Your comment has been approved');
        sendNotificationToRole('admin', 'info', 'A comment has been approved');
        logActivity($_SESSION['user']['id'], 'approve_comment', 'User '.$_SESSION['user']['username'].' approved a comment');
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'reject') {
        if ($comment['status'] === 'rejected') {
            echo json_encode(['error' => 'Comentariul este deja respins']);
            exit;
        }
        $db->query("UPDATE article_comments SET status = 'rejected' WHERE id = ?", [$commentId]);
        sendNotificationToUser($comment['user_id'], 'Comment Reject', 'Your comment has been rejected','info');
        logActivity($_SESSION['user']['id'], 'reject_comment', 'User '.$_SESSION['user']['username'].' rejected a comment');
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete') {
        $db->query("DELETE FROM article_comments WHERE id = ?", [$commentId]);
        sendNotificationToUser($comment['user_id'], 'Comment Reject', 'Your comment has been rejected','info');
        logActivity($_SESSION['user']['id'], 'delete_comment', 'User '.$_SESSION['user']['username'].' deleted a comment');
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);

?>
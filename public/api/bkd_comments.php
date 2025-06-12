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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $perPage = 5;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $perPage;

    $totalStmt = $db->query("SELECT COUNT(*) FROM article_comments");
    $totalComments = $totalStmt->fetchColumn();
    $totalPages = ceil($totalComments / $perPage);

    $stmt = $db->prepare("SELECT 
        c.id, c.content, c.created_at, c.status, u.username, a.title, a.id AS article_id
        FROM article_comments c
        JOIN users u ON u.id = c.user_id
        JOIN articles a ON a.id = c.article_id
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll();

    echo json_encode([
        'comments' => $comments,
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
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if (!$commentId || !in_array($action, ['approve', 'reject', 'delete'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date lipsă sau acțiune invalidă']);
        exit;
    }

    if ($action === 'approve') {
        $db->query("UPDATE article_comments SET status = 'approved' WHERE id = ?", [$commentId]);
        
        // send notification to user
        $userId = getCommentAuthorId($commentId);
        sendNotification($userId, 'info', 'Your comment has been approved');
        sendNotificationToRole('admin', 'info', 'A comment has been approved');
        
        // log activity
        logActivity($_SESSION['user']['id'], 'approve_comment', 'User '.$_SESSION['user']['username'].' approved a comment');
        
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'reject') {
        $db->query("UPDATE article_comments SET status = 'rejected' WHERE id = ?", [$commentId]);

        // send notification to user
        $userId = getCommentAuthorId($commentId);
        sendNotification($userId, 'Comment Reject', 'Your comment has been rejected','info');

        // log activity
        logActivity($_SESSION['user']['id'], 'reject_comment', 'User '.$_SESSION['user']['username'].' rejected a comment');
        
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete') {
        $db->query("DELETE FROM article_comments WHERE id = ?", [$commentId]);

        // send notification to user
        $userId = getCommentAuthorId($commentId);
        sendNotification($userId, 'Comment Reject', 'Your comment has been rejected','info');
        
        // log activity
        logActivity($_SESSION['user']['id'], 'delete_comment', 'User '.$_SESSION['user']['username'].' deleted a comment');
        
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
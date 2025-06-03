<?php

require_once '../config/bootstrap.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? 0;
$commentId = (int)($_POST['comment_id'] ?? 0);
$type = $_POST['type'] ?? ''; // 'like' sau 'dislike'

if (!$userId || !$commentId || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$db = new Database();

// 🔍 Verificăm dacă userul a votat deja
$existingVote = $db->fetchAll("SELECT vote_type FROM comment_likes WHERE user_id = ? AND comment_id = ?", [$userId, $commentId]);

if ($existingVote) {
    if ($existingVote['vote_type'] === $type) {
        // 👎 A dat deja același vot => ștergem
        $stmt = $db->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$userId, $commentId]);
    } else {
        // 🔄 Schimbă votul (ex: like -> dislike)
        $stmt = $db-> prepare("UPDATE comment_likes SET vote_type = ? WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$type, $userId, $commentId]);
    }
} else {
    // ➕ Adăugăm vot nou
    $stmt = $db->prepare("INSERT INTO comment_likes (user_id, comment_id, vote_type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $commentId, $type]);
}

// ♻️ Recalculăm voturile
$likes = $db->fetchSingle("SELECT COUNT(*) AS count FROM comment_likes WHERE comment_id = ? AND vote_type = 'like'", [$commentId]);
$dislikes = $db->fetchSingle("SELECT COUNT(*) AS count FROM comment_likes WHERE comment_id = ? AND vote_type = 'dislike'", [$commentId]);

echo json_encode([
    'status' => 'ok',
    'likes' => (int)$likes['count'],
    'dislikes' => (int)$dislikes['count']
]);
?>
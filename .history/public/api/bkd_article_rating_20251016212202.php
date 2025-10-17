<?php
require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';


$lang = $_SESSION['settings']['language'] ?? 'ro';

//require_once APP_ROOT . 'assets/lang/.$lang.php'; // sau en.php

$langFile = APP_ROOT . "assets/lang/{$lang}.php";
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include APP_ROOT . "assets/lang/en.php";
}

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'] ?? null;
$articleId = $_POST['article_id'] ?? $_GET['article_id'] ?? null;

// POST: rating stele sau feedback utilitate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stars = isset($_POST['stars']) ? intval($_POST['stars']) : null;
    $wasHelpful = isset($_POST['was_helpful']) ? intval($_POST['was_helpful']) : null;
    $csrf = $_POST['csrf_token'] ?? '';
    if (!$userId || !$articleId || $csrf !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Date invalide sau neautentificat.']);
        exit;
    }
    $db = new Database();

    // Dacă se trimite rating cu stele
    if ($stars !== null) {
        $stmt = $db->prepare("INSERT INTO article_ratings (article_id, user_id, stars)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE stars=?");
        $stmt->execute([$articleId, $userId, $stars, $stars]);
        
        // Track star rating in analytics
        require_once 'bkd_user_analytics.php';
        $analyticsData = [
            'user_id' => $userId,
            'article_id' => $articleId,
            'action_type' => 'rating',
            'value' => $stars
        ];
        trackUserActionDirect($analyticsData);
    }

    // Dacă se trimite feedback utilitate
    if ($wasHelpful !== null) {
        // Verifică dacă există deja rând pentru acest user/articol
        $stmt = $db->prepare("SELECT id FROM article_ratings WHERE article_id=? AND user_id=?");
        $stmt->execute([$articleId, $userId]);
        if ($stmt->fetch()) {
            // Există deja, facem UPDATE
            $stmt = $db->prepare("UPDATE article_ratings SET was_helpful=? WHERE article_id=? AND user_id=?");
            $stmt->execute([$wasHelpful, $articleId, $userId]);
        } else {
            // Nu există, facem INSERT cu stars NULL
            $stmt = $db->prepare("INSERT INTO article_ratings (article_id, user_id, was_helpful, stars) VALUES (?, ?, ?, NULL)");
            $stmt->execute([$articleId, $userId, $wasHelpful]);
        }
        
        // Track usefulness rating in analytics
        try {
            if (file_exists(APP_ROOT . '/public/api/bkd_user_analytics.php')) {
                require_once APP_ROOT . '/public/api/bkd_user_analytics.php';
                if (function_exists('trackUserActionDirect')) {
                    $analyticsData = [
                        'user_id' => $userId,
                        'article_id' => $articleId,
                        'action_type' => 'usefulness_rating',
                        'value' => $wasHelpful
                    ];
                    trackUserActionDirect($analyticsData);
                }
            }
        } catch (Exception $e) {
            error_log("Error tracking usefulness rating: " . $e->getMessage());
            // Continue execution - don't let analytics errors affect the main functionality
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// GET: statistici utilitate
if (isset($_GET['useful_stats']) && $articleId) {
    $db = new Database();
    $stmt = $db->prepare("SELECT 
        SUM(was_helpful=1) as useful_yes, 
        COUNT(was_helpful) as useful_total 
        FROM article_ratings WHERE article_id=? AND was_helpful IS NOT NULL");
    $stmt->execute([$articleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $percent = ($row['useful_total'] > 0) ? round($row['useful_yes'] / $row['useful_total'] * 100) : 0;
    echo json_encode([
        'useful_yes' => intval($row['useful_yes']),
        'useful_total' => intval($row['useful_total']),
        'useful_percent' => $percent
    ]);
    exit;
}

// GET: rating stele
if ($articleId) {
    $db = new Database();
    $stmt = $db->prepare("SELECT AVG(stars) as average, COUNT(*) as count FROM article_ratings WHERE article_id=?");
    $stmt->execute([$articleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $user_rating = null;
    if ($userId) {
        $stmt2 = $db->prepare("SELECT stars FROM article_ratings WHERE article_id=? AND user_id=?");
        $stmt2->execute([$articleId, $userId]);
        $user_rating = $stmt2->fetchColumn();
    }
    echo json_encode([
        'average' => floatval($row['average']),
        'count' => intval($row['count']),
        'user_rating' => $user_rating ? intval($user_rating) : 0
    ]);
    exit;
}

echo json_encode(['error' => 'Lipsă articol']);
?>
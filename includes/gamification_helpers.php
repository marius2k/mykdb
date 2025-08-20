<?php

/**
 * Funcții helper pentru sistemul de gamification
 */

function awardPoints($userId, $points, $action, $description = '', $relatedId = null, $relatedType = 'system') {
    try {
        $gamification = new Gamification();
        return $gamification->addPoints($userId, $points, $action, $description, $relatedId, $relatedType);
    } catch (Exception $e) {
        error_log("Gamification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Puncte pentru acțiuni specifice
 */
function awardArticlePublished($userId, $articleId, $title) {
    return awardPoints(
        $userId, 
        50, 
        'article_published', 
        "Published article: " . $title,
        $articleId,
        'article'
    );
}

function awardCommentAdded($userId, $commentId, $articleTitle) {
    return awardPoints(
        $userId, 
        5, 
        'comment_added', 
        "Added comment on: " . $articleTitle,
        $commentId,
        'comment'
    );
}

function awardArticleRead($userId, $articleId, $title) {
    return awardPoints(
        $userId, 
        1, 
        'article_read', 
        "Read article: " . $title,
        $articleId,
        'article'
    );
}

function awardProfileCompleted($userId) {
    return awardPoints(
        $userId, 
        25, 
        'profile_completed', 
        "Completed user profile"
    );
}

function awardDailyLogin($userId) {
    return awardPoints(
        $userId, 
        1, 
        'daily_login', 
        "Daily login bonus"
    );
}

function getUserGameStats($userId) {
    $gamification = new Gamification();
    return [
        'points' => $gamification->getUserPoints($userId),
        'badges' => $gamification->getUserBadges($userId)
    ];
}

/**
 * Verifică dacă profilul utilizatorului este complet
 */
function isProfileComplete($userId) {
    $db = new Database();
    $user = $db->fetchSingle("SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?", [$userId]);
    
    if (!$user) return false;
    
    // Profilul este considerat complet dacă are:
    // - first_name (nu gol)
    // - last_name (nu gol) 
    // - email (nu gol)
    // - profile_picture (nu null)
    
    return !empty(trim($user['first_name'])) &&
           !empty(trim($user['last_name'])) &&
           !empty(trim($user['email'])) &&
           !empty($user['profile_picture']);
}

/**
 * Verifică și acordă puncte pentru profilul complet
 */
function checkAndAwardProfileCompletion($userId) {
    // Verifică dacă a primit deja puncte pentru profilul complet
    $db = new Database();
    $alreadyAwarded = $db->fetchSingle(
        "SELECT id FROM points_history WHERE user_id = ? AND action = 'profile_completed'",
        [$userId]
    );
    
    // Dacă nu a primit puncte și profilul este complet, acordă puncte
    if (!$alreadyAwarded && isProfileComplete($userId)) {
        awardProfileCompleted($userId);
        return true;
    }
    
    return false;
}


?>
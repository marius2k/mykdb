<?php
/**
 * Script pentru verificarea utilizatorilor care au nevoie de puncte retroactive
 * Doar afișează informații, nu modifică nimic
 */

require_once '../config/bootstrap.php';

echo "🔍 GAMIFICATION AUDIT - Checking users who need retroactive points\n\n";

$db = new Database();

// ================================================
// 1. UTILIZATORI CU PROFIL COMPLET FĂRĂ PUNCTE
// ================================================
echo "📋 1. USERS WITH COMPLETE PROFILES (no points awarded yet):\n";
echo str_repeat("-", 60) . "\n";

$profileUsers = $db->fetchAll("
    SELECT u.id, u.username, u.first_name, u.last_name, u.email, 
           CASE WHEN u.profile_picture IS NOT NULL THEN 'Yes' ELSE 'No' END as has_picture,
           u.created_at
    FROM users u 
    WHERE u.first_name IS NOT NULL 
    AND u.first_name != ''
    AND u.last_name IS NOT NULL 
    AND u.last_name != ''
    AND u.email IS NOT NULL 
    AND u.email != ''
    AND u.profile_picture IS NOT NULL 
    AND u.profile_picture != ''
    AND u.id NOT IN (
        SELECT DISTINCT user_id FROM points_history WHERE action = 'profile_completed'
    )
    AND u.status = 'active'
    ORDER BY u.created_at ASC
");

if (empty($profileUsers)) {
    echo "✅ No users need profile completion points\n";
} else {
    echo "Found " . count($profileUsers) . " users:\n";
    foreach ($profileUsers as $user) {
        echo "  • {$user['username']} (ID: {$user['id']}) - Profile since: {$user['created_at']}\n";
    }
    echo "\nPotential points to award: " . (count($profileUsers) * 25) . " points\n";
}

// ================================================
// 2. ARTICOLE PUBLICATE FĂRĂ PUNCTE
// ================================================
echo "\n📝 2. PUBLISHED ARTICLES (no points awarded yet):\n";
echo str_repeat("-", 60) . "\n";

$articles = $db->fetchAll("
    SELECT a.id, a.title, u.username, a.created_at
    FROM articles a
    JOIN users u ON u.id = a.user_id
    WHERE a.status = 'published'
    AND a.id NOT IN (
        SELECT DISTINCT ph.related_id 
        FROM points_history ph 
        WHERE ph.action = 'article_published' 
        AND ph.related_type = 'article'
    )
    ORDER BY a.created_at ASC
");

if (empty($articles)) {
    echo "✅ No articles need publication points\n";
} else {
    echo "Found " . count($articles) . " articles:\n";
    foreach ($articles as $article) {
        $title = strlen($article['title']) > 50 ? substr($article['title'], 0, 50) . '...' : $article['title'];
        echo "  • '{$title}' by {$article['username']} - Published: {$article['created_at']}\n";
    }
    echo "\nPotential points to award: " . (count($articles) * 50) . " points\n";
}

// ================================================
// 3. COMENTARII APROBATE FĂRĂ PUNCTE  
// ================================================
echo "\n💬 3. APPROVED COMMENTS (no points awarded yet):\n";
echo str_repeat("-", 60) . "\n";

$comments = $db->fetchAll("
    SELECT c.id, c.content, u.username, a.title as article_title, c.created_at
    FROM article_comments c
    JOIN users u ON u.id = c.user_id
    JOIN articles a ON a.id = c.article_id  
    WHERE c.status = 'approved'
    AND c.id NOT IN (
        SELECT DISTINCT ph.related_id 
        FROM points_history ph 
        WHERE ph.action = 'comment_added' 
        AND ph.related_type = 'comment'
    )
    ORDER BY c.created_at ASC
");

if (empty($comments)) {
    echo "✅ No comments need approval points\n";
} else {
    echo "Found " . count($comments) . " comments:\n";
    foreach ($comments as $comment) {
        $content = strlen($comment['content']) > 40 ? substr($comment['content'], 0, 40) . '...' : $comment['content'];
        $title = strlen($comment['article_title']) > 30 ? substr($comment['article_title'], 0, 30) . '...' : $comment['article_title'];
        echo "  • '{$content}' by {$comment['username']} on '{$title}' - {$comment['created_at']}\n";
    }
    echo "\nPotential points to award: " . (count($comments) * 5) . " points\n";
}

// ================================================
// 4. SUMMARY
// ================================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 AUDIT SUMMARY\n";
echo str_repeat("=", 60) . "\n";

$totalUsers = count($profileUsers);
$totalArticles = count($articles);
$totalComments = count($comments);
$totalPoints = ($totalUsers * 25) + ($totalArticles * 50) + ($totalComments * 5);

echo "Users needing profile completion points: {$totalUsers} (+25 each)\n";
echo "Articles needing publication points: {$totalArticles} (+50 each)\n";
echo "Comments needing approval points: {$totalComments} (+5 each)\n";
echo str_repeat("-", 40) . "\n";
echo "TOTAL POINTS TO MIGRATE: {$totalPoints} points\n\n";

if ($totalPoints > 0) {
    echo "🚀 To migrate these points, run:\n";
    echo "   php scripts/migrate_all_gamification_points.php\n\n";
    echo "⚠️  Or for profile points only:\n";
    echo "   php scripts/migrate_profile_completion_points.php\n\n";
} else {
    echo "✅ No migration needed - all users already have appropriate points!\n\n";
}

echo "📈 Current gamification status:\n";
$currentStats = $db->fetchAll("
    SELECT action, COUNT(*) as count, SUM(points) as total_points 
    FROM points_history 
    GROUP BY action 
    ORDER BY total_points DESC
");

if (empty($currentStats)) {
    echo "  No gamification data yet.\n";
} else {
    foreach ($currentStats as $stat) {
        echo "  {$stat['action']}: {$stat['count']} actions, {$stat['total_points']} total points\n";
    }
}

echo "\n";

?>

<?php
/**
 * Script de migrare completă pentru acordarea retroactivă a punctelor
 * Acorda puncte pentru toate acțiunile făcute înainte de implementarea gamification
 */

require_once '../config/bootstrap.php';

echo "🚀 Starting complete gamification points migration...\n\n";

$db = new Database();
$totalMigrated = 0;

// ================================================
// 1. PROFILE COMPLETION POINTS
// ================================================
echo "📋 1. MIGRATING PROFILE COMPLETION POINTS\n";
echo str_repeat("-", 50) . "\n";

$profileQuery = "
    SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.profile_picture, u.created_at
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
        SELECT DISTINCT user_id 
        FROM points_history 
        WHERE action = 'profile_completed'
    )
    AND u.status = 'active'
";

$profileUsers = $db->fetchAll($profileQuery);
echo "Found " . count($profileUsers) . " users with complete profiles\n";

foreach ($profileUsers as $user) {
    if (awardProfileCompleted($user['id'])) {
        echo "  ✅ {$user['username']}: +25 points (profile completion)\n";
        $totalMigrated += 25;
    }
}

// ================================================
// 2. PUBLISHED ARTICLES POINTS  
// ================================================
echo "\n📝 2. MIGRATING PUBLISHED ARTICLES POINTS\n";
echo str_repeat("-", 50) . "\n";

$articlesQuery = "
    SELECT a.id, a.user_id, a.title, u.username, a.created_at
    FROM articles a
    JOIN users u ON u.id = a.user_id
    WHERE a.status = 'published'
    AND a.user_id NOT IN (
        SELECT DISTINCT ph.user_id 
        FROM points_history ph 
        WHERE ph.action = 'article_published' 
        AND ph.related_id = a.id
    )
    ORDER BY a.created_at ASC
";

$articles = $db->fetchAll($articlesQuery);
echo "Found " . count($articles) . " published articles without points\n";

foreach ($articles as $article) {
    if (awardArticlePublished($article['user_id'], $article['id'], $article['title'])) {
        echo "  ✅ {$article['username']}: +50 points (article: {$article['title']})\n";
        $totalMigrated += 50;
    }
}

// ================================================
// 3. APPROVED COMMENTS POINTS
// ================================================
echo "\n💬 3. MIGRATING APPROVED COMMENTS POINTS\n";
echo str_repeat("-", 50) . "\n";

$commentsQuery = "
    SELECT c.id, c.user_id, c.article_id, u.username, a.title as article_title, c.created_at
    FROM article_comments c
    JOIN users u ON u.id = c.user_id  
    JOIN articles a ON a.id = c.article_id
    WHERE c.status = 'approved'
    AND c.user_id NOT IN (
        SELECT DISTINCT ph.user_id 
        FROM points_history ph 
        WHERE ph.action = 'comment_added' 
        AND ph.related_id = c.id
    )
    ORDER BY c.created_at ASC
";

$comments = $db->fetchAll($commentsQuery);
echo "Found " . count($comments) . " approved comments without points\n";

foreach ($comments as $comment) {
    if (awardCommentAdded($comment['user_id'], $comment['id'], $comment['article_title'])) {
        echo "  ✅ {$comment['username']}: +5 points (comment on: {$comment['article_title']})\n";
        $totalMigrated += 5;
    }
}

// ================================================
// 4. READING POINTS (OPTIONAL - poate fi prea multe)
// ================================================
echo "\n📖 4. READING POINTS\n";
echo str_repeat("-", 50) . "\n";
echo "⚠️  Reading points migration skipped (would create too many entries)\n";
echo "    These will be awarded naturally as users read articles going forward.\n";

// ================================================
// 5. DAILY LOGIN POINTS (OPTIONAL - logic diferită)  
// ================================================
echo "\n🔐 5. DAILY LOGIN POINTS\n";
echo str_repeat("-", 50) . "\n";
echo "⚠️  Daily login points migration skipped (retroactive daily login doesn't make sense)\n";
echo "    These will be awarded naturally as users log in going forward.\n";

// ================================================
// FINAL SUMMARY
// ================================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 MIGRATION COMPLETED!\n";
echo str_repeat("=", 60) . "\n";

echo "📊 Summary:\n";
echo "  👥 Profile completions: " . count($profileUsers) . " users (+25 each)\n";
echo "  📝 Published articles: " . count($articles) . " articles (+50 each)\n";
echo "  💬 Approved comments: " . count($comments) . " comments (+5 each)\n";
echo "  🎯 Total points migrated: {$totalMigrated} points\n\n";

// Verifică statistici finale  
$finalStats = $db->fetchAll("
    SELECT action, COUNT(*) as count, SUM(points) as total_points 
    FROM points_history 
    GROUP BY action 
    ORDER BY total_points DESC
");

echo "📈 Current gamification statistics:\n";
foreach ($finalStats as $stat) {
    echo "  {$stat['action']}: {$stat['count']} actions, {$stat['total_points']} total points\n";
}

echo "\n✅ All users can now enjoy the full gamification experience!\n";

?>

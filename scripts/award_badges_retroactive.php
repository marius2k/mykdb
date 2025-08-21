<?php
/**
 * Retroactive Badge Award Script
 * Awards badges to all users based on their historical activity
 * 
 * Usage: Access via browser - http://192.168.1.178:8080/scripts/award_badges_retroactive.php
 * Or via CLI: php scripts/award_badges_retroactive.php
 */

// Include necessary files
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/gamification.php';

// Set content type for web access
if (!isset($argv)) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><title>Retroactive Badge Awards</title></head><body>";
    echo "<h1>🏆 Retroactive Badge Award System</h1>";
    echo "<pre style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
}

echo "=== RETROACTIVE BADGE AWARD SCRIPT ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Initialize database and gamification
    $database = new Database();
    $pdo = $database->getPdo();
    $gamification = new Gamification($pdo);
    
    // Get all active badges with their conditions
    $badgesQuery = "SELECT * FROM badges WHERE is_active = 1 ORDER BY id";
    $badges = $pdo->query($badgesQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Found " . count($badges) . " active badges to process:\n";
    foreach ($badges as $badge) {
        echo "   - {$badge['name']} ({$badge['category']}) - " . $badge['points_reward'] . " points\n";
    }
    echo "\n";
    
    // Get all users
    $usersQuery = "SELECT id, first_name, last_name, username FROM users WHERE status = 'active'";
    $users = $pdo->query($usersQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "👥 Found " . count($users) . " active users to process\n\n";
    
    $totalBadgesAwarded = 0;
    $userStats = [];
    
    // Process each user
    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['username'];
        
        echo "🔍 Processing user: {$userName} (ID: {$userId})\n";
        
        // Get user's current activity stats
        $userActivity = getUserActivityStats($pdo, $userId);
        echo "   Activity: {$userActivity['articles_published']} articles, {$userActivity['comments_made']} comments, {$userActivity['articles_read']} reads\n";
        
        // Get user's existing badges to avoid duplicates
        $existingBadges = getUserExistingBadges($pdo, $userId);
        echo "   Existing badges: " . count($existingBadges) . " (IDs: " . implode(', ', $existingBadges) . ")\n";
        
        $userBadgesAwarded = 0;
        
        // Check each badge condition
        foreach ($badges as $badge) {
            // Skip if user already has this badge
            if (in_array($badge['id'], $existingBadges)) {
                echo "   ⏭️  Already has: {$badge['name']} (ID: {$badge['id']})\n";
                continue;
            }
            
            // Parse badge conditions
            $conditions = json_decode($badge['conditions'], true);
            if (!$conditions) {
                echo "   ⚠️  Invalid conditions for badge: {$badge['name']}\n";
                continue;
            }
            
            // Debug: Show badge conditions and user stats
            echo "   🔍 Checking {$badge['name']} (ID: {$badge['id']}):\n";
            echo "      Required: " . json_encode($conditions) . "\n";
            echo "      User has: articles={$userActivity['articles_published']}, comments={$userActivity['comments_made']}, reads={$userActivity['articles_read']}\n";
            
            // Check if user meets the conditions
            $qualifies = checkBadgeConditions($userActivity, $conditions);
            echo "      Qualifies: " . ($qualifies ? 'YES' : 'NO') . "\n";
            
            if ($qualifies) {
                // Award the badge
                $success = awardBadgeToUser($pdo, $userId, $badge['id'], $badge['points_reward']);
                
                if ($success) {
                    echo "   ✅ Awarded: {$badge['name']} (+{$badge['points_reward']} points)\n";
                    $userBadgesAwarded++;
                    $totalBadgesAwarded++;
                } else {
                    echo "   ❌ Failed to award: {$badge['name']}\n";
                }
            } else {
                echo "   ⛔ Does not qualify for: {$badge['name']}\n";
            }
        }
        
        if ($userBadgesAwarded == 0) {
            echo "   ℹ️  No new badges to award\n";
        }
        
        $userStats[$userId] = [
            'name' => $userName,
            'badges_awarded' => $userBadgesAwarded,
            'activity' => $userActivity
        ];
        
        echo "\n";
    }
    
    // Summary report
    echo "=== SUMMARY REPORT ===\n";
    echo "📊 Total badges awarded: {$totalBadgesAwarded}\n";
    echo "👥 Users processed: " . count($users) . "\n\n";
    
    echo "🏆 Badge distribution by user:\n";
    foreach ($userStats as $userId => $stats) {
        if ($stats['badges_awarded'] > 0) {
            echo "   {$stats['name']}: {$stats['badges_awarded']} new badges\n";
        }
    }
    
    // Badge type summary
    echo "\n📈 Badge statistics:\n";
    $badgeTypeStats = getBadgeTypeStatistics($pdo);
    foreach ($badgeTypeStats as $category => $count) {
        echo "   {$category}: {$count} badges awarded\n";
    }
    
    echo "\n✅ Script completed successfully at " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

if (!isset($argv)) {
    echo "</pre>";
    echo "<p><strong>Script completed!</strong> <a href='../public/dashboard.php'>Return to Dashboard</a></p>";
    echo "</body></html>";
}

/**
 * Get user's activity statistics
 */
function getUserActivityStats($pdo, $userId) {
    $stats = [
        'articles_published' => 0,
        'comments_made' => 0,
        'articles_read' => 0
    ];
    
    // Count published articles - using correct column name 'user_id'
    $articleQuery = "SELECT COUNT(*) FROM articles WHERE user_id = ? AND status = 'approved'";
    $stmt = $pdo->prepare($articleQuery);
    $stmt->execute([$userId]);
    $stats['articles_published'] = (int)$stmt->fetchColumn();
    
    // Count comments made - using correct table name 'article_comments'
    $commentQuery = "SELECT COUNT(*) FROM article_comments WHERE user_id = ? AND status = 'approved'";
    $stmt = $pdo->prepare($commentQuery);
    $stmt->execute([$userId]);
    $stats['comments_made'] = (int)$stmt->fetchColumn();
    
    // Count articles read - first try points_history (new system)
    $readQuery = "SELECT COUNT(*) FROM points_history WHERE user_id = ? AND action = 'article_read'";
    $stmt = $pdo->prepare($readQuery);
    $stmt->execute([$userId]);
    $reads = (int)$stmt->fetchColumn();
    
    // RETROACTIVE: Also count from activity_log (old system)
    $activityReadQuery = "SELECT COUNT(*) FROM activity_log WHERE user_id = ? AND action_type = 'article_viewed'";
    $stmt = $pdo->prepare($activityReadQuery);
    $stmt->execute([$userId]);
    $retroactiveReads = (int)$stmt->fetchColumn();
    
    // Combine both sources
    $totalReads = $reads + $retroactiveReads;
    
    // If still no read history, make conservative estimate
    if ($totalReads == 0 && ($stats['articles_published'] > 0 || $stats['comments_made'] > 0)) {
        // Estimate: active users probably read at least 2x the articles they published + comments they made
        $totalReads = ($stats['articles_published'] * 2) + $stats['comments_made'];
    }
    
    $stats['articles_read'] = $totalReads;
    
    return $stats;
}

/**
 * Get user's existing badges
 */
function getUserExistingBadges($pdo, $userId) {
    $query = "SELECT badge_id FROM user_badges WHERE user_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Check if user meets badge conditions
 */
function checkBadgeConditions($userActivity, $conditions) {
    foreach ($conditions as $metric => $requiredValue) {
        if (!isset($userActivity[$metric]) || $userActivity[$metric] < $requiredValue) {
            return false;
        }
    }
    return true;
}

/**
 * Award badge to user
 */
function awardBadgeToUser($pdo, $userId, $badgeId, $pointsReward) {
    try {
        $pdo->beginTransaction();
        
        echo "      🔧 Step 1: Getting badge name...\n";
        $badgeName = getBadgeName($pdo, $badgeId);
        $description = "Earned badge: " . $badgeName;
        echo "         Badge name: {$badgeName}\n";
        
        echo "      🔧 Step 2: Inserting into user_badges...\n";
        // Insert user_badge record
        $badgeQuery = "INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($badgeQuery);
        $stmt->execute([$userId, $badgeId]);
        echo "         ✅ user_badges insert successful\n";
        
        echo "      🔧 Step 3: Updating user_points (using total_points column)...\n";
        // Add points to user_points - CORRECTED: using total_points instead of points
        $pointsQuery = "INSERT INTO user_points (user_id, total_points, updated_at) 
                       VALUES (?, ?, NOW()) 
                       ON DUPLICATE KEY UPDATE 
                       total_points = total_points + VALUES(total_points), 
                       updated_at = NOW()";
        $stmt = $pdo->prepare($pointsQuery);
        $stmt->execute([$userId, $pointsReward]);
        echo "         ✅ user_points update successful\n";
        
        echo "      🔧 Step 4: Inserting into points_history...\n";
        // Log the action in points_history
        $historyQuery = "INSERT INTO points_history (user_id, action, points, description, related_id, related_type) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($historyQuery);
        $stmt->execute([$userId, 'badge_earned', $pointsReward, $description, $badgeId, 'system']);
        echo "         ✅ points_history insert successful\n";
        
        $pdo->commit();
        echo "      🎉 Transaction committed successfully!\n";
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "      💥 Error at step: " . $e->getMessage() . "\n";
        echo "      📍 Error file: " . $e->getFile() . " line " . $e->getLine() . "\n";
        error_log("Failed to award badge {$badgeId} to user {$userId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Get badge name by ID
 */
function getBadgeName($pdo, $badgeId) {
    $query = "SELECT name FROM badges WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$badgeId]);
    return $stmt->fetchColumn() ?: 'Unknown Badge';
}

/**
 * Get badge type statistics
 */
function getBadgeTypeStatistics($pdo) {
    $query = "SELECT b.category, COUNT(ub.id) as count 
              FROM badges b 
              LEFT JOIN user_badges ub ON b.id = ub.badge_id 
              WHERE ub.earned_at >= CURDATE() 
              GROUP BY b.category 
              ORDER BY count DESC";
    
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return $results ?: [];
}

?>
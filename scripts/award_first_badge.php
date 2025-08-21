<?php
require_once '../config/bootstrap.php';

// Simple security check
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<h2>Award First Steps Badge to Admin</h2>";
    echo "<p>This script will award the 'First Steps' badge to the admin user.</p>";
    echo "<a href='?confirm=yes' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Award Badge</a>";
    exit;
}

try {
    $db = new Database();
    
    echo "<h2>🏆 Badge Award Process</h2>";
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 20px;'>";
    
    // Find admin user
    echo "<p>🔍 <strong>Step 1:</strong> Finding admin user...</p>";
    $admin = $db->fetchSingle("SELECT id, username FROM users WHERE username = 'admin'");
    
    if (!$admin) {
        echo "<p style='color: red;'>❌ Admin user not found!</p>";
        exit;
    }
    
    $adminId = $admin['id'];
    echo "<p style='color: green;'>✅ Found admin user (ID: {$adminId})</p>";
    
    // Check if badge already exists
    echo "<p>🔍 <strong>Step 2:</strong> Checking existing badges...</p>";
    $existingBadge = $db->fetchSingle("
        SELECT ub.id, b.name 
        FROM user_badges ub 
        JOIN badges b ON ub.badge_id = b.id 
        WHERE ub.user_id = ? AND ub.badge_id = 1
    ", [$adminId]);
    
    if ($existingBadge) {
        echo "<p style='color: orange;'>⚠️ Admin already has the 'First Steps' badge!</p>";
        
        // Show current badges
        $currentBadges = $db->fetchAll("
            SELECT b.name, b.description, b.category, ub.earned_at
            FROM user_badges ub 
            JOIN badges b ON ub.badge_id = b.id 
            WHERE ub.user_id = ?
            ORDER BY ub.earned_at DESC
        ", [$adminId]);
        
        echo "<h3>Current Badges:</h3>";
        if (empty($currentBadges)) {
            echo "<p>No badges found.</p>";
        } else {
            echo "<ul>";
            foreach ($currentBadges as $badge) {
                echo "<li><strong>{$badge['name']}</strong> ({$badge['category']}) - {$badge['description']} <em>(earned: {$badge['earned_at']})</em></li>";
            }
            echo "</ul>";
        }
        exit;
    }
    
    echo "<p style='color: green;'>✅ No existing badge found. Proceeding...</p>";
    
    // Award the badge
    echo "<p>🏆 <strong>Step 3:</strong> Awarding 'First Steps' badge...</p>";
    $db->query("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)", [$adminId, 1]);
    echo "<p style='color: green;'>✅ Badge awarded successfully!</p>";
    
    // Award bonus points
    echo "<p>💰 <strong>Step 4:</strong> Awarding bonus points...</p>";
    $db->query("
        INSERT INTO points_history (user_id, points, action, description, created_at) 
        VALUES (?, 50, 'badge_earned', 'Badge earned: First Steps', NOW())
    ", [$adminId]);
    echo "<p style='color: green;'>✅ 50 bonus points awarded!</p>";
    
    // Update total points and level
    echo "<p>📊 <strong>Step 5:</strong> Updating user level...</p>";
    $totalPoints = $db->fetchSingle("
        SELECT COALESCE(SUM(points), 0) as total 
        FROM points_history 
        WHERE user_id = ?
    ", [$adminId])['total'];
    
    $level = 'Rookie';
    if ($totalPoints >= 3000) $level = 'Legend';
    elseif ($totalPoints >= 1500) $level = 'Master';
    elseif ($totalPoints >= 700) $level = 'Expert';
    elseif ($totalPoints >= 300) $level = 'Contributor';
    elseif ($totalPoints >= 100) $level = 'Explorer';
    
    $db->query("
        INSERT INTO user_points (user_id, total_points, level) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE total_points = ?, level = ?
    ", [$adminId, $totalPoints, $level, $totalPoints, $level]);
    
    echo "<p style='color: green;'>✅ Level updated: <strong>{$level}</strong> with <strong>{$totalPoints} XP</strong></p>";
    
    // Final verification
    echo "<p>✅ <strong>Step 6:</strong> Final verification...</p>";
    $verification = $db->fetchSingle("
        SELECT 
            up.total_points,
            up.level,
            COUNT(ub.id) as badge_count
        FROM user_points up
        LEFT JOIN user_badges ub ON up.user_id = ub.user_id
        WHERE up.user_id = ?
        GROUP BY up.user_id
    ", [$adminId]);
    
    echo "<div style='background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #0369a1; margin-top: 0;'>🎉 Success!</h3>";
    echo "<p><strong>Admin now has:</strong></p>";
    echo "<ul>";
    echo "<li>Total Points: <strong>{$verification['total_points']} XP</strong></li>";
    echo "<li>Level: <strong>{$verification['level']}</strong></li>";
    echo "<li>Badges: <strong>{$verification['badge_count']}</strong></li>";
    echo "</ul>";
    echo "<p><em>Refresh the application to see the badge in the header!</em></p>";
    echo "</div>";
    
    echo "<p><a href='../public/dashboard.php' style='background: #16a34a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #fee; padding: 15px; border-radius: 5px; margin: 20px;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
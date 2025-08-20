<?php
/**
 * Script de migrare pentru acordarea retroactivă a punctelor pentru profilurile complete
 * Acorda puncte utilizatorilor care au completat profilul înainte de implementarea gamification
 */

require_once '../config/bootstrap.php';

echo "🚀 Starting profile completion points migration...\n\n";

$db = new Database();

// Găsește toți utilizatorii cu profil complet care nu au primit puncte pentru profile_completed
$query = "
    SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.profile_picture
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

$users = $db->fetchAll($query);

echo "📊 Found " . count($users) . " users with complete profiles who haven't received profile completion points:\n\n";

if (empty($users)) {
    echo "✅ No users need profile completion points migration.\n";
    exit;
}

$migratedCount = 0;
$errorCount = 0;

foreach ($users as $user) {
    echo "Processing user: {$user['username']} (ID: {$user['id']})...\n";
    
    // Verifică din nou dacă profilul este complet folosind funcția helper
    if (isProfileComplete($user['id'])) {
        // Acordă puncte pentru profilul complet
        $success = awardProfileCompleted($user['id']);
        
        if ($success) {
            echo "  ✅ Awarded 25 points for profile completion\n";
            $migratedCount++;
        } else {
            echo "  ❌ Error awarding points\n";
            $errorCount++;
        }
    } else {
        echo "  ⚠️  Profile not actually complete (skipped)\n";
    }
    
    echo "\n";
}

echo "📈 Migration Summary:\n";
echo "  ✅ Successfully migrated: {$migratedCount} users\n";
echo "  ❌ Errors: {$errorCount} users\n";
echo "  🎯 Total points awarded: " . ($migratedCount * 25) . " points\n\n";

// Verifică și raportează statistici finale
$totalProfilePoints = $db->fetchSingle(
    "SELECT COUNT(*) as count, SUM(points) as total_points 
     FROM points_history 
     WHERE action = 'profile_completed'"
);

echo "📊 Final Statistics:\n";
echo "  👥 Total users with profile completion points: {$totalProfilePoints['count']}\n";
echo "  🎯 Total profile completion points awarded: {$totalProfilePoints['total_points']}\n\n";

echo "🎉 Migration completed!\n";

?>

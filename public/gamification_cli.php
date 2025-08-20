<?php
require_once '../config/bootstrap.php';

// Verifică permisiuni de admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Access denied. Admin privileges required.');
}

header('Content-Type: text/plain; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'audit':
        echo "🔍 RUNNING GAMIFICATION AUDIT...\n";
        echo str_repeat("=", 50) . "\n\n";
        include '../scripts/check_gamification_audit.php';
        break;
        
    case 'migrate_profile':
        echo "📋 MIGRATING PROFILE COMPLETION POINTS...\n";
        echo str_repeat("=", 50) . "\n\n";
        include '../scripts/migrate_profile_completion_points.php';
        break;
        
    case 'migrate_all':
        echo "🚀 RUNNING FULL GAMIFICATION MIGRATION...\n";
        echo str_repeat("=", 50) . "\n\n";
        include '../scripts/migrate_all_gamification_points.php';
        break;
        
    default:
        echo "❌ Invalid action. Available actions:\n";
        echo "- ?action=audit (safe check)\n";
        echo "- ?action=migrate_profile (profile points only)\n";
        echo "- ?action=migrate_all (complete migration)\n";
}
?>

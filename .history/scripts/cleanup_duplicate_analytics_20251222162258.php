<?php
/**
 * Cleanup Duplicate Analytics Records
 * 
 * This script removes duplicate entries from user_activity_analytics table
 * for 'view' and 'rating' operations that have the same action_date.
 * Keeps the first record (lowest ID) and deletes duplicates.
 * 
 * Usage: php cleanup_duplicate_analytics.php [--dry-run]
 */

require_once __DIR__ . '/../config/bootstrap.php';

$dryRun = in_array('--dry-run', $argv);

if ($dryRun) {
    echo "=== DRY RUN MODE - No changes will be made ===\n\n";
} else {
    echo "=== CLEANUP MODE - Duplicates will be deleted ===\n\n";
}

try {
    $db = new Database();
    
    // Find duplicates for 'view' operations
    echo "Searching for duplicate 'view' records...\n";
    
    $findViewDuplicates = "
        SELECT 
            user_id,
            article_id,
            action_type,
            DATE(action_date) as action_day,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(id ORDER BY id) as ids
        FROM user_activity_analytics
        WHERE action_type = 'view'
        GROUP BY user_id, article_id, action_type, DATE(action_date)
        HAVING COUNT(*) > 1
    ";
    
    $stmt = $db->query($findViewDuplicates);
    $viewDuplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($viewDuplicates) . " duplicate 'view' groups\n";
    
    $totalViewDeleted = 0;
    foreach ($viewDuplicates as $dup) {
        $ids = explode(',', $dup['ids']);
        $keepId = array_shift($ids); // Keep the first (lowest) ID
        $deleteIds = $ids;
        
        echo sprintf(
            "  User %d, Article %d, Date %s: %d duplicates (keep ID %d, delete %s)\n",
            $dup['user_id'],
            $dup['article_id'],
            $dup['action_day'],
            $dup['duplicate_count'],
            $keepId,
            implode(', ', $deleteIds)
        );
        
        if (!$dryRun && !empty($deleteIds)) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $deleteSql = "DELETE FROM user_activity_analytics WHERE id IN ($placeholders)";
            $deleteStmt = $db->prepare($deleteSql);
            $deleteStmt->execute($deleteIds);
            $totalViewDeleted += count($deleteIds);
        }
    }
    
    echo "\nTotal 'view' duplicates " . ($dryRun ? "to be deleted" : "deleted") . ": $totalViewDeleted\n\n";
    
    // Find duplicates for 'rating' operations
    echo "Searching for duplicate 'rating' records...\n";
    
    $findRatingDuplicates = "
        SELECT 
            user_id,
            article_id,
            action_type,
            DATE(action_date) as action_day,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(id ORDER BY id) as ids
        FROM user_activity_analytics
        WHERE action_type = 'rating'
        GROUP BY user_id, article_id, action_type, DATE(action_date)
        HAVING COUNT(*) > 1
    ";
    
    $stmt = $db->query($findRatingDuplicates);
    $ratingDuplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($ratingDuplicates) . " duplicate 'rating' groups\n";
    
    $totalRatingDeleted = 0;
    foreach ($ratingDuplicates as $dup) {
        $ids = explode(',', $dup['ids']);
        $keepId = array_shift($ids); // Keep the first (lowest) ID
        $deleteIds = $ids;
        
        echo sprintf(
            "  User %d, Article %d, Date %s: %d duplicates (keep ID %d, delete %s)\n",
            $dup['user_id'],
            $dup['article_id'],
            $dup['action_day'],
            $dup['duplicate_count'],
            $keepId,
            implode(', ', $deleteIds)
        );
        
        if (!$dryRun && !empty($deleteIds)) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $deleteSql = "DELETE FROM user_activity_analytics WHERE id IN ($placeholders)";
            $deleteStmt = $db->prepare($deleteSql);
            $deleteStmt->execute($deleteIds);
            $totalRatingDeleted += count($deleteIds);
        }
    }
    
    echo "\nTotal 'rating' duplicates " . ($dryRun ? "to be deleted" : "deleted") . ": $totalRatingDeleted\n\n";
    
    // Summary
    echo "=== SUMMARY ===\n";
    echo "View duplicates " . ($dryRun ? "found" : "deleted") . ": $totalViewDeleted\n";
    echo "Rating duplicates " . ($dryRun ? "found" : "deleted") . ": $totalRatingDeleted\n";
    echo "Total: " . ($totalViewDeleted + $totalRatingDeleted) . "\n";
    
    if ($dryRun) {
        echo "\nRun without --dry-run to actually delete the duplicates.\n";
    } else {
        echo "\nCleanup completed successfully!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>

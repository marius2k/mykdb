<?php
/**
 * Rebuild User Activity Summary Table
 * 
 * This script rebuilds the user_activity_summary table from scratch
 * using the cleaned user_activity_analytics data.
 * 
 * Usage: php rebuild_user_activity_summary.php [--dry-run]
 */

require_once __DIR__ . '/../config/bootstrap.php';

$dryRun = in_array('--dry-run', $argv);

if ($dryRun) {
    echo "=== DRY RUN MODE - No changes will be made ===\n\n";
} else {
    echo "=== REBUILD MODE - Summary table will be rebuilt ===\n\n";
}

try {
    $db = new Database();
    
    // Step 1: Show current summary counts before rebuild
    echo "Current summary counts:\n";
    $currentSummary = "
        SELECT 
            user_id,
            SUM(views_count) as total_views,
            SUM(ratings_count) as total_ratings,
            SUM(bookmarks_count) as total_bookmarks,
            SUM(comments_count) as total_comments
        FROM user_activity_summary
        GROUP BY user_id
        ORDER BY user_id
    ";
    
    $stmt = $db->query($currentSummary);
    $currentCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($currentCounts as $row) {
        echo sprintf(
            "  User %d: views=%d, ratings=%d, bookmarks=%d, comments=%d\n",
            $row['user_id'],
            $row['total_views'],
            $row['total_ratings'],
            $row['total_bookmarks'],
            $row['total_comments']
        );
    }
    
    echo "\n";
    
    // Step 2: Calculate correct counts from user_activity_analytics
    echo "Calculating correct counts from analytics data...\n";
    
    $correctCounts = "
        SELECT 
            user_id,
            DATE(action_date) as activity_date,
            SUM(CASE WHEN action_type = 'view' THEN 1 ELSE 0 END) as views_count,
            SUM(CASE WHEN action_type = 'bookmark' THEN 1 ELSE 0 END) as bookmarks_count,
            SUM(CASE WHEN action_type = 'comment' THEN 1 ELSE 0 END) as comments_count,
            SUM(CASE WHEN action_type = 'rating' THEN 1 ELSE 0 END) as ratings_count,
            SUM(CASE WHEN action_type = 'vote' THEN 1 ELSE 0 END) as votes_count,
            SUM(CASE WHEN action_type = 'edit' THEN 1 ELSE 0 END) as edits_count,
            SUM(CASE WHEN action_type = 'publish' THEN 1 ELSE 0 END) as publishes_count,
            SUM(CASE WHEN action_type = 'approve' THEN 1 ELSE 0 END) as approvals_count,
            SUM(CASE WHEN action_type = 'save_pdf' THEN 1 ELSE 0 END) as pdf_saves_count,
            SUM(CASE WHEN action_type = 'usefulness_rating' THEN 1 ELSE 0 END) as usefulness_ratings_count
        FROM user_activity_analytics
        GROUP BY user_id, DATE(action_date)
        ORDER BY user_id, activity_date
    ";
    
    $stmt = $db->query($correctCounts);
    $newData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($newData) . " daily activity records to process\n\n";
    
    // Show summary of new counts
    $newSummary = [];
    foreach ($newData as $row) {
        $userId = $row['user_id'];
        if (!isset($newSummary[$userId])) {
            $newSummary[$userId] = [
                'views' => 0,
                'ratings' => 0,
                'bookmarks' => 0,
                'comments' => 0
            ];
        }
        $newSummary[$userId]['views'] += $row['views_count'];
        $newSummary[$userId]['ratings'] += $row['ratings_count'];
        $newSummary[$userId]['bookmarks'] += $row['bookmarks_count'];
        $newSummary[$userId]['comments'] += $row['comments_count'];
    }
    
    echo "New summary counts (from analytics):\n";
    foreach ($newSummary as $userId => $counts) {
        echo sprintf(
            "  User %d: views=%d, ratings=%d, bookmarks=%d, comments=%d\n",
            $userId,
            $counts['views'],
            $counts['ratings'],
            $counts['bookmarks'],
            $counts['comments']
        );
    }
    
    echo "\n";
    
    if (!$dryRun) {
        // Step 3: Clear the summary table
        echo "Truncating user_activity_summary table...\n";
        $db->query("TRUNCATE TABLE user_activity_summary");
        
        // Step 4: Insert the correct data
        echo "Inserting correct data...\n";
        
        $insertStmt = $db->prepare("
            INSERT INTO user_activity_summary (
                user_id,
                activity_date,
                views_count,
                bookmarks_count,
                comments_count,
                ratings_count,
                votes_count,
                edits_count,
                publishes_count,
                approvals_count,
                pdf_saves_count,
                usefulness_ratings_count
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertedCount = 0;
        foreach ($newData as $row) {
            $insertStmt->execute([
                $row['user_id'],
                $row['activity_date'],
                $row['views_count'],
                $row['bookmarks_count'],
                $row['comments_count'],
                $row['ratings_count'],
                $row['votes_count'],
                $row['edits_count'],
                $row['publishes_count'],
                $row['approvals_count'],
                $row['pdf_saves_count'],
                $row['usefulness_ratings_count']
            ]);
            $insertedCount++;
        }
        
        echo "Inserted $insertedCount records into user_activity_summary\n\n";
        
        // Step 5: Verify the rebuild
        echo "Verifying rebuilt summary...\n";
        $stmt = $db->query($currentSummary);
        $rebuiltCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rebuiltCounts as $row) {
            echo sprintf(
                "  User %d: views=%d, ratings=%d, bookmarks=%d, comments=%d\n",
                $row['user_id'],
                $row['total_views'],
                $row['total_ratings'],
                $row['total_bookmarks'],
                $row['total_comments']
            );
        }
        
        echo "\nRebuild completed successfully!\n";
    } else {
        echo "Run without --dry-run to rebuild the summary table.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>

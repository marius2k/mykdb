<?php
/**
 * Populate User Statistics - Backfill Analytics Data
 * 
 * This script populates the analytics tables with historical data from:
 * - admin_activity_analytics: Track edit, approve, publish actions
 * - user_activity_analytics: Track views, bookmarks, ratings, comments
 * - article_comments: Existing comments
 * 
 * Run this script to backfill statistics data for the User Statistics dashboard.
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "=== User Statistics Data Population Script ===\n\n";

$db = new Database();

// Counter for tracking
$stats = [
    'admin_actions' => 0,
    'user_actions' => 0,
    'comments' => 0,
    'errors' => 0
];

try {
    echo "Step 1: Populating admin_activity_analytics from article_versions history...\n";
    
    // Get all article versions to track edit/create actions
    $versions = $db->fetchAll("
        SELECT 
            av.article_id,
            av.author_id as user_id,
            av.version_number,
            av.status,
            av.created_at,
            av.updated_at
        FROM article_versions av
        WHERE av.article_id IS NOT NULL
        ORDER BY av.article_id, av.version_number
    ");
    
    echo "Found " . count($versions) . " article versions to process...\n";
    
    foreach ($versions as $version) {
        try {
            // Track article edit/creation
            $existingEdit = $db->fetchSingle("
                SELECT id FROM admin_activity_analytics 
                WHERE user_id = ? 
                AND article_id = ? 
                AND action_type = 'edit'
                AND DATE(action_date) = DATE(?)
            ", [$version['user_id'], $version['article_id'], $version['created_at']]);
            
            if (!$existingEdit) {
                $db->insert('admin_activity_analytics', [
                    'user_id' => $version['user_id'],
                    'article_id' => $version['article_id'],
                    'action_type' => 'edit',
                    'action_date' => $version['created_at'],
                    'ip_address' => '0.0.0.0',
                    'session_id' => 'backfill'
                ]);
                $stats['admin_actions']++;
            }
            
            // Track approval (for approved status)
            if ($version['status'] === 'approved') {
                $existingApprove = $db->fetchSingle("
                    SELECT id FROM admin_activity_analytics 
                    WHERE article_id = ? 
                    AND action_type = 'approve'
                    AND DATE(action_date) = DATE(?)
                ", [$version['article_id'], $version['updated_at']]);
                
                if (!$existingApprove) {
                    $db->insert('admin_activity_analytics', [
                        'user_id' => $version['user_id'],
                        'article_id' => $version['article_id'],
                        'action_type' => 'approve',
                        'action_date' => $version['updated_at'],
                        'ip_address' => '0.0.0.0',
                        'session_id' => 'backfill'
                    ]);
                    $stats['admin_actions']++;
                }
            }
            
        } catch (Exception $e) {
            echo "  Error processing version: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "✓ Processed article versions\n\n";
    
    // Step 2: Track publish actions from articles table
    echo "Step 2: Populating admin_activity_analytics with publish actions...\n";
    
    $publishedArticles = $db->fetchAll("
        SELECT 
            a.id as article_id,
            a.user_id,
            a.created_at
        FROM articles a
        WHERE a.status = 'published'
    ");
    
    echo "Found " . count($publishedArticles) . " published articles...\n";
    
    foreach ($publishedArticles as $article) {
        try {
            $existingPublish = $db->fetchSingle("
                SELECT id FROM admin_activity_analytics 
                WHERE article_id = ? 
                AND action_type = 'publish'
            ", [$article['article_id']]);
            
            if (!$existingPublish && $article['user_id']) {
                $db->insert('admin_activity_analytics', [
                    'user_id' => $article['user_id'],
                    'article_id' => $article['article_id'],
                    'action_type' => 'publish',
                    'action_date' => $article['created_at'],
                    'ip_address' => '0.0.0.0',
                    'session_id' => 'backfill'
                ]);
                $stats['admin_actions']++;
            }
        } catch (Exception $e) {
            echo "  Error processing publish: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "✓ Processed published articles\n\n";
    
    // Step 3: Populate user_activity_analytics from bookmarks
    echo "Step 3: Populating user_activity_analytics with bookmark actions...\n";
    
    $bookmarks = $db->fetchAll("
        SELECT 
            user_id,
            article_id,
            created_at
        FROM user_bookmarks
    ");
    
    echo "Found " . count($bookmarks) . " bookmarks...\n";
    
    foreach ($bookmarks as $bookmark) {
        try {
            $existingBookmark = $db->fetchSingle("
                SELECT id FROM user_activity_analytics 
                WHERE user_id = ? 
                AND article_id = ? 
                AND action_type = 'bookmark'
            ", [$bookmark['user_id'], $bookmark['article_id']]);
            
            if (!$existingBookmark) {
                $db->insert('user_activity_analytics', [
                    'user_id' => $bookmark['user_id'],
                    'article_id' => $bookmark['article_id'],
                    'action_type' => 'bookmark',
                    'action_date' => $bookmark['created_at'],
                    'session_id' => 'backfill'
                ]);
                $stats['user_actions']++;
            }
        } catch (Exception $e) {
            echo "  Error processing bookmark: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "✓ Processed bookmarks\n\n";
    
    // Step 4: Populate user_activity_analytics from ratings
    echo "Step 4: Populating user_activity_analytics with rating actions...\n";
    
    $ratings = $db->fetchAll("
        SELECT 
            user_id,
            article_id,
            stars as value,
            created_at
        FROM article_ratings
        WHERE stars IS NOT NULL
    ");
    
    echo "Found " . count($ratings) . " ratings...\n";
    
    foreach ($ratings as $rating) {
        try {
            $existingRating = $db->fetchSingle("
                SELECT id FROM user_activity_analytics 
                WHERE user_id = ? 
                AND article_id = ? 
                AND action_type = 'rating'
            ", [$rating['user_id'], $rating['article_id']]);
            
            if (!$existingRating) {
                $db->insert('user_activity_analytics', [
                    'user_id' => $rating['user_id'],
                    'article_id' => $rating['article_id'],
                    'action_type' => 'rating',
                    'action_date' => $rating['created_at'],
                    'value' => $rating['value'],
                    'session_id' => 'backfill'
                ]);
                $stats['user_actions']++;
            }
        } catch (Exception $e) {
            echo "  Error processing rating: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "✓ Processed ratings\n\n";
    
    // Step 5: Track helpful ratings
    echo "Step 5: Populating user_activity_analytics with helpful ratings...\n";
    
    $helpfulRatings = $db->fetchAll("
        SELECT 
            user_id,
            article_id,
            was_helpful as value,
            created_at
        FROM article_ratings
        WHERE was_helpful IS NOT NULL
    ");
    
    echo "Found " . count($helpfulRatings) . " helpful ratings...\n";
    
    foreach ($helpfulRatings as $helpful) {
        try {
            $existingHelpful = $db->fetchSingle("
                SELECT id FROM user_activity_analytics 
                WHERE user_id = ? 
                AND article_id = ? 
                AND action_type = 'helpful_rating'
            ", [$helpful['user_id'], $helpful['article_id']]);
            
            if (!$existingHelpful) {
                $db->insert('user_activity_analytics', [
                    'user_id' => $helpful['user_id'],
                    'article_id' => $helpful['article_id'],
                    'action_type' => 'helpful_rating',
                    'action_date' => $helpful['created_at'],
                    'value' => $helpful['value'],
                    'session_id' => 'backfill'
                ]);
                $stats['user_actions']++;
            }
        } catch (Exception $e) {
            echo "  Error processing helpful rating: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
    
    echo "✓ Processed helpful ratings\n\n";
    
    // Step 6: Track comments (already in article_comments table, just for stats)
    echo "Step 6: Counting existing comments...\n";
    
    $commentCount = $db->fetchSingle("SELECT COUNT(*) as count FROM article_comments");
    $stats['comments'] = $commentCount['count'];
    
    echo "✓ Found " . $stats['comments'] . " comments in database\n\n";
    
    // Summary
    echo "=== Population Complete ===\n\n";
    echo "Summary:\n";
    echo "  - Admin actions tracked: " . $stats['admin_actions'] . "\n";
    echo "  - User actions tracked: " . $stats['user_actions'] . "\n";
    echo "  - Existing comments: " . $stats['comments'] . "\n";
    echo "  - Errors encountered: " . $stats['errors'] . "\n\n";
    
    echo "✓ User statistics data has been populated successfully!\n";
    echo "You can now view the statistics in the User Analytics dashboard.\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

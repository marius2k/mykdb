<?php
/**
 * Test Real-Time Analytics Tracking
 * 
 * This script tests the AnalyticsTracker class to ensure it works correctly
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "=== Testing AnalyticsTracker ===\n\n";

try {
    $tracker = new AnalyticsTracker();
    echo "✓ AnalyticsTracker class instantiated successfully\n\n";
    
    // Get a test user and article
    $db = new Database();
    $testUser = $db->fetchSingle("SELECT id FROM users LIMIT 1");
    $testArticle = $db->fetchSingle("SELECT id FROM articles LIMIT 1");
    
    if (!$testUser || !$testArticle) {
        die("✗ No test user or article found in database\n");
    }
    
    $userId = $testUser['id'];
    $articleId = $testArticle['id'];
    
    echo "Test User ID: {$userId}\n";
    echo "Test Article ID: {$articleId}\n\n";
    
    // Test 1: Track View
    echo "Test 1: Tracking article view...\n";
    $result = $tracker->trackView($userId, $articleId);
    echo $result ? "✓ View tracked successfully\n" : "✗ Failed to track view\n";
    
    // Test 2: Track Rating
    echo "\nTest 2: Tracking article rating...\n";
    $result = $tracker->trackRating($userId, $articleId, 5);
    echo $result ? "✓ Rating tracked successfully\n" : "✗ Failed to track rating\n";
    
    // Test 3: Track Bookmark
    echo "\nTest 3: Tracking bookmark...\n";
    $result = $tracker->trackBookmark($userId, $articleId);
    echo $result ? "✓ Bookmark tracked successfully\n" : "✗ Failed to track bookmark\n";
    
    // Test 4: Track Edit
    echo "\nTest 4: Tracking article edit...\n";
    $result = $tracker->trackEdit($userId, $articleId);
    echo $result ? "✓ Edit tracked successfully\n" : "✗ Failed to track edit\n";
    
    // Test 5: Track Approve
    echo "\nTest 5: Tracking article approval...\n";
    $result = $tracker->trackApprove($userId, $articleId);
    echo $result ? "✓ Approve tracked successfully\n" : "✗ Failed to track approve\n";
    
    // Test 6: Track Publish
    echo "\nTest 6: Tracking article publish...\n";
    $result = $tracker->trackPublish($userId, $articleId);
    echo $result ? "✓ Publish tracked successfully\n" : "✗ Failed to track publish\n";
    
    // Verify data in database
    echo "\n=== Verification ===\n\n";
    
    $adminActions = $db->fetchSingle("
        SELECT COUNT(*) as count 
        FROM admin_activity_analytics 
        WHERE user_id = ? AND article_id = ? 
        AND action_date > NOW() - INTERVAL 1 MINUTE
    ", [$userId, $articleId]);
    
    echo "Admin actions tracked: {$adminActions['count']}\n";
    
    $userActions = $db->fetchSingle("
        SELECT COUNT(*) as count 
        FROM user_activity_analytics 
        WHERE user_id = ? AND article_id = ? 
        AND action_date > NOW() - INTERVAL 1 MINUTE
    ", [$userId, $articleId]);
    
    echo "User actions tracked: {$userActions['count']}\n";
    
    if ($adminActions['count'] >= 3 && $userActions['count'] >= 3) {
        echo "\n✓ All tests passed! Real-time tracking is working correctly.\n";
    } else {
        echo "\n⚠ Some tracking may have failed. Check the database tables.\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

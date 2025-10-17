<?php
/**
 * Script to apply the User Analytics SQL migration
 * 
 * This script will create the necessary tables for the User Analytics system
 */

require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';

// Check permissions - only admin and superadmin can access
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('Location: ' . APP_URL . 'public/login.php');
    exit;
}

include APP_ROOT . 'includes/header.php';

// Initialize database connection
$db = new Database();

// Check if the tables already exist
$tablesExist = false;
try {
    $result = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_analytics'");
    $tablesExist = !empty($result);
} catch (Exception $e) {
    // Table doesn't exist or other error
}

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])) {
    try {
        // Array of SQL statements to execute directly
        $sqlStatements = [
            // user_activity_analytics table
            "CREATE TABLE IF NOT EXISTS `user_activity_analytics` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) DEFAULT NULL,
              `action_type` enum('view', 'bookmark', 'comment', 'rating', 'vote', 'save_pdf', 'usefulness_rating') NOT NULL,
              `article_id` int(11) DEFAULT NULL,
              `comment_id` int(11) DEFAULT NULL,
              `value` decimal(10,2) DEFAULT NULL COMMENT 'Used for ratings, votes, etc.',
              `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `session_id` varchar(255) DEFAULT NULL,
              `ip_address` varchar(45) DEFAULT NULL,
              `user_agent` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`),
              KEY `idx_article_id` (`article_id`),
              KEY `idx_action_type` (`action_type`),
              KEY `idx_action_date` (`action_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // user_activity_summary table
            "CREATE TABLE IF NOT EXISTS `user_activity_summary` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `activity_date` date NOT NULL,
              `views_count` int(11) NOT NULL DEFAULT 0,
              `bookmarks_count` int(11) NOT NULL DEFAULT 0,
              `comments_count` int(11) NOT NULL DEFAULT 0,
              `ratings_count` int(11) NOT NULL DEFAULT 0,
              `votes_count` int(11) NOT NULL DEFAULT 0,
              `pdf_saves_count` int(11) NOT NULL DEFAULT 0,
              `usefulness_ratings_count` int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `idx_user_date` (`user_id`, `activity_date`),
              KEY `idx_activity_date` (`activity_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // user_reading_time table
            "CREATE TABLE IF NOT EXISTS `user_reading_time` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `article_id` int(11) NOT NULL,
              `reading_time` int(11) NOT NULL COMMENT 'Time in seconds',
              `session_id` varchar(255) NOT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_user_article` (`user_id`, `article_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // admin_activity_analytics table
            "CREATE TABLE IF NOT EXISTS `admin_activity_analytics` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `action_type` enum('edit', 'publish', 'approve', 'reject', 'delete', 'restore') NOT NULL,
              `article_id` int(11) DEFAULT NULL,
              `comment_id` int(11) DEFAULT NULL,
              `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `ip_address` varchar(45) DEFAULT NULL,
              `user_agent` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`),
              KEY `idx_article_id` (`article_id`),
              KEY `idx_action_type` (`action_type`),
              KEY `idx_action_date` (`action_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];
        
        // Execute each SQL statement
        foreach ($sqlStatements as $sql) {
            $db->query($sql);
        }
        
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
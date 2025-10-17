-- Add User Analytics Tables
-- Created: 2025-10-08

-- Table for tracking user activity analytics
CREATE TABLE IF NOT EXISTS `user_activity_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` enum('view', 'bookmark', 'comment', 'rating', 'vote', 'edit', 'publish', 'approve', 'save_pdf', 'usefulness_rating') NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `value` decimal(10,2) DEFAULT NULL COMMENT 'Used for ratings, votes, etc.',
  `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_action_date` (`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for summarizing user activity by day (for faster analytics)
CREATE TABLE IF NOT EXISTS `user_activity_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_date` date NOT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `bookmarks_count` int(11) NOT NULL DEFAULT 0,
  `comments_count` int(11) NOT NULL DEFAULT 0,
  `ratings_count` int(11) NOT NULL DEFAULT 0,
  `votes_count` int(11) NOT NULL DEFAULT 0,
  `edits_count` int(11) NOT NULL DEFAULT 0,
  `publishes_count` int(11) NOT NULL DEFAULT 0,
  `approvals_count` int(11) NOT NULL DEFAULT 0,
  `pdf_saves_count` int(11) NOT NULL DEFAULT 0,
  `usefulness_ratings_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`, `activity_date`),
  KEY `idx_activity_date` (`activity_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for tracking user reading time
CREATE TABLE IF NOT EXISTS `user_reading_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `reading_time` int(11) NOT NULL COMMENT 'Time in seconds',
  `session_id` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for tracking admin actions
CREATE TABLE IF NOT EXISTS `admin_activity_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` enum('edit', 'publish', 'approve', 'reject', 'delete', 'restore') NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_action_date` (`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create a trigger to update the summary table when new activity is added
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_user_activity_summary
AFTER INSERT ON user_activity_analytics
FOR EACH ROW
BEGIN
    DECLARE activity_type VARCHAR(30);
    SET activity_type = NEW.action_type;
    
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
    ) 
    VALUES (
        NEW.user_id, 
        DATE(NEW.action_date),
        IF(activity_type = 'view', 1, 0),
        IF(activity_type = 'bookmark', 1, 0),
        IF(activity_type = 'comment', 1, 0),
        IF(activity_type = 'rating', 1, 0),
        IF(activity_type = 'vote', 1, 0),
        IF(activity_type = 'edit', 1, 0),
        IF(activity_type = 'publish', 1, 0),
        IF(activity_type = 'approve', 1, 0),
        IF(activity_type = 'save_pdf', 1, 0),
        IF(activity_type = 'usefulness_rating', 1, 0)
    )
    ON DUPLICATE KEY UPDATE
        views_count = views_count + IF(activity_type = 'view', 1, 0),
        bookmarks_count = bookmarks_count + IF(activity_type = 'bookmark', 1, 0),
        comments_count = comments_count + IF(activity_type = 'comment', 1, 0),
        ratings_count = ratings_count + IF(activity_type = 'rating', 1, 0),
        votes_count = votes_count + IF(activity_type = 'vote', 1, 0),
        edits_count = edits_count + IF(activity_type = 'edit', 1, 0),
        publishes_count = publishes_count + IF(activity_type = 'publish', 1, 0),
        approvals_count = approvals_count + IF(activity_type = 'approve', 1, 0),
        pdf_saves_count = pdf_saves_count + IF(activity_type = 'save_pdf', 1, 0),
        usefulness_ratings_count = usefulness_ratings_count + IF(activity_type = 'usefulness_rating', 1, 0);
END$$
DELIMITER ;

-- Create a trigger to update the admin activity summary
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_admin_activity_summary
AFTER INSERT ON admin_activity_analytics
FOR EACH ROW
BEGIN
    DECLARE activity_type VARCHAR(30);
    SET activity_type = NEW.action_type;
    
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
    ) 
    VALUES (
        NEW.user_id, 
        DATE(NEW.action_date),
        0, 0, 0, 0, 0,
        IF(activity_type = 'edit', 1, 0),
        IF(activity_type = 'publish', 1, 0),
        IF(activity_type = 'approve', 1, 0),
        0, 0
    )
    ON DUPLICATE KEY UPDATE
        edits_count = edits_count + IF(activity_type = 'edit', 1, 0),
        publishes_count = publishes_count + IF(activity_type = 'publish', 1, 0),
        approvals_count = approvals_count + IF(activity_type = 'approve', 1, 0);
END$$
DELIMITER ;
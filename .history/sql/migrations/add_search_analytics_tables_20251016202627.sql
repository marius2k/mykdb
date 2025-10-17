-- Add Search Analytics Tables
-- Created: 2025-10-16

-- Table for tracking search queries
CREATE TABLE IF NOT EXISTS `search_queries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `query` varchar(255) NOT NULL,
  `result_count` int(11) DEFAULT 0,
  `search_id` varchar(50) DEFAULT NULL COMMENT 'Unique ID for correlating searches with clicks',
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_search_id` (`search_id`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `idx_query` (`query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for tracking clicks on search results
CREATE TABLE IF NOT EXISTS `search_result_clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `search_id` varchar(50) DEFAULT NULL COMMENT 'Correlates with search_queries.search_id',
  `query` varchar(255) DEFAULT NULL,
  `result_url` varchar(1000) NOT NULL,
  `result_title` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT NULL COMMENT 'Position in search results (1-based)',
  `time_to_click` int(11) DEFAULT NULL COMMENT 'Time in milliseconds from search to click',
  `article_id` varchar(50) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_search_id` (`search_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update user_activity_analytics to support search tracking
ALTER TABLE `user_activity_analytics`
ADD COLUMN IF NOT EXISTS `search_query` varchar(255) DEFAULT NULL AFTER `approved_by`;

-- Add enum value for search actions
-- Note: MySQL doesn't allow direct ALTER of ENUM values in a safe way
-- Use this format for safety, recreating the column with the new values
ALTER TABLE `user_activity_analytics` 
MODIFY COLUMN `action_type` ENUM('view', 'bookmark', 'comment', 'rating', 'vote', 'edit', 'publish', 'approve', 'save_pdf', 'usefulness_rating', 'search', 'search_click') NOT NULL;
-- Add File Downloads Table for tracking PDF downloads
-- Created: 2025-10-16

-- Table for tracking file downloads (PDFs, etc.)
CREATE TABLE IF NOT EXISTS `file_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `article_id` varchar(50) NOT NULL,
  `file_type` varchar(20) NOT NULL DEFAULT 'pdf',
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `download_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_download_date` (`download_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
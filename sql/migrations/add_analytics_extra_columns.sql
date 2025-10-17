-- Add additional columns for user analytics tables
-- Created: 2025-10-16

-- Add columns to user_activity_analytics
ALTER TABLE `user_activity_analytics` 
ADD COLUMN `vote_type` ENUM('like', 'dislike') NULL AFTER `ip_address`,
ADD COLUMN `target_type` ENUM('article', 'comment') NULL AFTER `vote_type`,
ADD COLUMN `status` VARCHAR(50) NULL AFTER `target_type`,
ADD COLUMN `approved_by` INT(11) NULL AFTER `status`;

-- Add columns to admin_activity_analytics
ALTER TABLE `admin_activity_analytics` 
ADD COLUMN `vote_type` ENUM('like', 'dislike') NULL AFTER `ip_address`,
ADD COLUMN `target_type` ENUM('article', 'comment') NULL AFTER `vote_type`,
ADD COLUMN `status` VARCHAR(50) NULL AFTER `target_type`,
ADD COLUMN `approved_by` INT(11) NULL AFTER `status`;
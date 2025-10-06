-- Migration 004: Add publish_at field to article_versions table
-- This field will store the planned publication date for each version

ALTER TABLE `article_versions` 
ADD COLUMN `publish_at` timestamp NULL DEFAULT NULL 
AFTER `updated_at`;

-- Populate publish_at field for existing records
-- For online versions (is_online=1), copy publish_at from articles table
UPDATE `article_versions` av
JOIN `articles` a ON av.article_id = a.id
SET av.publish_at = a.publish_at
WHERE av.is_online = 1;

-- For approved offline versions (is_online=0 AND status='approved'), 
-- set publish_at to current timestamp as they are ready for publication
UPDATE `article_versions` 
SET `publish_at` = CURRENT_TIMESTAMP
WHERE `is_online` = 0 AND `status` = 'approved' AND `publish_at` IS NULL;

-- Add index for better performance on publish_at queries
ALTER TABLE `article_versions` 
ADD INDEX `idx_publish_at` (`publish_at`);

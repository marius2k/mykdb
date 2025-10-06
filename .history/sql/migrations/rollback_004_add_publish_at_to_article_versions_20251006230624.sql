-- Rollback Migration 004: Remove publish_at field from article_versions table

-- Remove index first
ALTER TABLE `article_versions` 
DROP INDEX `idx_publish_at`;

-- Remove the column
ALTER TABLE `article_versions` 
DROP COLUMN `publish_at`;

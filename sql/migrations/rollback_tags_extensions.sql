-- Rollback Migration: Remove additional fields from tags and article_tags tables
-- Date: 2025-08-06
-- Description: Remove extended fields if needed

USE knowledge_db;

-- Rollback article_tags table changes
ALTER TABLE `article_tags` 
DROP FOREIGN KEY `article_tags_assigned_by_fk`;

ALTER TABLE `article_tags` 
DROP INDEX `idx_created_at`,
DROP INDEX `idx_assigned_by`;

ALTER TABLE `article_tags` 
DROP COLUMN `created_at`,
DROP COLUMN `assigned_by`;

-- Rollback tags table changes
ALTER TABLE `tags` 
DROP COLUMN `description`,
DROP COLUMN `created_at`,
DROP COLUMN `updated_at`;

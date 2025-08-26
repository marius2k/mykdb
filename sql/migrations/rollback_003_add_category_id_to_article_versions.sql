-- Rollback: Remove category_id column from article_versions table
-- Description: Rollback for migration 003_add_category_id_to_article_versions.sql

-- Remove foreign key constraint first
ALTER TABLE article_versions 
DROP FOREIGN KEY fk_article_versions_category;

-- Remove category_id column
ALTER TABLE article_versions 
DROP COLUMN category_id;

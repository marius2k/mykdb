-- Migration: Add additional fields to article_tags table
-- Date: 2025-08-06
-- Description: Extend article_tags table with tracking fields

USE knowledge_db;

-- Add created_at field to article_tags table for tracking when tag was assigned
ALTER TABLE `article_tags` 
ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Add user_id field to track who assigned the tag
ALTER TABLE `article_tags` 
ADD COLUMN `assigned_by` INT NULL;

-- Add foreign key constraint for assigned_by field
ALTER TABLE `article_tags` 
ADD CONSTRAINT `article_tags_assigned_by_fk` 
FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add index for better performance on queries
ALTER TABLE `article_tags` 
ADD INDEX `idx_created_at` (`created_at`),
ADD INDEX `idx_assigned_by` (`assigned_by`);

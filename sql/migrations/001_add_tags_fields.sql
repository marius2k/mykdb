-- Migration: Add description and created_at fields to tags table
-- Date: 2025-08-06
-- Description: Extend tags table with additional fields for advanced tag management

USE knowledge_db;

-- Add description field to tags table
ALTER TABLE `tags` 
ADD COLUMN `description` TEXT NULL AFTER `name`;

-- Add created_at field to tags table
ALTER TABLE `tags` 
ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `description`;

-- Add updated_at field to tags table for tracking modifications
ALTER TABLE `tags` 
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Update existing tags to have a created_at timestamp
UPDATE `tags` 
SET `created_at` = CURRENT_TIMESTAMP 
WHERE `created_at` IS NULL OR `created_at` = '0000-00-00 00:00:00';

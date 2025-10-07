-- Migration: Add view_source tracking to analytics tables
-- Date: 2025-10-07
-- Purpose: Separate public views from admin views for better analytics

-- Add view_source column to article_views table
ALTER TABLE article_views 
ADD COLUMN view_source VARCHAR(20) DEFAULT 'public' 
COMMENT 'Source of the view: public, admin_preview, admin_edit, admin_analytics';

-- Add view_source column to article_reading_time table
ALTER TABLE article_reading_time 
ADD COLUMN view_source VARCHAR(20) DEFAULT 'public'
COMMENT 'Source of the reading session: public, admin_preview, admin_edit, admin_analytics';

-- Add index for better query performance
CREATE INDEX idx_article_views_source ON article_views(article_id, view_source);
CREATE INDEX idx_article_reading_time_source ON article_reading_time(article_id, view_source);

-- Update existing records to 'public' (already default, but explicit)
UPDATE article_views SET view_source = 'public' WHERE view_source IS NULL;
UPDATE article_reading_time SET view_source = 'public' WHERE view_source IS NULL;
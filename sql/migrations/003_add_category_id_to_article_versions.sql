-- Migration: Add category_id column to article_versions table for VoA system
-- Description: Each version should be able to have its own category

-- Add category_id column to article_versions
ALTER TABLE article_versions 
ADD COLUMN category_id INT DEFAULT NULL AFTER content;

-- Add foreign key constraint to categories table
ALTER TABLE article_versions 
ADD CONSTRAINT fk_article_versions_category 
FOREIGN KEY (category_id) REFERENCES categories(id);

-- Update existing records to inherit category from main articles table
UPDATE article_versions av 
JOIN articles a ON av.article_id = a.id 
SET av.category_id = a.category_id 
WHERE av.category_id IS NULL;

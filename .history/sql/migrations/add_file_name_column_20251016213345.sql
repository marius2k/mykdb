-- Add file_name column to file_downloads table
ALTER TABLE file_downloads ADD COLUMN file_name VARCHAR(255) DEFAULT NULL AFTER file_type;
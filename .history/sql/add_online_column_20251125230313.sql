-- Add online column to users table to track logged in users
-- Run this SQL in your MySQL database

ALTER TABLE `users` 
ADD COLUMN `online` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'User online status: 1=online, 0=offline' AFTER `status`;

-- Set all existing users to offline initially
UPDATE `users` SET `online` = 0;

-- Optional: Add index for better performance when counting online users
ALTER TABLE `users` ADD INDEX `idx_online` (`online`);

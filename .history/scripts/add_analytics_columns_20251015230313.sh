#!/bin/bash
# Script to add new columns to analytics tables
# Run this script inside Docker container

echo "Running SQL migration to add analytics extra columns..."

# Navigate to the SQL directory
cd /var/www/html

# Execute the SQL commands directly using mysql client
mysql -h db -u root knowledge_db <<EOF
-- Add columns to user_activity_analytics
ALTER TABLE user_activity_analytics 
ADD COLUMN vote_type ENUM('like', 'dislike') NULL AFTER ip_address,
ADD COLUMN target_type ENUM('article', 'comment') NULL AFTER vote_type,
ADD COLUMN status VARCHAR(50) NULL AFTER target_type,
ADD COLUMN approved_by INT(11) NULL AFTER status;

-- Add columns to admin_activity_analytics
ALTER TABLE admin_activity_analytics 
ADD COLUMN vote_type ENUM('like', 'dislike') NULL AFTER ip_address,
ADD COLUMN target_type ENUM('article', 'comment') NULL AFTER vote_type,
ADD COLUMN status VARCHAR(50) NULL AFTER target_type,
ADD COLUMN approved_by INT(11) NULL AFTER status;
EOF

echo "Migration completed. Check for any errors above."
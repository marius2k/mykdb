<?php
// Script to add additional columns to analytics tables
// Use in Docker environment

echo "Starting migration to add additional columns to analytics tables...\n";

try {
    // Connect directly using Docker environment variables
    $db_host = 'db'; // Docker service name
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'knowledge_db';
    
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Add columns to user_activity_analytics
    $sql1 = "ALTER TABLE user_activity_analytics 
             ADD COLUMN IF NOT EXISTS vote_type ENUM('like', 'dislike') NULL,
             ADD COLUMN IF NOT EXISTS target_type ENUM('article', 'comment') NULL,
             ADD COLUMN IF NOT EXISTS status VARCHAR(50) NULL,
             ADD COLUMN IF NOT EXISTS approved_by INT(11) NULL";
             
    $conn->exec($sql1);
    echo "Added columns to user_activity_analytics table.\n";
    
    // Add columns to admin_activity_analytics
    $sql2 = "ALTER TABLE admin_activity_analytics 
             ADD COLUMN IF NOT EXISTS vote_type ENUM('like', 'dislike') NULL,
             ADD COLUMN IF NOT EXISTS target_type ENUM('article', 'comment') NULL,
             ADD COLUMN IF NOT EXISTS status VARCHAR(50) NULL,
             ADD COLUMN IF NOT EXISTS approved_by INT(11) NULL";
             
    $conn->exec($sql2);
    echo "Added columns to admin_activity_analytics table.\n";
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
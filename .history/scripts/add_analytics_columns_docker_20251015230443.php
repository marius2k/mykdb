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
    
    // Check if columns exist before adding them
    function columnExists($conn, $table, $column) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count 
                               FROM information_schema.columns 
                               WHERE table_schema = DATABASE() 
                               AND table_name = ? 
                               AND column_name = ?");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    // Add columns to user_activity_analytics
    $table = "user_activity_analytics";
    $columns = [
        "vote_type" => "ADD COLUMN vote_type ENUM('like', 'dislike') NULL",
        "target_type" => "ADD COLUMN target_type ENUM('article', 'comment') NULL",
        "status" => "ADD COLUMN status VARCHAR(50) NULL",
        "approved_by" => "ADD COLUMN approved_by INT(11) NULL"
    ];
    
    foreach ($columns as $column => $addSql) {
        if (!columnExists($conn, $table, $column)) {
            $sql = "ALTER TABLE $table $addSql";
            $conn->exec($sql);
            echo "Added column $column to $table.\n";
        } else {
            echo "Column $column already exists in $table.\n";
        }
    }
    
    // Add columns to admin_activity_analytics
    $table = "admin_activity_analytics";
    $columns = [
        "vote_type" => "ADD COLUMN vote_type ENUM('like', 'dislike') NULL",
        "target_type" => "ADD COLUMN target_type ENUM('article', 'comment') NULL",
        "status" => "ADD COLUMN status VARCHAR(50) NULL",
        "approved_by" => "ADD COLUMN approved_by INT(11) NULL"
    ];
             
    foreach ($columns as $column => $addSql) {
        if (!columnExists($conn, $table, $column)) {
            $sql = "ALTER TABLE $table $addSql";
            $conn->exec($sql);
            echo "Added column $column to $table.\n";
        } else {
            echo "Column $column already exists in $table.\n";
        }
    }
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
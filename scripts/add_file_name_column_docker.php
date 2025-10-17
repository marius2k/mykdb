<?php
// Execute the SQL to add file_name column to file_downloads table
require_once '../config/bootstrap.php';
require_once APP_ROOT . 'classes/database.php';

echo "Starting to add file_name column to file_downloads table...\n";

try {
    // Connect to database
    $db = new Database();
    
    // SQL to add the column
    $sql = "ALTER TABLE file_downloads ADD COLUMN file_name VARCHAR(255) DEFAULT NULL AFTER file_type";
    
    echo "Executing SQL: $sql\n";
    
    // Execute the SQL
    $result = $db->query($sql);
    
    echo "Column file_name added successfully to file_downloads table!\n";
    
} catch (Exception $e) {
    // Check if the error is because the column already exists
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "The file_name column already exists. No action needed.\n";
        exit(0);
    }
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
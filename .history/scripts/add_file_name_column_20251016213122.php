<?php
// Script to add file_name column to file_downloads table
require_once __DIR__ . '/../config/bootstrap.php';
require_once APP_ROOT . 'classes/database.php';

echo "Starting migration to add file_name column to file_downloads table...\n";

try {
    // Connect to database
    $db = new Database();
    
    // SQL statement to add the column
    $sql = "ALTER TABLE file_downloads ADD COLUMN file_name VARCHAR(255) DEFAULT NULL AFTER file_type";
    
    echo "Executing: $sql\n";
    
    try {
        $db->query($sql);
        echo "Column file_name added successfully to file_downloads table.\n";
    } catch (Exception $e) {
        echo "Error executing statement: " . $e->getMessage() . "\n";
        // If the error is about the column already existing, we can consider this a success
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column already exists. Migration completed.\n";
            exit(0);
        } else {
            throw $e;
        }
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
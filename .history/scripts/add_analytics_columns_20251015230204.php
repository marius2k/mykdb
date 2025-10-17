<?php
// Script to add additional columns to analytics tables
require_once __DIR__ . '/../config/bootstrap.php';
require_once APP_ROOT . 'classes/database.php';

echo "Starting migration to add additional columns to analytics tables...\n";

try {
    $db = new Database();
    
    // Read the SQL file
    $sql = file_get_contents(APP_ROOT . 'sql/migrations/add_analytics_extra_columns.sql');
    
    // Split the SQL commands by semicolon
    $commands = explode(';', $sql);
    
    // Execute each command
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command)) {
            echo "Executing: " . substr($command, 0, 50) . "...\n";
            $db->query($command);
            echo "Command executed successfully.\n";
        }
    }
    
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    // If the error contains 'Duplicate column', it likely means the columns already exist
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Columns may already exist. This is not necessarily an error.\n";
    }
}
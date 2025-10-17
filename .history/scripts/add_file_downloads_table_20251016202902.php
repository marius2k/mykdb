<?php
// Script to add file downloads table
require_once __DIR__ . '/../config/bootstrap.php';
require_once APP_ROOT . 'classes/database.php';

echo "Starting migration to add file downloads table...\n";

try {
    // Connect to database
    $db = new Database();
    
    // Read the SQL file
    $sqlFile = APP_ROOT . 'sql/migrations/add_file_downloads_table.sql';
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Split SQL by semicolons
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement)) {
            continue;
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        
        try {
            $db->query($statement);
            echo "Statement executed successfully.\n";
        } catch (Exception $e) {
            echo "Error executing statement: " . $e->getMessage() . "\n";
            // Continue with the next statement
        }
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
#!/usr/bin/env php
<?php
/**
 * Database Migration Runner
 * Script pentru a rula migrațiile bazei de date
 */

require_once __DIR__ . '/../config/bootstrap.php';

function runMigrations() {
    $db = new Database();
    
    echo "🚀 Starting database migrations for tags improvements...\n";
    
    // Lista migrațiilor în ordinea de executare
    $migrations = [
        '001_add_tags_fields.sql' => 'Add description, created_at, updated_at to tags table',
        '002_extend_article_tags.sql' => 'Add tracking fields to article_tags table'
    ];
    
    $migrationsPath = __DIR__ . '/../sql/migrations/';
    
    foreach ($migrations as $file => $description) {
        $filePath = $migrationsPath . $file;
        
        if (!file_exists($filePath)) {
            echo "❌ Migration file not found: $file\n";
            continue;
        }
        
        echo "⏳ Running migration: $description\n";
        
        try {
            $sql = file_get_contents($filePath);
            
            // Împarte SQL-ul în instrucțiuni separate
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($statement) {
                    return !empty($statement) && 
                           !preg_match('/^(USE|--|\/\*)/', $statement);
                }
            );
            
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $db->query($statement . ';');
                }
            }
            
            echo "✅ Migration completed: $file\n";
            
        } catch (Exception $e) {
            echo "❌ Migration failed: $file - " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    echo "🎉 All migrations completed successfully!\n";
    return true;
}

function checkDatabaseConnection() {
    try {
        $db = new Database();
        echo "✅ Database connection successful\n";
        return true;
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
echo "=== Database Migration Tool ===\n";

if (!checkDatabaseConnection()) {
    exit(1);
}

$success = runMigrations();
exit($success ? 0 : 1);

<?php
/**
 * Database Structure Checker
 * Verifică structura actuală a tabelelor tags și article_tags
 */

require_once __DIR__ . '/../config/bootstrap.php';

function checkTableStructure() {
    $db = new Database();
    
    echo "=== Database Structure Check ===\n\n";
    
    // Verifică structura tabelei tags
    echo "📋 Tags table structure:\n";
    try {
        $columns = $db->fetchAll("DESCRIBE tags");
        foreach ($columns as $column) {
            echo sprintf("  %-15s %-20s %-8s %-8s %-15s %s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'] ?? 'NULL',
                $column['Extra'] ?? ''
            );
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Error checking tags table: " . $e->getMessage() . "\n\n";
    }
    
    // Verifică structura tabelei article_tags
    echo "📋 Article_tags table structure:\n";
    try {
        $columns = $db->fetchAll("DESCRIBE article_tags");
        foreach ($columns as $column) {
            echo sprintf("  %-15s %-20s %-8s %-8s %-15s %s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'] ?? 'NULL',
                $column['Extra'] ?? ''
            );
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Error checking article_tags table: " . $e->getMessage() . "\n\n";
    }
    
    // Verifică indexurile
    echo "🔍 Tags table indexes:\n";
    try {
        $indexes = $db->fetchAll("SHOW INDEX FROM tags");
        foreach ($indexes as $index) {
            echo sprintf("  %-20s %-15s %s\n", 
                $index['Key_name'], 
                $index['Column_name'],
                $index['Non_unique'] ? '(non-unique)' : '(unique)'
            );
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Error checking tags indexes: " . $e->getMessage() . "\n\n";
    }
    
    echo "🔍 Article_tags table indexes:\n";
    try {
        $indexes = $db->fetchAll("SHOW INDEX FROM article_tags");
        foreach ($indexes as $index) {
            echo sprintf("  %-20s %-15s %s\n", 
                $index['Key_name'], 
                $index['Column_name'],
                $index['Non_unique'] ? '(non-unique)' : '(unique)'
            );
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Error checking article_tags indexes: " . $e->getMessage() . "\n\n";
    }
    
    // Verifică dacă există date în tabele
    echo "📊 Data summary:\n";
    try {
        $tagCount = $db->fetchSingle("SELECT COUNT(*) as count FROM tags")['count'];
        echo "  Tags: $tagCount records\n";
        
        $relationCount = $db->fetchSingle("SELECT COUNT(*) as count FROM article_tags")['count'];
        echo "  Article-Tag relations: $relationCount records\n";
        
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Error getting data summary: " . $e->getMessage() . "\n\n";
    }
}

checkTableStructure();

<?php
/**
 * Script to apply the User Analytics SQL migration
 * 
 * This script will create the necessary tables for the User Analytics system
 */

require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';

// Check permissions - only admin and superadmin can access
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('Location: ' . APP_URL . 'public/login.php');
    exit;
}

include APP_ROOT . 'includes/header.php';

// Read the SQL file
$sqlFilePath = APP_ROOT . 'sql/migrations/add_user_analytics_tables.sql';
$sql = file_get_contents($sqlFilePath);

if ($sql === false) {
    die("Error reading SQL file: " . $sqlFilePath);
}

// Replace DELIMITER statements which can cause issues when running in PHP
$sql = preg_replace('/DELIMITER \$\$/', '', $sql);
$sql = preg_replace('/DELIMITER ;/', '', $sql);

// Split the SQL file into individual statements
$statements = explode(';', $sql);
$statements = array_filter($statements, function($statement) {
    return trim($statement) !== '';
});

// Initialize database connection
$db = new Database();

// Check if the tables already exist
$tablesExist = false;
try {
    $result = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_analytics'");
    $tablesExist = !empty($result);
} catch (Exception $e) {
    // Table doesn't exist or other error
}

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])) {
    try {
        // Execute each statement
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Special handling for trigger statements which end with END$$
            if (strpos($statement, 'CREATE TRIGGER') !== false) {
                $statement = str_replace('END$$', 'END', $statement);
            }
            
            // Execute the statement
            $db->query($statement);
        }
        
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
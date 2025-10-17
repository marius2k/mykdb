<?php
/**
 * Script to apply the User Analytics SQL migration
 * 
 * This script will create the necessary tables for the User Analytics system
 */

require_once '../config/bootstrap.php';
require_once '../config/db.php';

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. You must be an admin to run this script.';
    exit;
}

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

// Connect to the database
$conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Execute each statement
$error = false;
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Special handling for trigger statements which end with END$$
    if (strpos($statement, 'CREATE TRIGGER') !== false) {
        $statement = str_replace('END$$', 'END', $statement);
    }
    
    if (!$conn->query($statement)) {
        echo "Error executing: " . $statement . "<br>";
        echo "MySQL Error: " . $conn->error . "<br><br>";
        $error = true;
    } else {
        echo "Successfully executed: " . substr($statement, 0, 100) . "...<br>";
    }
}

if (!$error) {
    echo "<br><strong>Migration completed successfully!</strong>";
} else {
    echo "<br><strong>Migration completed with errors. Please check the output above.</strong>";
}

$conn->close();
?>
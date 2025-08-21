<?php
require_once 'config/bootstrap.php';

echo "<h3>PDO Connection Test</h3>";

$db = new Database();

// Test simple query
$result = $db->query("SELECT 'test' as name, 'description' as description")->fetchAll();
echo "<pre>Simple test query result: ";
var_dump($result);
echo "</pre>";

// Test with explicit FETCH_ASSOC
$result2 = $db->query("SELECT 'test' as name, 'description' as description")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>Explicit FETCH_ASSOC result: ";
var_dump($result2);
echo "</pre>";

// Test badges table if it exists
try {
    $badges = $db->query("SELECT * FROM badges LIMIT 1")->fetchAll();
    echo "<pre>Badges table test: ";
    var_dump($badges);
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>Error querying badges: " . $e->getMessage() . "</pre>";
}

// Test user_badges table if it exists
try {
    $userBadges = $db->query("SELECT * FROM user_badges LIMIT 1")->fetchAll();
    echo "<pre>User badges table test: ";
    var_dump($userBadges);
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>Error querying user_badges: " . $e->getMessage() . "</pre>";
}
?>

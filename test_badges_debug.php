<?php
require_once 'config/bootstrap.php';

// Simulăm un user ID pentru test
$userId = 3; // sau orice ID valid din baza de date

$db = new Database();
$gamification = new Gamification($db);

echo "<h3>Testing getUserBadges() method</h3>";

$userBadgesData = $gamification->getUserBadges($userId);
echo "<pre>getUserBadges() returned: ";
var_dump($userBadgesData);
echo "</pre>";

echo "<h3>Testing fetchAll() method directly</h3>";
$directResult = $db->fetchAll(
    "SELECT b.*, ub.earned_at 
     FROM user_badges ub 
     JOIN badges b ON ub.badge_id = b.id 
     WHERE ub.user_id = ? 
     ORDER BY ub.earned_at DESC",
    [$userId]
);
echo "<pre>Direct fetchAll() returned: ";
var_dump($directResult);
echo "</pre>";

echo "<h3>Testing type of each element</h3>";
foreach ($userBadgesData as $index => $item) {
    echo "<p>Item $index type: " . gettype($item) . "</p>";
    if (is_array($item)) {
        echo "<pre>Item $index keys: " . implode(', ', array_keys($item)) . "</pre>";
        if (isset($item['name'])) {
            echo "<pre>Item $index name: '" . $item['name'] . "'</pre>";
        }
    }
}
?>

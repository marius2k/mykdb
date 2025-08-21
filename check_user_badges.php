<?php
require_once 'config/bootstrap.php';

echo "<h3>Verificare badge-uri pentru user_id = 5</h3>";

$db = new Database();
$userId = 5;

// Verifică dacă user-ul există
$user = $db->query("SELECT id, first_name, last_name FROM users WHERE id = ?", [$userId])->fetch();
if ($user) {
    echo "<p>User găsit: " . $user['first_name'] . " " . $user['last_name'] . " (ID: " . $user['id'] . ")</p>";
} else {
    echo "<p>User cu ID $userId nu există</p>";
    exit;
}

// Verifică badge-urile din user_badges
echo "<h4>Badge-uri din tabelul user_badges:</h4>";
$userBadgesRaw = $db->query("SELECT * FROM user_badges WHERE user_id = ?", [$userId])->fetchAll();
echo "<pre>";
var_dump($userBadgesRaw);
echo "</pre>";

// Verifică badge-urile cu JOIN
echo "<h4>Badge-uri cu JOIN (metoda getUserBadges):</h4>";
$userBadgesJoin = $db->query(
    "SELECT b.*, ub.earned_at 
     FROM user_badges ub 
     JOIN badges b ON ub.badge_id = b.id 
     WHERE ub.user_id = ? 
     ORDER BY ub.earned_at DESC",
    [$userId]
)->fetchAll();
echo "<pre>";
var_dump($userBadgesJoin);
echo "</pre>";

// Testează și clasa Gamification
echo "<h4>Rezultat din clasa Gamification:</h4>";
$gamification = new Gamification($db);
$gamificationResult = $gamification->getUserBadges($userId);
echo "<pre>";
var_dump($gamificationResult);
echo "</pre>";

// Verifică toate badge-urile disponibile
echo "<h4>Toate badge-urile disponibile:</h4>";
$allBadges = $db->query("SELECT * FROM badges ORDER BY id")->fetchAll();
foreach ($allBadges as $badge) {
    echo "<p>Badge ID: " . $badge['id'] . ", Name: '" . $badge['name'] . "'</p>";
}
?>

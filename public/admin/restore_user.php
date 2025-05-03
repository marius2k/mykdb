<?php
require_once '../../config/bootstrap.php';
require_admin();

$db = new Database();


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header('Location: users.php');
exit;
?>
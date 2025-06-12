<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

$db = new Database();
$categories = $db->fetchAll("SELECT id, name, icon FROM categories WHERE is_active = 1 ORDER BY name");
echo json_encode($categories);

?>
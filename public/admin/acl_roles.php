<?php
require_once '../../config/bootstrap.php';

$op = "edit_acl";

if (!hasPermission($_SESSION['user']['id'],$op)) {
    
    http_response_code(403);
    exit("Access denied.");

}



$db = new Database();

$roles = $db->fetchAll("SELECT id, name, label FROM roles");
?>
<?php include APP_ROOT . 'includes/header.php'; ?>

<h2>Administrare ACL – Roluri</h2>
<ul>
<?php foreach ($roles as $role): ?>
    <li>
        <strong><?= htmlspecialchars($role['label']) ?></strong>
        (<code><?= $role['name'] ?></code>) —
        <a href="acl_edit.php?role_id=<?= $role['id'] ?>">Editează permisiuni</a>
    </li>
<?php endforeach; ?>
</ul>
<?php include APP_ROOT . 'includes/footer.php'; ?>

<?php
require_once '../../config/bootstrap.php';

$op = ["edit_acl"];

if (!hasPermission($_SESSION['user']['id'],$op)) {
    
    http_response_code(403);
    exit("Access denied.");

}

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
    exit;
}


$db = new Database();

$roles = $db->fetchAll("SELECT id, name, label FROM roles");
?>
<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="breadcrumb-container" style="width: 100%; margin-top: 20px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <br>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                ACL - Roles
            </li>
        </ol>
    </nav>
</div>
<hr style="height: 1px; border: none; background-color: gray; margin: 0; width: calc(100vw - 20px); margin-left: calc(-50vw + 50% + 10px);">
<br>

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

<?php

require_once '../../config/bootstrap.php';

require_admin();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


//echo "Username:". $_SESSION['user']['username'] . " Role:" . $_SESSION ['user']['role'];


$db = new Database();
//$pdo = $db->getDatabaseConnection();

/*
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: users.php');
    exit;
}
*/


if (isset($_GET['disable']) && is_numeric($_GET['disable'])) {
    $userIdToDisable = (int)$_GET['disable'];

    if ($_SESSION['user']['id'] === $userIdToDisable) {
        die("Nu poți șterge contul propriu!");
    }

    // Soft delete
    $stmt = $db->prepare("UPDATE users SET status = 'disabled' WHERE id = ?");
    $stmt->execute([$userIdToDisable]);
    logActivity($userIdToDisable, 'user_disabled', 'User disabled: ' . $_SESSION['user']['username']);
    header('Location: users.php');
    exit;
}


$users = $db->query("SELECT * FROM users")->fetchAll();




$perPage = 5; // useri pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Total articole (pt paginare)
$totalStmt = $db->query("SELECT COUNT(*) FROM users");
$totalUsers = $totalStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

?>

<?php include APP_ROOT . '/includes/header.php'; ?>

<h2><?=lang_users?></h2>
<div>
    <table class="articles-table">
        <thead>
            <tr>
                <th>ID</th>
                <th><?=lang_users_username?></th>
                <th><?=lang_users_role?></th>
                <th><?=lang_users_status?></th>
                <th><?=lang_users_name?></th>
                <th><?=lang_users_actions?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= escape($user['username']) ?></td>
                <td><?= escape($user['role']) ?></td>
                <td><?= escape($user['status']) ?></td>
                <td><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></td>
                <td>
                        <?php if ($user['status'] === 'pending'): ?>
                                <div class="btn-group">                   
                                <a href="approve_user.php?id=<?= $user['id'] ?>" class="btn-sm btn-outline-grey">✅ <?=lang_btn_approve?></a>
                                </div>
                        <?php else: ?>
                                <?php if ($user['status'] === 'disabled'): ?>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <div class="btn-group">
                                        <a href="?disable=<?= $user['id'] ?>"class="btn-sm btn-disabled fake-disabled">♻️ <?=lang_btn_enable?></a>
                                        </div>
                                    <?php else: ?>
                                        <div class="btn-group">
                                        <a href="restore_user.php?id=<?= $user['id'] ?>" class="btn-sm btn-outline-grey">♻️ <?=lang_btn_enable?></a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <div class="btn-group">
                                        <a href="?disable=<?= $user['id'] ?>"class="btn-sm btn-disabled fake-disabled">🗑️ <?=lang_btn_disable?></a>
                                        </div>
                                    <?php else: ?>
                                        <div class="btn-group">
                                        <a href="?disable=<?= $user['id'] ?>" class="btn-sm btn-outline-grey" onclick="return confirm('<?=lang_users_msg_disable?> <?= escape($user['username']) ?>?')">🗑️ <?=lang_btn_disable?></a>
                                        </div>
                                    <?php endif;?>
                                <?php endif; ?>                                
                        <?php endif; ?>
                
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<div class="pagination" style="margin-top: 20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>


<?php include APP_ROOT . '/includes/footer.php'; ?>

<?php

require_once '../../config/bootstrap.php';

//require_admin();


$ops = ['edit_user','disable_user','enable_user','delete_user','modify_user','approve_user'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    
    exit;
        
}

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


$users = $db->query("
        SELECT u.*, r.name AS role_name, r.label AS role_label
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ")->fetchAll();




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
    <table class="articles-table" width="70%">
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
                <td><?= escape($user['role_label']) ?></td>
                <td><?= escape($user['status']) ?></td>
                <td><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></td>
                <td align="center">
                        <?php if ($user['status'] === 'pending'): ?>
                                <div>                   
                                <a href="approve_user.php?id=<?= $user['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="><?=lang_btn_approve?>"></a>
                                </div>
                        <?php else: ?>
                                <?php if ($user['status'] === 'disabled'): ?>
                                    <?php if ($user['role_name'] === 'admin'): ?>
                                        <div>
                                        <a href="?disable=<?= $user['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-enable.svg" class="op-icon" title="<?=lang_btn_enable?>"></a>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                        <a href="restore_user.php?id=<?= $user['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-enable.svg" class="op-icon" title="<?=lang_btn_enable?>"></a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($user['role_name'] === 'admin'): ?>
                                        <div >
                                        <a href="?disable=<?= $user['id'] ?>"class="btn-disabled fake-disabled"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?=lang_btn_disable?>"></a>
                                        </div>
                                    <?php else: ?>
                                        <div >
                                        <a href="?disable=<?= $user['id'] ?>" onclick="return confirm('<?=lang_users_msg_disable?> <?= escape($user['username']) ?>?')"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?=lang_btn_disable?>"></a>
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

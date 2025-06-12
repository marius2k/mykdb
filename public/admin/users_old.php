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

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


//echo "Username:". $_SESSION['user']['username'] . " Role:" . $_SESSION ['user']['role'];


$db = new Database();


// Fetch all roles for dropdown
$roles = $db->fetchAll("SELECT id, label FROM roles ORDER BY label");
$rolesJson = json_encode($roles);


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







$perPage = 5; // useri pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Total articole (pt paginare)
$totalStmt = $db->query("SELECT COUNT(*) FROM users");
$totalUsers = $totalStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);





$stmt = $db->prepare("
        SELECT u.*, r.name AS role_name, r.label AS role_label
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LIMIT :limit OFFSET :offset
        ");


$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll();


?>



<?php include APP_ROOT . '/includes/header.php'; ?>

<h2><?= lang('lang_users') ?></h2>
<div>
    <table class="articles-table" width="70%">
        <thead>
            <tr>
                <th>ID</th>
                <th><?= lang('lang_users_username') ?></th>
                <th><?= lang('lang_users_role') ?></th>
                <th><?= lang('lang_users_status') ?></th>
                <th><?= lang('lang_users_name') ?></th>
                <th><?= lang('lang_users_actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr id="user-row-<?= $user['id'] ?>">
                <td><?= $user['id'] ?></td>
                <td><?= escape($user['username']) ?></td>
                <td id="role-cell-<?= $user['id'] ?>"><?= escape($user['role_label']) ?></td>
                <td><?= escape($user['status']) ?></td>
                <td><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></td>
                <td align="center">
                        <?php if ($user['status'] === 'pending'): ?>
                                <div>                   
                                <a href="approve_user.php?id=<?= $user['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="><?= lang('lang_btn_approve') ?>"></a>
                                </div>
                        <?php else: ?>
                                <?php if ($user['status'] === 'disabled'): ?>
                                    <?php if (($user['role_name'] === 'admin') || ($user['role_name'] === 'superadmin')): ?>
                                        <span>
                                        <a href="?disable=<?= $user['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-enable.svg" class="op-icon" title="<?= lang('lang_btn_enable') ?>"></a>
                                        </span>
                                        <span>
                                            <!-- Buton schimbare rol -->
                                            <button class="op-icon" onclick="showRoleDropdownInline(<?= $user['id'] ?>, <?= $user['role_id'] ?>)">
                                                <img src="<?= APP_URL ?>assets/icons/icon-user-change-role.svg" alt="Change Role" class="op-icon" title="Change Role">
                                            </button>
                                        </span>
                                    <?php else: ?>
                                        <span>
                                        <a href="restore_user.php?id=<?= $user['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-enable.svg" class="op-icon" title="<?= lang('lang_btn_enable') ?>"></a>
                                        </span>
                                        <span>
                                            <!-- Buton schimbare rol -->
                                            <button class="op-icon" onclick="showRoleDropdownInline(<?= $user['id'] ?>, <?= $user['role_id'] ?>)">
                                                <img src="<?= APP_URL ?>assets/icons/icon-user-change-role.svg" alt="Change Role" class="op-icon" title="Change Role">
                                            </button>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($user['role_name'] === 'admin'): ?>
                                        <div >
                                        <a href="?disable=<?= $user['id'] ?>"class="btn-disabled fake-disabled"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?= lang('lang_btn_disable') ?>"></a>
                                        </div>
                                    <?php else: ?>
                                        <div >
                                        <a href="?disable=<?= $user['id'] ?>" onclick="return confirm('<?= lang('lang_users_msg_disable') ?> <?= escape($user['username']) ?>?')"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?= lang('lang_btn_disable') ?>"></a>
                                        </div>
                                    <?php endif;?>
                                <?php endif; ?>                                
                        <?php endif; ?>
                
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>

        <?php
        If($totalPages > 1): ?>
            <tfoot>
                <tr>
                    <td colspan="6">
                        <div id="pagination-results">
                                    <?php 
                                        echo renderPagination($page, $totalPages,[]);
                                                            
                                    ?>
                            </div>      
                    </td>

                </tr>                       
            </tfoot>
        <?php endif; ?>
    </table>

</div>



<?php include APP_ROOT . '/includes/footer.php'; ?>

<script>
  const allRoles = <?= $rolesJson ?>;



  function showRoleDropdownInline(userId, currentRoleId) {
        const cell = document.getElementById('role-cell-' + userId);
        
        // Construim dropdown-ul inline
        const select = document.createElement('select');
        select.onchange = () => changeUserRoleInline(select, userId);

        allRoles.forEach(role => {
            const option = document.createElement('option');
            option.value = role.id;
            option.textContent = role.label;
            if (parseInt(role.id) === parseInt(currentRoleId)) {
            option.selected = true;
            }
            select.appendChild(option);
        });

        // Injectează în celulă
        cell.innerHTML = '';
        cell.appendChild(select);
}



function changeUserRoleInline(selectEl, userId) {
  const roleId = selectEl.value;

  fetch('../change_role.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `user_id=${encodeURIComponent(userId)}&role_id=${encodeURIComponent(roleId)}`
  })
  .then(res => res.json())
  .then(data => {
    console.log('[ROLE CHANGE RESPONSE]', data); // 🔍 debug

    if (data.status === 'ok') {
      const cell = document.getElementById('role-cell-' + userId);
      const roleLabel = data.new_role_label?.label || data.new_role_label;
      cell.innerHTML = `<span class="user-role-label">${roleLabel}</span>`;
    } else {
      alert(data.message || 'Eroare la salvarea rolului.');
    }
  })
  .catch(err => {
    console.error('AJAX role change error', err);
    alert('Eroare la schimbarea rolului (AJAX)');
  });
}






</script>


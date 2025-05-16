<?php

require_once '../../config/bootstrap.php';

$ops = ['edit_acl'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
  $_SESSION['flash'] = "⚠️ Access Denied";
  $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

  echo "<script>
          alert('⚠️ Access Denied');
          window.location.href = '$referer';
      </script>";
  exit;     
}

$db = new Database();

$lockedOps = [];

// Rol implicit – cel al userului logat
$currentUserRole = $_SESSION['user']['role_name'] ?? 'guest';
//echo "acl_edit.php: Current User Role: ".$currentUserRole;

// Dacă există parametru GET, suprascrie

$selectedRoleName = $_GET['role'] ?? $currentUserRole;
//$selectedRoleId = $_SESSION['user']['role_id'];

$roles = $db->fetchAll("SELECT id, name, label FROM roles ORDER BY id");
$role = $db->fetchSingle("SELECT * FROM roles WHERE name = ?", [$selectedRoleName] ?? $roles[0]);

if (!$role) {
    die("Rol invalid.");
}

$roleId = $role['id'];
//echo "acl_edit.php: Selected Role Name: ".$selectedRoleName;
//echo "<br> acl_edit.php: Selected Role ID: ".$roleId;

$isSuperadmin = ($currentUserRole === 'superadmin');

$allOps = $db->fetchAll("SELECT id, name, description FROM operations ORDER BY name");
$roleOps = $db->fetchAll("SELECT operation_id FROM role_permissions WHERE role_id = ?", [$roleId]);
$currentOps = array_column($roleOps, 'operation_id');


//echo "<br> acl_edit.php: Current User Role: ".$currentUserRole;

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedOps = $_POST['operations'] ?? [];

    // Protecție: exclude locked ops dacă nu e superadmin
    if (!$isSuperadmin) {
        $lockedIds = $db->fetchAll("SELECT id FROM operations WHERE name IN ('" . implode("','", $lockedOps) . "')");
        $lockedIds = array_column($lockedIds, 'id');
        $selectedOps = array_diff($selectedOps, $lockedIds);
    }

    $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
    foreach ($selectedOps as $opId) {
        $db->query("INSERT INTO role_permissions (role_id, operation_id) VALUES (?, ?)", [$roleId, $opId]);
    }

    $_SESSION['flash'] = "Permisiunile au fost salvate.";
    header("Location: acl_edit.php?role=" . urlencode($selectedRoleName));
    exit;
}



$roles = $db->fetchAll("SELECT id, name, label FROM roles");

?>
<?php include APP_ROOT . 'includes/header.php'; ?>

<h4>ACL - Permisiuni pe rol</h4>

<div class="acl-container">
  <!-- STÂNGA: roluri -->
  <ul class="role-list">
    <?php foreach ($roles as $r): ?>
      <li class="<?= $r['name'] === $selectedRoleName ? 'active' : '' ?>">
        <a href="?role=<?= $r['name'] ?>"><?= htmlspecialchars($r['label']) ?></a>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- DREAPTA: permisiuni -->
  <div class="permissions">
    <form method="post">
      <div class="perm-columns">
        <?php foreach ($allOps as $op): 
          $checked = in_array($op['id'], $currentOps) ? 'checked' : '';
          $disabled = (!$isSuperadmin && in_array($op['name'], $lockedOps)) ? 'disabled' : '';
        ?>
          <label>
            <input type="checkbox" name="operations[]" value="<?= $op['id'] ?>" <?= $checked ?> <?= $disabled ?>>
            <b><?= htmlspecialchars($op['name']) ?></b> : <?= lang($op['name']) ?? htmlspecialchars($op['description'])?>
          </label>
        <?php endforeach; ?>
      </div>
      <div style="float: right;">    
        <br><br>
        <button type="submit" class="btn btn-primary">Salvează</button>
        <a href="acl_roles.php" class="btn btn-secondary">Renunță</a>
      </div>
    </form>
  </div>
</div>


<?php include APP_ROOT . 'includes/footer.php'; ?>

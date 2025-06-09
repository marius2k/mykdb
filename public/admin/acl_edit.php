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

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
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


$groupedOps = [];

foreach ($allOps as $op) {
    // Extragem grupul (tot ce vine după ultimul "_")
    $parts = explode('_', $op['name']);
    $group = end($parts); // ex: user, article, category

    // Sau dacă vrei prefix (ex: edit_user → group: user)
    $group = $parts[count($parts) - 1];

    // Alternativ: extrage prefixul (prima parte) → $parts[0]

    $groupedOps[$group][] = $op;
}









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
        
              <?php foreach ($groupedOps as $group => $ops): ?>
                  <div class="custom-box" >
                          <div class="corner-label">
                            <?= ucfirst($group) ?>
                          </div>
                          <div class="box-content">
                              <?php foreach ($ops as $op): 
                                  $checked = in_array($op['id'], $currentOps) ? 'checked' : '';
                                  $disabled = (!$isSuperadmin && in_array($op['name'], $lockedOps)) ? 'disabled' : '';
                              ?>
                                <label style="display:block; margin-left: 10px;">
                                  <input type="checkbox" name="operations[]" value="<?= $op['id'] ?>" <?= $checked ?> <?= $disabled ?>>
                                  <b><?= htmlspecialchars($op['name']) ?></b> : <?= lang($op['name']) ?? htmlspecialchars($op['description'])?>
                                </label>
                              <?php endforeach; ?>
                          </div>
                  </div>
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
<script>
 
 
  document.addEventListener('DOMContentLoaded', () => {
    const permColumns = document.querySelector('.permissions .perm-columns');
    const allCustomBoxes = document.querySelectorAll('.custom-box');

    if (permColumns && allCustomBoxes.length > 0) {
        // Inițializăm Masonry
        const msnry = new Masonry(permColumns, {
            // Options
            itemSelector: '.custom-box',
            columnWidth: '.custom-box', // Utilizează lățimea primului item ca lățime de coloană
            gutter: 20, // Spațiul între coloane și rânduri (20px)
            percentPosition: true // Asigură că lățimile în % sunt respectate
            // isFitWidth: true, // Poate fi util dacă vrei să centrezi containerul
        });

        // Inițializăm fiecare custom-box cu funcția ta
        allCustomBoxes.forEach(box => {
            initializeCustomBox(box);
        });

        // Refacem layout-ul Masonry și actualizăm "tăietura" bordurii la redimensionare
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                msnry.layout(); // Reface layout-ul Masonry
                allCustomBoxes.forEach(box => {
                    initializeCustomBox(box); // Re-actualizează tăietura bordurii
                });
            }, 100); // Debounce pentru performanță
        });
    }
  });


  </script>
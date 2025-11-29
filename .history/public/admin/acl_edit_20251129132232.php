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



<!-- Component CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/components/Button.css">

<!-- React CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

<!-- Button Component (Compiled from JSX) -->
<script src="<?= APP_URL ?>assets/js/react-components-dist/Button.js"></script>

<div class="breadcrumb-filter-section" style="display: flex; justify-content: space-between; align-items: center; width: 100vw; padding: 6px 20px; margin-top: 0; margin-bottom: 0; margin-left: calc(-50vw + 50%);">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb" style="margin-bottom: 0;">
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_edit_acl') ?>
            </li>
        </ol>
    </nav>
</div>
<br>

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
        <div id="button-save-changes"></div>
        <button type="submit" class="btn btn-primary">Salvează</button>
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

// Function to submit operations to backend
async function submitOperations() {
    // Get the current role ID
    const roleId = <?= $roleId ?>;
    
    // Get all checked operation checkboxes
    const checkboxes = document.querySelectorAll('input[name="operations[]"]:checked');
    const selectedOperations = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    console.log('Submitting operations:', selectedOperations, 'for role ID:', roleId);
    
    try {
        const response = await fetch('<?= APP_URL ?>public/api/bkd_acl_edit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                roleId: roleId,
                operations: selectedOperations
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            alert('✅ ' + data.message);
            // Optionally reload the page to show updated state
            // window.location.reload();
        } else {
            // Show error message
            alert('❌ ' + data.message);
        }
        
    } catch (error) {
        console.error('Error submitting operations:', error);
        alert('❌ Eroare la salvarea permisiunilor: ' + error.message);
    }
}

setTimeout(() => {
    if (window.Button && window.React && window.ReactDOM) {
        const saveRoot = ReactDOM.createRoot(document.getElementById('button-save-changes'));
        
        saveRoot.render(
            React.createElement(Button, {
                text: '<?= lang('lang_save') ?>',
                onClick: () => submitOperations(),
                variant: 'primary',
                icon: '�',
                size: 'medium'
            })
        );
    } else {
        console.error('React components not loaded:', { Button: window.Button, React: window.React, ReactDOM: window.ReactDOM });
    }
}, 100);

  </script>
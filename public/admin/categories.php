<?php

require_once '../../config/bootstrap.php';


//require_admin();

$ops = ['add_category','edit_category'];

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



switch ($_POST['form_id'] ?? null) {

    case 'add_category':
    
                $name = trim($_POST['cat_name']);
                $description = trim($_POST['cat_description'] ?? '');
                $icon = trim($_POST['cat_icon']);
            
                // check if category already exists
                    
                $existing = $db->fetchSingle("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)", [$name]);

                if ($existing) {
                    $error = "Categoria „{$name}” există deja.";
                } else {

                    if (strlen($name) >= 2) {
                        $stmt = $db->prepare("INSERT INTO categories (name, icon, description) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $icon, $description]);
                        header("Location: categories.php");
                        exit;
                    } else {
                        echo "<p style='color:red; text-align:center;'>Numele trebuie să aibă cel puțin 2 caractere.</p>";
                    }
                }    
                
                break;

    case 'disable_category':

                if (!empty($_POST['category_id'])) {
                
                    echo "cat id:". $_POST['category_id'];

                    $cid = trim($_POST['category_id']);


                } else {
                    echo "<script>
                        alert(' No Category selected to disable!');
                        window.location.href = 'categories.php';
                        </script>";
                
                    exit;
                }
                
                $cid = trim($_POST['category_id']);
                $stmt = $db->prepare("UPDATE categories SET is_active = '0' WHERE id = ?");
                $stmt->execute([$cid]);
                header("Location: categories.php");
                //logActivity($_SESSION['user']['id'], 'category_disabled', 'User'.$_SESSION['user']['username']. ' enabled category : ' . $cname);

                break;

    case 'enable_category':
        

                if (!empty($_POST['category_id'])) {
                
                    echo "cat id:". $_POST['category_id'];

                    $cid = trim($_POST['category_id']);


                } else {
                    echo "<script>
                        alert(' No Category selected to enable!');
                        window.location.href = 'categories.php';
                        </script>";
                
                    exit;
                }
                
                $cid = trim($_POST['category_id']);
                $stmt = $db->prepare("UPDATE categories SET is_active = '1' WHERE id = ?");
                $stmt->execute([$cid]);
                header("Location: categories.php");
                //logActivity($_SESSION['user']['id'], 'category_enabled', 'User'.$_SESSION['user']['username']. ' enabled category : ' . $cname);

                break;
    case 'add_icon':

            if (isset($_FILES['icon_filename']['name'])) {

                    $file = $_FILES['icon_filename'];

                    if($file['error'] === UPLOAD_ERR_OK){
                        //echo "filename:".$file;

                        $iconLabel = $_POST['icon_label']; 
                        $iconFile = $file['name'];

                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'gif','svg'];
                        if (in_array($ext, $allowed)) {
                                $filen = $iconFile;
                                $parentDir = dirname(__DIR__, 2);
                                echo "parent dir: ".$parentDir;
                                $dest = $parentDir.'/assets/icons/categories/' . $filen;

                                if (!is_dir($parentDir.'/assets/icons/categories/')) {
                                    mkdir($parentDir.'/assets/icons/categories/',0777, true);
                                }
                                //echo "Destinatia: " . $dest . "<br>";
                                //echo "Numele fișierului: " . $file . "<br>";


                                move_uploaded_file($file['tmp_name'], $dest);
                                if (!iconExists($filen)){
                                    $stmt = $db->prepare("INSERT INTO categories_icons (filename, label) VALUES (?, ?)");
                                    $stmt->execute([$filen, $iconLabel]);
                                    header('Location: categories.php');
                                    exit;
                                }else{

                                    echo "<script>
                                            alert('⚠️ Icon is already in DB');
                                            window.location.href = 'categories.php';
                                            </script>";
                
                                    exit;
                                }



                        }
                }
            }else{
                echo "<script>
                        alert('⚠️ Icon is not selected');
                        window.location.href = 'categories.php';
                        </script>";
                exit;
            }

        break;
    default:
        // fallback
}






// delete category

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    
    // Log the deletion
    logActivity($_SESSION['user']['id'], 'category_deleted', 'User'.$_SESSION['user']['username']. ' deleted category : ' . $_GET['delete']);
  
    header('Location: categories.php');
    exit;
}



// paginate categories

$perPage = 5;
$totalStmt = $db->query("SELECT COUNT(*) FROM categories");
$totalCategories = $totalStmt->fetchColumn();
$totalPages = ceil($totalCategories / $perPage);

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $perPage;

// fetch all categories
$stmt = $db->prepare("SELECT * FROM categories LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$allCategories = $stmt->fetchAll();


// fetch active categories
$stmt = $db->prepare("SELECT * FROM categories WHERE is_active = '1' LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$activeCategories = $stmt->fetchAll();


// fetch disabled categories
$stmt = $db->prepare("SELECT * FROM categories WHERE is_active = '0' LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$disabledCategories = $stmt->fetchAll();



  // extragem toate icon-urile disponibile
$allIcons = $db->fetchAll("SELECT filename, label FROM categories_icons");

// extragem toate icon-urile deja folosite în categorii
$usedIcons = $db->fetchAll("SELECT icon FROM categories WHERE icon IS NOT NULL");

// convertim într-un array simplu
$usedFilenames = array_column($usedIcons, 'icon');

// filtrăm iconurile disponibile
$availableIcons = array_filter($allIcons, function($icon) use ($usedFilenames) {
    return !in_array($icon['filename'], $usedFilenames);
});

$availableIcons = array_map(function($icon) {
    return [
        'value' => $icon['filename'],
        'label' => $icon['label']
    ];
}, array_filter($allIcons, function($icon) use ($usedFilenames) {
    return !in_array($icon['filename'], $usedFilenames);
}));

/*
$availableIcons = [
  ['value' => 'icon-howto.svg', 'label' => 'HOWTOs'],
  ['value' => 'icon-install.svg', 'label' => 'Install and Config'],
  ['value' => 'icon-user-guide.svg', 'label' => 'User Guides'],
  ['value' => 'icon-design.svg', 'label' => 'Design'],
  ['value' => 'icon-docs.svg', 'label' => 'Documentation'],
  ['value' => 'icon-idea.svg', 'label' => 'Ideas']
];
*/
//echo "icons".$availableIcons['filename'];

/*
$availableCategories = array_map(function($cat) {
    return [
        'name' => $cat['name'],
        'icon' => $cat['icon']
    ];
}, $categories);
*/


//$class = $isFuture ? 'text-primary' : '';

?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="category-container" >

        <div class="category-box-2" style="width: fit-content">
            <div class="operations-bar">
                <div>
                    <button class="btn-flat btn-toggle-form" onclick="toggleAddFormHide('form-category',this)" title="Adaugă categorie">
                    <img src="../../assets/icons/icon-add-category.svg" alt="Add Category" class="op-icon">
                     Add Category&nbsp;&nbsp;      
                    </button>
                </div>
                <div>
                    <button class="btn-flat btn-toggle-form" onclick="toggleAddFormHide('form-icons',this)" title="Adaugă categorie">
                    <img src="../../assets/icons/icon-add-icons.svg" alt="Add Category" class="op-icon">
                    Add Icons &nbsp;&nbsp; 
                    </button>
                    
                </div>
                <div>
                    <button class="btn-flat btn-toggle-form" onclick="toggleAddFormHide('form-enable',this)" title="Adaugă categorie">
                    <img src="../../assets/icons/icon-enable-cat.svg" alt="Add Category" class="op-icon">
                    Enable Category &nbsp;&nbsp; 
                    </button>
                    
                </div>
                <div>
                    <button class="btn-flat btn-toggle-form" onclick="toggleAddFormHide('form-disable',this)" title="Adaugă categorie">
                    <img src="../../assets/icons/icon-disable-cat.svg" alt="Add Category" class="op-icon">
                    Disable Category &nbsp;&nbsp; 
                    </button>
                    
                </div>
            </div>
            <div class="form-container-1">
                
            
                <!-- BEGIN: Formular de adăugare categorie -->

                <div id="form-category" class="form-box" style="display: none; width: 100%;">
                    <form name="new_category" method="POST" class="form-grid">

                            <div class="form-row">
                                <div >
                                    <label for="cat_icon">Icon:</label>
                                </div>
                                <div class="dropdown-wrapper" data-type="icon">
                                    <select name="cat_icon" id="cat_icon_select" class="select2-icon" style="width:100%" placeholder="<?=lang_cat_select?>">
                                    <option></option>
                                    <?php foreach ($availableIcons as $icon): ?>
                                        <option value="<?= $icon['value'] ?>" data-img="/mykdb/assets/icons/categories/<?= $icon['value'] ?>">
                                        <?= htmlspecialchars($icon['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <label for="cat_name">Category Name:</label>
                                <input type="text" name="cat_name" id="cat_name" placeholder="<?=lang_cat_category?>" required>
                            </div>

                            <div class="form-row">
                                <label for="cat_description">Category Description:</label>
                                <input type="text" name="cat_description" id="cat_description" placeholder="<?=lang_cat_description?>">
                            </div>

                            <div class="form-row form-actions">
                                <button type="submit" name="save_category" class="btn-sm btn-outline-grey"><?=lang_btn_save?>
                                <!--
                                <img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>">
                                -->
                                </button>
                            </div>
                            <input type="hidden" name="form_id" value="add_category">                       
                    </form>
                </div>

                <!-- END: formular de adăugare categorie -->



                
                <!-- BEGIN: Formular de adăugare icon -->

                <div id="form-icons" class="form-box" style="display: none; width: 100%;">
                
                            <form name="new_icon" method="POST" enctype="multipart/form-data">
                                <div class="dropdown-wrapper">
                                    <table><tr><td align="right">
                                        Label:
                                    </td><td align="left">
                                        <input type="text" name="icon_label" placeholder="<?=lang_cat_icon_label?>" required>
                                    </td></tr>
                                    <tr><td align="right">
                                        Filename:
                                    </td><td align="left">
                                        <input type="file" name="icon_filename" accept="image/*">
                                    </td></tr>
                                    <tr><td align="right" colspan="2" style="padding: 10px; ">
                                        <button type="submit" name="save_icon" class="btn-sm btn-outline-grey"><?=lang_btn_save?>
                                        <!--
                                        <button type="submit" name="save_icon" class="btn-plus-icon"><img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>">
                                        -->
                                        </button>
                                    </td></tr>
                                    </table>
                                </div>
                                <input type="hidden" name="form_id" value="add_icon">
                            </form>
                </div>
                <!-- END: formular de adăugare icon -->

                

                <!-- BEGIN: Formular de activare categorie -->
                
                <div id="form-enable" class="form-box" style="display: none; width: 100%;">
                            <form name="enable_category" method="POST" enctype="multipart/form-data" class="form-grid">

                                <div class="form-row">
                                    <div>
                                        <label for="category">Disabled Categories:</label>
                                    </div>  
                                    <div class="dropdown-wrapper" data-type="category">
                                        <select name="category_id" id="dcategory_select" class="select2-category" style="width:100%" placeholder="<?=lang_cat_select?>">
                                        <option></option>
                                        <?php foreach ($disabledCategories as $dc): ?>
                                            <option value="<?= $dc['id'] ?>" data-img="/mykdb/assets/icons/categories/<?= $dc['icon'] ?>">
                                            <?= htmlspecialchars($dc['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                
                                <div class="form-row form-actions">
                                    <button type="submit" name="enable_category" class="btn-sm btn-outline-grey"><?=lang_btn_enable?>
                                    <!--
                                    <button type="submit" name="enable_category" class="btn-plus-icon">
                                    <img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>">
                                    -->
                                    </button>
                                </div>
                                <input type="hidden" name="form_id" value="enable_category">
                            </form>
                </div>

                <!-- END: formular de activare categorie -->

              <!-- BEGIN: Formular de dezactivare categorie -->
                
                <div id="form-disable" class="form-box" style="display: none; width: 100%;">
                            <form name="disable_category" method="POST" enctype="multipart/form-data" class="form-grid">

                                <div class="form-row">
                                    <div>
                                        <label for="category">Active Categories:</label>
                                    </div>  
                                    <div class="dropdown-wrapper" data-type="category">
                                        <select name="category_id" id="acategory_select" class="select2-category" style="width:100%" placeholder="<?=lang_cat_select?>">
                                        <option></option>
                                        <?php foreach ($activeCategories as $ac): ?>
                                            <option value="<?= $ac['id'] ?>" data-img="/mykdb/assets/icons/categories/<?= $ac['icon'] ?>">
                                            <?= htmlspecialchars($ac['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                
                                <div class="form-row form-actions">
                                    <button type="submit" name="disable_category" class="btn-sm btn-outline-grey"><?=lang_btn_disable?>
                                    <!--
                                    <button type="submit" name="disable_category" class="btn-plus-icon">
                                    <img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>">
                                    -->
                                    </button>
                                </div>
                                <input type="hidden" name="form_id" value="disable_category">
                            </form>
                </div>

                <!-- END: formular de dezactivare categorie -->

                               


            </div>
        
        </div>



    <div class="category-box-1" style="width: 80%;">
        <table class="articles-table" id="categories-table" >
            <thead>
                <tr>
                    <th width="50px" align="center">
                    <!--
                    <button class="btn-plus-icon" onclick="toggleAddFormHide('add-form-row')" title="Adaugă categorie">
                    <img src="../../assets/icons/icon-add.svg" alt="Add Category" class="op-icon">
                    </button>
                    -->
                    </th>
                    <th><?=lang_cat_icon?></th>
                    <th><?=lang_cat_name?></th>
                    <th><?=lang_cat_description?></th>
                    <th><?=lang_cat_status?></th>
                    <th align="center"><?=lang_cat_actions?></th>
                </tr>
            </thead>
            <tbody>
                
                <!-- Formular de adăugare, ascuns -->
                <tr style="display: none; ">
                
                </tr>

                <!-- Rânduri existente -->
                <?php foreach ($allCategories as $c): ?>
                    <tr>
                        <td align="center"><?= $c['id'] ?></td>
                        <td align="center"><a href="#" name="icon" ><img src="<?=APP_URL?>assets/icons/categories/<?=$c['icon']?>" width="35" height="auto" ></a></td>
                        <td><?= escape($c['name']) ?></td>
                        <td><?= escape($c['description']) ?></td>
                        <td>
                            <?php if ($c['is_active'] == '1'): ?>
                                <span class=""><?=lang_cat_active?></span>
                            <?php else: ?>
                                <span class="text-primary"><?=lang_cat_inactive?></span>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <div>
                                <a href="edit_category.php?id=<?= $c['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?=lang_btn_edit?>"></a>
                                <a href="delete_category.php?id=<?= $c['id'] ?>" onclick="return confirm('<?=lang_cat_msg_delete?>')"><img src="<?=APP_URL?>assets/icons/icon-delete.svg" class="op-icon" title="<?=lang_btn_delete?>"></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

            <tfoot>
            <tr>
                <td colspan="4">
                    <div class="pagination-footer">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="<?= $i === $currentPage ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </td>
            </tr>
            </tfoot>

        </table>
    </div>
</div>

<script>

// Initializare Select2 pentru selectoare

$(document).ready(function () {
  function formatWithIcon(option) {
    if (!option.id) return option.text;

    const img = $(option.element).data('img');
    return $(
      `<span><img src="${img}" class="select2-option-img" width="20" style="margin-right:8px;" />${option.text}</span>`
    );
  }

  // ICON select
  $('#cat_icon_select').select2({
    placeholder: "Alege icon",
    templateResult: formatWithIcon,
    templateSelection: formatWithIcon,
    allowClear: true
  });

  // Disabled CATEGORY select
  $('#dcategory_select').select2({
    placeholder: "Alege categorie",
    templateResult: formatWithIcon,
    templateSelection: formatWithIcon,
    allowClear: true
  });

  // Active CATEGORY select
  $('#acategory_select').select2({
    placeholder: "Alege categorie",
    templateResult: formatWithIcon,
    templateSelection: formatWithIcon,
    allowClear: true
  });
});









</script>




<?php include APP_ROOT . 'includes/footer.php'; ?>

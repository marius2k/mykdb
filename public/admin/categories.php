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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cat_name'])) {
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
}


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


$stmt = $db->prepare("SELECT * FROM categories LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();

// Chedking and saving new icon 

if (isset($_POST['save_icon']) && isset($_FILES['icon_filename']['name'])) {

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
  }

  // 1. Luăm toate icon-urile disponibile
$allIcons = $db->fetchAll("SELECT filename, label FROM categories_icons");

// 2. Luăm toate icon-urile deja folosite în categorii
$usedIcons = $db->fetchAll("SELECT icon FROM categories WHERE icon IS NOT NULL");

// 3. Convertim într-un array simplu
$usedFilenames = array_column($usedIcons, 'icon');

// 4. Filtrăm iconurile disponibile
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


$availableCategories = array_map(function($cat) {
    return [
        'name' => $cat['name'],
        'icon' => $cat['icon']
    ];
}, $categories);


?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="category-container" >

        <div class="category-box-2" style="width: 55%;">
            <div class="operations-bar" >
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
                    <button class="btn-flat btn-toggle-form" onclick="toggleAddFormHide('form-icons',this)" title="Adaugă categorie">
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
                                <div>
                                    <label for="cat_icon">Icons:</label>
                                </div>
                                <div class="dropdown-wrapper" data-type="icon">
                                    <button type="button" class="dropdown-toggle-1" onclick="toggleDropdown(this)">
                                    <span class="label">Alege icon</span>
                                    <span class="preview"></span>
                                    </button>
                                    <ul class="dropdown-menu-1" style="display: none;">
                                    <?php foreach ($availableIcons as $icon): ?>
                                    <li onclick="selectDropdownOption(this, '<?= $icon['value'] ?>')">
                                        <img src="/mykdb/assets/icons/categories/<?= $icon['value'] ?>" width="20" height="20" alt="">
                                        <?= htmlspecialchars($icon['label']) ?>
                                    </li>
                                    <?php endforeach; ?>
                                    </ul>
                                    <input type="hidden" name="cat_icon" value="">
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
                                <button type="submit" name="save_category" class="btn-plus-icon">
                                <img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>">
                                </button>
                            </div>

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
                                        <button type="submit" name="save_icon" class="btn-plus-icon"><img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>"></button>
                                    </td></tr>
                                    </table>
                                </div>
                           
                            </form>
                </div>
                <!-- END: formular de adăugare icon -->

                

                <!-- BEGIN: Formular de activare categorie -->
                
                <div id="form-disable" class="form-box" style="display: none; width: 100%;">
                            <form name="disable_category" method="POST" enctype="multipart/form-data" class="form-grid">

                                <div class="form-row">
                                    <div>
                                        <label for="category">Categories:</label>
                                    </div>  
                                    <div class="dropdown-wrapper" data-type="category">
                                        <button type="button" class="dropdown-toggle-1" onclick="toggleDropdown(this)">
                                        <span class="label">Alege Categorie</span>
                                        <span class="preview"></span>
                                        </button>
                                        <ul class="dropdown-menu-1" style="display:none;">
                                        <?php foreach ($categories as $categ): ?>
                                        <li onclick="selectDropdownOption(this, '<?= $categ['icon'] ?>')">
                                            <img src="/mykdb/assets/icons/categories/<?= $categ['icon'] ?>" width="20" height="20" alt="">
                                            <?= htmlspecialchars($categ['name']) ?>
                                        </li>
                                        <?php endforeach; ?>
                                        </ul>
                                        <input type="hidden" name="category" value="">
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
                                    <button type="submit" name="save_category" class="btn-plus-icon">
                                    <img src="<?=APP_URL?>assets/icons/icon-save.svg" class="op-icon" title="<?=lang_btn_save?>">
                                    </button>
                                </div>

                            </form>
                </div>

                <!-- END: formular de activare categorie -->




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
                    <th align="center"><?=lang_cat_actions?></th>
                </tr>
            </thead>
            <tbody>
                
                <!-- Formular de adăugare, ascuns -->
                <tr style="display: none; ">
                
                </tr>

                <!-- Rânduri existente -->
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td align="center"><?= $c['id'] ?></td>
                        <td align="center"><a href="#" name="icon" ><img src="<?=APP_URL?>assets/icons/categories/<?=$c['icon']?>" width="35" height="auto" ></a></td>
                        <td><?= escape($c['name']) ?></td>
                        <td><?= escape($c['description']) ?></td>
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


document.addEventListener('click', function (e) {
  document.querySelectorAll('.dropdown-wrapper').forEach(wrapper => {
    if (!wrapper.contains(e.target)) {
      const menu = wrapper.querySelector('.dropdown-menu-1');
      if (menu) menu.style.display = 'none';
    }
  });
});




function toggleDropdown(button) {
  const wrapper = button.closest('.dropdown-wrapper');
  const menu = wrapper.querySelector('.dropdown-menu-1');
  const isOpen = menu && menu.style.display === 'block';

  // Închide toate meniurile (dar nu pe cel curent)
  document.querySelectorAll('.dropdown-menu-1').forEach(m => {
    const mWrapper = m.closest('.dropdown-wrapper');
    if (mWrapper !== wrapper) {
      m.style.display = 'none';
    }
  });

  // Toggle pe meniul curent
  if (menu) {
    menu.style.display = isOpen ? 'none' : 'block';
  }
}

function selectDropdownOption(liEl, value) {
  const wrapper = liEl.closest('.dropdown-wrapper');
  const label = wrapper.querySelector('.label');
  const preview = wrapper.querySelector('.preview');
  const input = wrapper.querySelector('input[type="hidden"]');
  const menu = wrapper.querySelector('.dropdown-menu-1');
  const type = wrapper.dataset.type || 'icon';

  if (input) input.value = value;
  if (menu) menu.style.display = 'none';
  if (label) label.textContent = value;


  //label.textContent = value;
  //preview.innerHTML = `<img src="/mykdb/assets/icons/categories/${value}" width="20" height="20">`;

  
  if (preview) {
    if (type === 'icon') {
      preview.innerHTML = `<img src="/mykdb/assets/icons/categories/${value}" width="20" height="20">`;
    } else if (type === 'category') {
      preview.innerHTML = value;
    } else {
      preview.innerHTML = value;
    }
  }
  /*
    if (preview) {
    if (type === 'icon') {
      preview.innerHTML = `<img src="/mykdb/assets/icons/categories/${value}" width="20" height="20">`;
    } else if (type === 'category') {
      preview.innerHTML = `${value}<img src="/mykdb/assets/icons/categories/${value}" width="20" height="20">`;
    } else {
      preview.innerHTML = value;
    }
  }
    */
}









function toggleIconDropdown(button) {
  const wrapper = button.closest('.icon-dropdown-wrapper');
  const menu = wrapper.querySelector('.dropdown-menu-1');

  const isOpen = menu.style.display === 'block';
  document.querySelectorAll('.dropdown-menu-1').forEach(m => m.style.display = 'none');
  menu.style.display = isOpen ? 'none' : 'block';
}




function selectIconDropdown(liEl, value) {
  const wrapper = liEl.closest('.icon-dropdown-wrapper');
  const label = wrapper.querySelector('.label');
  const preview = wrapper.querySelector('.preview');
  const input = wrapper.querySelector('input[type="hidden"]'); 
  const menu = wrapper.querySelector('.dropdown-menu-1');

  if (input) input.value = value;
  menu.style.display = 'none';

  label.textContent = value;
  preview.innerHTML = `<img src="/mykdb/assets/icons/categories/${value}" width="20" height="20">`;
}

function toggleCatDropdown(button) {
  const wrapper = button.closest('.cat-dropdown-wrapper');
  const menu = wrapper.querySelector('.dropdown-menu-1');

  const isOpen = menu.style.display === 'block';
  document.querySelectorAll('.dropdown-menu-1').forEach(m => m.style.display = 'none');
  menu.style.display = isOpen ? 'none' : 'block';
}

function selectCatDropdown(liEl, value) {
  const wrapper = liEl.closest('.cat-dropdown-wrapper');
  const label = wrapper.querySelector('.label');
  const preview = wrapper.querySelector('.preview');
  const input = wrapper.querySelector('input[type="hidden"]'); 
  const menu = wrapper.querySelector('.dropdown-menu-1');

  if (input) input.value = value;
  menu.style.display = 'none';

  label.textContent = value;
  preview.innerHTML = `<img src="/mykdb/assets/icons/categories/${value}" width="20" height="20">`;
}




</script>




<?php include APP_ROOT . 'includes/footer.php'; ?>

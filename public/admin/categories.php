<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "publc/login.php");
    exit;
}

// mapping local language codes for DataTables

$lang = $_SESSION['settings']['language'] ?? 'en';
if ($lang === 'ro') $lang = 'ro';
if ($lang === 'en') $lang = 'en-GB';

?>

<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>
<link rel="stylesheet" href="//cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="//cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>


<div class="category-container">
    <div class="category-box-2" style="width: fit-content">
        <div class="operations-bar">
            <button class="btn-flat" onclick="openCategoryModal('add')" title="Add Category">
                <img src="../../assets/icons/icon-add-category.svg" class="op-icon">
                Add Category
            </button>
            <button class="btn-flat" onclick="openCategoryModal('icon')" title="Add Icon">
                <img src="../../assets/icons/icon-add-icons.svg" class="op-icon">
                Add Icon
            </button>
            <button class="btn-flat" onclick="openCategoryModal('enable')" title="Enable Category">
                <img src="../../assets/icons/icon-enable-cat.svg" class="op-icon">
                Enable Category
            </button>
            <button class="btn-flat" onclick="openCategoryModal('disable')" title="Disable Category">
                <img src="../../assets/icons/icon-disable-cat.svg" class="op-icon">
                Disable Category
            </button>
        </div>
    </div>

    <div class="category-box-1" style="width: 80%;">
        <table id="categoriesTable" class="articles-table" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= lang('lang_cat_icon') ?></th>
                    <th><?= lang('lang_cat_name') ?></th>
                    <th><?= lang('lang_cat_description') ?></th>
                    <th><?= lang('lang_cat_status') ?></th>
                    <th><?= lang('lang_cat_actions') ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


<!-- Overlay pentru fundal -->
<div id="modalOverlayCategory" style="display:none;"></div>


<!-- Modal Add Category -->
<div id="modalAddCategory" style="display:none; ">
    <form id="formAddCategory">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-header-category" >
            <span class="modal-title-category"><?=lang('lang_cat_add')?></span>
            <span class="modal-close-category" onclick="closeCategoryModal('add')">&times;</span>
        </div>
        <div class="modal-content-category" >
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <label><?= lang('lang_cat_name') ?></label>
                <input type="text" name="cat_name" required>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between;">    
                <label><?= lang('lang_cat_description') ?></label>
                <input type="text" name="cat_description">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between;gap: 10px;">
                <label><?= lang('lang_cat_icon') ?></label>
                <select name="cat_icon" id="cat_icon_select" style="width:100%"></select>
            </div>    
        </div>
        <div class="modal-footer-category">
            <button class="modal-btn-category cancel" type="button" onclick="closeCategoryModal('add')"><?= lang('lang_btn_cancel') ?></button>
            <button class="modal-btn-category primary" type="submit"><?= lang('lang_btn_save') ?></button>
        </div>
    </form>
</div>




<!-- Modal Add Icon -->
<div id="modalAddIcon" style="display:none;">
    <form id="formAddIcon" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-header-category ">
            <span class="modal-title-category"><?= lang('lang_cat_add_icon') ?></span>
            <span class="modal-close-category" onclick="closeCategoryModal('icon')">&times;</span>
        </div>
        <div class="modal-content-category" ">
            <div style="display: flex; align-items: center; ">
                <label><?= lang('lang_cat_icon_label') ?></label>
                <input type="text" name="icon_label" style="width: 160px;" required>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <label><?= lang('lang_cat_select_icon') ?></label>  
                <input type="file" name="icon_filename" accept="image/*" required>
            </div>
            
        </div>
        <div class="modal-footer-category">
            <button class="modal-btn-category cancel" type="button" onclick="closeCategoryModal('icon')"><?= lang('lang_btn_cancel') ?></button>
            <button class="modal-btn-category primary" type="submit"><?= lang('lang_btn_save') ?></button>
        </div>
    </form>
</div>

<!-- Modal Enable Category -->
<div id="modalEnableCategory" style="display:none;">
    <form id="formEnableCategory" enctype="multipart/form-data">
         <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-header-category">
            <span class="modal-title-category"><?= lang('lang_cat_enable') ?></span>
            <span class="modal-close-category" onclick="closeCategoryModal('enable')">&times;</span>
        </div>
        <div class="modal-content-category">
                <div style="width: 100%; display: flex; align-items: center; justify-content: space-between;">
                   
                    <label><?= lang('lang_cat_select') ?></label>
                    <select name="category_id" id="enable_category_select" style="width:100%"></select>
                </div>
        </div>
        <div class="modal-footer-category">
                <button class="modal-btn-category cancel" type="button" onclick="closeCategoryModal('enable')"><?= lang('lang_btn_cancel') ?></button>
                <button class="modal-btn-category primary" type="submit"><?= lang('lang_btn_enable') ?></button>
        </div>
         </div>
    </form>
</div>


<!-- Modal Disable Category -->
<div id="modalDisableCategory" style="display:none;">
    <form id="formDisableCategory" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-header-category">
            <span class="modal-title-category"><?= lang('lang_cat_disable') ?></span>
            <span class="modal-close-category" onclick="closeCategoryModal('disable')">&times;</span>
        </div>
        <div class="modal-content-category">
            <div style="width: 100%; display: flex; align-items: center; justify-content: space-between;">
                <label><?= lang('lang_cat_select') ?></label>
                <select name="category_id" id="disable_category_select" style="width:100%"></select>
            </div>
        </div>
        <div class="modal-footer-category">
                <button class="modal-btn-category cancel" type="button" onclick="closeCategoryModal('disable')"><?= lang('lang_btn_cancel') ?></button>
                <button class="modal-btn-category primary" type="submit"><?= lang('lang_btn_disable') ?></button>
        </div>
    </form>
</div>

<script>
$(document).ready(function () {
    // DataTables pentru categorii
    const table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../api/bkd_categories.php',
            type: 'GET'
        },
        columns: [
            { data: 'id', orderable: false, searchable: false },
            { data: 'icon', orderable: false, render: function(data) {
                return data ? `<img src="<?=APP_URL?>assets/icons/categories/${data}" width="35">` : '';
            }},
            { data: 'name', orderable: true, searchable: true },
            { data: 'description', orderable: false, searchable: true },
            { data: 'is_active', orderable: true, render: function(data) {
                return data == 1 ? '<span><?=lang('lang_cat_active')?></span>' : '<span class="text-primary"><?=lang('lang_cat_inactive')?></span>';
            }},
            { data: null, orderable: false, render: function(data, type, row) {
                return `
                    <a href="edit_category.php?id=${row.id}"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_btn_edit') ?>"></a>
                    <a href="#" onclick="deleteCategory(${row.id});return false;"><img src="<?=APP_URL?>assets/icons/icon-delete.svg" class="op-icon" title="<?= lang('lang_btn_delete') ?>"></a>
                `;
            }}
        ],
        order: [[0, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/<?=lang($lang)?>.json"
        }
    });
    window.reloadCategoriesTable = () => table.ajax.reload(null, false);


    function formatWithIcon(option) {
        if (!option.id) return option.text;
        const img = $(option.element).data('img');
        if (!img) return option.text;
        return $(
            `<span><img src="${img}" class="select2-option-img" width="20" style="margin-right:8px;" />${option.text}</span>`
        );
    }   
    // Populate selects for enable/disable/icon modals (AJAX)
    function loadCategorySelects() {
    $.get('../api/bkd_categories.php?action=selects', function(data) {
        $('#cat_icon_select').html(data.icons).select2({
            dropdownParent: $('#modalAddCategory'),
            placeholder: '<?=lang(('lang_icon_select'))?>',
            templateResult: formatWithIcon,
            templateSelection: formatWithIcon,
            allowClear: true
        });
        $('#enable_category_select').html(data.disabled).select2({
            dropdownParent: $('#modalEnableCategory'),
            placeholder: '<?=lang('lang_cat_select_category')?>',
            templateResult: formatWithIcon,
            templateSelection: formatWithIcon,
            allowClear: true
        });
        $('#disable_category_select').html(data.enabled).select2({
            dropdownParent: $('#modalDisableCategory'),
            placeholder: '<?=lang('lang_cat_select_category')?>',
            templateResult: formatWithIcon,
            templateSelection: formatWithIcon,
            allowClear: true
        });
    }, 'json');
}

    // Modal logic
    window.openCategoryModal = function(type) {
        
        // reset forms
        if (type === 'add') {
            $('#formAddCategory')[0].reset(); // <-- Resetează formularul
            // Dacă folosești Select2, resetează și selectul:
            $('#cat_icon_select').val(null).trigger('change');
        }   
        if (type === 'icon') {
            $('#formAddIcon')[0].reset();
        }
        if (type === 'enable') {
            $('#formEnableCategory')[0].reset();
            $('#enable_category_select').val(null).trigger('change');
        }
        if (type === 'disable') {
            $('#formDisableCategory')[0].reset();
            $('#disable_category_select').val(null).trigger('change');
        }

        loadCategorySelects();
        
        $('#modalAddCategory, #modalAddIcon, #modalEnableCategory, #modalDisableCategory').hide();
        $('#modalOverlayCategory').show();
        if (type === 'add') $('#modalAddCategory').show();
        if (type === 'icon') $('#modalAddIcon').show();
        if (type === 'enable') $('#modalEnableCategory').show();
        if (type === 'disable') $('#modalDisableCategory').show();
    }
    window.closeCategoryModal = function(type) {
        if (type === 'add') $('#modalAddCategory').hide();
        if (type === 'icon') $('#modalAddIcon').hide();
        if (type === 'enable') $('#modalEnableCategory').hide();
        if (type === 'disable') $('#modalDisableCategory').hide();
        $('#modalOverlayCategory').hide();
    }

    // Add Category
    $('#formAddCategory').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/bkd_categories.php', $(this).serialize() + '&action=add_category&csrf_token=' + window.CSRF_TOKEN, function(resp) {
            if (resp.success) {
                closeCategoryModal('add');
                reloadCategoriesTable();
            } else {
                alert(resp.error || 'Eroare!');
            }
        }, 'json');
    });

    // Add Icon
    $('#formAddIcon').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'add_icon');
        formData.append('csrf_token', window.CSRF_TOKEN);
        $.ajax({
            url: '../api/bkd_categories.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    closeCategoryModal('icon');
                    reloadCategoriesTable();
                } else {
                    alert(resp.error || 'Eroare!');
                }
            }
        });
    });

    // Enable Category
    $('#formEnableCategory').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/bkd_categories.php', $(this).serialize() + '&action=enable_category&csrf_token=' + window.CSRF_TOKEN, function(resp) {
            if (resp.success) {
                closeCategoryModal('enable');
                reloadCategoriesTable();
            } else {
                alert(resp.error || 'Eroare!');
            }
        }, 'json');
    });

    // Disable Category
    $('#formDisableCategory').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/bkd_categories.php', $(this).serialize() + '&action=disable_category&csrf_token=' + window.CSRF_TOKEN, function(resp) {
            if (resp.success) {
                closeCategoryModal('disable');
                reloadCategoriesTable();
            } else {
                alert(resp.error || 'Eroare!');
            }
        }, 'json');
    });
});

// Delete Category
function deleteCategory(id) {
    if (!confirm('<?=lang('lang_cat_msg_delete')?>')) return;
    $.post('../api/bkd_categories.php', {action: 'delete_category', category_id: id, csrf_token: window.CSRF_TOKEN}, function(resp) {
        if (resp.success) reloadCategoriesTable();
        else alert(resp.error || 'Eroare!');
    }, 'json');
}


document.getElementById('modalOverlayCategory').onclick = function(e) {

    if ($('#modalAddCategory').is(':visible')) {
        closeCategoryModal('add');
    }
    if ($('#modalAddIcon').is(':visible')) {
        closeCategoryModal('icon');
    }
    if ($('#modalEnableCategory').is(':visible')) {
        closeCategoryModal('enable');
    }
    if ($('#modalDisableCategory').is(':visible')) {
        closeCategoryModal('disable');
    }
    
};


</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
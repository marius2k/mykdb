<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Debug: Să vedem rolul utilizatorului
//echo "<script>console.log('PHP Session role: " . ($_SESSION['user']['role'] ?? 'NOT SET') . "');</script>";



$ops = ['edit_article','disable_article','enable_article','create_article','edit_own_article'];

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
    header("Location:".APP_URL. "public/login.php");
    exit;
}


$lang = $_SESSION['settings']['language'] ?? 'en';
// Mapare rapidă dacă ai coduri locale
if ($lang === 'ro') $lang = 'ro';
if ($lang === 'en') $lang = 'en-GB';


?>

<script>
window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
window.USER_ROLE = "<?= $_SESSION['user']['role'] ?>";
</script>

<style>
/* Styling pentru icon-urile de acțiuni */
.op-icon {
    width: 20px;
    height: 20px;
    transition: opacity 0.3s ease;
}

.op-icon.disabled {
    filter: grayscale(100%) brightness(0.7);
    opacity: 0.4;
    cursor: not-allowed;
}

/* Previne click-ul pe icon-urile disabled */
a:has(.op-icon.disabled) {
    pointer-events: none;
}

#articlesTable {
    font-size: 0.9em; /* Poți ajusta: 0.8em, 0.9em, etc. */
}

#articlesTable thead th {
    font-size: 0.9em; /* Font mai mic pentru header */
    font-weight: 600;
}

#articlesTable tbody td {
    font-size: 0.9em; /* Font mai mic pentru conținut */
    padding: 8px 10px; /* Reduce și padding-ul dacă vrei */
    text-align:left;
}

/* Pentru dropdown-urile de versiuni */
#articlesTable .version-select {
    font-size: 0.9em;
}
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<div class="breadcrumb-container" style="width: 100%; margin-top: 20px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <br>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_articles') ?>
            </li>
        </ol>
    </nav>
</div>
<hr style="height: 1px; border: none; background-color: gray; margin: 0; width: calc(100vw - 20px); margin-left: calc(-50vw + 50% + 10px);">
<br>

<div class="category-container">
    <div class="category-box-1" style="width: 100%;">
        <table id="articlesTable" class="articles-table" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= lang('lang_art_title') ?></th>
                    <th style="text-align:center;"><?= lang('lang_art_version') ?></th>
                    <th style="text-align:center;"><?= lang('lang_art_author') ?></th>
                    <th style="text-align:center;"><?= lang('lang_art_category') ?></th>
                    <th style="text-align:center;"><?= lang('lang_art_status') ?></th>
                    <th style="text-align:center;"><?= lang('lang_art_publish_at') ?></th>
                    <th style="text-align:center;"><?= lang('lang_art_updated_at') ?></th>
                    <th style="width:280px; text-align:center;"><?= lang('lang_art_actions') ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal Overlay pentru editare articol -->
<div id="modalOverlayAddArticle" style="display:none;"></div>

<!-- Modal Create Article -->
<div id="modal-add-article" class="modal" style="display:none;">
    <div class="modal-header-edit" style="border-bottom: 1px solid #d1d1d1ff;padding-bottom: 12px;">
        <span class="modal-title-edit" style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;font-size:2em; line-height: 0.8;" id="modal-title"><?=lang('lang_art_edit')?></span>
        <span class="modal-close-edit" onclick="closeArticleModal()">&times;</span>
    </div>
    <br>
    <div class="modal-content-edit" style="padding-top: 0px;">
        <form id="add_article" class="article-form" autocomplete="off">
                
                <!-- Title Section - Full Width -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="title" class="form-label"><?= lang('lang_art_title') ?></label>
                        <input type="text" id="title" name="title" class="form-input" required>
                    </div>
                </div>
                
                <!-- Compact Row: Version, Category, Publication Date -->
                <div class="form-section">
                    <div class="form-row-compact">
                        <div class="form-group-compact">
                            <label for="version" class="form-label"><?= lang('lang_art_version') ?></label>
                            <input type="text" id="version" name="version" class="form-input" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        </div>
                        <div class="form-group-compact">
                            <label for="add_category_select" class="form-label"><?= lang('lang_article_category') ?></label>
                            <select name="category_id" id="add_category_select" class="form-select select2-icon" required>
                                <option value="">--<?= lang('lang_cat_select') ?> --</option>
                            </select>
                        </div>
                        
                        <div class="form-group-compact">
                            <label for="publish_at" class="form-label"><?= lang('lang_art_publish_at') ?></label>
                            <input type="datetime-local" name="publish_at" id="publish_at" class="form-input">
                        </div>
                        
                    </div>
                </div>
                
                <!-- Article Content Section -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="summernote" class="form-label"><?= lang('lang_create_article_content') ?></label>
                        <textarea id="summernote" name="content" class="form-input"></textarea>
                    </div>
                </div>

                <!-- Tags Section -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="tags" class="form-label"><?= lang('lang_create_article_tags') ?></label>
                        <input type="text" id="tags" name="tags" class="form-input">
                    </div>
                </div>
                    
                <!-- Change Note Section -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="change_note" class="form-label">Change Note</label>
                        <textarea id="change_note" name="change_note" rows="3" class="form-input" placeholder="Descrie modificările făcute în această versiune..."></textarea>
                        <small class="form-helper">Opțional - ajută la urmărirea modificărilor</small>
                    </div>
                </div>

        </form>
        <div id="article-feedback" class="mt-2 text-success d-none"></div>
    </div>
    <div class="modal-footer-edit">
        <button type="button" onclick="submitArticle2('draft')" class="modal-btn-edit"><?= lang('lang_create_article_draft') ?></button>
        <button type="button" onclick="submitArticle2('submit')" class="modal-btn-edit"><?= lang('lang_create_article_approve') ?></button>
        <button type="button" onclick="closeArticleModal()" class="modal-btn-edit"><?= lang('lang_btn_cancel') ?></button>
    </div>
</div>

<!-- Modal pentru istoricul versiunilor -->
<!-- Modal Overlay -->
<div id="modalOverlayVersionHistory" style="display:none;"></div>

<!-- Modal Version History -->
<div id="modal-version-history" style="display:none;">
    <div class="modal-header-history" style="font-weight: 400; text-transform: uppercase; letter-spacing: 0.3px;font-size:1.3em; line-height:3;" id="modal-title">
        <span class="modal-title-history"><?=lang('lang_art_history')?></span>
        <span class="modal-close-history" onclick="closeVersionHistoryModal()">&times;</span>
    </div>
    <div class="modal-content-history">
        <div id="version-history-content">
            <!-- Conținutul va fi populat dinamic -->
        </div>
    </div>
    <div class="modal-footer-history">
        <button class="modal-btn-history" onclick="closeVersionHistoryModal()"><?= lang('lang_btn_close') ?></button>
    </div>
</div>


<script>

$(document).ready(function() {
    const table = $('#articlesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../api/bkd_articles.php',
            type: 'GET'
        },
        columns: [
            { data: 'rownum', orderable: false },
            { data: 'title', render: function(data, type, row) { return data || ''; } },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let html = `<select class='version-select' data-article-id='${row.article_id || 0}'>`;
                    (row.versions || []).forEach(v => {
                        const versionNumber = typeof v === 'object' ? v.version_number : v;
                        const isSelected = versionNumber == (row.current_version || 1) ? 'selected' : '';
                        html += `<option value='${versionNumber}' ${isSelected}>v${versionNumber}</option>`;
                    });
                    html += `</select>`;
                    return html;
                }
            },
            { data: 'username', render: function(data, type, row) { return data || ''; } },
            { data: 'category', render: function(data, type, row) { return data || ''; } },
            { data: 'status', render: function(data, type, row) { return `<span class='version-status'>${data || ''}</span>`; } },
            { 
                data: 'publish_at',
                render: function(data, type, row) {
                    return generatePublishAtForArticle(row);
                }
            },
            {
                data: 'updated_at',
                render: function(data, type, row) {
                    return row.updated_at || '';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return generateActionsForArticle(row);
                }
            }
            
        ],
        order: [[0, 'asc']],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
    window.reloadArticlesTable = () => table.ajax.reload(null, false);

    // Event pentru schimbare versiune
    $('#articlesTable tbody').on('change', '.version-select', function() {
        const selectElement = $(this);
        const articleId = selectElement.data('article-id');
        const version = selectElement.val();
        const tr = selectElement.closest('tr');
        
        const url = `../api/bkd_articles.php?action=get_version&id=${articleId}&version=${version}`;
        
        fetch(url)
            .then(res => res.json())
            .then(function(data) {
                if (!data.success) {
                    console.error('Failed to get version data:', data);
                    return;
                }
                
                // Actualizează coloana Status (coloana 6)
                tr.find('td:nth-child(6) .version-status').text(data.status || '');
                
                // Actualizează titlul (coloana 2)
                tr.find('td:nth-child(2)').text(data.title || '');
                
                // Actualizează coloana Publication Date (coloana 7) cu logica role-based
                const publishAtHtml = generatePublishAtForArticleWithVersionData(articleId, data);
                tr.find('td:nth-child(7)').html(publishAtHtml);
                
                // Actualizează updated_at dacă există (coloana 8)
                if (data.updated_at) {
                    tr.find('td:nth-child(8)').html(`<span>${data.updated_at}</span>`);
                }
                
                // Actualizează acțiunile din coloana 9 (Actions)
                const actionsHtml = generateActionsForArticleWithVersionData(articleId, data, tr.closest('table').DataTable().row(tr).data());
                tr.find('td:nth-child(9)').html(actionsHtml);
            })
            .catch(error => {
                console.error('Error fetching version:', error);
            });
            
    });

    // Check URL parameters for modal auto-opening
    const urlParams = new URLSearchParams(window.location.search);
    const modalParam = urlParams.get('modal');
    
    if (modalParam === 'create') {
        setTimeout(() => {
            openArticleModal();
        }, 500);
    }
});

// Funcție globală pentru a obține versiunea selectată pentru un articol din dropdown
function getSelectedVersion(articleId) {
    const dropdown = document.querySelector(`.version-select[data-article-id="${articleId}"]`);
    return dropdown ? dropdown.value : 1;
}

// Funcție pentru generarea acțiunilor pentru un articol (folosită în DataTables render)
function generateActionsForArticle(row) {
    // La prima încărcare, presupune că versiunea selectată este cea online (current_version)
    // Pentru că dropdown-ul încă nu există în DOM la momentul render-ului
    let selectedVersion;
    try {
        const dropdownVersion = getSelectedVersion(row.article_id || 0);
        // Dacă dropdown-ul există și are o valoare, folosește-o
        // Altfel, folosește versiunea online ca default
        selectedVersion = dropdownVersion || row.current_version || 1;
    } catch (e) {
        // Fallback la versiunea online
        selectedVersion = row.current_version || 1;
    }
    
    /*
    console.log('🎯 generateActionsForArticle initial render:', {
        articleId: row.article_id,
        selectedVersion: selectedVersion,
        currentVersion: row.current_version,
        dropdownExists: !!document.querySelector(`.version-select[data-article-id="${row.article_id}"]`)
    });
    */

    // Pentru prima încărcare, când dropdown-ul nu există încă, 
    // presupune că se afișează versiunea online
    if (!document.querySelector(`.version-select[data-article-id="${row.article_id}"]`)) {
        //console.log('📋 First render - using online version data');
        return buildActionsHtml(
            row.article_id || 0, 
            row.status, 
            row.publish_at || '', 
            row.current_version, 
            row.user_id || 0, 
            row.current_version || null
        );
    }
    
    // Pentru versiunea curentă/online, folosește statusul din rând
    if (selectedVersion == row.current_version) {
        //console.log('🎯 Using current version status:', row.status);
        return buildActionsHtml(row.article_id || 0, row.status, row.publish_at || '', selectedVersion, row.user_id || 0, row.current_version || null);
    }
    
    // Pentru alte versiuni, încearcă să obții datele din array-ul versions
    const selectedVersionData = getVersionDataFromRow(row, selectedVersion);
    const versionStatus = selectedVersionData ? selectedVersionData.status : row.status;
    
    /*
    console.log('generateActionsForArticle with existing dropdown:', {
        articleId: row.article_id,
        selectedVersion: selectedVersion,
        currentVersion: row.current_version,
        versionStatus: versionStatus,
        rowStatus: row.status,
        selectedVersionData: selectedVersionData
    });
    */

    return buildActionsHtml(row.article_id || 0, versionStatus, row.publish_at || '', selectedVersion, row.user_id || 0, row.current_version || null);
}

// Funcție pentru generarea Publication Date pentru un articol (folosită în DataTables render)
function generatePublishAtForArticle(row) {
    const selectedVersion = getSelectedVersion(row.article_id || 0);
    const selectedVersionData = getVersionDataFromRow(row, selectedVersion);
    const versionStatus = selectedVersionData ? selectedVersionData.status : row.status;
    
    return buildPublishAtHtml(row.article_id || 0, versionStatus, row.publish_at || '');
}

// Funcție pentru generarea acțiunilor cu date de versiune specifice (folosită în event handler)
function generateActionsForArticleWithVersionData(articleId, versionData, rowData) {
    return buildActionsHtml(articleId, versionData.status, rowData.publish_at || '', versionData.version_number, rowData.user_id || 0, rowData.current_version || null);
}

// Funcție pentru generarea Publication Date cu date de versiune specifice (folosită în event handler)  
function generatePublishAtForArticleWithVersionData(articleId, versionData) {
    return buildPublishAtHtml(articleId, versionData.status, versionData.publish_at || '');
}

// Funcție pentru construirea HTML-ului acțiunilor
function buildActionsHtml(articleId, status, publishAt, version = null, authorId = null, onlineVersion = null) {
    const currentUserId = <?= $_SESSION['user']['id'] ?? 0 ?>;
    const userRole = window.USER_ROLE || '';
    
    // Validează statusul - dacă este undefined sau null, folosește 'draft' ca fallback
    const normalizedStatus = status || 'draft';
    
    // Definește acțiunile disponibile
    const actions = [
        {
            name: 'view',
            icon: 'icon-view.svg',
            title: '<?=lang('lang_art_view')?>',
            onclick: `viewArticleWithVersion(${articleId}, ${onlineVersion || 'null'});return false;`
        },
        {
            name: 'edit', 
            icon: 'icon-edit.svg',
            title: '<?=lang('lang_art_edit')?>',
            onclick: `openEditArticleModal(${articleId}, getSelectedVersion(${articleId}));return false;`
        },
        {
            name: 'history',
            icon: 'icon-history.svg',
            title: '<?=lang('lang_art_history')?>',
            onclick: `showVersionHistory(${articleId});return false;`
        },
        {
            name: 'approve',
            icon: 'icon-approve.svg',
            title: '<?=lang('lang_art_approve')?>',
            onclick: version
                ? `articleAction('approve', ${articleId}, '${publishAt}', ${version});return false;`
                : `articleAction('approve', ${articleId}, '${publishAt}', getSelectedVersion(${articleId}));return false;`
        },
        {
            name: 'publish',
            icon: 'icon-publish.svg',
            title: '<?=lang('lang_art_publish')?>',
            onclick: version
                ? `articleAction('publish', ${articleId}, '', ${version});return false;`
                : `articleAction('publish', ${articleId}, '', getSelectedVersion(${articleId}));return false;`
        },
        {
            name: 'disable',
            icon: 'icon-disable.svg',
            title: '<?=lang('lang_art_disable')?>',
            onclick: version
                ? `articleAction('disable', ${articleId}, '', ${version});return false;`
                : `articleAction('disable', ${articleId}, '', getSelectedVersion(${articleId}));return false;`
        },
        {
            name: 'restore',
            icon: 'icon-restore.svg',
            title: '<?=lang('lang_art_restore')?>',
            onclick: version
                ? `articleAction('restore', ${articleId}, '', ${version});return false;`
                : `articleAction('restore', ${articleId}, '', getSelectedVersion(${articleId}));return false;`
        },
        {
            name: 'delete',
            icon: 'icon-delete.svg',
            title: '<?=lang('lang_art_delete')?>',
            onclick: `articleAction('delete', ${articleId});return false;`
        }
    ];
    
    let html = '';
    
    actions.forEach(action => {
        const isEnabled = isActionEnabled(action.name, userRole, normalizedStatus, authorId, currentUserId, onlineVersion, version);
        
        // Pentru disable/restore, afișează doar una din ele în funcție de status
        if (action.name === 'disable' && normalizedStatus === 'disabled') {
            return; // Nu afișa disable pentru articole disabled
        }
        if (action.name === 'restore' && normalizedStatus !== 'disabled') {
            return; // Nu afișa restore pentru articole care nu sunt disabled
        }
        
        const cssClass = isEnabled ? 'op-icon' : 'op-icon disabled';
        const clickHandler = isEnabled ? `onclick="${action.onclick}"` : '';
        
        html += `<a href="#" ${clickHandler}><img src="<?=APP_URL?>assets/icons/${action.icon}" class="${cssClass}" title="${action.title}" style="width: 32px;"></a> `;
    });
    
    return html;
}

function isActionEnabled(actionName, userRole, articleStatus, authorId, currentUserId, onlineVersion = null, selectedVersion = null) {
    // Convertește ID-urile la numere pentru comparație
    const isOwner = parseInt(authorId) === parseInt(currentUserId);
    
    // Normalizează statusurile pentru comparație - elimină spațiile și convertește la lowercase
    const status = (articleStatus || '').toString().trim().toLowerCase();
    
    // Determină dacă versiunea selectată este online
    const isSelectedVersionOnline = selectedVersion && onlineVersion && 
                                   parseInt(selectedVersion) === parseInt(onlineVersion);
   
    // Pentru acțiunea "edit", verifică dacă versiunea online poate fi editată
    if (actionName === 'edit' && isSelectedVersionOnline) {
        // Versiunea online se poate edita doar dacă este disabled
        if (status !== 'disabled') {
            return false;
        }
    }
    
    // Pentru acțiunea "publish", verifică dacă versiunea selectată este online
    if (actionName === 'publish') {
        // Nu se poate publica o versiune care este deja online
        if (isSelectedVersionOnline) {
            return false;
        }
    }
    
    // Pentru acțiunea "disable", verifică dacă versiunea selectată este online
    if (actionName === 'disable') {
        // Doar versiunile online pot fi dezactivate
        if (!isSelectedVersionOnline) {
            return false;
        }
        
        // Disable se face doar pentru status approved (nu disabled)
        if (status !== 'approved') {
            return false;
        }
    }

    // Pentru acțiunea "restore", verifică dacă versiunea este online și disabled
    if (actionName === 'restore') {
        // Restore se face doar pentru versiuni online care sunt disabled
        if (!isSelectedVersionOnline || status !== 'disabled') {
            return false;
        }
    }
    /*
    console.log('isActionEnabled debug:', {
        "Action Name" : actionName,
        "User Role: ": userRole,
        "Status: ": status,
        "Author ID: ": authorId,
        "Current User ID: ": currentUserId,
        "Is Owner: ": isOwner,
        "Online Version: ": onlineVersion,
        "Selected Version: ": selectedVersion,
        "Is Selected Version Online: ": isSelectedVersionOnline
    });
    */

    switch (userRole) {
        case 'contributor':
            switch (actionName) {
                    case 'view':
                    // Contributor poate vedea TOATE articolele proprii (orice status)
                    return isOwner;
                case 'edit':
                    // Draft (propriu) și NU online
                    return status === 'draft' && isOwner
                case 'history':
                    // Toate articolele proprii
                    return isOwner;
                case 'approve':
                case 'publish':
                case 'disable':
                case 'restore':
                    // Contributor nu poate face aceste acțiuni
                    return false;
                case 'delete':
                    // Draft (propriu) și NU online
                    return status === 'draft' && isOwner && !isSelectedVersionOnline;
                default:
                    return false;
            }
            
        case 'editor':
            switch (actionName) {
                case 'view':
                    // Editor: toate articolele în orice status
                    return true;
                case 'edit':
                    // Editor: draft, pending, approved + versiuni online disabled
                    if (isSelectedVersionOnline) {
                        return status === 'disabled';
                    }
                    return status === 'draft' || status === 'pending' || status === 'approved';
                case 'history':
                    // Editor: toate articolele
                    return true;
                case 'approve':
                    // Editor: doar pending
                    return status === 'pending';
                case 'publish':
                    // Editor: doar approved (și nu online)
                    return status === 'approved';
                case 'disable':
                    // Editor: doar approved online
                    return status === 'approved';
                case 'restore':
                    // Editor: doar disabled online
                    return status === 'disabled';
                case 'delete':
                    // Editor: toate draft și pending
                    return status === 'draft' || status === 'pending';
                default:
                    return false;
            }
            
        case 'moderator':
            switch (actionName) {
                case 'view':
                    // Moderator: toate articolele în orice status
                    return true;
                case 'edit':
                    // Moderator: draft, pending + versiuni online disabled
                    if (isSelectedVersionOnline) {
                        return status === 'disabled';
                    }
                    return status === 'draft' || status === 'pending';
                case 'history':
                    // Moderator: toate articolele
                    return true;
                case 'approve':
                    // Moderator: doar pending
                    return status === 'pending';
                case 'publish':
                    // Moderator: doar approved (și nu online)
                    return status === 'approved';
                case 'disable':
                    // Moderator: doar approved online
                    return status === 'approved';
                case 'restore':
                    // Moderator: doar disabled online
                    return status === 'disabled';
                case 'delete':
                    // Moderator: toate draft și pending
                    return status === 'draft' || status === 'pending';
                default:
                    return false;
            }
            
        case 'admin':
            switch (actionName) {
                case 'view':
                    // Admin: toate articolele în orice status
                    return true;
                case 'edit':
                    // Admin: toate versiunile + versiuni online doar dacă sunt disabled
                    if (isSelectedVersionOnline) {
                        return status === 'disabled';
                    }
                    return true;
                case 'history':
                    // Admin: toate articolele
                    return true;
                case 'approve':
                    // Admin: doar pending
                    return status === 'pending';
                case 'publish':
                    // Admin: doar approved (și nu online)
                    return status === 'approved';
                case 'disable':
                    // Admin: doar approved online
                    return status === 'approved';
                case 'restore':
                    // Admin: doar disabled online
                    return status === 'disabled';
                case 'delete':
                    // Admin: toate versiunile în toate stările
                    return true;
                default:
                    return false;
            }
            
        case 'superadmin':
            switch (actionName) {
                case 'view':
                    // Superadmin: toate articolele în orice status
                    return true;
                case 'edit':
                    // Superadmin: toate versiunile + versiuni online doar dacă sunt disabled
                    if (isSelectedVersionOnline) {
                        return status === 'disabled';
                    }
                    return true;
                case 'history':
                    // Superadmin: toate articolele
                    return true;
                case 'approve':
                    // Superadmin: doar pending
                    return status === 'pending';
                case 'publish':
                    // Superadmin: doar approved (și nu online)
                    return status === 'approved';
                case 'disable':
                    // Superadmin: doar approved online
                    return status === 'approved';
                case 'restore':
                    // Superadmin: doar disabled online
                    return status === 'disabled';
                case 'delete':
                    // Superadmin: toate versiunile în toate stările
                    return true;
                default:
                    return false;
            }
            
        default:
            return false;
    }
}

// Funcție pentru construirea HTML-ului Publication Date
function buildPublishAtHtml(articleId, status, publishAt) {
    const data = publishAt || '';
    
    if ((status || '') === 'approved' && data) {
        const pubDate = new Date(data.replace(' ', 'T'));
        const now = new Date();
        if (pubDate > now) {
            return `<span style="color: #e67e22;" title="Publish At">${data}</span>`;
        } else {
            return `<span>${data}</span>`;
        }
    } else if ((status || '') === 'pending' && (window.USER_ROLE === 'moderator' || window.USER_ROLE === 'admin')) {
        // Populează cu data și ora curentă dacă nu există valoare
        let defaultValue = '';
        if (data) {
            defaultValue = data.replace(' ', 'T');
        } else {
            // Setează data și ora curentă ca valoare implicită
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            defaultValue = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        return `<input type="datetime-local" value="${defaultValue}" onchange="changePublishAt(this.value, ${articleId})">`;
    } else {
        return `<span>${data}</span>`;
    }
}

// Funcție pentru a obține datele versiunii din rândul DataTables
function getVersionDataFromRow(row, versionNumber) {
    
    /*
    console.log('🔧 getVersionDataFromRow called:', {
        articleId: row.article_id,
        versionNumber: versionNumber,
        rowVersions: row.versions,
        rowStatus: row.status
    });
    */
    if (!row.versions || !row.versions.length) {
        //console.log('⚠️ No versions found, using main row data');
        // Dacă nu există versiuni, returnează datele principale
        return {
            version_number: versionNumber,
            status: row.status,
            title: row.title,
            content: row.content,
            publish_at: row.publish_at,
            updated_at: row.updated_at
        };
    }
    
    // Caută versiunea specifică în array-ul versions
    const versionData = row.versions.find(v => {
        if (typeof v === 'object') {
            const vNum = v.version_number;
            //console.log(`🔍 Checking version object:`, v, `version_number: ${vNum}, target: ${versionNumber}`);
            return vNum == versionNumber;
        } else {
            //console.log(`🔍 Checking version primitive:`, v, `target: ${versionNumber}`);
            return v == versionNumber;
        }
    });
    
    //console.log('🎯 Found version data:', versionData);
    
    if (versionData && typeof versionData === 'object') {
        // Dacă am găsit versiunea și e un obiect complet, o returnez
        //console.log('✅ Returning found version object');
        return versionData;
    }
    
    // Dacă versiunea găsită e doar un număr, sau nu am găsit nimic
    // încearcă să faci un request la API pentru a obține datele complete
    //console.log('⚠️ Version data incomplete, falling back to main row data with status from dropdown');
    
    // Fallback: ia statusul din coloana Status a tabelului pentru versiunea selectată
    // Aceasta este o soluție temporară - ar fi mai bine să obții datele complete de la API
    
    // Încearcă să găsești statusul din interfață
    let statusFromUI = row.status; // default
    try {
        const dropdown = document.querySelector(`.version-select[data-article-id="${row.article_id}"]`);
        if (dropdown) {
            const tr = dropdown.closest('tr');
            const statusCell = tr.querySelector('td:nth-child(6) .version-status');
            if (statusCell && statusCell.textContent.trim()) {
                statusFromUI = statusCell.textContent.trim();
                //console.log('📋 Got status from UI:', statusFromUI);
            }
        }
    } catch (e) {
        //console.log('❌ Error getting status from UI:', e);
    }
    
    return {
        version_number: versionNumber,
        status: statusFromUI,
        title: row.title,
        content: row.content,
        publish_at: row.publish_at,
        updated_at: row.updated_at
    };
}

// Funcție globală pentru a naviga la view_article.php cu versiunea selectată
function viewArticleWithVersion(articleId, onlineVersion = null) {
    const selectedVersion = getSelectedVersion(articleId);
    
    // Pentru a determina is_online, verifică dacă versiunea selectată este online
    let isOnlineVersion = 0; // Default la 0 în loc de false
    
    // Găsește rândul curent în tabelul DataTables
    const table = $('#articlesTable').DataTable();
    const rowData = table.rows().data().toArray().find(row => row.article_id == articleId);
    
    if (rowData && rowData.versions) {
        const versionData = rowData.versions.find(v => v.version_number == selectedVersion);
        if (versionData && versionData.is_online == 1) {
            isOnlineVersion = 1;
        }
    }
    
    console.log('View article:', {
        articleId: articleId,
        selectedVersion: selectedVersion,
        onlineVersion: onlineVersion,
        isOnlineVersion: isOnlineVersion,
        rowData: rowData
    });
    
    const url = `../view_article.php?id=${articleId}&version=${selectedVersion}&isonline=${isOnlineVersion}`;
    console.log('Redirecting to:', url);
    window.location.href = url;
}


let currentPage = 1;
let totalPages = 1;
let categories = [];



function loadCategories() {
    fetch('../api/bkd_select_categories.php')
        .then(res => res.json())
        .then(data => {
            categories = data;
            let select = document.getElementById('add_category_select');
            select.innerHTML = `<option value="">--<?= lang('lang_cat_select') ?> --</option>`;
            data.forEach(c => {
                select.innerHTML += `<option value="${c.id}" data-img="/assets/icons/categories/${c.icon}">${escapeHtml(c.name)}</option>`;
            });
            // Reinițializează Select2
            if (window.$ && $(select).select2) {
                $(select).select2({
                    placeholder: "<?= lang('lang_cat_select') ?>",
                    templateResult: formatWithIcon2,
                    templateSelection: formatWithIcon2,
                    allowClear: true,
                    dropdownParent: $('#modal-add-article')
                });
            }
        });
}

function formatWithIcon2(option) {
    if (!option.id) return option.text;
    const img = $(option.element).data('img');
    return $(
      `<span><img src="${img}" class="select2-option-img" width="20" style="margin-right:8px;" />${option.text}</span>`
    );
}

function loadArticles(page = 1) {
    fetch(`../api/bkd_articles.php?page=${page}`)
        .then(res => res.json())
        .then(data => {
            currentPage = data.page;
            totalPages = data.totalPages;
            renderArticlesTable(data.articles);
            renderPagination(currentPage, totalPages);
        });
}

function renderArticlesTable(articles) {
    let html = '';
    let x = 1 + (currentPage - 1) * 5;
    const now = new Date();
    articles.forEach(a => {
        // Verifică dacă articolul este aprobat și data de publicare e în viitor
        let publishAtHtml = '';
        if (a.status === 'approved' && a.publish_at) {
            const pubDate = new Date(a.publish_at.replace(' ', 'T'));
            if (pubDate > now) {
                publishAtHtml = `<span style="color: #e67e22;" title="<?=lang('lang_publish_at')?>">${escapeHtml(a.publish_at)}</span>`;
            } else {
                publishAtHtml = `<span>${escapeHtml(a.publish_at)}</span>`;
            }
        } else if (a.status === 'pending') {
            publishAtHtml = `<input type="datetime-local" value="${a.publish_at ? a.publish_at.replace(' ', 'T') : ''}" onchange="changePublishAt(this.value, ${a.id})">`;
        } else {
            publishAtHtml = `<span>${escapeHtml(a.publish_at || '')}</span>`;
        }
        crtVersion = getSelectedVersion(a.id);
        html += `<tr style="font-size: 0.8em;">
            <td align="center">${x}</td>
            <td>${escapeHtml(a.title)}</td>
            <td>${escapeHtml(a.username)}</td>
            <td>${escapeHtml(a.category)}</td>
            <td>${escapeHtml(a.status)}</td>
            <td>${publishAtHtml}</td>
            <td style="width:220px; text-align:center;">
                <a href="../view_article.php?id=${a.id}&version=${crtVersion}"><img src="<?=APP_URL?>assets/icons/icon-view.svg" class="op-icon" title="<?= lang('lang_btn_view') ?>"></a>
                <a href="#" onclick="openEditArticleModal(${a.id});return false;"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_btn_edit') ?>"></a>
                ${a.status === 'pending'
                    ? `<a href="#" onclick="articleAction('approve', ${a.id}, '${a.publish_at}');return false;"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?= lang('lang_btn_approve') ?>"></a>`
                    : `<a href="#" onclick="articleAction('disable', ${a.id});return false;"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?= lang('lang_btn_disable') ?>"></a>`
                }
            </td>
        </tr>`;
        x++;
    });
    document.getElementById('articles-table-body').innerHTML = html;
}

function renderPagination(page, totalPages) {
    if (totalPages <= 1) return;
    let html = '<ul class="pagination" style="justify-content:center;">';
    html += `<li class="page-item${page === 1 ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(currentPage>1){currentPage=${page-1};loadArticles(${page-1});}return false;">&laquo;</a>
    </li>`;
    let shown = [];
    shown.push(1);
    if (page - 1 > 1) shown.push(page - 1);
    if (page !== 1 && page !== totalPages) shown.push(page);
    if (page + 1 < totalPages) shown.push(page + 1);
    if (totalPages > 1) shown.push(totalPages);
    shown = Array.from(new Set(shown)).sort((a, b) => a - b);
    let last = 0;
    for (let i = 0; i < shown.length; i++) {
        if (shown[i] - last > 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item${shown[i] === page ? ' active' : ''}">
            <a class="page-link${shown[i] === page ? ' active' : ''}" href="#" onclick="currentPage=${shown[i]};loadArticles(${shown[i]});return false;">${shown[i]}</a>
        </li>`;
        last = shown[i];
    }
    html += `<li class="page-item${page === totalPages ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(currentPage<${totalPages}){currentPage=${page+1};loadArticles(${page+1});}return false;">&raquo;</a>
    </li>`;
    html += '</ul>';
    document.getElementById('pagination-results').innerHTML = html;
}

function submitArticle2(submitType) {
    const form = document.getElementById('add_article');
    
    // Ia HTML-ul din Summernote
    if ($('#summernote').summernote) {
        form.querySelector('textarea[name="content"]').value = $('#summernote').summernote('code');
    }
    
    const formData = new FormData(form);
    formData.append('csrf_token', window.CSRF_TOKEN);

    // Verifică dacă e edit sau add
    const editId = form.getAttribute('data-edit-id');
    const editVersion = form.getAttribute('data-edit-version');
    const isOnlineVersion = form.getAttribute('data-is-online-version');
    
    if (editId && editVersion) {
        // Folosește direct informația din atributul data-is-online-version
        const isEditingOnlineVersion = parseInt(isOnlineVersion) || 0;
        
        // Debug logging
        
        console.log('Online version check (using is_online field):', {
            editVersion: editVersion,
            isOnlineVersion: isOnlineVersion,
            isEditingOnlineVersion: isEditingOnlineVersion,
            description: isEditingOnlineVersion ? 'Editing ONLINE version (is_online=1)' : 'Editing NON-ONLINE version (is_online=0)'
        });
        

        formData.append('action', 'edit_article');
        formData.append('article_id', editId);
        formData.append('base_version', editVersion);
        formData.append('is_editing_online_version', isEditingOnlineVersion);
    } else {
        formData.append('action', 'add_article');
        // Pentru articole noi, nu se aplică conceptul de versiune online
        formData.append('is_editing_online_version', 0);
    }
    
    formData.append('submit_type', submitType);
    
    const action = formData.get('action');
    let url = '';
    
    switch (action) {
        case 'add_article':
            url = '../api/bkd_article_add.php';
            break;
        case 'edit_article':
            url = '../api/bkd_article_edit.php';
            break;
        default:
            console.error('Unknown action:', action);
            return;
    }

    //debug logging
    console.log("action:", action, "url:", url);

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('article-feedback').textContent = 'Articolul a fost salvat!';
            document.getElementById('article-feedback').classList.remove('d-none');
            form.reset();
            form.removeAttribute('data-edit-id');
            form.removeAttribute('data-edit-version');
            form.removeAttribute('data-online-version');
            form.removeAttribute('data-is-online-version');
            setTimeout(closeArticleModal, 1200);
            reloadArticlesTable();
            console.log('Article saved:', data['version_id'] ? `Version ID: ${data['version_id']}` : '', data);
        } else {
            document.getElementById('article-feedback').textContent = data.error || 'Eroare la salvare!';
            document.getElementById('article-feedback').classList.remove('d-none');
        }
    });
}

function changePublishAt(value, articleId) {
    fetch('../api/bkd_articles.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=publish_at&article_id=${encodeURIComponent(articleId)}&publish_at=${encodeURIComponent(value)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) reloadArticlesTable();
        else alert(data.error || 'Eroare la schimbarea datei!');
    });
}


// actions: apporove, publish, disable, restore, delete(to be implemented)

function articleAction(action, articleId, publishAt = '', version = null) {


    let body = `action=${encodeURIComponent(action)}&article_id=${encodeURIComponent(articleId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`;
    
    if (action === 'approve' && publishAt) {
        body += `&publish_at=${encodeURIComponent(publishAt)}`;
    }
    
    if ((action === 'approve' || action === 'publish') && version) {
        body += `&version=${encodeURIComponent(version)}`;
    }
    
    // Pentru acțiunea publish, afișează o confirmare
    if (action === 'publish') {
        const selectedVersion = version || getSelectedVersion(articleId);
        if (!confirm(`Sigur vrei să publici versiunea ${selectedVersion} a acestui articol? Aceasta va înlocui versiunea curent publicată.`)) {
            return;
        }
    }
    
    // Pentru acțiunea disable, afișează o confirmare
    if (action === 'disable' && version) {
        if (!confirm(`Sigur vrei să dezactivezi acest articol? Acesta nu va mai fi vizibil publicului.`)) {
            return;
        }
       body += `&version=${encodeURIComponent(version)}`;
    }
    
    // Pentru acțiunea restore, afișează o confirmare
    if (action === 'restore' && version) {
        /*
        if (!confirm(`Sigur vrei să restaurezi acest articol? Acesta va deveni din nou vizibil publicului.`)) {
            return;
        }*/
        body += `&version=${encodeURIComponent(version)}`;
        
    }
    
    let url = '';

    switch(action) {
        case 'approve':
            url = '../api/bkd_article_approve.php';
            break;
        case 'publish':
            url = '../api/bkd_article_publish.php';
            break;
        case 'disable':
            url = '../api/bkd_article_disable.php';
            break;
        case 'restore':
            url = '../api/bkd_article_restore.php';
            break;
        case 'delete':
            url = '../api/bkd_article_delete.php';
            break;
        default:
            console.error('Unknown action:', action);
            return;
    }

    console.log('URL:', url + body);
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            if (action === 'publish') {
                alert('Articolul a fost publicat cu succes!');
            } else if (action === 'disable') {
                alert('Articolul a fost dezactivat cu succes!');
            } else if (action === 'restore') {
                alert('Articolul a fost restaurat cu succes!');
            }
            reloadArticlesTable();
        } else {
            alert(data.error || 'Eroare la acțiune!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la comunicarea cu serverul! Detalii: ' + error.message);
    });
}

// Modal logic
function openArticleModal() {
    document.getElementById('modal-title').textContent = '<?=lang('lang_create_article')?>';
    document.getElementById('modalOverlayAddArticle').style.display = 'block';
    document.getElementById('modal-add-article').style.display = 'flex';


    // Încarcă categoriile la deschiderea modalului!
    loadCategories();

    setTimeout(() => {
        // Inițializează Summernote dacă nu e deja inițializat
        if (!$('#summernote').next('.note-editor').length) {
            $('#summernote').summernote({
                height: 250,
                placeholder: 'Scrie conținutul articolului...',
                codemirror: { // codemirror options
                    theme: 'default',
                    mode: 'text/html',
                    lineNumbers: true
                },
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video','code']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        }
        const firstInput = document.getElementById('modal-add-article').querySelector('input,textarea,select');
        if (firstInput) firstInput.focus();
    }, 200);

    // initialize custom boxes from the modal form;
    const allCustomBoxes = document.querySelectorAll('.custom-box-2');
    allCustomBoxes.forEach(box => {
      initializeCustomBox2(box);
    });

}

function loadArticleVersions(articleId, currentVersion) {
    // Pentru că versiunea nu este editabilă, doar setăm valoarea în input
    const versionInput = document.getElementById('version');
    versionInput.value = 'v' + currentVersion;
    
    // Nu mai avem nevoie de onchange pentru versiune deoarece nu se poate modifica
    // Versiunea se selectează din tabelul principal
}

function openEditArticleModal(articleId, version = null) {
    openArticleModal();
    document.getElementById('modal-title').textContent = '<?=lang('lang_edit_article')?>';
    
    // Folosește versiunea selectată din dropdown sau versiunea implicită
    const targetVersion = version || getSelectedVersion(articleId) || 1;
    
    // Încarcă direct versiunea pentru editare
    fetch(`../api/bkd_articles.php?action=get_version&id=${articleId}&version=${targetVersion}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to load version data:', data);
                return;
            }
            
            // Stochează informația despre versiunea online în formular
            const form = document.getElementById('add_article');
            form.setAttribute('data-online-version', data.is_online ? targetVersion : 'unknown');
            
            /*
            console.log('Edit modal setup:', {
                targetVersion: targetVersion,
                isOnlineVersion: data.is_online,
                editingOnlineVersion: data.is_online ? 1 : 0
            });
            */


            // Completează câmpurile formularului cu datele versiunii selectate
            document.getElementById('title').value = data.title || '';
            $('#summernote').summernote('code', data.content || '');
            document.getElementById('change_note').value = data.change_note || '';
            
            // Marchează formularul ca "edit" și salvează versiunea
            form.setAttribute('data-edit-id', articleId);
            form.setAttribute('data-edit-version', targetVersion);
            form.setAttribute('data-is-online-version', data.is_online ? 1 : 0);
            
            // Populează dropdown-ul de versiuni
            loadArticleVersions(articleId, targetVersion);
        });
    
    // Încarcă alte date necesare (categorii, etc.)
    fetch(`../api/bkd_articles.php?action=get_article&id=${articleId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#add_category_select').val(data.article.category_id).trigger('change');
                document.getElementById('publish_at').value = data.article.publish_at ? data.article.publish_at.replace(' ', 'T') : '';
                document.getElementById('tags').value = data.article.tags ? data.article.tags.join(', ') : '';
            }
        });
}

function closeArticleModal() {
    document.getElementById('modalOverlayAddArticle').style.display = 'none';
    document.getElementById('modal-add-article').style.display = 'none';
    document.getElementById('add_article').reset();
    document.getElementById('article-feedback').classList.add('d-none');
    
    // Curăță toate atributele de editare
    const form = document.getElementById('add_article');
    form.removeAttribute('data-edit-id');
    form.removeAttribute('data-edit-version');
    form.removeAttribute('data-online-version');
    form.removeAttribute('data-is-online-version');
    
    // Reset Summernote
    if ($('#summernote').summernote) {
        $('#summernote').summernote('reset');
    }
    // Reset Select2
    if (window.$ && $('#add_category_select').select2) {
        $('#add_category_select').val('').trigger('change');
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('modal-add-article');
    if (event.target === modal) closeArticleModal();
}

// Funcții pentru istoricul versiunilor
function showVersionHistory(articleId) {
    // Încarcă detaliile articolului și istoricul versiunilor în paralel
    Promise.all([
        fetch(`../api/bkd_articles.php?action=get_article&id=${articleId}`).then(res => res.json()),
        fetch(`../api/bkd_articles.php?action=get_version_history&id=${articleId}`).then(res => res.json())
    ])
    .then(([articleData, historyData]) => {
        if (historyData.success) {
            const articleTitle = articleData.success ? articleData.article.title : 'Articol necunoscut';
            const onlineVersion = articleData.success ? articleData.article.version : null;
            
            // Setează articleId în dataset pentru a fi folosit de funcția restore
            document.getElementById('modal-version-history').dataset.articleId = articleId;
            
            displayVersionHistory(historyData.history, articleTitle, onlineVersion);
            document.getElementById('modalOverlayVersionHistory').style.display = 'block';
            document.getElementById('modal-version-history').style.display = 'block';
        } else {
            alert('Eroare la încărcarea istoricului');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Eroare la încărcarea istoricului');
    });
}


// genereaza butonul Restore in fereastra modala history
function generateRestoreButton(version, onlineVersion = null) {
    const userRole = window.USER_ROLE || '';
    const isModerator = userRole === 'moderator' || userRole === 'admin' || userRole === 'superadmin';
    
    // Folosește direct câmpul is_online din date (1 sau 0)
    const isOnlineVersion = version.is_online == 1;
    const isEligible = version.status === 'approved' && !isOnlineVersion;
    
    if (!isModerator) {
        return '';
    }
    
    if (isEligible) {
        return `<button onclick="restoreVersion(${version.version_number})" class="version-action-btn restore">
                    <?=lang('lang_art_restore')?>
                </button>`;
    } else {
        const reason = isOnlineVersion ? 'Versiune online' : 'Nu este approved';
        return `<button class="version-action-btn restore disabled" disabled title="${reason}">
                    <?=lang('lang_art_restore')?>
                </button>`;
    }
}

function displayVersionHistory(history, articleTitle = '', onlineVersion = null) {
    const content = document.getElementById('version-history-content');
    if (!history || history.length === 0) {
        content.innerHTML = '<p>Nu există istoric pentru acest articol.</p>';
        return;
    }
    
    let html = '';
    
    // Adaugă titlul articolului dacă există
    if (articleTitle) {
        html += `<div class="article-title-header">
            <h3 class="article-title" style="text-align: left;"><?=lang('lang_art_title')?>:  ${escapeHtml(articleTitle)}</h3>
        </div>`;
    }
    
    history.forEach(version => {
        const date = new Date(version.created_at).toLocaleDateString('ro-RO');
        const time = new Date(version.created_at).toLocaleTimeString('ro-RO');
        
        // Folosește direct câmpul is_online din date
        const isOnlineVersion = version.is_online == 1;
        
        html += `
            <div class="version-item">
                <div class="version-header">
                    <div class="version-title-section">
                        <span class="version-number"><?=lang('lang_art_version')?>: ${version.version_number}</span>
                        <span class="version-status" style="background: ${getStatusColor(version.status)}; color: white;">
                            ${version.status}
                        </span>
                        ${isOnlineVersion ? '<span class="version-online-indicator">ONLINE</span>' : ''}
                    </div>
                    <div class="version-date">
                        <?=lang('lang_art_updated_at')?>: ${date} ${time}
                    </div>
                </div>
                <div class="version-meta">
                    <strong><?=lang('lang_art_author')?>: </strong> ${version.author || 'Necunoscut'}
                </div>
                ${version.change_note ? 
                    `<div class="version-note">"${version.change_note}"</div>` : 
                    '<div class="version-note" style="color: #999;"><?=lang('lang_art_no_change_note')?></div>'
                }
                <div class="version-actions">
                    <button onclick="viewVersion(${version.version_number}, ${version.is_online})" class="version-action-btn view">
                        <?=lang('lang_art_view')?>
                    </button>
                    <!--
                    ${generateRestoreButton(version)}
                    -->
                </div>
            </div>
        `;
    });
    
    content.innerHTML = `<div class="version-list">${html}</div>`;
}

function getStatusColor(status) {
    switch(status) {
        case 'approved': return '#27ae60';
        case 'pending': return '#e67e22';
        case 'draft': return '#95a5a6';
        default: return '#bdc3c7';
    }
}

function closeVersionHistoryModal() {
    document.getElementById('modal-version-history').style.display = 'none';
    document.getElementById('modalOverlayVersionHistory').style.display = 'none';
}

function viewVersion(versionNumber, isOnline = 0) {
    const articleId = parseInt(document.getElementById('modal-version-history').dataset.articleId);
    if (!articleId) {
        alert('Eroare: Nu s-a putut identifica articolul.');
        return;
    }
    
    // Folosește direct parametrul is_online
    const isOnlineVersion = isOnline == 1;
    
    const url = `../view_article.php?id=${articleId}&version=${versionNumber}&isonline=${isOnlineVersion ? 1 : 0}`;
    window.location.href = url;
}

function restoreVersion(versionNumber) {
    if (!confirm(`Sigur vrei să restaurezi versiunea ${versionNumber}? Aceasta va înlocui conținutul articolului curent cu cel din versiunea selectată.`)) {
        return;
    }
    
    // Obține ID-ul articolului din modalul deschis
    const articleId = parseInt(document.getElementById('modal-version-history').dataset.articleId);
    
    if (!articleId) {
        alert('Eroare: Nu s-a putut identifica articolul.');
        return;
    }
    
    // Trimite cererea de restore
    const formData = new FormData();
    formData.append('action', 'restore');
    formData.append('id', articleId);
    formData.append('version', versionNumber);
    formData.append('csrf_token', window.CSRF_TOKEN);
    
    fetch(`../api/bkd_articles.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Versiunea a fost restaurată cu succes!');
            closeVersionHistoryModal();
            // Reîncarcă tabelul pentru a reflecta modificările
            if (window.articlesTable) {
                window.articlesTable.ajax.reload();
            }
        } else {
            alert('Eroare la restaurare: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert('Eroare la comunicarea cu serverul.');
    });
}

// Event listener pentru click pe overlay (închide modalul)
document.getElementById('modalOverlayVersionHistory').onclick = function(e) {
    if (document.getElementById('modal-version-history').style.display === 'block') {
        closeVersionHistoryModal();
    }
};

</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
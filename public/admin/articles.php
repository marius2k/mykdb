<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Debug: Să vedem rolul utilizatorului
echo "<script>console.log('PHP Session role: " . ($_SESSION['user']['role'] ?? 'NOT SET') . "');</script>";


$ops = ['edit_article','disable_article','enable_article','create_article'];

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
                    <th><?= lang('lang_art_version') ?></th>
                    <th><?= lang('lang_art_author') ?></th>
                    <th><?= lang('lang_art_category') ?></th>
                    <th><?= lang('lang_art_status') ?></th>
                    <th><?= lang('lang_art_publish_at') ?></th>
                    <th><?= lang('lang_art_updated_at') ?></th>
                    <th><?= lang('lang_art_actions') ?></th>
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
    const selectedVersion = getSelectedVersion(row.article_id || 0);
    const selectedVersionData = getVersionDataFromRow(row, selectedVersion);
    const versionStatus = selectedVersionData ? selectedVersionData.status : row.status;
    
    return buildActionsHtml(row.article_id || 0, versionStatus, row.publish_at || '', selectedVersion, row.user_id || 0);
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
    return buildActionsHtml(articleId, versionData.status, rowData.publish_at || '', versionData.version_number, rowData.user_id || 0);
}

// Funcție pentru generarea Publication Date cu date de versiune specifice (folosită în event handler)  
function generatePublishAtForArticleWithVersionData(articleId, versionData) {
    return buildPublishAtHtml(articleId, versionData.status, versionData.publish_at || '');
}

// Funcție pentru construirea HTML-ului acțiunilor
function buildActionsHtml(articleId, status, publishAt, version = null, authorId = null) {
    const currentUserId = <?= $_SESSION['user']['id'] ?? 0 ?>;
    const userRole = window.USER_ROLE || '';
    
    // Definește acțiunile disponibile
    const actions = [
        {
            name: 'view',
            icon: 'icon-view.svg',
            title: '<?=lang('lang_art_view')?>',
            onclick: `viewArticleWithVersion(${articleId});return false;`
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
            name: 'disable',
            icon: 'icon-disable.svg',
            title: '<?=lang('lang_art_disable')?>',
            onclick: `articleAction('disable', ${articleId});return false;`
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
        const isEnabled = isActionEnabled(action.name, userRole, status, authorId, currentUserId);
        const cssClass = isEnabled ? 'op-icon' : 'op-icon disabled';
        const clickHandler = isEnabled ? `onclick="${action.onclick}"` : '';
        
        html += `<a href="#" ${clickHandler}><img src="<?=APP_URL?>assets/icons/${action.icon}" class="${cssClass}" title="${action.title}" style="width: 32px;"></a> `;
    });
    
    return html;
}

function isActionEnabled(actionName, userRole, articleStatus, authorId, currentUserId) {
    // Convertește ID-urile la numere pentru comparație
    const isOwner = parseInt(authorId) === parseInt(currentUserId);
    
    // Debug logging pentru dezvoltare
    console.log(`Action: ${actionName}, Role: ${userRole}, Status: ${articleStatus}, IsOwner: ${isOwner}, AuthorId: ${authorId}, CurrentUserId: ${currentUserId}`);
    
    // Normalizează statusurile pentru comparație
    const status = (articleStatus || '').toLowerCase();
    
    switch (userRole) {
        case 'contributor':
            switch (actionName) {
                case 'view':
                    return (status === 'draft' && isOwner) || 
                           (status === 'pending' && isOwner) || 
                           (status === 'approved');
                case 'edit':
                    return status === 'draft' && isOwner;
                case 'history':
                    return (status === 'draft' && isOwner) || 
                           (status === 'pending' && isOwner) || 
                           (status === 'approved');
                case 'approve':
                    return false; // N/A
                case 'disable':
                    return false; // N/A
                case 'delete':
                    return status === 'draft' && isOwner;
                default:
                    return false;
            }
            
        case 'editor':
            switch (actionName) {
                case 'view':
                    return true; // Toate statusurile
                case 'edit':
                    return status === 'draft' || status === 'pending';
                case 'history':
                    return true; // Toate statusurile
                case 'approve':
                    return status === 'pending';
                case 'disable':
                    return status === 'approved';
                case 'delete':
                    return status === 'draft' || status === 'pending';
                default:
                    return false;
            }
            
        case 'moderator':
            switch (actionName) {
                case 'view':
                    return true; // Toate statusurile
                case 'edit':
                    return status === 'draft' || status === 'pending';
                case 'history':
                    return true; // Toate statusurile
                case 'approve':
                    return status === 'pending';
                case 'disable':
                    return status === 'approved';
                case 'delete':
                    return status === 'draft' || status === 'pending';
                default:
                    return false;
            }
            
        case 'admin':
            switch (actionName) {
                case 'view':
                    return true; // Toate statusurile
                case 'edit':
                    return true; // Toate statusurile
                case 'history':
                    return true; // Toate statusurile
                case 'approve':
                    return status === 'pending';
                case 'disable':
                    return status === 'approved';
                case 'delete':
                    return true; // Toate statusurile
                default:
                    return false;
            }
            
        case 'superadmin':
            switch (actionName) {
                case 'view':
                    return true; // Toate statusurile
                case 'edit':
                    return true; // Toate statusurile
                case 'history':
                    return true; // Toate statusurile
                case 'approve':
                    return status === 'pending';
                case 'disable':
                    return status === 'approved';
                case 'delete':
                    return true; // Toate statusurile
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
    if (!row.versions || !row.versions.length) return null;
    return row.versions.find(v => v.version_number == versionNumber);
}

// Funcție globală pentru a naviga la view_article.php cu versiunea selectată
function viewArticleWithVersion(articleId) {
    const selectedVersion = getSelectedVersion(articleId);
    const url = `../view_article.php?id=${articleId}&version=${selectedVersion}`;
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
        html += `<tr>
            <td align="center">${x}</td>
            <td>${escapeHtml(a.title)}</td>
            <td>${escapeHtml(a.username)}</td>
            <td>${escapeHtml(a.category)}</td>
            <td>${escapeHtml(a.status)}</td>
            <td>${publishAtHtml}</td>
            <td align="center">
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
    if (editId) {
        formData.append('action', 'edit_article');
        formData.append('article_id', editId);
        if (editVersion) {
            formData.append('base_version', editVersion);
        }
    } else {
        formData.append('action', 'add_article');
    }
    formData.append('submit_type', submitType);

    fetch('../api/bkd_articles.php', {
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
            setTimeout(closeArticleModal, 1200);
            loadArticles(currentPage);
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


function articleAction(action, articleId, publishAt = '', version = null) {
    let body = `action=${encodeURIComponent(action)}&article_id=${encodeURIComponent(articleId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`;
    if (action === 'approve' && publishAt) {
        body += `&publish_at=${encodeURIComponent(publishAt)}`;
    }
    if (action === 'approve' && version) {
        body += `&version=${encodeURIComponent(version)}`;
    }
    fetch('../api/bkd_articles.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) reloadArticlesTable();
        else alert(data.error || 'Eroare la acțiune!');
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
    
    // fetch article data pentru versiunea specificată
    fetch(`../api/bkd_articles.php?action=get_version&id=${articleId}&version=${targetVersion}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to load version data:', data);
                return;
            }
            
            // Completează câmpurile formularului cu datele versiunii selectate
            document.getElementById('title').value = data.title || '';
            $('#summernote').summernote('code', data.content || '');
            
            // Populează change_note dacă există
            document.getElementById('change_note').value = data.change_note || '';
            
            // Marchează formularul ca "edit" și salvează versiunea
            const form = document.getElementById('add_article');
            form.setAttribute('data-edit-id', articleId);
            form.setAttribute('data-edit-version', targetVersion);
            
            // Populează dropdown-ul de versiuni și selectează versiunea curentă
            loadArticleVersions(articleId, targetVersion);
        });
    
    // Încarcă și alte date necesare (categorii, etc.)
    fetch(`../api/bkd_articles.php?action=get_article&id=${articleId}`)
        .then(res => res.json())
        .then(data => {
            $('#add_category_select').val(data.article.category_id).trigger('change');
            document.getElementById('publish_at').value = data.article.publish_at ? data.article.publish_at.replace(' ', 'T') : '';
            document.getElementById('tags').value = data.article.tags ? data.article.tags.join(', ') : '';
        });
}

function closeArticleModal() {
    document.getElementById('modalOverlayAddArticle').style.display = 'none';
    document.getElementById('modal-add-article').style.display = 'none';
    document.getElementById('add_article').reset();
    document.getElementById('article-feedback').classList.add('d-none');
    document.getElementById('add_article').removeAttribute('data-edit-id');
    document.getElementById('add_article').removeAttribute('data-edit-version');
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

/*
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadArticles();
});
*/
/*
document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });
    
});
*/

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

function generateRestoreButton(version, isOnlineVersion) {
    // Verifică dacă utilizatorul este moderator
    const userRole = window.USER_ROLE || '<?= $_SESSION['user']['role'] ?? '' ?>';
    const isModerator = userRole === 'moderator' || userRole === 'admin';
    
    // Debug logging
    console.log('generateRestoreButton called:', {
        version: version.version_number,
        status: version.status,
        isOnlineVersion: isOnlineVersion,
        userRole: userRole,
        isModerator: isModerator
    });
    
    // Verifică eligibilitatea pentru restore
    const isEligible = version.status === 'approved' && !isOnlineVersion;
    
    console.log('Eligibility check:', {
        'version.status': version.status,
        'isOnlineVersion': isOnlineVersion,
        'isEligible': isEligible
    });
    
    if (!isModerator) {
        // Utilizatorul nu este moderator - nu afișa butonul
        console.log('User is not moderator, no button');
        return '';
    }
    
    if (isEligible) {
        // Versiune eligibilă - buton activ
        console.log('Version eligible, showing active button');
        return `<button onclick="restoreVersion(${version.version_number})" class="version-action-btn restore">
                    Restaurează
                </button>`;
    } else {
        // Versiune neeligibilă - buton disabled
        const reason = isOnlineVersion ? 'Versiune online' : 'Nu este approved';
        console.log('Version not eligible:', reason);
        return `<button class="version-action-btn restore disabled" disabled title="${reason}">
                    Restaurează
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
            <h3 class="article-title" style="text-align: left;">Titlu:  ${escapeHtml(articleTitle)}</h3>
        </div>`;
    }
    
    history.forEach(version => {
        const date = new Date(version.created_at).toLocaleDateString('ro-RO');
        const time = new Date(version.created_at).toLocaleTimeString('ro-RO');
        
        // Verifică dacă aceasta este versiunea online
        const isOnlineVersion = onlineVersion && version.version_number == onlineVersion;
        
        html += `
            <div class="version-item">
                <div class="version-header">
                    <div class="version-title-section">
                        <span class="version-number">Versiunea: ${version.version_number}</span>
                        <span class="version-status" style="background: ${getStatusColor(version.status)}; color: white;">
                            ${version.status}
                        </span>
                        ${isOnlineVersion ? '<span class="version-online-indicator">ONLINE</span>' : ''}
                    </div>
                    <div class="version-date">
                        Actualizat la: ${date} ${time}
                    </div>
                </div>
                <div class="version-meta">
                    <strong>Autor: </strong> ${version.author || 'Necunoscut'}
                </div>
                ${version.change_note ? 
                    `<div class="version-note">"${version.change_note}"</div>` : 
                    '<div class="version-note" style="color: #999;">Fără notă de modificare</div>'
                }
                <div class="version-actions">
                    <button onclick="viewVersion(${version.version_number})" class="version-action-btn view">
                        Vizualizează
                    </button>
                    ${generateRestoreButton(version, isOnlineVersion)}
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

function viewVersion(versionNumber) {
    // Implementare viitoare pentru vizualizarea unei versiuni specifice
    alert(`Funcționalitate în dezvoltare: Vizualizare versiunea ${versionNumber}`);
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
    
    fetch('../api/bkd_articles.php', {
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
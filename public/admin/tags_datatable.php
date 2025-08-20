<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verifică permisiuni (doar admin poate gestiona tagurile)
$requiredPermissions = ['manage_tags']; // sau adaptează la sistemul tău de permisiuni
// if (!hasPermission($_SESSION['user']['id'], $requiredPermissions)) {
//     header('Location: ../index.php');
//     exit;
// }

$lang = $_SESSION['settings']['language'] ?? 'en';
// Mapare rapidă dacă ai coduri locale
if ($lang === 'ro') $lang = 'ro';
if ($lang === 'en') $lang = 'en-GB';

?>

<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<div class="breadcrumb-container" style="width: 100%; margin-top: 20px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <br>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_tags') ?> - DataTable
            </li>
        </ol>
    </nav>
</div>
<hr style="height: 1px; border: none; background-color: gray; margin: 0; width: calc(100vw - 20px); margin-left: calc(-50vw + 50% + 10px);">
<br>

<div class="category-container">
    <div class="category-box-2" style="width: fit-content">
        <div class="operations-bar">
            <button class="btn-flat" onclick="openAddTagModal()" title="<?= lang('lang_add_tag') ?>">
                <img src="../../assets/icons/icon-add.svg" alt="Add Tag" class="op-icon">
                <?= lang('lang_add_tag') ?>
            </button>
            <button class="btn-flat" onclick="showTagStats()" title="<?= lang('lang_tag_statistics') ?>">
                <img src="../../assets/icons/icon-stats.svg" alt="Statistics" class="op-icon">
                <?= lang('lang_tag_statistics') ?>
            </button>
            <button class="btn-flat" onclick="cleanUnusedTags()" title="<?= lang('lang_clean_unused_tags') ?>">
                <img src="../../assets/icons/icon-delete.svg" alt="Clean" class="op-icon">
                <?= lang('lang_clean_unused_tags') ?>
            </button>
        </div>
    </div>

    <div class="category-box-1" style="width: 100%;">
        <!-- Tabel tags cu DataTable -->
        <table id="tagsTable" class="tags-table" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= lang('lang_tag_name') ?></th>
                    <th><?= lang('lang_tag_description') ?></th>
                    <th><?= lang('lang_usage_count') ?></th>
                    <th><?= lang('lang_last_used') ?></th>
                    <th><?= lang('lang_actions') ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Overlay pentru fundal -->
<div id="modalOverlayCategory" style="display:none;"></div>

<!-- Modal Adăugare/Editare Tag -->
<div id="modal-tag" style="display:none;">
    <form id="tag-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-header-category">
            <span class="modal-title-category" id="modal-tag-title"><?= lang('lang_add_tag') ?></span>
            <span class="modal-close-category" onclick="closeTagModal()">&times;</span>
        </div>
        <div class="modal-content-category">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <label><?= lang('lang_tag_name') ?></label>
                <input type="text" id="tag-name" name="name" required style="width: 220px; min-height: 32px; height: 32px; box-sizing: border-box; border-radius: 7px; border: 1px solid #bbb; font-size: 1em; background: #fafbfc;">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <label><?= lang('lang_tag_description') ?></label>
                <input type="text" id="tag-description" name="description" style="width: 220px; min-height: 32px; height: 32px; box-sizing: border-box; border-radius: 7px; border: 1px solid #bbb; font-size: 1em; background: #fafbfc;">
            </div>
        </div>
        <div class="modal-footer-category">
            <button class="modal-btn-category cancel" type="button" onclick="closeTagModal()"><?= lang('lang_btn_cancel') ?></button>
            <button class="modal-btn-category primary" type="submit"><?= lang('lang_btn_save') ?></button>
        </div>
    </form>
</div>

<style>
#modal-tag {
    position: fixed;
    left: 50%; 
    top: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 18px;
    min-width: 450px;
    max-width: 100vw;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    z-index: 9999;
    font-family: inherit;
    animation: modalPop 0.18s cubic-bezier(.4,1.6,.6,1) 1;
    display: none;
    flex-direction: column;
}
</style>

<script>
let editingTagId = null;

// Inițializează DataTable la încărcarea paginii
$(document).ready(function() {
    const table = $('#tagsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../api/bkd_tags_management.php',
            type: 'GET',
            data: function(d) {
                d.action = 'datatable';
            }
        },
        columns: [
            { data: 'rownum', orderable: false },
            { data: 'name' },
            { 
                data: 'description',
                render: function(data, type, row) {
                    return data ? escapeHtml(data) : '<em>Fără descriere</em>';
                }
            },
            { 
                data: 'usage_count',
                render: function(data, type, row) {
                    const usageClass = data > 0 ? 'text-success' : 'text-muted';
                    return `<span class="${usageClass}">${data}</span>`;
                }
            },
            { 
                data: 'last_used',
                render: function(data, type, row) {
                    return data ? data : '<em>Niciodată</em>';
                }
            },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let html = '';
                    html += `<a href="#" onclick="editTag(${row.id}); return false;"><img src="../../assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_edit') ?>"></a> `;
                    html += `<a href="#" onclick="viewTagArticles(${row.id}, '${escapeHtml(row.name)}'); return false;"><img src="../../assets/icons/icon-view.svg" class="op-icon" title="<?= lang('lang_view_articles') ?>"></a> `;
                    if (row.usage_count === 0) {
                        html += `<a href="#" onclick="deleteTag(${row.id}, '${escapeHtml(row.name)}'); return false;"><img src="../../assets/icons/icon-delete.svg" class="op-icon" title="<?= lang('lang_delete') ?>"></a>`;
                    } else {
                        html += `<a href="#" onclick="mergeTag(${row.id}, '${escapeHtml(row.name)}'); return false;"><img src="../../assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_merge') ?>"></a>`;
                    }
                    return html;
                }
            }
        ],
        order: [[3, 'desc']], // Sortează după usage_count descendent
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Toate"]]
    });
    
    // Funcție pentru reîncărcarea tabelului
    window.reloadTagsTable = () => table.ajax.reload(null, false);
});

// Funcții pentru gestionarea tagurilor
function editTag(id) {
    editingTagId = id;
    
    // Obține informațiile tag-ului
    fetch(`../api/bkd_tags_management.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('tag-name').value = data.tag.name;
                document.getElementById('tag-description').value = data.tag.description || '';
                document.getElementById('modal-tag-title').textContent = '<?= lang('lang_edit_tag') ?>';
                document.getElementById('modalOverlayCategory').style.display = 'block';
                document.getElementById('modal-tag').style.display = 'block';
                document.getElementById('tag-name').focus();
            } else {
                alert('Eroare la încărcarea tag-ului: ' + (data.error || 'Eroare necunoscută'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Eroare la încărcarea tag-ului');
        });
}

function deleteTag(id, name) {
    if (!confirm(`<?= lang('lang_confirm_delete_tag') ?> "${name}"?`)) {
        return;
    }
    
    fetch('../api/bkd_tags_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&id=${encodeURIComponent(id)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadTagsTable();
            alert('<?= lang('lang_tag_deleted_success') ?>');
        } else {
            alert('Eroare la ștergerea tag-ului: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la ștergerea tag-ului');
    });
}

function viewTagArticles(id, name) {
    // Aici poți deschide o pagină separată sau un modal cu articolele
    window.open(`../articles_by_tag.php?tag_id=${id}`, '_blank');
}

function mergeTag(id, name) {
    // Implementează funcționalitatea de merge
    alert('Funcționalitatea de merge va fi implementată în viitor');
}

// Funcții pentru modal
function openAddTagModal() {
    editingTagId = null;
    document.getElementById('tag-form').reset();
    document.getElementById('modal-tag-title').textContent = '<?= lang('lang_add_tag') ?>';
    document.getElementById('modalOverlayCategory').style.display = 'block';
    document.getElementById('modal-tag').style.display = 'block';
    document.getElementById('tag-name').focus();
}

function closeTagModal() {
    document.getElementById('modal-tag').style.display = 'none';
    document.getElementById('modalOverlayCategory').style.display = 'none';
    editingTagId = null;
    document.getElementById('tag-form').reset();
}

// Submit form pentru adăugare/editare tag
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('tag-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const action = editingTagId ? 'update' : 'add';
        formData.append('action', action);
        formData.append('csrf_token', window.CSRF_TOKEN);
        
        if (editingTagId) {
            formData.append('id', editingTagId);
        }
        
        fetch('../api/bkd_tags_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeTagModal();
                reloadTagsTable();
                alert(editingTagId ? '<?= lang('lang_tag_updated_success') ?>' : '<?= lang('lang_tag_added_success') ?>');
            } else {
                alert('Eroare: ' + (data.error || 'Eroare necunoscută'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Eroare la salvarea tag-ului');
        });
    });
});

// Funcții pentru statistici
function showTagStats() {
    fetch('../api/bkd_tags_management.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStatsModal(data.stats);
            } else {
                alert('Eroare la încărcarea statisticilor: ' + (data.error || 'Eroare necunoscută'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Eroare la încărcarea statisticilor');
        });
}

function cleanUnusedTags() {
    if (!confirm('<?= lang('lang_confirm_clean_unused') ?>')) {
        return;
    }
    
    fetch('../api/bkd_tags_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=clean_unused&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reloadTagsTable();
            alert(`<?= lang('lang_cleanup_success') ?> ${data.deleted_count} <?= lang('lang_tags_deleted') ?>`);
        } else {
            alert('Eroare la curățarea tag-urilor: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la curățarea tag-urilor');
    });
}

// Funcții helper
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Modal pentru statistici
function displayStatsModal(stats) {
    // Implementează afișarea statisticilor în modal - versiune simplificată
    alert('Statistici: ' + JSON.stringify(stats));
}

// Click pe backdrop pentru închiderea modalului
window.onclick = function(event) {
    const overlay = document.getElementById('modalOverlayCategory');
    
    if (event.target === overlay) {
        closeTagModal();
    }
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

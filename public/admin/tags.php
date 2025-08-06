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
// Mapare rapidă dacă ai coduri locale pentru DataTables
if ($lang === 'ro') $lang = 'ro';
if ($lang === 'en') $lang = 'en-GB';

?>

<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>

<link rel="stylesheet" href="//cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="//cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<div class="category-container">
    <div class="category-box-2" style="width: fit-content">
        <div class="operations-bar">
            <button class="btn-flat" onclick="openAddTagModal()" title="<?= lang('lang_add_tag') ?>">
                <img src="../../assets/icons/icon-add.svg" alt="Add Tag" class="op-icon">
                <?= lang('lang_add_tag') ?>
            </button>
            <button class="btn-flat" onclick="showTagStats()" title="<?= lang('lang_tag_statistics') ?>">
                <img src="../../assets/icons/icon-view.svg" alt="Statistics" class="op-icon">
                <?= lang('lang_tag_statistics') ?>
            </button>
            <button class="btn-flat" onclick="cleanUnusedTags()" title="<?= lang('lang_clean_unused_tags') ?>">
                <img src="../../assets/icons/icon-delete.svg" alt="Clean" class="op-icon">
                <?= lang('lang_clean_unused_tags') ?>
            </button>
        </div>
    </div>

    <div class="category-box-1" style="width: 80%;">
        <!-- Tabel tags cu DataTable -->
        <table id="tagsTable" class="articles-table" width="100%">
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

<!-- Modal Adăugare/Editare Tag -->
<div id="modal-tag" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="modal-tag-title"><?= lang('lang_add_tag') ?></h3>
            <span class="close" onclick="closeTagModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="tag-form">
                <div class="form-group">
                    <label for="tag-name"><?= lang('lang_tag_name') ?>:</label>
                    <input type="text" id="tag-name" name="name" required style="width: 100%; padding: 8px; margin: 5px 0;">
                </div>
                <div class="form-group">
                    <label for="tag-description"><?= lang('lang_tag_description') ?>:</label>
                    <textarea id="tag-description" name="description" style="width: 100%; padding: 8px; margin: 5px 0; height: 80px;"></textarea>
                </div>
                <div class="modal-footer" style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeTagModal()" class="btn btn-outline-grey"><?= lang('lang_cancel') ?></button>
                    <button type="submit" class="btn btn-outline-grey"><?= lang('lang_save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Statistici -->
<div id="modal-stats" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><?= lang('lang_tag_statistics') ?></h3>
            <span class="close" onclick="closeStatsModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="stats-content">
                <!-- Statisticile vor fi încărcate prin JavaScript -->
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    max-width: 500px;
    width: 90%;
}

.modal-header {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: right;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.btn {
    padding: 8px 16px;
    margin: 0 5px;
    border: 1px solid #ccc;
    background-color: #f8f9fa;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
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
            { data: 'rownum', orderable: false, searchable: false },
            { data: 'name', orderable: true, searchable: true },
            { 
                data: 'description',
                orderable: false, 
                searchable: true,
                render: function(data, type, row) {
                    return data ? escapeHtml(data) : '<em>Fără descriere</em>';
                }
            },
            { 
                data: 'usage_count',
                orderable: true,
                render: function(data, type, row) {
                    const usageClass = data > 0 ? 'text-success' : 'text-muted';
                    return `<span class="${usageClass}">${data}</span>`;
                }
            },
            { 
                data: 'last_used',
                orderable: true,
                render: function(data, type, row) {
                    return data ? data : '<em>Niciodată</em>';
                }
            },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let html = '';
                    html += `<a href="#" onclick="editTag(${row.id}); return false;"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_edit') ?>"></a> `;
                    html += `<a href="#" onclick="viewTagArticles(${row.id}, '${escapeHtml(row.name)}'); return false;"><img src="<?=APP_URL?>assets/icons/icon-view.svg" class="op-icon" title="<?= lang('lang_view_articles') ?>"></a> `;
                    if (row.usage_count === 0) {
                        html += `<a href="#" onclick="deleteTag(${row.id}, '${escapeHtml(row.name)}'); return false;"><img src="<?=APP_URL?>assets/icons/icon-delete.svg" class="op-icon" title="<?= lang('lang_delete') ?>"></a>`;
                    } else {
                        html += `<a href="#" onclick="mergeTag(${row.id}, '${escapeHtml(row.name)}'); return false;"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_merge') ?>"></a>`;
                    }
                    return html;
                }
            }
        ],
        order: [[3, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/<?=lang($lang)?>.json"
        }
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
    document.getElementById('modal-tag').style.display = 'block';
    document.getElementById('tag-name').focus();
}

function closeTagModal() {
    document.getElementById('modal-tag').style.display = 'none';
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
    document.getElementById('modal-stats').style.display = 'block';
    
    fetch('../api/bkd_tags_management.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderStats(data.stats);
            } else {
                document.getElementById('stats-content').innerHTML = '<p>Eroare la încărcarea statisticilor.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('stats-content').innerHTML = '<p>Eroare la încărcarea statisticilor.</p>';
        });
}

function renderStats(stats) {
    let html = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <h5><?= lang("lang_most_used_tags") ?></h5>
                <ol>
    `;
    
    if (stats.most_used && stats.most_used.length > 0) {
        stats.most_used.forEach(tag => {
            html += `<li><strong>${escapeHtml(tag.name)}</strong> (${tag.usage_count} <?= lang("lang_articles") ?>)</li>`;
        });
    } else {
        html += '<li><?= lang("lang_no_data") ?></li>';
    }
    
    html += `
                </ol>
            </div>
            <div>
                <h5><?= lang("lang_recent_tags") ?></h5>
                <ul>
    `;
    
    if (stats.recent && stats.recent.length > 0) {
        stats.recent.forEach(tag => {
            html += `<li><strong>${escapeHtml(tag.name)}</strong></li>`;
        });
    } else {
        html += '<li><?= lang("lang_no_data") ?></li>';
    }
    
    html += `
                </ul>
            </div>
        </div>
        <div>
            <h5><?= lang("lang_usage_distribution") ?></h5>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <p><strong><?= lang("lang_heavily_used") ?> (10+):</strong> ${stats.distribution?.heavy || 0} <?= lang("lang_tags") ?></p>
                <p><strong><?= lang("lang_moderately_used") ?> (1-9):</strong> ${stats.distribution?.moderate || 0} <?= lang("lang_tags") ?></p>
                <p><strong><?= lang("lang_unused") ?> (0):</strong> ${stats.distribution?.unused || 0} <?= lang("lang_tags") ?></p>
            </div>
        </div>
    `;
    
    document.getElementById('stats-content').innerHTML = html;
}

function closeStatsModal() {
    document.getElementById('modal-stats').style.display = 'none';
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

// Click pe backdrop pentru închiderea modalului
window.onclick = function(event) {
    const tagModal = document.getElementById('modal-tag');
    const statsModal = document.getElementById('modal-stats');
    
    if (event.target === tagModal) {
        closeTagModal();
    }
    if (event.target === statsModal) {
        closeStatsModal();
    }
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

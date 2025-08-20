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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<div class="breadcrumb-container" style="width: 100%; margin-top: 20px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <br>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_tags') ?>
            </li>
        </ol>
    </nav>
</div>
<hr style="height: 1px; border: none; background-color: gray; margin: 0; width: calc(100vw - 20px); margin-left: calc(-50vw + 50% + 10px);">
<br>

<div class="category-container">
    <div class="category-box-1" style="width: 100%;">
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

<!-- Modal Statistici -->
<div id="modal-stats" style="display:none;">
    <div class="modal-header-category">
        <span class="modal-title-category"><?= lang('lang_tag_statistics') ?></span>
        <span class="modal-close-category" onclick="closeStatsModal()">&times;</span>
    </div>
    <div class="modal-content-category">
        <div id="stats-content">
            <!-- Statisticile vor fi încărcate prin JavaScript -->
        </div>
    </div>
    <div class="modal-footer-category">
        <button class="modal-btn-category cancel" type="button" onclick="closeStatsModal()"><?= lang('lang_btn_close') ?></button>
    </div>
</div>

<style>
#modal-tag, #modal-stats {
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

@keyframes modalPop {
    0% { transform: translate(-50%, -60%) scale(0.95);}
    100% { transform: translate(-50%, -50%) scale(1);}
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
                    return data ? escapeHtml(data) : '<em><?=lang('lang_no_description')?></em>';
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
                    return data ? data : '<em><?=lang('lang_never_used')?></em>';
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
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?=lang($lang)?>.json"
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
    // Deschide pagina cu articolele care conțin acest tag
    window.location.href = `../articles_by_tag.php?tag=${encodeURIComponent(name)}`;
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
        const action = editingTagId ? 'update' : 'create';
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
    document.getElementById('modalOverlayCategory').style.display = 'block';
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
    document.getElementById('modalOverlayCategory').style.display = 'none';
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
    const overlay = document.getElementById('modalOverlayCategory');
    
    if (event.target === overlay) {
        // Verifică care modal este deschis și închide-l
        if (document.getElementById('modal-tag').style.display === 'block') {
            closeTagModal();
        }
        if (document.getElementById('modal-stats').style.display === 'block') {
            closeStatsModal();
        }
    }
}

// Auto-execute action based on URL parameter
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    
    setTimeout(function() {
        if (action === 'add') {
            openAddTagModal();
        } else if (action === 'stats') {
            showTagStats();
        } else if (action === 'clean') {
            cleanUnusedTags();
        }
    }, 500); // Give time for page to fully load
});

</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

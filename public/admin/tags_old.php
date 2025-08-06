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

<!-- Modal Adăugare/Editare Tag -->
<div id="modal-tag" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px; margin: auto; position: relative;">
        <span class="close" onclick="closeTagModal()" style="position: absolute; top: 10px; right: 20px; font-size: 2em; cursor: pointer;">&times;</span>
        <h4 id="modal-tag-title"><?= lang('lang_add_tag') ?></h4><br>
        
        <form id="tag-form">
            <div class="form-group">
                <label for="tag-name"><?= lang('lang_tag_name') ?>:</label>
                <input type="text" id="tag-name" name="name" required style="width: 100%; padding: 8px; margin: 5px 0;">
                <small style="color: #666;"><?= lang('lang_tag_name_help') ?></small>
            </div>
            
            <div class="form-group">
                <label for="tag-description"><?= lang('lang_tag_description') ?>:</label>
                <textarea id="tag-description" name="description" rows="3" style="width: 100%; padding: 8px; margin: 5px 0; resize: vertical;" placeholder="<?= lang('lang_tag_description_help') ?>"></textarea>
                <small style="color: #666;"><?= lang('lang_tag_description_optional') ?></small>
            </div>
            
            <div class="form-group" id="merge-section" style="display: none;">
                <label><?= lang('lang_merge_with_existing') ?>:</label>
                <select id="merge-target" style="width: 100%; padding: 8px; margin: 5px 0;">
                    <option value=""><?= lang('lang_select_tag_to_merge') ?></option>
                </select>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeTagModal()" class="btn btn-outline-grey"><?= lang('lang_cancel') ?></button>
                <button type="submit" class="btn btn-outline-grey"><?= lang('lang_save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Statistici -->
<div id="modal-stats" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px; margin: auto; position: relative;">
        <span class="close" onclick="closeStatsModal()" style="position: absolute; top: 10px; right: 20px; font-size: 2em; cursor: pointer;">&times;</span>
        <h4><?= lang('lang_tag_statistics') ?></h4><br>
        
        <div id="stats-content">
            <!-- Statisticile vor fi încărcate prin JavaScript -->
        </div>
    </div>
</div>

<style>
.tags-table {
    border-collapse: collapse;
    margin-top: 0;
}

.tags-table th,
.tags-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.tags-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.tags-table tbody tr:hover {
    background-color: #f5f5f5;
}

.tag-usage-high {
    color: #28a745;
    font-weight: bold;
}

.tag-usage-medium {
    color: #ffc107;
    font-weight: bold;
}

.tag-usage-low {
    color: #dc3545;
}

.tag-unused {
    color: #6c757d;
    font-style: italic;
}

.modal {
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
    padding: 20px;
    border: 1px solid #888;
    border-radius: 5px;
    max-height: 90vh;
    overflow-y: auto;
}

.btn {
    padding: 8px 16px;
    margin: 0 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
</style>

<script>
let tags = [];
let filteredTags = [];
let currentPage = 1;
let tagsPerPage = 20;
let editingTagId = null;

// Încarcă toate tagurile la început
document.addEventListener('DOMContentLoaded', function() {
    loadTags();
});

function loadTags() {
    fetch('../api/bkd_tags_management.php?action=list', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            tags = data.tags;
            filteredTags = [...tags];
            updateQuickStats();
            sortTags();
            renderTagsTable();
            renderPagination();
        } else {
            alert('Eroare la încărcarea tagurilor: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la încărcarea tagurilor');
    });
}

function updateQuickStats() {
    const totalTags = tags.length;
    const usedTags = tags.filter(tag => tag.usage_count > 0).length;
    const unusedTags = totalTags - usedTags;
    const totalUsage = tags.reduce((sum, tag) => sum + parseInt(tag.usage_count), 0);

    document.getElementById('total-tags').textContent = totalTags;
    document.getElementById('used-tags').textContent = usedTags;
    document.getElementById('unused-tags').textContent = unusedTags;
    document.getElementById('total-usage').textContent = totalUsage;
}

function filterTags() {
    const searchTerm = document.getElementById('search-tags').value.toLowerCase();
    const usageFilter = document.getElementById('filter-usage').value;
    
    filteredTags = tags.filter(tag => {
        const matchesSearch = tag.name.toLowerCase().includes(searchTerm);
        
        let matchesUsage = true;
        if (usageFilter === 'used') {
            matchesUsage = parseInt(tag.usage_count) > 0;
        } else if (usageFilter === 'unused') {
            matchesUsage = parseInt(tag.usage_count) === 0;
        }
        
        return matchesSearch && matchesUsage;
    });
    
    currentPage = 1;
    renderTagsTable();
    renderPagination();
}

function sortTags() {
    const sortBy = document.getElementById('sort-tags').value;
    
    filteredTags.sort((a, b) => {
        switch (sortBy) {
            case 'name_asc':
                return a.name.localeCompare(b.name);
            case 'name_desc':
                return b.name.localeCompare(a.name);
            case 'usage_desc':
                return parseInt(b.usage_count) - parseInt(a.usage_count);
            case 'usage_asc':
                return parseInt(a.usage_count) - parseInt(b.usage_count);
            case 'created_desc':
                return 0; // Nu avem created_at, deci nu sortăm
            case 'created_asc':
                return 0; // Nu avem created_at, deci nu sortăm
            default:
                return 0;
        }
    });
    
    renderTagsTable();
}

function renderTagsTable() {
    const startIndex = (currentPage - 1) * tagsPerPage;
    const endIndex = startIndex + tagsPerPage;
    const pageData = filteredTags.slice(startIndex, endIndex);
    
    let html = '';
    pageData.forEach((tag, index) => {
        const globalIndex = startIndex + index + 1;
        const usageCount = parseInt(tag.usage_count);
        
        let usageClass = 'tag-unused';
        if (usageCount > 10) usageClass = 'tag-usage-high';
        else if (usageCount > 0) usageClass = 'tag-usage-medium';
        else if (usageCount === 0) usageClass = 'tag-usage-low';
        
        const lastUsed = tag.last_used ? formatDate(tag.last_used) : '<?= lang("lang_never") ?>';
        
        html += `
            <tr>
                <td>${globalIndex}</td>
                <td>
                    <strong>${escapeHtml(tag.name)}</strong>
                </td>
                <td class="${usageClass}">${usageCount}</td>
                <td>${lastUsed}</td>
                <td>
                    <a href="#" onclick="editTag(${tag.id}); return false;"><img src="../../assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_edit') ?>"></a>
                    <a href="#" onclick="viewTagArticles(${tag.id}, '${escapeHtml(tag.name)}'); return false;"><img src="../../assets/icons/icon-view.svg" class="op-icon" title="<?= lang('lang_view_articles') ?>"></a>
                    ${usageCount === 0 ? `
                        <a href="#" onclick="deleteTag(${tag.id}, '${escapeHtml(tag.name)}'); return false;"><img src="../../assets/icons/icon-delete.svg" class="op-icon" title="<?= lang('lang_delete') ?>"></a>
                    ` : `
                        <a href="#" onclick="mergeTag(${tag.id}, '${escapeHtml(tag.name)}'); return false;"><img src="../../assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_merge') ?>"></a>
                    `}
                </td>
            </tr>
        `;
    });
    
    document.getElementById('tags-table-body').innerHTML = html;
}

function renderPagination() {
    const totalPages = Math.ceil(filteredTags.length / tagsPerPage);
    
    if (totalPages <= 1) {
        document.getElementById('pagination-tags').innerHTML = '';
        return;
    }
    
    let html = '<ul class="pagination">';
    
    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">&laquo;</a>
    </li>`;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">&raquo;</a>
    </li>`;
    
    html += '</ul>';
    document.getElementById('pagination-tags').innerHTML = html;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredTags.length / tagsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        renderTagsTable();
        renderPagination();
    }
}

function openAddTagModal() {
    editingTagId = null;
    document.getElementById('modal-tag-title').textContent = '<?= lang("lang_add_tag") ?>';
    document.getElementById('tag-name').value = '';
    document.getElementById('tag-description').value = '';
    document.getElementById('merge-section').style.display = 'none';
    document.getElementById('modal-tag').style.display = 'block';
}

function editTag(tagId) {
    editingTagId = tagId;
    const tag = tags.find(t => t.id == tagId);
    if (!tag) return;
    
    document.getElementById('modal-tag-title').textContent = '<?= lang("lang_edit_tag") ?>';
    document.getElementById('tag-name').value = tag.name;
    document.getElementById('tag-description').value = tag.description || '';
    document.getElementById('merge-section').style.display = 'none';
    document.getElementById('modal-tag').style.display = 'block';
}

function closeTagModal() {
    document.getElementById('modal-tag').style.display = 'none';
    editingTagId = null;
}

// Handle form submission
document.getElementById('tag-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', editingTagId ? 'update' : 'create');
    formData.append('name', document.getElementById('tag-name').value.trim());
    formData.append('description', document.getElementById('tag-description').value.trim());
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
            loadTags();
            alert(editingTagId ? '<?= lang("lang_tag_updated") ?>' : '<?= lang("lang_tag_created") ?>');
        } else {
            alert('Eroare: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la salvarea tag-ului');
    });
});

function deleteTag(tagId, tagName) {
    if (!confirm(`<?= lang("lang_confirm_delete_tag") ?> "${tagName}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', tagId);
    formData.append('csrf_token', window.CSRF_TOKEN);
    
    fetch('../api/bkd_tags_management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTags();
            alert('<?= lang("lang_tag_deleted") ?>');
        } else {
            alert('Eroare: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la ștergerea tag-ului');
    });
}

function viewTagArticles(tagId, tagName) {
    window.open(`../articles_by_tag.php?tag=${encodeURIComponent(tagName)}`, '_blank');
}

function cleanUnusedTags() {
    if (!confirm('<?= lang("lang_confirm_clean_unused") ?>')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clean_unused');
    formData.append('csrf_token', window.CSRF_TOKEN);
    
    fetch('../api/bkd_tags_management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTags();
            alert(`<?= lang("lang_cleaned_tags") ?> ${data.deleted_count} <?= lang("lang_unused_tags_deleted") ?>`);
        } else {
            alert('Eroare: ' + (data.error || 'Eroare necunoscută'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la curățarea tag-urilor');
    });
}

function showTagStats() {
    document.getElementById('modal-stats').style.display = 'block';
    
    fetch('../api/bkd_tags_management.php?action=stats', {
        method: 'GET'
    })
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
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h5><?= lang("lang_most_used_tags") ?></h5>
                <ol>
    `;
    
    stats.most_used.forEach(tag => {
        html += `<li><strong>${escapeHtml(tag.name)}</strong> (${tag.usage_count} <?= lang("lang_articles") ?>)</li>`;
    });
    
    html += `
                </ol>
            </div>
            <div>
                <h5><?= lang("lang_recent_tags") ?></h5>
                <ul>
    `;
    
    stats.recent.forEach(tag => {
        html += `<li><strong>${escapeHtml(tag.name)}</strong></li>`;
    });
    
    html += `
                </ul>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <h5><?= lang("lang_usage_distribution") ?></h5>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <p><strong><?= lang("lang_heavily_used") ?> (10+):</strong> ${stats.distribution.heavy} <?= lang("lang_tags") ?></p>
                <p><strong><?= lang("lang_moderately_used") ?> (1-9):</strong> ${stats.distribution.moderate} <?= lang("lang_tags") ?></p>
                <p><strong><?= lang("lang_unused") ?> (0):</strong> ${stats.distribution.unused} <?= lang("lang_tags") ?></p>
            </div>
        </div>
    `;
    
    document.getElementById('stats-content').innerHTML = html;
}

function closeStatsModal() {
    document.getElementById('modal-stats').style.display = 'none';
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

// Close modals when clicking outside
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

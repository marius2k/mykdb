<?php
include_once '../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>

<div style="display: flex; width: 50%;">
    <div class="custom-box-1">
                    <div class="corner-label-1">Filter by</div>
                    <div id="logs-filters" class="box-content-1" style="justify-content: flex-end;">
                    <!-- JS: renderFilters(data.users) -->
                    </div>
    </div>
</div>


<form id="bulkLogForm">
    <table class="articles-table">
        <thead>
            <tr>
                <th><input type="checkbox" id="checkAll" onclick="toggleAllLogs(this)"></th>
                <th><?= lang('lang_db_log_table_user') ?></th>
                <th><?= lang('lang_db_log_table_action') ?></th>
                <th><?= lang('lang_db_log_table_agent') ?></th>
                <th><?= lang('lang_db_log_table_details') ?></th>
                <th><?= lang('lang_db_log_table_data') ?></th>
                <th width="100px">Archive/Delete</th>
            </tr>
        </thead>
        <tbody id="logs-table-body"></tbody>
        <tfoot>
            <tr>
                <td colspan="7"><div id="pagination-results"></div></td>
            </tr>
        </tfoot>
    </table>
    <div style="margin-top: 10px;">
        <button type="button" class="btn btn-outline-grey" onclick="submitBulkLogs('archive')"><?= lang('lang_log_archive_selected') ?></button>
        <button type="button" class="btn btn-outline-grey" onclick="submitBulkLogs('delete')"><?= lang('lang_log_delete_selected') ?></button>
    </div>
</form>

<script>
let allLogs = [];
let allUsers = [];
let isAdmin = false;
let currentUserId = null;

let filterUserId = 0;
let filterStart = '';
let filterEnd = '';
let currentPage = 1;

function escapeHtml(text) {
    var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function renderFilters(users) {
    let html = `<form id="logFilterForm" class="mb-3" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
        <div>
            <label><?= lang('lang_log_filter_user') ?></label><br>
            <select id="filterUserId" name="user_id">
                <option value="0">-- <?= lang('lang_db_filter_all_users') ?> --</option>`;
    users.forEach(u => {
        html += `<option value="${u.id}">${escapeHtml(u.username)}</option>`;
    });
    html += `</select>
        </div>
        <div>
            <label for="start_date"><?= lang('lang_log_filter_start_date') ?? 'De la data' ?></label><br>
            <input type="datetime-local" id="start_date" name="start_date">
        </div>
        <div>
            <label for="end_date"><?= lang('lang_log_filter_end_date') ?? 'Până la data' ?></label><br>
            <input type="datetime-local" id="end_date" name="end_date">
        </div>
        <div>
            <a href="#" id="resetFiltersLink">
                <img src="<?=APP_URL?>assets/icons/icon-reset.svg" title="<?= lang('lang_log_filter_reset') ?>" alt="Reset Filters" style="width: 25px; height: auto;">
            </a>
        </div>
    </form>`;
    document.getElementById('logs-filters').innerHTML = html;

    document.getElementById('filterUserId').onchange = function() {
        filterUserId = this.value;
        loadLogs(1);
    };
    document.getElementById('start_date').onchange = function() {
        filterStart = this.value;
        loadLogs(1);
    };
    document.getElementById('end_date').onchange = function() {
        filterEnd = this.value;
        loadLogs(1);
    };

    document.getElementById('resetFiltersLink').onclick = function(e) {
        e.preventDefault();
        filterUserId = 0;
        filterStart = '';
        filterEnd = '';
        loadLogs(1);
    };
}

function renderLogsTable(logs) {
    let html = '';
    logs.forEach(log => {
        let details = '';
        try {
            const parsed = JSON.parse(log.details);
            if (typeof parsed === 'object') {
                for (const k in parsed) {
                    details += `<strong>${escapeHtml(k)}:</strong> ${escapeHtml(parsed[k])}<br>`;
                }
            } else {
                details = escapeHtml(log.details);
            }
        } catch {
            details = escapeHtml(log.details);
        }
        html += `<tr>
            <td><input type="checkbox" name="log_ids[]" value="${log.id}"></td>
            <td>${escapeHtml(log.username)}</td>
            <td>${escapeHtml(log.action_type)}</td>
            <td>${escapeHtml(log.user_agent).substring(0,70)}</td>
            <td>${details}</td>
            <td>${escapeHtml(log.created_at)}</td>
            <td align="center">`;
        if (log.user_id == currentUserId || isAdmin) {
            html += `<a href="#" onclick="archiveLog(${log.id});return false;"><img src="<?= APP_URL?>assets/icons/icon-archive.svg" width="25"></a>&nbsp;
                     <a href="#" onclick="deleteLog(${log.id});return false;"><img src="<?= APP_URL?>assets/icons/icon-delete.svg" width="25"></a>`;
        }
        html += `</td></tr>`;
    });
    document.getElementById('logs-table-body').innerHTML = html;
}

function renderPagination(page, totalPages) {
    if (totalPages <= 1) return;
    let html = '<ul class="pagination" style="justify-content:center;">';

    // Previous
    html += `<li class="page-item${page === 1 ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(window.currentPage>1){window.currentPage=${page-1};loadLogs(${page-1});}return false;">&laquo;</a>
    </li>`;

    let shown = [];

    // Always show first page
    shown.push(1);

    // Show page before current
    if (page - 1 > 1) shown.push(page - 1);

    // Show current page
    if (page !== 1 && page !== totalPages) shown.push(page);

    // Show page after current
    if (page + 1 < totalPages) shown.push(page + 1);

    // Always show last page
    if (totalPages > 1) shown.push(totalPages);

    // Remove duplicates and sort
    shown = Array.from(new Set(shown)).sort((a, b) => a - b);

    let last = 0;
    for (let i = 0; i < shown.length; i++) {
        if (shown[i] - last > 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item${shown[i] === page ? ' active' : ''}">
            <a class="page-link${shown[i] === page ? ' active' : ''}" href="#" onclick="window.currentPage=${shown[i]};loadLogs(${shown[i]});return false;">${shown[i]}</a>
        </li>`;
        last = shown[i];
    }

    // Next
    html += `<li class="page-item${page === totalPages ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(window.currentPage<${totalPages}){window.currentPage=${page+1};loadLogs(${page+1});}return false;">&raquo;</a>
    </li>`;
    html += '</ul>';
    document.getElementById('pagination-results').innerHTML = html;
}

function loadLogs(page = currentPage) {
    // Folosește valorile globale
    const params = new URLSearchParams();
    if (filterUserId && filterUserId !== '0') params.append('user_id', filterUserId);
    if (filterStart) params.append('start_date', filterStart);
    if (filterEnd) params.append('end_date', filterEnd);
    params.append('page', page);

    fetch('api/bkd_logs.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            allLogs = data.logs;
            allUsers = data.users;
            isAdmin = data.isAdmin;
            currentUserId = data.userId;
            renderFilters(data.users);
            renderLogsTable(data.logs);
            renderPagination(data.page, data.totalPages);
            currentPage = data.page;
            // Setează filtrele la valorile actuale
            if (document.getElementById('filterUserId')) document.getElementById('filterUserId').value = filterUserId;
            if (document.getElementById('start_date')) document.getElementById('start_date').value = filterStart;
            if (document.getElementById('end_date')) document.getElementById('end_date').value = filterEnd;
            document.getElementById('checkAll').checked = false;
        });
}

function archiveLog(logId) {
    fetch('api/bkd_logs.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=archive&log_ids[]=${encodeURIComponent(logId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) loadLogs(currentPage);
        else alert(data.error || 'Eroare la arhivare!');
    });
}

function deleteLog(logId) {
    
    fetch('api/bkd_logs.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete&log_ids[]=${encodeURIComponent(logId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) loadLogs(currentPage);
        else alert(data.error || 'Eroare la ștergere!');
    });
}

function submitBulkLogs(action) {
    const checked = Array.from(document.querySelectorAll('input[name="log_ids[]"]:checked')).map(cb => cb.value);
    if (!checked.length) return alert('Selectează cel puțin un log!');
    fetch('api/bkd_logs.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=${encodeURIComponent(action)}&${checked.map(id => `log_ids[]=${encodeURIComponent(id)}`).join('&')}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) loadLogs(currentPage);
        else alert(data.error || 'Eroare la acțiunea bulk!');
    });
}

function toggleAllLogs(checkbox) {
    document.querySelectorAll('input[name="log_ids[]"]').forEach(cb => cb.checked = checkbox.checked);
}

document.addEventListener('DOMContentLoaded', function() {
    window.currentPage = 1;
    loadLogs();
});

document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
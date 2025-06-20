<?php

require_once '../../config/bootstrap.php';
include APP_ROOT. 'includes/header.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>


<h2><?= lang('lang_users') ?></h2>
<div>
    <table class="articles-table" width="70%">
        <thead>
            <tr>
                <th>ID</th>
                <th><?= lang('lang_users_username') ?></th>
                <th><?= lang('lang_users_role') ?></th>
                <th><?= lang('lang_users_status') ?></th>
                <th><?= lang('lang_users_name') ?></th>
                <th><?= lang('lang_users_actions') ?></th>
            </tr>
        </thead>
        <tbody id="users-table-body">
            <!-- Populat dinamic -->
            <!--JS: renderUsersTable(data.users) -->
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">
                    <div id="pagination-results">
                        <!--JS: renderPagination(data.pagination) -->
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
let allRoles = [];
let allUsers = [];



function renderActions(user) {
    let html = '';
    if (user.status === 'pending') {
        html += `<a href="#" onclick="userAction('enable', ${user.id}); return false;"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?= lang('lang_btn_approve') ?>"></a>`;
    } else if (user.status === 'disabled') {
        html += `<a href="#" onclick="userAction('enable', ${user.id}); return false;"><img src="<?=APP_URL?>assets/icons/icon-enable.svg" class="op-icon" title="<?= lang('lang_btn_enable') ?>"></a>`;
        // Buton schimbare rol doar dacă userul e dezactivat
        html += `<button class="op-icon" onclick="showRoleDropdownInline(${user.id}, ${user.role_id})">
                    <img src="<?= APP_URL ?>assets/icons/icon-user-change-role.svg" alt="Change Role" class="op-icon" title="Change Role">
                </button>`;
    } else {
        html += `<a href="#" onclick="if(confirm('<?= lang('lang_users_msg_disable') ?> ${escapeHtml(user.username)}?')) userAction('disable', ${user.id}); return false;"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?= lang('lang_btn_disable') ?>"></a>`;
    }
    return html;
}

function renderUsersTable(users) {
    let html = '';
    users.forEach(user => {
        html += `<tr id="user-row-${user.id}">
            <td>${user.id}</td>
            <td>${escapeHtml(user.username)}</td>
            <td id="role-cell-${user.id}">${escapeHtml(user.role_label)}</td>
            <td>${escapeHtml(user.status)}</td>
            <td>${escapeHtml((user.first_name || '') + ' ' + (user.last_name || ''))}</td>
            <td align="center">${renderActions(user)}</td>
        </tr>`;
    });
    document.getElementById('users-table-body').innerHTML = html;
}

function showRoleDropdownInline(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;
    const currentRoleId = user.role_id;
    const cell = document.getElementById('role-cell-' + userId);
    const select = document.createElement('select');
    select.onchange = () => changeUserRoleInline(select, userId);

    allRoles.forEach(role => {
        const option = document.createElement('option');
        option.value = role.id;
        option.textContent = role.label;
        if (parseInt(role.id) === parseInt(currentRoleId)) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    cell.innerHTML = '';
    cell.appendChild(select);
}

function changeUserRoleInline(selectEl, userId) {
    const roleId = selectEl.value;
    fetch('../api/bkd_users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=change_role&user_id=${encodeURIComponent(userId)}&role_id=${encodeURIComponent(roleId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const cell = document.getElementById('role-cell-' + userId);
            cell.innerHTML = `<span class="user-role-label">${escapeHtml(data.new_role_label)}</span>`;
            // Actualizează role_id și role_label în array-ul JS
            const user = allUsers.find(u => u.id == userId);
            if (user) {
                user.role_id = parseInt(roleId);
                user.role_label = data.new_role_label;
            }
        } else {
            alert(data.error || 'Eroare la salvarea rolului.');
        }
    })
    .catch(err => {
        alert('Eroare la schimbarea rolului (AJAX)');
    });
}

function userAction(action, userId) {
    fetch('../api/bkd_users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${encodeURIComponent(action)}&user_id=${encodeURIComponent(userId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadUsers(window.currentPage || 1);
        } else {
            alert(data.error || 'Eroare!');
        }
    });
}

function renderPagination(page, totalPages) {
    if (totalPages <= 1) return;
    let html = '<ul class="pagination" style="justify-content:center;">';
    html += `<li class="page-item${page === 1 ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(window.currentPage>1){window.currentPage=${page-1};loadUsers(${page-1});}return false;">&laquo;</a>
    </li>`;
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item${i === page ? ' active' : ''}">
            <a class="page-link${i === page ? ' active' : ''}" href="#" onclick="window.currentPage=${i};loadUsers(${i});return false;">${i}</a>
        </li>`;
    }
    html += `<li class="page-item${page === totalPages ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(window.currentPage<${totalPages}){window.currentPage=${page+1};loadUsers(${page+1});}return false;">&raquo;</a>
    </li>`;
    html += '</ul>';
    document.getElementById('pagination-results').innerHTML = html;
}

function loadUsers(page = 1) {
    fetch(`../api/bkd_users.php?page=${page}`)
        .then(res => res.json())
        .then(data => {
            allRoles = data.roles;
            allUsers = data.users; // Actualizează array-ul global
            renderUsersTable(data.users);
            renderPagination(data.page, data.totalPages);
        });
}
document.addEventListener('DOMContentLoaded', function() {
    window.currentPage = 1;
    loadUsers();
});
</script>

<?php include APP_ROOT . '/includes/footer.php'; ?>
<?php
include_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';



$ops = ['edit_user','disable_user','enable_user','delete_user','modify_user','approve_user'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    
    exit;
        
}
// Verifică dacă utilizatorul are rolul de admin sau superadmin
/*
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'superadmin'])) {
    header('Location: /');
    exit;
}
*/

$lang = $_SESSION['settings']['language'] ?? 'en';
// Mapare rapidă dacă ai coduri locale
if ($lang === 'ro') $lang = 'ro';
if ($lang === 'en') $lang = 'en-GB';


$allRoles = getAllRoles();


//error_log('all roles: ' . var_export($allRoles, true));

?>
<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>

<div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 20px; margin-bottom: 10px;">
    <!-- Breadcrumb on the left -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb" style="margin-bottom: 0;">
            <br>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_users') ?>
            </li>
        </ol>
    </nav>
    
    <!-- Filter on the right -->
    <div id="users-filters" style="border: 0px solid #ccc; border-radius: 8px; padding: 10px 15px; background-color: #f8f9fa;">
        <!-- JS: renderUserFilters() -->
    </div>
</div>
<hr style="height: 1px; border: none; background-color: gray; margin: 0; width: calc(100vw - 20px); margin-left: calc(-50vw + 50% + 10px);">
<br>

<table id="usersTable" class="articles-table">
    <thead>
        <tr>
            <th>ID</th>
            <th><?= lang('lang_users_username') ?></th>
            <th><?= lang('lang_prof_email') ?></th>
            <th><?= lang('lang_users_role') ?></th>
            <th><?= lang('lang_users_status') ?></th>
            <th><?= lang('lang_users_created') ?></th>
            <th><?= lang('lang_users_actions') ?></th>
        </tr>
    </thead>
    <tbody></tbody>
</table>


<!-- Overlay pentru fundal -->
<div id="modalOverlay" style="display:none;"></div>

<!-- Modal modern -->
<div id="roleModal" style="display:none;">
    <div class="modal-header-modern">
        <span class="modal-title-modern"><?= lang('lang_users_change_role_title') ?></span>
        <span class="modal-close-modern" onclick="closeRoleModal()">&times;</span>
    </div>
    <div class="modal-content-modern">
        <div id="modalUserInfo" class="modal-userinfo-modern"></div>
        <div class="modal-row-modern">
            <select id="modalRoleSelect" class="modal-input-modern"></select>
        </div>
    </div>
    <div class="modal-footer-modern">
        <button class="modal-btn-modern remove" onclick="closeRoleModal()">
            <img src="<?=APP_URL?>assets/icons/icon-cancel.svg" style="width:18px;vertical-align:middle;margin-right:6px;"> <?= lang('lang_btn_cancel') ?>
        </button>
        <button class="modal-btn-modern primary" onclick="saveRoleChange()">
            <img src="<?=APP_URL?>assets/icons/icon-save.svg" style="width:18px;vertical-align:middle;margin-right:6px;"> <?= lang('lang_btn_save') ?>
        </button>
    </div>
</div>


<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>

let currentEditUserId = null;
const allRoles = <?= json_encode($allRoles) ?>;



$(document).ready(function() {
    renderUserFilters();
    
    const table = $('#usersTable').DataTable({
        ajax: {
            url: '../api/bkd_users.php',
            type: 'GET',
            data: function(d) {
                d.role = $('#filterRole').val();
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            { data: 'username' },
            { data: 'email' },
            { data: 'role_label' }, // afișează label-ul rolului
            { data: 'status' },
            { data: 'created_at' },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    let html = '';
                    if (row.status === 'pending') {
                        html += `<a href="#" onclick="approveUser(${row.id});return false;" title="<?=lang('lang_users_btn_approve')?>">
                                    <img src="<?=APP_URL?>assets/icons/icon-approve.svg" width="24">
                                </a>`;
                    } else if (row.status === 'disabled' || row.status == 0) {
                        html += `<a href="#" onclick="enableUser(${row.id});return false;" title="<?=lang('lang_users_btn_enable')?>">
                                    <img src="<?=APP_URL?>assets/icons/icon-enable.svg" width="24">
                                </a>&nbsp;`;
                        html += `<a href="#" onclick="showRoleModal(${row.id}, ${row.role_id}, '${row.username.replace(/'/g,"\\'")}', '${row.email.replace(/'/g,"\\'")}');return false;" title="<?=lang('lang_users_btn_change_role')?>">
                                    <img src="<?=APP_URL?>assets/icons/icon-user-change-role.svg" width="24">
                                </a>`;
                    } else if (row.status === 'active' || row.status == 1) {
                        html += `<a href="#" onclick="disableUser(${row.id});return false;" title="<?=lang('lang_users_btn_disable')?>">
                                    <img src="<?=APP_URL?>assets/icons/icon-disable.svg" width="24">
                                </a>`;
                    }
                    return html;
                }
            }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        },
        serverSide: true,
        processing: true,
        createdRow: function(row, data) {
            $(row).attr('data-id', data.id);
        }
    });

    // Filtru rol
    $(document).on('change', '#filterRole', function() {
        table.ajax.reload();
    });
});

// Modal logic

function showRoleModal(userId, currentRoleId, username, email) {
    currentEditUserId = userId;
    // Populează dropdown-ul cu roluri
    let html = '';
    allRoles.forEach(function(role) {
        html += `<option value="${role.id}"${role.id == currentRoleId ? ' selected' : ''}>${role.label}</option>`;
    });
    $('#modalRoleSelect').html(html);

    // Info user
    $('#modalUserInfo').html(
        `<div style="font-size:0.97em;color:#888;"><?=lang('lang_users_change_role_text')?></div>
        <div name="username" style="font-size:0.97em;color:black;">${username}</div>`
    );

    $('#modalOverlay').show();
    $('#roleModal').show();
}

function closeRoleModal() {
    $('#roleModal').hide();
    $('#modalOverlay').hide();
    $('#modalUserInfo').html('');
    currentEditUserId = null;
}

// Închide modalul la click pe overlay
$('#modalOverlay').on('click', closeRoleModal);


  

function saveRoleChange() {
    let newRoleId = $('#modalRoleSelect').val();
    $.post('../api/bkd_users.php', { action: 'change_role', user_id: currentEditUserId, role_id: newRoleId, csrf_token: window.CSRF_TOKEN }, function(resp) {
        if (resp.success) {
            $('#usersTable').DataTable().ajax.reload();
            closeRoleModal();
        } else {
            alert(resp.error || 'Error!');
        }
    }, 'json');
}

// Enable/Disable user
function disableUser(userId) {
    //if (!confirm('Disable this user?')) return;
    $.post('../api/bkd_users.php', { action: 'disable', user_id: userId, csrf_token: window.CSRF_TOKEN }, function(resp) {
        if (resp.success) $('#usersTable').DataTable().ajax.reload();
        else alert(resp.error || 'Error!');
    }, 'json');
}

function enableUser(userId) {
    //if (!confirm('Enable this user?')) return;
    $.post('../api/bkd_users.php', { action: 'enable', user_id: userId, csrf_token: window.CSRF_TOKEN }, function(resp) {
        if (resp.success) $('#usersTable').DataTable().ajax.reload();
        else alert(resp.error || 'Error!');
    }, 'json');
}



// Approve user 
function approveUser(userId) {
    //`if (!confirm('Approve this user?')) return;
    $.post('../api/bkd_users.php', { action: 'approve', user_id: userId, csrf_token: window.CSRF_TOKEN }, function(resp) {
        if (resp.success) $('#usersTable').DataTable().ajax.reload();
        else alert(resp.error || 'Error!');
    }, 'json');
}

// Render filters (dropdown rol)
function renderUserFilters() {
    let html = `<div style="display: flex; gap: 10px; align-items: center;">
            <label style="color: #666; font-size: 14px; margin: 0;" for="filterRole"><?= lang('lang_users_role') ?>:</label>
            <select id="filterRole" name="role" style="padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; color: #666">
                <option value="">-- <?= lang('lang_users_all_roles') ?> --</option>
                <?php foreach ($allRoles as $role): ?>
                    <option value="<?= htmlspecialchars($role['name']) ?>"><?= htmlspecialchars($role['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>`;
    document.getElementById('users-filters').innerHTML = html;
}

/*
document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });
    
});
*/
</script>
<?php include APP_ROOT . 'includes/footer.php'; ?>
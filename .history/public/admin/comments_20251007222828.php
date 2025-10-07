<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$ops = ['approve_comment','edit_comment','delete_comment','reject_comment'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';
    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}

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
                <?= lang('lang_com_comments') ?>
            </li>
        </ol>
    </nav>
</div>
<hr style="height: 1px; border: none; background-color: gray; margin: 0; width: calc(100vw - 20px); margin-left: calc(-50vw + 50% + 10px);">
<br>

<div class="category-container">
    <div class="category-box-1" style="width: 100%;">
        <table id="commentsTable" class="articles-table" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= lang('lang_com_comm') ?></th>
                    <th><?= lang('lang_com_author') ?></th>
                    <th><?= lang('lang_com_article') ?></th>
                    <th><?= lang('lang_com_status') ?></th>
                    <th><?= lang('lang_com_data') ?></th>
                    <th style="width:150px"><?= lang('lang_com_actions') ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    const table = $('#commentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../api/bkd_comments.php',
            type: 'GET'
        },
        columns: [
            { data: 'rownum', orderable: false },
            { data: 'content', render: function(data) {
                return $('<div>').text(data).html(); // escape HTML
            }},
            { data: 'username' },
            { data: 'article_title', render: function(data, type, row) {
                return `<a href="../view_article.php?id=${row.article_id}&admin_source=admin_analytics">${$('<div>').text(data).html()}</a>`;
            }},
            { data: 'status', render: function(data,type,row) {
                if (data === 'pending') return `<span style="color:#e67e22;">${data}</span>`;
                if (data === 'approved') return `<span style="color:#27ae60;">${data}</span>`;
                if (data === 'rejected') return `<span style="color:#e74c3c;">${data}</span>`;
                return data;
            }},
            { data: 'created_at' },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let html = '';
                    html += `<a href="#" onclick="viewComment(${row.id});return false;" title="<?=lang('lang_com_view') ?>"><img src="<?=APP_URL?>assets/icons/icon-view.svg" class="op-icon"></a> `;
                    if (row.status === 'pending') {
                        html += `<a href="#" onclick="commentAction('approve', ${row.id});return false;" title="<?=lang('lang_com_approve') ?>"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon"></a> `;
                        html += `<a href="#" onclick="commentAction('reject', ${row.id});return false;" title="<?=lang('lang_com_reject') ?>"><img src="<?=APP_URL?>assets/icons/icon-reject.svg" class="op-icon"></a> `;
                    }
                    if (row.status === 'approved') {
                        html += `<a href="#" onclick="commentAction('delete', ${row.id});return false;" title="<?=lang('lang_com_delete') ?>"><img src="<?=APP_URL?>assets/icons/icon-delete.svg" class="op-icon"></a>`;
                    }

                    return html;
                }
            }
        ],
        order: [[0, 'desc']],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
    window.reloadCommentsTable = () => table.ajax.reload(null, false);
});

function commentAction(action, commentId) {
    if (action === 'delete' && !confirm('Ștergi acest comentariu?')) return;
    fetch('../api/bkd_comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=${encodeURIComponent(action)}&comment_id=${encodeURIComponent(commentId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) reloadCommentsTable();
        else alert(data.error || 'Eroare la acțiune!');
    });
}

function viewComment(commentId) {
    fetch(`../api/bkd_comments.php?action=view&id=${commentId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.comment) {
                alert(data.error || 'Eroare la încărcarea comentariului!');
                return;
            }
            // Info user și articol
            document.getElementById('modalCommentInfo').innerHTML =
                `<div style="display:flex;align-items:center;gap:12px;">
                    <div>
                        <div style="font-size:0.8em;"><?=lang('lang_user') ?>:
                            ${data.comment.username} | <?=lang('lang_com_modal_article') ?>: 
                            <a href="../view_article.php?id=${data.comment.article_id}&admin_source=admin_analytics" target="_blank">${data.comment.article_title}</a> | 
                            <?=lang('lang_com_modal_date')?>: ${data.comment.created_at} | 
                            <?=lang('lang_com_modal_status')?>: ${data.comment.status}
                        
                        </div>
                    </div>
                </div>`;
            // Conținut comentariu
            document.getElementById('modalCommentContent').textContent = data.comment.content;

            // Afișează modalul
            document.getElementById('modalOverlayComment').style.display = 'block';
            document.getElementById('viewCommentModal').style.display = 'block';
        });
}
function closeViewCommentModal() {
    document.getElementById('viewCommentModal').style.display = 'none';
    document.getElementById('modalOverlayComment').style.display = 'none';
    document.getElementById('modalCommentInfo').innerHTML = '';
    document.getElementById('modalCommentContent').textContent = '';
}

const overlay = document.getElementById('modalOverlayComment');
if (overlay) overlay.onclick = closeViewCommentModal;

</script>

<!-- Overlay pentru fundal -->
<div id="modalOverlayComment" style="display:none;"></div>

<!-- Modal vizualizare comentariu -->
<div id="viewCommentModal" style="display:none;">
    <div class="modal-header-comments">
        <span class="modal-title-comments"><?= lang('lang_com_view_title') ?></span>
        <span class="modal-close-modern" onclick="closeViewCommentModal()">&times;</span>
    </div>
    <div class="modal-content-comments">
        
        <div class="modal-row-comments">
            
            <div id="modalCommentContent" class="modal-input-comments" ></div>
        
        </div>
        <div id="modalCommentInfo" class="modal-userinfo-comments"></div>
    </div>
    <div class="modal-footer-comments">
        <button class="modal-btn-comments" onclick="closeViewCommentModal()"><?= lang('lang_btn_close') ?></button>
    </div>
</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>
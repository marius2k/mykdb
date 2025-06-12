<?php 

require_once '../../config/bootstrap.php';


include APP_ROOT. 'includes/header.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>




<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>

<h2><?= lang('lang_com_admin_comments') ?></h2>
<div id="comments-table"></div>





<script>



function renderCommentsTable(comments, page, totalPages) {
    if (!comments.length) {
        return `<p><?= lang('lang_com_msg_nocommw') ?></p>`;
    }
    let html = `<table class="articles-table" width="90%">
    <thead>
      <tr>
        <th><?= lang('lang_com_article') ?></th>
        <th><?= lang('lang_com_user') ?></th>
        <th><?= lang('lang_com_comm') ?></th>
        <th><?= lang('lang_com_data') ?></th>
        <th><?= lang('lang_com_status') ?></th>
        <th><?= lang('lang_com_actions') ?></th>
      </tr>
    </thead>
    <tbody>`;
    comments.forEach(function(c) {
        html += `<tr>
          <td width="20%">
            <a href="../view_article.php?id=${c.article_id}#comments">
              ${escapeHtml(c.title)}
            </a>
          </td>
          <td width="10%">${escapeHtml(c.username)}</td>
          <td width="40%">${escapeHtml(c.content).replace(/\n/g, '<br>')}</td>
          <td width="10%">${escapeHtml(c.created_at)}</td>
          <td>${escapeHtml(c.status)}</td>
          <td width="20%" align="center">
            ${renderActions(c)}
          </td>
        </tr>`;
    });
    html += `</tbody>`;
    if (totalPages > 1) {
        html += `<tfoot>
          <tr>
            <td colspan="7">
              <div id="pagination-results"></div>
            </td>
          </tr>
        </tfoot>`;
    }
    html += `</table>`;
    return html;
}

function renderActions(c) {
    let html = '';
    // Aproba
    if ((c.status === 'pending' || c.status === 'rejected')) {
        html += `<a href="#" onclick="commentAction('approve', ${c.id}); return false;"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-approve.svg" title="<?= lang('lang_btn_approve') ?>"></a>`;
    }
    // Editare
    html += `<a href="edit_comment.php?cid=${c.id}"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-edit.svg" title="<?= lang('lang_btn_edit') ?>"></a>`;
    // Respinge
    if (c.status === 'approved') {
        html += `<a href="#" onclick="commentAction('reject', ${c.id}); return false;"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-reject.svg" title="<?= lang('lang_btn_reject') ?>"></a>`;
    }
    // Șterge
    html += `<a href="#" onclick="if(confirm('Sigur?')) commentAction('delete', ${c.id}); return false;"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-delete.svg" title="<?= lang('lang_btn_delete') ?>"></a>`;
    return html;
}

function loadComments(page = 1) {
    fetch(`../api/bkd_comments.php?page=${page}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('comments-table').innerHTML = renderCommentsTable(data.comments, data.page, data.totalPages);
            renderPagination(data.page, data.totalPages);
        });
}

function commentAction(action, commentId) {
    fetch('../api/bkd_comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=${encodeURIComponent(action)}&comment_id=${encodeURIComponent(commentId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadComments(window.currentPage || 1);
        } else {
            alert(data.error || 'Eroare!');
        }
    });
}

function renderPagination(page, totalPages) {
    if (totalPages <= 1) return;
    let html = '<ul class="pagination" style="justify-content:center;">';

    // Buton "Previous"
    html += `<li class="page-item${page === 1 ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(window.currentPage>1){window.currentPage=${page-1};loadComments(${page-1});}return false;">&laquo; Prev</a>
    </li>`;

    // Pagini numerotate
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item${i === page ? ' active' : ''}">
            <a class="page-link${i === page ? ' active' : ''}" href="#" onclick="window.currentPage=${i};loadComments(${i});return false;">${i}</a>
        </li>`;
    }

    // Buton "Next"
    html += `<li class="page-item${page === totalPages ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="if(window.currentPage<${totalPages}){window.currentPage=${page+1};loadComments(${page+1});}return false;">Next &raquo;</a>
    </li>`;

    html += '</ul>';
    document.getElementById('pagination-results').innerHTML = html;
}


document.addEventListener('DOMContentLoaded', function() {
    window.currentPage = 1;
    loadComments();
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
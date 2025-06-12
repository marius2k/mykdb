<?php

require_once '../../config/bootstrap.php';
include APP_ROOT. 'includes/header.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>




<script>window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";</script>

<div class="category-container">
    <div class="category-box-2" style="width: fit-content">
        <div class="operations-bar">
            <div>
                <button  class="btn-flat btn-toggle-form" onclick="toggleAddFormHide2('form-add-article',this)" title="Add Article">
                    <img id="toggle-arrow-icon" src="../../assets/icons/icon-arrow-down.svg" alt="Add Article" class="op-icon">
                    <?= lang('lang_create_article') ?>&nbsp;&nbsp;      
                </button>
            </div>
        </div>
        <div class="form-container-1">
            <div id="form-add-article" class="form-box" style="display: none; width: 100%; padding: 30px;">
                <form id="add_article" class="article-form">
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div class="form-group" style="display: flex;">
                            <label for="title" style=" width: 30%;"><?= lang('lang_art_title') ?>:</label>
                            <input style="width: 70%" type="text" id="title" name="title" placeholder="<?= lang('lang_art_title') ?>" required>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="form-group" style="display: flex; flex-direction: row;">
                                <label style="width: 30%" for="category"><?= lang('lang_article_category') ?>:</label>
                                <select name="category_id" id="add_category_select" class="select2-icon" style="width:70%" required>
                                    <option value="">--<?= lang('lang_cat_select') ?> --</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: row; gap: 10px;">
                                <label style="width:30%" for="publish_at"><?= lang('lang_art_publish_at') ?>:</label>
                                <input style="width:70%" type="datetime-local" name="publish_at" id="publish_at" class="form-control">
                            </div>
                        </div>
                        <div style="padding: 20px" class="form-group">
                            <label style="align: left;" for="content"><?= lang('lang_create_article_content') ?></label>
                            <input id="content" type="hidden" name="content">
                            <trix-editor input="content"></trix-editor>
                        </div>
                        <div>
                            <button type="button" onclick="submitArticle('draft')" class="btn btn-outline-grey"><?= lang('lang_create_article_draft') ?></button>
                            <button type="button" onclick="submitArticle('submit')" class="btn btn-outline-grey"><?= lang('lang_create_article_submit') ?></button>    
                        </div>
                    </div>
                </form>
                <div id="article-feedback" class="mt-2 text-success d-none"></div>
            </div>
        </div>
    </div>

    <div class="category-box-1" style="width: 80%;">
        <table class="articles-table" width="80%">
            <thead>
                <tr>
                    <th align="center">#</th>
                    <th><?= lang('lang_art_title') ?></th>
                    <th><?= lang('lang_art_author') ?></th>
                    <th><?= lang('lang_art_category') ?></th>
                    <th><?= lang('lang_art_status') ?></th>
                    <th><?= lang('lang_art_publish_at') ?></th>
                    <th align="center"><?= lang('lang_art_actions') ?></th>
                </tr>
            </thead>
            <tbody id="articles-table-body"></tbody>
            <tfoot>
                <tr>
                    <td colspan="7">
                        <div id="pagination-results"></div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let categories = [];

function escapeHtml(text) {
    var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function loadCategories() {
    fetch('../api/bkd_select_categories.php')
        .then(res => res.json())
        .then(data => {
            categories = data;
            let select = document.getElementById('add_category_select');
            select.innerHTML = `<option value="">--<?= lang('lang_cat_select') ?> --</option>`;
            data.forEach(c => {
                select.innerHTML += `<option value="${c.id}" data-img="/mykdb/assets/icons/categories/${c.icon}">${escapeHtml(c.name)}</option>`;
            });
            // Reinițializează Select2
            if (window.$ && $(select).select2) {
                $(select).select2({
                    placeholder: "<?= lang('lang_cat_select') ?>",
                    templateResult: formatWithIcon2,
                    templateSelection: formatWithIcon2,
                    allowClear: true
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

        html += `<tr>
            <td align="center">${x}</td>
            <td>${escapeHtml(a.title)}</td>
            <td>${escapeHtml(a.username)}</td>
            <td>${escapeHtml(a.category)}</td>
            <td>${escapeHtml(a.status)}</td>
            <td>${publishAtHtml}</td>
            <td align="center">
                <a href="../view_article.php?id=${a.id}"><img src="<?=APP_URL?>assets/icons/icon-view.svg" class="op-icon" title="<?= lang('lang_btn_view') ?>"></a>
                <a href="../edit_article.php?id=${a.id}"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_btn_edit') ?>"></a>
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

function submitArticle(submitType) {
    const form = document.getElementById('add_article');
    const formData = new FormData(form);
    formData.append('action', 'add_article');
    formData.append('submit_type', submitType);
    formData.append('csrf_token', window.CSRF_TOKEN);

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
        if (data.success) loadArticles(currentPage);
        else alert(data.error || 'Eroare la schimbarea datei!');
    });
}

function articleAction(action, articleId, publishAt = '') {
    let body = `action=${encodeURIComponent(action)}&article_id=${encodeURIComponent(articleId)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`;
    if (action === 'approve' && publishAt) {
        body += `&publish_at=${encodeURIComponent(publishAt)}`;
    }
    fetch('../api/bkd_articles.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) loadArticles(currentPage);
        else alert(data.error || 'Eroare la acțiune!');
    });
}

function toggleAddFormHide2(formId, btn) {
    const form = document.getElementById(formId);
    const arrow = btn.querySelector('#toggle-arrow-icon');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        if (arrow) arrow.src = '../../assets/icons/icon-arrow-up.svg';
        form.scrollIntoView({behavior: "smooth"});
    } else {
        form.style.display = 'none';
        if (arrow) arrow.src = '../../assets/icons/icon-arrow-down.svg';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadArticles();
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
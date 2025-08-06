<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$ops = ['edit_article','disable_article','enable_article','create_article'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "public/login.php");
    exit;
}


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
            <button class="btn-flat" onclick="openArticleModal()" title="Add Article">
                <img src="../../assets/icons/icon-create-article.svg" alt="Add Article" class="op-icon">
                <?= lang('lang_create_article') ?>
            </button>
        </div>
    </div>

    <div class="category-box-1" style="width: 100%;">
        <table id="articlesTable" class="articles-table" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= lang('lang_art_title') ?></th>
                    <th><?= lang('lang_art_author') ?></th>
                    <th><?= lang('lang_art_category') ?></th>
                    <th><?= lang('lang_art_status') ?></th>
                    <th><?= lang('lang_art_publish_at') ?></th>
                    <th><?= lang('lang_art_actions') ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal Create Article -->
<div id="modal-add-article" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:70%;margin:auto;position:relative;">
    <span class="close" onclick="closeArticleModal()" style="position:absolute;top:10px;right:20px;font-size:2em;cursor:pointer;">&times;</span>
    <h4 id="modal-title"></h4><br>


    <form id="add_article" class="article-form" autocomplete="off">
        
            <div class="custom-box-2" style="width: 100%;">
                <span class="corner-label-2" ><?= lang('lang_art_title') ?></span>
                <div class="box-content-2" style="padding: 20px">
                    <input type="text" id="title" name="title" style="font-size: medium;" required>
                </div>
            </div>
            <div style="display: flex; flex-direction: row; gap: 10px; width: 100%;">
                    <div style="width: 50%;" class="custom-box-2">
                        <span class="corner-label-2"><?= lang('lang_article_category') ?></span>
                        <div class="box-content-2" style="width: 100%; padding: 20px">
                            <select name="category_id" id="add_category_select" class="select2-icon" style="width: 100%;" required>
                                <option value="">--<?= lang('lang_cat_select') ?> --</option>
                            </select>
                        </div>
                    </div>
                    <div style="width: 50%;" class="custom-box-2">
                        <span class="corner-label-2"><?= lang('lang_art_publish_at') ?></span>
                        <div class="box-content-2" style="height:auto; padding:20px">   
                            <input type="datetime-local" name="publish_at" id="publish_at" class="form-control">
                        </div>
                    </div>
            </div>
            
            <div style="padding: 20px width: 100%;" class="custom-box-2">
                <span class="corner-label-2" style="align: left;"><?= lang('lang_create_article_content') ?></span>
                <div class="box-content-2" style="padding: 20px">
                    <textarea id="summernote" name="content"></textarea>
                </div>
            </div>

            <div style="padding: 20px width: 100%;" class="custom-box-2">
                <span class="corner-label-2" style="align: left;"><?= lang('lang_create_article_tags') ?></span>
                <div class="box-content-2" style="padding: 20px">
                    <input type="text" id="tags" name="tags">
                </div>
            </div>
            <div>
                <button type="button" onclick="submitArticle2('draft')" class="btn btn-outline-grey"><?= lang('lang_create_article_draft') ?></button>
                <button type="button" onclick="submitArticle2('submit')" class="btn btn-outline-grey"><?= lang('lang_create_article_submit') ?></button>
            </div>
        
    </form>
    
    
    <div id="article-feedback" class="mt-2 text-success d-none"></div>
  </div>
</div>


<script>

$(document).ready(function() {
    const table = $('#articlesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../api/bkd_articles.php',
            type: 'GET'
        },
        columns: [
            { data: 'rownum', orderable: false },
            { data: 'title' },
            { data: 'username' },
            { data: 'category' },
            { data: 'status' },
            { 
                data: 'publish_at',
                render: function(data, type, row) {
                    if (row.status === 'approved' && data) {
                        const pubDate = new Date(data.replace(' ', 'T'));
                        const now = new Date();
                        if (pubDate > now) {
                            return `<span style="color: #e67e22;" title="<?=lang('lang_publish_at')?>">${data}</span>`;
                        } else {
                            return `<span>${data}</span>`;
                        }
                    } else if (row.status === 'pending') {
                        return `<input type="datetime-local" value="${data ? data.replace(' ', 'T') : ''}" onchange="changePublishAt(this.value, ${row.id})">`;
                    } else {
                        return `<span>${data || ''}</span>`;
                    }
                }
            },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let html = '';
                    html += `<a href="../view_article.php?id=${row.id}"><img src="<?=APP_URL?>assets/icons/icon-view.svg" class="op-icon" title="<?= lang('lang_btn_view') ?>"></a> `;
                    html += `<a href="#" onclick="openEditArticleModal(${row.id});return false;"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_btn_edit') ?>"></a> `;
                    if (row.status === 'pending') {
                        html += `<a href="#" onclick="articleAction('approve', ${row.id}, '${row.publish_at}');return false;"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?= lang('lang_btn_approve') ?>"></a>`;
                    } else {
                        html += `<a href="#" onclick="articleAction('disable', ${row.id});return false;"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?= lang('lang_btn_disable') ?>"></a>`;
                    }
                    return html;
                }
            }
        ],
        order: [[0, 'asc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
    window.reloadArticlesTable = () => table.ajax.reload(null, false);
});

let currentPage = 1;
let totalPages = 1;
let categories = [];



function loadCategories() {
    fetch('../api/bkd_select_categories.php')
        .then(res => res.json())
        .then(data => {
            categories = data;
            let select = document.getElementById('add_category_select');
            select.innerHTML = `<option value="">--<?= lang('lang_cat_select') ?> --</option>`;
            data.forEach(c => {
                select.innerHTML += `<option value="${c.id}" data-img="/assets/icons/categories/${c.icon}">${escapeHtml(c.name)}</option>`;
            });
            // Reinițializează Select2
            if (window.$ && $(select).select2) {
                $(select).select2({
                    placeholder: "<?= lang('lang_cat_select') ?>",
                    templateResult: formatWithIcon2,
                    templateSelection: formatWithIcon2,
                    allowClear: true,
                    dropdownParent: $('#modal-add-article')
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
                <a href="#" onclick="openEditArticleModal(${a.id});return false;"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?= lang('lang_btn_edit') ?>"></a>
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

function submitArticle2(submitType) {
    const form = document.getElementById('add_article');
    // Ia HTML-ul din Summernote
    if ($('#summernote').summernote) {
        form.querySelector('textarea[name="content"]').value = $('#summernote').summernote('code');
    }
    const formData = new FormData(form);
    formData.append('csrf_token', window.CSRF_TOKEN);

    // Verifică dacă e edit sau add
    const editId = form.getAttribute('data-edit-id');
    if (editId) {
        formData.append('action', 'edit_article');
        formData.append('article_id', editId);
    } else {
        formData.append('action', 'add_article');
    }
    formData.append('submit_type', submitType);

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
            form.removeAttribute('data-edit-id');
            setTimeout(closeArticleModal, 1200);
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
        if (data.success) reloadArticlesTable();
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
        if (data.success) reloadArticlesTable();
        else alert(data.error || 'Eroare la acțiune!');
    });
}

// Modal logic
function openArticleModal() {
    document.getElementById('modal-title').textContent = '<?=lang('lang_create_article')?>';
    document.getElementById('modal-add-article').style.display = 'block';


    // Încarcă categoriile la deschiderea modalului!
    loadCategories();

    setTimeout(() => {
        // Inițializează Summernote dacă nu e deja inițializat
        if (!$('#summernote').next('.note-editor').length) {
            $('#summernote').summernote({
                height: 250,
                placeholder: 'Scrie conținutul articolului...',
                codemirror: { // codemirror options
                    theme: 'default',
                    mode: 'text/html',
                    lineNumbers: true
                },
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video','code']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        }
        const firstInput = document.getElementById('modal-add-article').querySelector('input,textarea,select');
        if (firstInput) firstInput.focus();
    }, 200);

    // initialize custom boxes from the modal form;
    const allCustomBoxes = document.querySelectorAll('.custom-box-2');
    allCustomBoxes.forEach(box => {
      initializeCustomBox2(box);
    });

}

function openEditArticleModal(articleId) {

    //console.log('openEditArticleModal', articleId);
    // open modal

    openArticleModal();
    document.getElementById('modal-title').textContent = '<?=lang('lang_edit_article')?>';
    // fetch article data

    fetch(`../api/bkd_articles.php?action=get_article&id=${articleId}`)
        .then(res => res.json())
        .then(data => {
            // Completează câmpurile formularului cu datele articolului
            document.getElementById('title').value = data.article.title;
            $('#add_category_select').val(data.article.category_id).trigger('change');
            document.getElementById('publish_at').value = data.article.publish_at ? data.article.publish_at.replace(' ', 'T') : '';
            $('#summernote').summernote('code', data.article.content);
            // Completează și TAGURILE aici:
            document.getElementById('tags').value = data.article.tags ? data.article.tags.join(', ') : '';  
            // Marchează formularul ca "edit"
            document.getElementById('add_article').setAttribute('data-edit-id', articleId);

            // Deschide modalul
            //openArticleModal();
        });
}

function closeArticleModal() {
    document.getElementById('modal-add-article').style.display = 'none';
    document.getElementById('add_article').reset();
    document.getElementById('article-feedback').classList.add('d-none');
    document.getElementById('add_article').removeAttribute('data-edit-id');
    // Reset Summernote
    if ($('#summernote').summernote) {
        $('#summernote').summernote('reset');
    }
    // Reset Select2
    if (window.$ && $('#add_category_select').select2) {
        $('#add_category_select').val('').trigger('change');
    }
}


window.onclick = function(event) {
    const modal = document.getElementById('modal-add-article');
    if (event.target === modal) closeArticleModal();
}

/*
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadArticles();
});
*/
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
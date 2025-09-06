<?php
require_once '../config/bootstrap.php';
require_login();
/*
$ops = ['view_own_logs', 'view_all_logs'];
if (!hasPermission($_SESSION['user']['id'], $ops)) {
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';
    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;
}
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];



include APP_ROOT . 'includes/header.php';


//error_log("Translations:" $translations['lang_create_article_error'] ?? 'N/A');
?>

<script>
window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
window.USER_ROLE = "<?= $_SESSION['user']['role'] ?>";
</script>

<br>
<div id="dashboard-root">
    

    <div class="loading">
        
             Se încarcă dashboard-ul...
    
    </div>
</div>


<!-- Modal Overlay pentru editare articol -->
<div id="modalOverlayAddArticle" style="display:none;"></div>

<!-- Modal Create Article -->
<div id="modal-add-article" class="modal" style="display:none;">
    <div class="modal-header-edit" style="border-bottom: 1px solid #d1d1d1ff;padding-bottom: 12px;">
        <span class="modal-title-edit" style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;font-size:2em; line-height: 0.8;" id="modal-title"><?=lang('lang_art_edit')?></span>
        <span class="modal-close-edit" onclick="closeArticleModal()">&times;</span>
    </div>
    <br>
    <div class="modal-content-edit" style="padding-top: 0px;">
        <form id="add_article" class="article-form" autocomplete="off">
                 
                <!-- Title Section - Full Width -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="title" class="form-label"><?= lang('lang_art_title') ?></label>
                        <input type="text" id="title" name="title" class="form-input" required>
                    </div>
                </div>
                
                <!-- Compact Row: Version, Category, Publication Date -->
                <div class="form-section">
                    <div class="form-row-compact">
                        <div class="form-group-compact">
                            <label for="version" class="form-label"><?= lang('lang_art_version') ?></label>
                            <input type="text" id="version" name="version" class="form-input" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        </div>
                        <div class="form-group-compact">
                            <label for="add_category_select" class="form-label"><?= lang('lang_article_category') ?></label>
                            <select name="category_id" id="add_category_select" class="form-select select2-icon" required>
                                <option value="">--<?= lang('lang_cat_select') ?> --</option>
                            </select>
                        </div>
                        <div class="form-group-compact">
                            <label for="publish_at" class="form-label"><?= lang('lang_art_publish_at') ?></label>
                            <input type="datetime-local" name="publish_at" id="publish_at" class="form-input">
                        </div>
                    </div>
                </div>
                
                <!-- Article Content Section -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="summernote" class="form-label"><?= lang('lang_create_article_content') ?></label>
                        <textarea id="summernote" name="content" class="form-input"></textarea>
                    </div>
                </div>

                <!-- Tags Section -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="tags" class="form-label"><?= lang('lang_create_article_tags') ?></label>
                        <input type="text" id="tags" name="tags" class="form-input">
                    </div>
                </div>
                    
                <!-- Change Note Section -->
                <div class="form-section">
                    <div class="form-group-full">
                        <label for="change_note" class="form-label">Change Note</label>
                        <textarea id="change_note" name="change_note" rows="3" class="form-input" placeholder="Descrie modificările făcute în această versiune..."></textarea>
                        <small class="form-helper">Opțional - ajută la urmărirea modificărilor</small>
                    </div>
                </div>

        </form>
        <div id="article-feedback" class="mt-2 text-success d-none"></div>
    </div>
    <div class="modal-footer-edit">
        <button type="button" onclick="submitArticle2('draft')" class="modal-btn-edit"><?= lang('lang_create_article_draft') ?></button>
        <button type="button" onclick="submitArticle2('submit')" class="modal-btn-edit"><?= lang('lang_create_article_approve') ?></button>
        <button type="button" onclick="closeArticleModal()" class="modal-btn-edit"><?= lang('lang_btn_cancel') ?></button>
    </div>
</div>
<?php include APP_ROOT . 'includes/footer.php'; ?>

<script>
$(function() {
    $.getJSON('api/bkd_dashboard.php', function(data) {
        if (data.error) {
            $('#dashboard-root').html('<div class="error">' + data.error + '</div>');
            return;
        }
        $('#dashboard-root').html(data.html);
        $('#art_top_view').text(data.titleTopViewed);
        $('#art_top_like').text(data.titleTopLiked);
        $('#art_top_com').text(data.titleTopCommented);

        // Inițializează graficele dacă există date
        if (typeof data.articlesChart === 'object') {
            renderChart('articlesChart', data.articlesChart);
        }
        if (typeof data.commentsChart === 'object') {
            renderChart('commentsChart', data.commentsChart);
        }

        // Inițializează custom-box-1 după ce HTML-ul a fost inserat
        document.querySelectorAll('.custom-box-1').forEach(box => {
            initializeCustomBox1(box);
        });
    });
});

/**
 * Approve article; status = pending->approved
 */
function approveArticle(articleId, version = 1) {
    

    fetch(`api/bkd_articles.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'article_id=' + encodeURIComponent(articleId) + '&version=' + encodeURIComponent(version) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
                //alert('Articolul a fost aprobat cu succes!');
                location.reload();
        } else {
            alert(data.error || 'Eroare la aprobat!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la comunicarea cu serverul! Detalii: ' + error.message);
    });
}




// Submit an article for approval (draft->pending) from the Draft Articles section in Dashboard

function submitForApproval(articleId, version = 1) {
   
    /*
    const formData = new FormData();
    formData.append('article_id', articleId);
    formData.append('version', version);
    
    */
    //formData.append('csrf_token', window.CSRF_TOKEN);

    fetch('api/bkd_submit_approval.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'article_id=' + encodeURIComponent(articleId) + '&version=' + encodeURIComponent(version) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            //alert('Article submitted for approval successfully!');
            // Refresh dashboard sau doar secțiunea draft articles
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

// Funcție pentru a desena graficele Chart.js
function renderChart(canvasId, chartData) {
    if (!document.getElementById(canvasId)) return;
    new Chart(document.getElementById(canvasId).getContext('2d'), chartData);
}

function deleteNotif(id) {
    fetch('api/bkd_delete_notification.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('notif-' + id);
            if (row) row.remove();
        } else {
            alert(data.error || 'Eroare la ștergere!');
        }
    });
}

function markRead(id) {
    fetch('api/bkd_mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('notif-' + id);
            if (row) row.remove(); // elimină rândul din tabel
        } else {
            alert(data.error || 'Eroare la marcare!');
        }
    });
}

function deleteComment(id) {
    fetch('api/bkd_delete_comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error in delete comment!');
        }
    });
}

function approveComment(id) {
    fetch('api/bkd_approve_comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
    })
    .then(res => res.json())
    .then(data => {
         if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error in approve comment!');
        }
    });

}

// save article as Draft or send it for approval from the edit form (modal)

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
    const editVersion = form.getAttribute('data-edit-version');
    const isOnlineVersion = form.getAttribute('data-is-online-version');
    
    if (editId && editVersion) {
        // Folosește direct informația din atributul data-is-online-version
        const isEditingOnlineVersion = parseInt(isOnlineVersion) || 0;
        
        // Debug logging
        /*
        console.log('Online version check (using is_online field):', {
            editVersion: editVersion,
            isOnlineVersion: isOnlineVersion,
            isEditingOnlineVersion: isEditingOnlineVersion,
            description: isEditingOnlineVersion ? 'Editing ONLINE version (is_online=1)' : 'Editing NON-ONLINE version (is_online=0)'
        });
        */

        formData.append('action', 'edit_article');
        formData.append('article_id', editId);
        formData.append('base_version', editVersion);
        formData.append('is_editing_online_version', isEditingOnlineVersion);
    } else {
        formData.append('action', 'add_article');
        // Pentru articole noi, nu se aplică conceptul de versiune online
        formData.append('is_editing_online_version', 0);
    }
    
    formData.append('submit_type', submitType);

    //console.log('CSRF_Token', formData.get('csrf_token'));
    console.log('csrf_token', window.CSRF_TOKEN);
    console.log('CSRF Token Sesion: ' + "<?= $_SESSION['csrf_token'] ?>");
    
    console.log('Form Data:', Array.from(formData.entries()));

    fetch('api/bkd_articles.php', {
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
            form.removeAttribute('data-edit-version');
            form.removeAttribute('data-online-version');
            form.removeAttribute('data-is-online-version');
            setTimeout(closeArticleModal, 1200);
            
        } else {
            document.getElementById('article-feedback').textContent = data.error || 'Eroare la salvare!';
            document.getElementById('article-feedback').classList.remove('d-none');
        }
    });
}


function loadCategories() {
    fetch('api/bkd_select_categories.php')
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

function loadArticleVersions(articleId, currentVersion) {
    // Pentru că versiunea nu este editabilă, doar setăm valoarea în input
    const versionInput = document.getElementById('version');
    versionInput.value = 'v' + currentVersion;
    
    // Nu mai avem nevoie de onchange pentru versiune deoarece nu se poate modifica
    // Versiunea se selectează din tabelul principal
}

// Modal logic
function openArticleModal() {
    document.getElementById('modal-title').textContent = '<?=lang('lang_create_article')?>';
    document.getElementById('modalOverlayAddArticle').style.display = 'block';
    document.getElementById('modal-add-article').style.display = 'flex';


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
    //const allCustomBoxes = document.querySelectorAll('.custom-box-2');
    //allCustomBoxes.forEach(box => {
    //  initializeCustomBox2(box);
    //});

}

function openEditArticleModal(articleId, version = null) {
    openArticleModal();
    
    //console.log('Opening edit modal for article:', articleId, 'version:', version);

    document.getElementById('modal-title').textContent = '<?=lang('lang_edit_article')?>';
    
    // Folosește versiunea selectată din dropdown sau versiunea implicită
    //const targetVersion = version || getSelectedVersion(articleId) || 1;
    
    // Încarcă direct versiunea pentru editare
    fetch(`api/bkd_articles.php?action=get_version&id=${articleId}&version=${version}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to load version data:', data);
                return;
            }
            
            // Stochează informația despre versiunea online în formular
            const form = document.getElementById('add_article');
            form.setAttribute('data-online-version', data.is_online ? version : 'unknown');
            
            /*
            console.log('Edit modal setup:', {
                targetVersion: targetVersion,
                isOnlineVersion: data.is_online,
                editingOnlineVersion: data.is_online ? 1 : 0
            });
            */


            // Completează câmpurile formularului cu datele versiunii selectate
            document.getElementById('title').value = data.title || '';
            $('#summernote').summernote('code', data.content || '');
            document.getElementById('change_note').value = data.change_note || '';
            
            // Marchează formularul ca "edit" și salvează versiunea
            form.setAttribute('data-edit-id', articleId);
            form.setAttribute('data-edit-version', version);
            form.setAttribute('data-is-online-version', data.is_online ? 1 : 0);
            
            // Populează dropdown-ul de versiuni
            loadArticleVersions(articleId, version);
        });
    
    // Încarcă alte date necesare (categorii, etc.)
    fetch(`api/bkd_articles.php?action=get_article&id=${articleId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#add_category_select').val(data.article.category_id).trigger('change');
                document.getElementById('publish_at').value = data.article.publish_at ? data.article.publish_at.replace(' ', 'T') : '';
                document.getElementById('tags').value = data.article.tags ? data.article.tags.join(', ') : '';
            }
        });
}

function closeArticleModal() {
    document.getElementById('modalOverlayAddArticle').style.display = 'none';
    document.getElementById('modal-add-article').style.display = 'none';
    document.getElementById('add_article').reset();
    document.getElementById('article-feedback').classList.add('d-none');
    
    // Curăță toate atributele de editare
    const form = document.getElementById('add_article');
    form.removeAttribute('data-edit-id');
    form.removeAttribute('data-edit-version');
    form.removeAttribute('data-online-version');
    form.removeAttribute('data-is-online-version');
    
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


</script>



<?php
// filepath: /home/marius/work/projects/mykdb/public/favorites.php
include_once '../config/bootstrap.php';
include_once APP_ROOT . 'includes/header.php';

// Redirect dacă nu e logat
if (empty($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="container main-content" style="text-align: center;">
    <div class="custom-box-1">
        <span class="corner-label-1" style="font-size: 24px;"><img src="/assets/icons/icon-bookmark-full.svg" style="width:30px;vertical-align:middle;margin-right:6px;"><?=lang('lang_favorites')?></span>
        <div class="box-content-1" id="favorites-list" style="padding: 20px;">
            <div style="text-align:center; color:#888; padding:30px;" id="favorites-loading">
                 Loading...
            </div>
        </div>
    </div>
</div>

<script>
function renderFavorites(articles) {
    if (!articles.length) {
        document.getElementById('favorites-list').innerHTML = '<div style="color:#888; text-align:center; padding:30px;">Nu ai niciun articol favorit.</div>';
        return;
    }
    let html = '<div >';
    articles.forEach(a => {
        html += `
       
            <div style="font-size: 18px; margin-top: 8px; margin-bottom: 8px; padding-left: 40px">
                    <img src="/assets/icons/categories/${a.icon}" title="${a.category}" style="width:24px;vertical-align:middle;margin-right:6px;">
                    <a href="view_article.php?id=${a.id}" style="color:#048eb1;text-decoration:none;">
                        ${a.title}
                    </a>
            </div>
        `;
    });
    html += '</div>';
    document.getElementById('favorites-list').innerHTML = html;
}

fetch('api/bkd_list_bookmarks.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderFavorites(data.bookmarks);
        } else {
            document.getElementById('favorites-list').innerHTML = '<div style="color:red; text-align:center; padding:30px;">Eroare: ' + (data.error || 'Nu s-au putut încărca favoritele.') + '</div>';
        }
    });



    document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });
    
});
</script>

<?php include_once APP_ROOT . 'includes/footer.php'; ?>
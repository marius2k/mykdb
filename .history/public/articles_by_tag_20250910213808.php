<?php
require_once '../config/bootstrap.php';
include_once '../includes/header.php';

// Preia tag-ul din URL
$tag = $_GET['tag'] ?? '';
if (empty($tag)) {
    echo '<div class="container"><div class="alert alert-danger">' . lang('lang_tag_required_for_filter') . '</div></div>';
    include APP_ROOT . 'includes/footer.php';
    exit;
}

?>

<div class="container" style="max-width: 80%; margin: 20px auto;">
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center;">
        <h2><?= lang('lang_articles_with_tag') ?> <span class="tag-badge" style="font-size: 1.1em;"><?= htmlspecialchars($tag) ?></span></h2>
        <a href="javascript:history.back()" style="text-decoration: none;" title="<?= lang('lang_back_to_previous_page') ?>">
            <img src="<?=APP_URL?>assets/icons/icon-arrow-up.svg" alt="<?= lang('lang_back_to_previous_page') ?>" style="width: 32px; height: 32px; transform: rotate(-90deg); filter: brightness(0.7); transition: filter 0.2s;" onmouseover="this.style.filter='brightness(0.5)'" onmouseout="this.style.filter='brightness(0.7)'">
        </a>
    </div>
   
    
    <div id="articles-container">
        <div class="text-center" style="text-align: left;">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p><?= lang('lang_loading_articles') ?></p>
        </div>
    </div>
</div>

<script>
// Traduceri pentru JavaScript
const translations = {
    noArticlesWithTag: '<?= lang("lang_no_articles_with_tag") ?>',
    author: '<?= lang("lang_article_author") ?>',
    category: '<?= lang("lang_article_category") ?>',
    published: '<?= lang("lang_article_published") ?>',
    views: '<?= lang("lang_article_views") ?>',
    tags: '<?= lang("lang_tags") ?>',
    readMore: '<?= lang("lang_read_more") ?>',
    errorLoadingArticles: '<?= lang("lang_error_loading_articles") ?>'
};

document.addEventListener('DOMContentLoaded', function() {
    loadArticlesByTag('<?= htmlspecialchars($tag, ENT_QUOTES) ?>');
});

function loadArticlesByTag(tag) {

    const hrLine = '<div style="height: 0.5px; background-color: #ccc; width: 100%;"></div><br>';

    fetch(`api/bkd_articles_by_tag.php?tag=${encodeURIComponent(tag)}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('articles-container');
            
            if (data.error) {
                container.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.error)}</div>`;
                return;
            }
            
            if (!data.articles || data.articles.length === 0) {
                container.innerHTML = `<div class="alert alert-info">${translations.noArticlesWithTag} "${escapeHtml(tag)}".</div>`;
                return;
            }
            
            let articlesHtml = '';
            data.articles.forEach(article => {
                // Creează excerpt din content
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = article.content;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';
                const excerpt = textContent.length > 200 ? textContent.substring(0, 200) + '...' : textContent;
                
                // Icon categorie
                let iconHtml = '';
                if (article.category_icon) {
                    if (article.category_icon.startsWith('http') || article.category_icon.endsWith('.png') || article.category_icon.endsWith('.svg')) {
                        iconHtml = `<img src="<?=APP_URL?>assets/icons/categories/${escapeHtml(article.category_icon)}" alt="icon" style="width: 45px; vertical-align: middle;">`;
                    } else {
                        iconHtml = `<span>${escapeHtml(article.category_icon)}</span>`;
                    }
                }
                
                // Tags
                let tagsHtml = '';
                if (article.tags && article.tags.length > 0) {
                    tagsHtml = article.tags.map(tagName => 
                        `<a href="articles_by_tag.php?tag=${encodeURIComponent(tagName)}" class="tag-badge">${escapeHtml(tagName)}</a>`
                    ).join('');
                }
                
                articlesHtml += `
                    <div class="article-card">
                        <h3 class="article-title" style="padding: 20px;text-align: left;">
                            <a href="view_article.php?id=${article.id}" style="text-decoration: none; color: #333;">
                                ${iconHtml}${escapeHtml(article.title)}
                            </a>
                        </h3>
                        <div class="article-meta">
                            <strong>${translations.author}:</strong> ${escapeHtml(article.username)} | 
                            <strong>${translations.category}:</strong> ${escapeHtml(article.category_name)} | 
                            <strong>${translations.published}:</strong> ${escapeHtml(article.created_at)} |
                            <strong>${translations.views}:</strong> ${article.views || 0} |
                            <img src="<?=APP_URL?>assets/images/icon-like.png" width="16" height="auto" title="Like"> ${article.likes || 0}
                            <img src="<?=APP_URL?>assets/images/icon-dlike.png" width="16" height="auto" title="Dislike"> ${article.dislikes || 0}
                        </div><br>
                        ${tagsHtml ? `<div class="article-tags"><strong>${translations.tags}</strong> ${tagsHtml}</div><br>${hrLine}` : `${hrLine}`}
                        <div class="article-excerpt">${excerpt}
                            <a href="view_article.php?id=${article.id}" style="text-decoration: none;">
                                <img width="24" height="auto" src="<?=APP_URL?>assets/icons/icon-read-more.svg" title="${translations.readMore}" style="vertical-align: middle; margin-left: 5px;">
                            </a>
                        </div>
                    </div>
                    <br>
                `;
            });
            
            container.innerHTML = articlesHtml;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('articles-container').innerHTML = 
                `<div class="alert alert-danger">${translations.errorLoadingArticles}</div>`;
        });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

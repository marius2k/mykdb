<?php 
include_once '../config/bootstrap.php';
include '../includes/header.php'; 


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<script>
    window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
</script>


    <div style="max-width:80%; margin:20px auto;">
        <h2 class="article-title" id="article-title"></h2>
        <p id="article-meta"></p>

        <div class="article-view">
            <div id="article-content"></div>
            <div class="vote-buttons" id="article-votes"></div>
        </div>

        <div id="comments" class="article-view">
            <h4 id="comments-title">💬 Comentarii</h4>
            <div id="comments-list"></div>
        </div>
    </div>

<div class="article-view" style="max-width:80%;margin:20px auto;">
    <form id="comment-form" class="mb-4">
        <input type="hidden" name="article_id" id="article_id">
        <textarea name="content" id="comment-content" class="form-control" required rows="3" placeholder="Scrie un comentariu..."></textarea>
        <button type="submit" class="btn btn-primary mt-2">Trimite</button>
        <div id="comment-feedback" class="mt-2 text-success d-none">Comentariul a fost trimis!</div>
    </form>
</div>

<script>
const urlParams = new URLSearchParams(window.location.search);
const articleId = urlParams.get('id');
document.getElementById('article_id').value = articleId;



function loadArticle() {
    fetch('api/bkd_view_article.php?id=' + articleId)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                document.getElementById('article-title').textContent = data.error;
                return;
            }
            // Icon categorie
            let iconHtml = '';
            if (data.icon) {
                if (data.icon.startsWith('http') || data.icon.endsWith('.png') || data.icon.endsWith('.svg')) {
                    iconHtml = `<img src="<?=APP_URL?>assets/icons/categories/${escapeHtml(data.icon)}" alt="icon" class="me-1" style="width: 45px; vertical-align: middle;">`;
                } else {
                    iconHtml = `<span class="me-1">${escapeHtml(data.icon)}</span>`;
                }
            }
            document.getElementById('article-title').innerHTML = iconHtml + escapeHtml(data.title);

            document.getElementById('article-meta').innerHTML =
                `<em>Autor: ${escapeHtml(data.username)} | Categorie: ${escapeHtml(data.category)} | Publicat: ${escapeHtml(data.created_at)} | Actualizat: ${escapeHtml(data.updated_at)}</em>`;

            document.getElementById('article-content').innerHTML = data.content;
            
            //debug
            console.log(data.content);

            // Vote buttons articol
            document.getElementById('article-votes').innerHTML = `
                <a href="#" onclick="voteArticle(${data.id}, 'like', this); return false;">
                    <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon" width="20" height="auto" title="Like">
                </a>
                <span class="like-count">${data.likes || 0}</span>
                <a href="#" onclick="voteArticle2(${data.id}, 'dislike', this); return false;">
                    <img src="<?=APP_URL?>assets/images/icon-dlike.png" class="vote-icon" width="20" height="auto" title="Dislike">
                </a>
                <span class="dislike-count">${data.dislikes || 0}</span>
            `;

            // Comentarii
            let commentsHtml = '';
            if (data.comments && data.comments.length) {
                data.comments.forEach(function(c) {
                    commentsHtml += `
                    <div id="comment-${c.id}" class="comment border rounded p-2 mb-2">
                        <strong>${escapeHtml(c.username)}</strong>
                        <small class="text-muted">${escapeHtml(c.created_at)}</small>
                        <p class="mb-0" style="padding: 20px;">${escapeHtml(c.content).replace(/\n/g, '<br>')}</p>
                        <div class="comment-footer" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <a href="#" onclick="voteComment2(${c.id}, 'like'); return false;">
                                    <img width="20" height="auto" src="<?= APP_URL ?>assets/images/icon-like.png"
                                        id="like-icon-${c.id}"
                                        class="vote-icon"
                                        title="Like">
                                    <span class="like-count" id="like-count-${c.id}">${c.likes || 0}</span>
                                </a>
                                <a href="#" onclick="voteComment2(${c.id}, 'dislike'); return false;">
                                    <img width="20" height="auto" src="<?= APP_URL ?>assets/images/icon-dlike.png"
                                        id="dislike-icon-${c.id}"
                                        class="vote-icon"
                                        title="Dislike">
                                    <span class="dislike-count" id="dislike-count-${c.id}">${c.dislikes || 0}</span>
                                </a>
                            </div>
                        </div>
                    </div>`;
                });
            } else {
                commentsHtml = '<p>Nu există comentarii aprobate.</p>';
            }
            document.getElementById('comments-list').innerHTML = commentsHtml;
            // Scroll la secțiunea de comentarii dacă există hash în URL
        if (window.location.hash === '#comments') {
            const commentsDiv = document.getElementById('comments');
            if (commentsDiv) {
                commentsDiv.scrollIntoView({behavior: "smooth"});
            }
        }
    });
}

function voteArticle2(articleId, vote, el) {
    fetch('api/bkd_vote.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=article&id=${encodeURIComponent(articleId)}&vote=${encodeURIComponent(vote)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            el.parentNode.querySelector('.like-count').textContent = data.likes;
            el.parentNode.querySelector('.dislike-count').textContent = data.dislikes;
        } else {
            alert(data.error || 'Eroare la vot!');
        }
    });
}

function voteComment2(commentId, vote) {
    fetch('api/bkd_vote.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=comment&id=${encodeURIComponent(commentId)}&vote=${encodeURIComponent(vote)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('like-count-' + commentId).textContent = data.likes;
            document.getElementById('dislike-count-' + commentId).textContent = data.dislikes;
        } else {
            alert(data.error || 'Eroare la vot!');
        }
    });
}

document.getElementById('comment-form').onsubmit = function(e) {
    e.preventDefault();
    const content = document.getElementById('comment-content').value;
    const articleId = document.getElementById('article_id').value;
    fetch('api/bkd_add_comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `article_id=${encodeURIComponent(articleId)}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-feedback').classList.remove('d-none');
            document.getElementById('comment-content').value = '';
        } else {
            alert(data.error || 'Eroare la trimitere!');
        }
    });
    return false;
};

document.addEventListener('DOMContentLoaded', loadArticle);
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
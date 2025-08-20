<?php 
include_once '../config/bootstrap.php';
include '../includes/header.php'; 

// Get article ID from URL parameter
$articleId = (int)($_GET['id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<script>
    window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
</script>


    

       <div class="article-view" style="max-width:80%; margin:20px auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 class="article-title" id="article-title"></h2>
                </div>
                <div id="bookmark-img"></div>              
            </div>
            <div class="article-header-flex" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="article-meta" id="article-meta"></div>
                <div id="article-header-flex"></div>
            </div>
            <div class="article-tags" id="article-tags" style="margin: 10px 0;"></div>
            <br>
            <div style="height: 0.5px; background-color: #ccc; width: 100%;"></div>
            <br>
            <div id="article-content"></div>
            <br>
            <div style="height: 0.5px; background-color: #ccc; width: 100%;"></div>
            <br>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="vote-buttons" id="article-votes"></div>
                <div class="article-useful" id="article-useful" cstyle="margin-top:8px;"></div>
            </div>
        </div>
        <div id="comments" class="article-view" style="max-width:80%; margin:20px auto;">
            <h4 id="comments-title"><?= lang ('lang_view_article_comments');?></h4>
            <div id="comments-list"></div>
        </div>


        <div class="article-view" style="max-width:80%;margin:20px auto;">
            <form id="comment-form" class="mb-4">
                <input type="hidden" name="article_id" id="article_id">
                <textarea name="content" id="comment-content" class="form-control" required rows="3" placeholder="<?= lang('lang_view_article_write_a_comment'); ?>"></textarea>
                <button type="submit" class="btn btn-primary mt-2"><?= lang ('lang_view_article_comment_send');?></button>
                <div id="comment-feedback" class="mt-2 text-success d-none"><?= lang ('lang_view_article_comment_sent');?></div>
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
            
            // bookmark-btn

            let bookmarkHtmtl = '';

            if (data.is_bookmarked) {
                bookmarkHtml = `<img id="bookmark-img" src="<?=APP_URL?>assets/icons/icon-bookmark-full.svg" alt="Bookmark" class="bookmark-icon" style="cursor:pointer; width:30px; height:auto;" onclick="toggleBookmark(${data.id}, this)" title="<?=lang('lang_favorites_remove')?>" >`;
            } else {
                bookmarkHtml = `<img id="bookmark-img" src="<?=APP_URL?>assets/icons/icon-bookmark-empty.svg" alt="Bookmark" class="bookmark-icon" style="cursor:pointer; width:30px; height:auto;" onclick="toggleBookmark(${data.id}, this)" title="<?=lang('lang_favorites_add')?>" >`;
            }

            document.getElementById('bookmark-img').innerHTML = bookmarkHtml;

            document.getElementById('article-meta').innerHTML =
                `<em><?= lang ('lang_view_article_author');?>: ${escapeHtml(data.username)} | <?= lang('lang_view_article_category');?>: ${escapeHtml(data.category)} | <?= lang('lang_view_article_published');?>: ${escapeHtml(data.created_at)} | <?= lang('lang_view_article_updated');?>: ${escapeHtml(data.updated_at)}</em>`;

            // Afișează tagurile
            if (data.tags && data.tags.length > 0) {
                const tagsHtml = data.tags.map(tag => 
                    `<a href="articles_by_tag.php?tag=${encodeURIComponent(tag)}" class="tag-badge">${escapeHtml(tag)}</a>`
                ).join('');
                document.getElementById('article-tags').innerHTML = `<strong>Tags:</strong> ${tagsHtml}`;
            } else {
                document.getElementById('article-tags').innerHTML = '';
            }

   
            // HTML cu stele
            const articleStars = `
                    <div class="article-rating" data-article-id="${data.id}">
                        <span class="star" data-value="1">&#9733;</span>
                        <span class="star" data-value="2">&#9733;</span>
                        <span class="star" data-value="3">&#9733;</span>
                        <span class="star" data-value="4">&#9733;</span>
                        <span class="star" data-value="5">&#9733;</span>
                        <span id="rating-average" style="margin-left:10px; color:#048eb1; font-size:0.9rem;"></span>
                    </div>
                `;
            document.getElementById('article-header-flex').innerHTML = articleStars;

            // Inițializează stelele după ce le-ai inserat
            initRatingStars(data.id);

        

            

            document.getElementById('article-content').innerHTML = data.content;
            
            // useful-feedback

            const usefulHtml = `
                        <span style="margin-right:10px;"><?= lang('lang_view_article_useful');?></span>
                        <button id="btn-useful-yes" class="btn-useful-grey" type="button"><?= lang('lang_view_article_useful_yes');?></button>
                        <button id="btn-useful-no" class="btn-useful-grey" type="button"><?= lang('lang_view_article_useful_no');?></button>
                        <span id="useful-feedback" style="margin-left:10px; color:#048eb1; font-size:0.9rem;"></span>           
            `;

            document.getElementById('article-useful').innerHTML = usefulHtml;
            
            
            // init useful-feedback

            initUsefulFeedback(data.id);



            //debug
            //console.log(data.content);

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
                    <div id="comment-${c.id}" class="comment border rounded p-2 mb-2" style="background-color:rgb(250, 250, 250);">
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
                commentsHtml = '<p><?= lang('lang_view_article_no_comments');?></p>';
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


// init rating stars

function initRatingStars(articleId) {
    const stars = document.querySelectorAll('.article-rating .star');
    const ratingMsg = document.getElementById('rating-message');
    const ratingAvg = document.getElementById('rating-average');
    const articleRatingDiv = document.querySelector('.article-rating');


    let userRating = 0;
    
    if (!articleRatingDiv) return; // safety

    function highlightStars(val) {
      stars.forEach(star => {
        star.classList.toggle('selected', parseInt(star.dataset.value) <= val);
      });
    }

    stars.forEach(star => {
      star.addEventListener('mouseenter', function() {
        highlightStars(this.dataset.value);
        stars.forEach(s => s.classList.toggle('hovered', parseInt(s.dataset.value) <= this.dataset.value));
      });
      star.addEventListener('mouseleave', function() {
        highlightStars(userRating);
        stars.forEach(s => s.classList.remove('hovered'));
      });
      star.addEventListener('click', function() {
        userRating = this.dataset.value;
        highlightStars(userRating);
        sendRating(userRating, articleId, ratingAvg, ratingMsg);
      });
    });

    getAverageRating(articleId, ratingAvg, stars, function(val) { userRating = val; });
}


// Trimite ratingul la backend
function sendRating(val, articleId, ratingAvg, ratingMsg) {
  fetch('api/bkd_article_rating.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `article_id=${encodeURIComponent(articleId)}&stars=${encodeURIComponent(val)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (ratingMsg) ratingMsg.textContent = "<?= lang('lang_article_view_rating_thanks'); ?>";
      getAverageRating(articleId, ratingAvg, document.querySelectorAll('.article-rating .star'));
    } else {
      if (ratingMsg) ratingMsg.textContent = data.error || "Eroare la rating!";
      if (ratingMsg) ratingMsg.style.color = "red";
    }
  });
}

// Afișează ratingul mediu
function getAverageRating(articleId, ratingAvg, stars, setUserRatingCb) {
  fetch('api/bkd_article_rating.php?article_id=' + encodeURIComponent(articleId))
    .then(res => res.json())
    .then(data => {
      if (data.average) {
        if (ratingAvg) ratingAvg.textContent = `(${data.average.toFixed(2)} / 5, ${data.count} <?= lang('lang_view_article_rating_votes');?>)`;
        if (stars) {
          stars.forEach(star => {
            star.classList.toggle('selected', parseInt(star.dataset.value) <= (data.user_rating || 0));
          });
        }
        if (setUserRatingCb) setUserRatingCb(data.user_rating || 0);
      } else {
        if (ratingAvg) ratingAvg.textContent = "<?= lang('lang_view_article_no_ratings')?>";
      }
    });
}

function initUsefulFeedback(articleId) {
    const btnYes = document.getElementById('btn-useful-yes');
    const btnNo = document.getElementById('btn-useful-no');
    const feedback = document.getElementById('useful-feedback');

    if (!btnYes || !btnNo) return;

    btnYes.onclick = function() { sendUsefulFeedback(articleId, 1, feedback); };
    btnNo.onclick = function() { sendUsefulFeedback(articleId, 0, feedback); };

    getUsefulStats(articleId, feedback);
}

function sendUsefulFeedback(articleId, wasHelpful, feedback) {
    fetch('api/bkd_article_rating.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `article_id=${encodeURIComponent(articleId)}&was_helpful=${encodeURIComponent(wasHelpful)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            feedback.textContent = "<?= lang('lang_view_article_rating_thanks');?>";
            getUsefulStats(articleId, feedback);
        } else {
            feedback.textContent = data.error || "Eroare la feedback!";
            feedback.style.color = "red";
        }
    });
}

function getUsefulStats(articleId, feedback) {
    fetch('api/bkd_article_rating.php?article_id=' + encodeURIComponent(articleId) + '&useful_stats=1')
        .then(res => res.json())
        .then(data => {
            if (data.useful_percent !== undefined) {
                feedback.textContent = `<?= lang('lang_view_article_util');?>: ${data.useful_percent}% (${data.useful_yes} <?= lang('lang_view_article_from');?> ${data.useful_total} <?php lang('lang_view_article_rating_votes');?>)`;
            }
        });
}

function toggleBookmark(articleId, img) {
    fetch('api/bkd_toggle_bookmark.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'article_id=' + encodeURIComponent(articleId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.bookmarked) {
                img.src = '<?=APP_URL?>assets/icons/icon-bookmark-full.svg';
                img.title = "Elimină din favorite";
            } else {
                img.src = '<?=APP_URL?>assets/icons/icon-bookmark-empty.svg';
                img.title = "Adaugă la favorite";
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', loadArticle);


</script>

<?php
// JavaScript pentru tracking citirea articolului
if (isset($_SESSION['user']['id'])) {
    echo '<script>
    // Tracking pentru citirea completă a articolului
    let readStartTime = Date.now();
    let hasAwarded = false;
    
    // Verifică dacă utilizatorul a citit cel puțin 30 de secunde
    setTimeout(function() {
        if (!hasAwarded) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    }, 30000); // 30 secunde
    
    // Sau când utilizatorul ajunge la sfârșitul articolului
    window.addEventListener("scroll", function() {
        if (!hasAwarded && (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
            fetch("api/bkd_award_reading_points.php", {
                method: "POST", 
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "article_id=' . $articleId . '&csrf_token=' . $_SESSION['csrf_token'] . '"
            });
            hasAwarded = true;
        }
    });
    </script>';
}
?>


<?php include APP_ROOT . 'includes/footer.php'; ?>
<?php

//loadConfig();

require_once '../config/bootstrap.php';

//echo "App ROOT" . APP_ROOT;

//require_once APP_ROOT.'config/config.php';
//require_once APP_ROOT.'config/db.php';

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


$filter = '';
$params= [];


// Optional category filter
if (isset($_GET['category'])) {
    $filter = "AND category_id = ?";
    $params[] = $_GET['category'];
    //echo $_GET['category'];
    //echo $filter;
}

if ($_GET['category'] == NULL) {
    $filter = '';
    $params = [];
}

/*
if ($_GET['category'] == NULL) {
    $filter = '';
    $params= [];
    //echo $_GET['category'];
    //echo "xxx".$filter;
}
*/


$db = new Database();

// Fetch categories for dropdown
$stmt1 = $db->query("SELECT * FROM categories");
//$stmt1 -> execute($params);
$categories = $stmt1->fetchAll();

// Fetch approved articles
$stmt = $db->prepare("
    SELECT a.*, u.username, c.name AS category 
    FROM articles a 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.status = 'approved' $filter
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$articles = $stmt->fetchAll();

if (isset($_SESSION['user']['id'])) {

    // user is logged in

    $db = new Database();

    $userId = $_SESSION['user']['id'];
    $settings = new UserSettings($db);
    $currentSettings = $settings->getAll($userId);
    $lang = $currentSettings['language'];
    $theme = $currentSettings['theme'];
    $_SESSION['settings'] = $currentSettings;

}else {

    // user not logged in
    $lang = 'en';
    $theme = 'light';
}


//echo "index.php: user id: ". $_SESSION['user']['id'];




?>

<?php include APP_ROOT . 'includes/header.php'; ?>


<div class="search-category">
    <div class="category-box">
        <!-- Category filter -->
        <em class="settings-category-text"><?=lang_categories?>: </em>
        <form method="GET">
            <select name="category" onchange="this.form.submit()" class="settings-dropdown">
                <option value="">--<?= lang_all_categories ?>--</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="search-box">
            <em><?=lang_search?>: </em> 
            <input type="text" id="liveSearch" placeholder="<?=lang_search_placeholder; ?>" autocomplete="off">
    </div>

</div>

<br>

<!-- search results -->
<div id="searchResults" style="margin-top:10px;"></div>

<div id="defaultContent">
    
        <h2><?=lang_articles?></h2>
    <?php if (count($articles) === 0): ?>
        <p>Niciun articol aprobat momentan.</p>
    <?php else: ?>
    
    
        <div class="article-grid">
            <?php 
            foreach ($articles as $a): 
            

                    $content = $a['content'];

                    // Extragem prima imagine (dacă există)
                    preg_match('/<img[^>]+src="([^">]+)"/i', $content, $matches);
                    $image = $matches[1] ?? null;
                    
                    // Extragem textul fără HTML
                    $textOnly = strip_tags($content);
                    
                    // Scurtăm textul
                    $shortText = shortenText($textOnly, 500);
                    

                    $preview = truncateHtmlWithImages($a['content'], 100);
                    ?>

                    <div class="article-card">
                        
                            <h3 class="article-title"><?= escape($a['title']) ?></h3>
                            <div class="article-body" id="article<?=$a['id']?>">
                                <p><?= nl2br(escape($shortText)) ?><a href="view_article.php?id=<?= (int)$a['id'] ?>"><img width="24" height="auto" src="<?=APP_URL?>assets/images/icon-rm1.png" title="<?= lang_read_more; ?>"> </a></p>
                            </div>
                            
                            <div class="article-footer">
                                <div>
                                    <span class="article-meta"><?=lang_article_author?>:<?=escape($a['username']) ?> | <?=lang_article_category?>:<?=escape($a['category']) ?> | <?=lang_article_published?>:<?=formatDate($a['created_at']) ?>| <?=lang_article_updated?>:<?=formatDate($a['updated_at'])?></span>
                                </div>
                                <div class="vote-buttons-container"> 
                                            <div class="vote-buttons">
                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>">
                                                <img src="<?=APP_URL?>assets/images/icon-view.png" title="<?=lang_article_views?>" class="vote-icon"></a>
                                                <span class="like-count"><?= escape($a['views']) ?></span>

                                                <a href="<?=APP_URL?>public/view_comments.php?id=<?= (int)$a['id'] ?>">
                                                <img src="<?=APP_URL?>assets/images/icon-comm.png" title="<?=lang_article_add_comments?>" class="vote-icon"></a>
                                                <span class="like-count"><?php $commCount = getCommentCount($a['id']); echo $commCount;?></span>
                                            </div>
                                               
                                            <div class="vote-buttons">
                                                
                                                <?php
                                                    $votes = getArticleLikesDislikes($a['id']);
                                                    $currentVote = $userId ? getUserVote($a['id'], $userId) : null;
                                                    //echo "crt vote:".$currentVote;

                                                ?>

                                                <!-- LIKE -->
                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'like', this); return false;">
                                                    <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon <?= $currentVote === 'like' ? 'active' : '' ?>" width="20" high="auto" title="<?=lang_article_like?>">  
                                                </a>
                                                <span class="like-count"><?= $votes['like'] ?></span>
                                                
                                                <!-- DISLIKE -->
                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'dislike', this); return false;">
                                                    <img src="<?=APP_URL?>assets/images/icon-dlike.png" class="vote-icon <?= $currentVote === 'dislike' ? 'active' : '' ?>" width="20" height="auto" title="<?=lang_article_dislike?>">    
                                                
                                                </a>
                                                <span class="dislike-count"><?= $votes['dislike'] ?></span>

                                            </div>
                                </div>                               

                                   
                                
                            </div>
                    </div>


            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
</div>


<script>
/*
document.addEventListener("DOMContentLoaded", function () {
    const clampElements = document.querySelectorAll(".clamp-fallback");
    clampElements.forEach(el => {
        if (!CSS.supports("-webkit-line-clamp", "4") && !CSS.supports("line-clamp", "4")) {
            const lineHeight = parseInt(window.getComputedStyle(el).lineHeight);
            const maxHeight = lineHeight * 4; // 👈 4 lines
            el.style.maxHeight = maxHeight + "px";
            el.style.overflow = "hidden";
            el.style.textOverflow = "ellipsis";
        }
    });
});
*/
</script>

<script>
function voteArticle(articleId, voteType, el) {
  fetch('vote_article.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: `aid=${articleId}&vote=${voteType}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      // Găsim ambele span-uri (în același container)
      const container = el.closest('div') || el.parentNode;
      const likeSpan = container.querySelector('.like-count');
      const dislikeSpan = container.querySelector('.dislike-count');

      if (likeSpan) likeSpan.textContent = data.likes;
      if (dislikeSpan) dislikeSpan.textContent = data.dislikes;
        // eliminăm toate clasele "active" locale
        container.querySelectorAll('.vote-icon').forEach(img => img.classList.remove('active'));

        // adăugăm clasa doar pe iconul votat
        if (voteType === 'like') {
                container.querySelector('img[src*="icon-like"]').classList.add('active');
        } else {
                container.querySelector('img[src*="icon-dlike"]').classList.add('active');
        }
    } else {
      alert(data.message || 'Eroare la vot!');
    }
  })
  .catch(err => {
    console.error(err);
    alert('Eroare AJAX!');
  });

 
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
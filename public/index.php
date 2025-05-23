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
if (isset($_GET['category']) && $_GET['category'] !== '') {
    $filter = "AND category_id = ?";
    $params[] = $_GET['category'];
    //echo $_GET['category'];
    //echo $filter;
}else {
    $filter = '';
    $params = [];
}

//echo "filter: ".$filter;


/*
if (!isset($_GET['category'])) {
    $filter = '';
    $params = [];
}
*/
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
$stmt1 = $db->query("SELECT * FROM categories WHERE is_active = 1");
//$stmt1 -> execute($params);
$categories = $stmt1->fetchAll();

// Fetch approved articles from the active categories
$stmt = $db->prepare("SELECT a.*, u.username, c.name AS category, c.icon 
    FROM articles a 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.status = 'approved' $filter
    AND (a.publish_at IS NULL OR a.publish_at <= NOW())
    AND c.is_active = 1
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
    $lang = $currentSettings['language'] ?? 'en';
    $theme = $currentSettings['theme'] ?? 'light';
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
                        
                            <h3 class="article-title">
                                <?php if (!empty($a['icon'])): ?>
                                        <?php if (str_starts_with($a['icon'], 'http') || str_ends_with($a['icon'], '.png') || str_ends_with($a['icon'], '.svg')): ?>
                                            <img src="<?=APP_URL?>assets/icons/categories/<?= $a['icon'] ?>" alt="icon" class="me-1" style="width: 45px; vertical-align: middle;">
                                            <?php else: ?>
                                            <span class="me-1"><?= htmlspecialchars($a['icon']) ?></span>
                                        <?php endif; ?>
                                <?php endif; ?><?= escape($a['title']) ?></h3>
                            <div class="article-body" id="article<?=$a['id']?>">
                                <p><?= nl2br(escape($shortText)) ?><a href="view_article.php?id=<?= (int)$a['id'] ?>"><img width="24" height="auto" src="<?=APP_URL?>assets/icons/icon-read-more.svg" title="<?= lang_read_more; ?>"> </a></p>
                            </div>
                            
                            <div class="article-footer">
                                <div>
                                    <span class="article-meta"><?=lang_article_author?>:<?=escape($a['username']) ?> | <?=lang_article_category?>:<?=escape($a['category']) ?> | <?=lang_article_published?>:<?=formatDate($a['created_at']) ?>| <?=lang_article_updated?>:<?=formatDate($a['updated_at'])?></span>
                                </div>
                                <div class="vote-buttons-container"> 
                                            <div class="vote-buttons" id="meta-<?=$a['id']?>">
                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>">
                                                <img src="<?=APP_URL?>assets/images/icon-view.png" title="<?=lang_article_views?>" class="vote-icon"></a>
                                                <span class="view-count"><?= escape($a['views']) ?></span>

                                                <a href="<?=APP_URL?>public/view_article.php?id=<?= (int)$a['id'] ?>#comments">
                                                <img src="<?=APP_URL?>assets/images/icon-comm.png" title="<?=lang_article_add_comments?>" class="vote-icon"></a>
                                                <span class="comments-count"><?=getCommentCount($a['id'])?></span>
                                            </div>
                                               
                                            <div class="vote-buttons">
                                                
                                                <?php
                                                    $votes = getArticleLikesDislikes($a['id']);
                                                    $currentVote = $userId ? getUserVote($a['id'], $userId) : null;
                                                    //echo "crt vote:".$currentVote;

                                                ?>

                                                <!-- LIKE -->
                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'like', this); return false; updateArticleMeta(<?=$a['id']?>);">
                                                    <img src="<?=APP_URL?>assets/images/icon-like.png" class="vote-icon <?= $currentVote === 'like' ? 'active' : '' ?>" width="20" high="auto" title="<?=lang_article_like?>">  
                                                </a>
                                                <span class="like-count"><?= $votes['like'] ?></span>
                                                
                                                <!-- DISLIKE -->
                                                <a href="#" onclick="voteArticle(<?= $a['id'] ?>, 'dislike', this); return false; updateArticleMeta(<?=$a['id']?>);">
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




<?php include APP_ROOT . 'includes/footer.php'; ?>
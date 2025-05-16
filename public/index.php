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
                    $shortText = shortenText($textOnly, 300);
                    

                    $preview = truncateHtmlWithImages($a['content'], 100);
                    ?>

                    <div class="article-card">
                        
                            <h3 class="article-title"><?= escape($a['title']) ?></h3>
                            <div class="article-body">
                                <p><?= nl2br(escape($shortText)) ?>...</d>
                            </div>
                            <a class="read-more" href="view_article.php?id=<?= (int)$a['id'] ?>" style="color:blue;"><?= lang_read_more; ?> </a>
                            <div class="article-footer">
                                <span class="article-meta"><?=lang_article_author?>:<?=escape($a['username']) ?> | <?=lang_article_category?>:<?=escape($a['category']) ?> | <?=lang_article_published?>:<?=formatDate($a['created_at']) ?>| <?=lang_article_updated?>:<?=formatDate($a['updated_at'])?></span>
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


<?php include APP_ROOT . 'includes/footer.php'; ?>
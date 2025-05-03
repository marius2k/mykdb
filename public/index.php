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


?>


?>

<?php include APP_ROOT . 'includes/header.php'; ?>



<!-- Category filter -->
<form method="GET">
    <select name="category" onchange="this.form.submit()">
        <option value="">-- Toate categoriile --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<hr>

<!-- search results -->
<div id="searchResults" style="margin-top:10px;"></div>

<div id="defaultContent">

    <?php if (count($articles) === 0): ?>
        <p>Niciun articol aprobat momentan.</p>
    <?php else: ?>
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
                    

                    $preview = truncateHtmlWithImages($a['content'], 500);
                    ?>

                    <div class="article-preview">
                        <div class="preview-text">
                            <h3><?= escape($a['title']) ?></h3>
                            <p><em>Autor: <?= escape($a['username']) ?> | Categorie: <?= escape($a['category']) ?> | Publicat: <?= formatDate($a['created_at']) ?></em></p>
                            <p><?= nl2br(escape($shortText)) ?>...</p>
                            <a href="view_article.php?id=<?= (int)$a['id'] ?>" style="color:blue;">Citește mai mult...</a>
                        </div>

                        <?php if ($image): ?>
                            <div class="preview-image">
                                <img src="<?= escape($image) ?>" alt="Imagine articol">
                            </div>
                        <?php endif; ?>
                    </div>


            <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>

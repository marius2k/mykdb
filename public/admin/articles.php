<?php

require_once '../../config/bootstrap.php';


//require_admin();


$ops = ['edit_article','disable_article','enable_article','view_article'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}



$db = new Database();


$perPage = 5; // articole pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Total articole (pt paginare)
$totalStmt = $db->query("SELECT COUNT(*) FROM articles");
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);



// update publish date

if ($_GET['action'] === 'publish_at' && isset($_GET['article_id'])) {
    $articleId = (int) $_GET['article_id'];
    $publishAt = $_GET['publish_at'] ?? null;

    if ($publishAt && !strtotime($publishAt)) {
        die('⚠️ Dată invalidă.');
    }

    $db->query("UPDATE articles SET publish_at = ? WHERE id = ?", [
        $publishAt ?: null, $articleId
    ]);

    header("Location: articles.php#article" . $articleId); // opțional: scroll
    exit;
}



if (isset($_GET['approve'])) {
    $stmt = $db->prepare("UPDATE articles SET status = 'approved' WHERE id = ?");
    $stmt->execute([$_GET['approve']]);
    logActivity($_SESSION['user']['id'], 'article_approved', 'User ' .$_SESSION['user']['username'] .' approved an article');
}

if (isset($_GET['disable'])) {
    $newDate=date('Y-m-d H:i:s');

    $stmt = $db->prepare("UPDATE articles SET status='pending', publish_at = ? WHERE id=?");
    $stmt->execute([$newDate,$_GET['disable']]);
    logActivity($_SESSION['user']['id'], 'article_disabled', 'User ' .$_SESSION['user']['username'] .' disabled an article');
}

$stmt = $db->prepare("
    SELECT a.*, u.username, c.name AS category
    FROM articles a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$articles = $stmt->fetchAll();

$x=1;

/*
$articles = $db->query("
    SELECT a.*, u.username, c.name AS category 
    FROM articles a 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN categories c ON a.category_id = c.id 
    ORDER BY a.created_at DESC
")->fetchAll();
*/

?>

<?php include APP_ROOT . 'includes/header.php'; ?>


<h2><?=lang_art_articles?>&nbsp;&nbsp;&nbsp;<a href="<?=APP_URL?>public/create_article.php"><img src="<?=APP_URL?>assets/icons/icon-create-article.svg" class="op-icon" title="<?=lang_art_create?>"></a></h2>
<table class="articles-table" width="80%">
    <thead>
        <tr>
            <th align="center">#</th>
            <th><?=lang_art_title?></th>
            <th><?=lang_art_author?></th>
            <th><?=lang_art_category?></th>
            <th><?=lang_art_status?></th>
            <th><?=lang_art_publish_at?></th>
            <th align="center"><?=lang_art_actions?></th>
        </tr>
    </thead>
    <tbody>

    
        <?php foreach ($articles as $a): ?>
        <tr>
            <td align="center"><?=$x?></td>
            <td><?= htmlspecialchars($a['title']) ?></td>
            <td><?= htmlspecialchars($a['username']) ?></td>
            <td><?= htmlspecialchars($a['category']) ?></td>
            <td><?= $a['status'] ?></td>
            <?php
            
            if ($a['status'] == 'pending' ){ ?>

                <!-- daca articolul este in pending si data publicarii este in viitor, pot sa-l aprob si sa-i setez data publicarii -->
                <td>
                <input type="datetime-local" value="<?= $a['publish_at'] ? date('Y-m-d\TH:i', strtotime($a['publish_at'])) : '' ?>" onchange="changePublishAt(this.value, <?= $a['id'] ?>)">
                </td>
        <?php }else{
                
                //daca articolul nu este in pending (deja publicat), afisez doar data publicarii (disabled)
                
                $isFuture = strtotime($a['publish_at']) > time();
                $class = $isFuture ? 'text-primary' : '';
                //$pdate = new DateTime($a['publish_at']);
                //$crtdate = new DateTime();

                if ($isFuture) { ?>

                    <td class="<?=$class?>">
                        <?php echo $a['publish_at']; ?>
                    </td>
                    
                <?php }else{ ?>
                    <td>
                        <?php echo $a['publish_at']; ?>
                    </td>
                    
                <?php }
            
            } ?>
           
            <td align="center">
                <?php
                    $op=['view_article'];

                    if (hasPermission($_SESSION['user']['id'],$op)){
                ?>
                        <a href="../view_article.php?id=<?= $a['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-view.svg" class="op-icon" title="<?=lang_btn_view?>"></a>
                <?php } ?>

                <?php
                    $op = ['edit_article'];
                    if (hasPermission($_SESSION['user']['id'],$op)){
                ?>
                <a href="../edit_article.php?id=<?= $a['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?=lang_btn_edit?>"></a>
                <?php } ?>

                <?php if ($a['status'] == 'pending'): ?>

                    <?php
                        $op=['approve_article'];
                        if (hasPermission($_SESSION['user']['id'],$op)){
                    ?>
                    <a href="?approve=<?= $a['id'] ?>&publish_at=<?=$publish_at?>" ><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?=lang_btn_approve?>"></a>
                    <?php } ?>

                <?php else: ?>
                    <?php
                        $op=['disable_article'];
                        if (hasPermission($_SESSION['user']['id'],$op)){
                    ?>
                    <a href="?disable=<?= $a['id'] ?>" onclick="updateStatus(<?= $a['id'] ?>)"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?=lang_btn_disable?>"></a>

                    <?php } ?>
                <?php endif; ?>
            <?php $x++; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="6">
            <div class="pagination-footer">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </td>
    </tr>
    </tfoot>

</table>
<script>
function changePublishAt(value,articleId) {
  const encoded = encodeURIComponent(value);
  const url = `articles.php?action=publish_at&article_id=${articleId}&publish_at=${encoded}`;
  window.location.href = url;
}

function updateStatus(articleId) {
  const encoded = encodeURIComponent(value);
  const url = `articles.php?disable=${articleId}`;
  window.location.href = url;
}
</script>


<?php include APP_ROOT . 'includes/footer.php'; ?>

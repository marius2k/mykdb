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





if (isset($_GET['approve'])) {
    $stmt = $db->prepare("UPDATE articles SET status='approved' WHERE id=?");
    $stmt->execute([$_GET['approve']]);
    logActivity($_SESSION['user']['id'], 'article_approved', 'User ' .$_SESSION['user']['username'] .' approved an article');
}

if (isset($_GET['disable'])) {
    $stmt = $db->prepare("UPDATE articles SET status='pending' WHERE id=?");
    $stmt->execute([$_GET['disable']]);
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

<h2><?=lang_art_articles?></h2>
<table class="articles-table" width="80%">
    <thead>
        <tr>
            <th align="center">#</th>
            <th><?=lang_art_title?></th>
            <th><?=lang_art_author?></th>
            <th><?=lang_art_category?></th>
            <th><?=lang_art_status?></th>
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
                    <a href="?approve=<?= $a['id'] ?>" ><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?=lang_btn_approve?>"></a>
                    <?php } ?>

                <?php else: ?>
                    <?php
                        $op=['disable_article'];
                        if (hasPermission($_SESSION['user']['id'],$op)){
                    ?>
                    <a href="?disable=<?= $a['id'] ?>" onclick="return confirm('<?=lang_art_msg_disable?>')"><img src="<?=APP_URL?>assets/icons/icon-disable.svg" class="op-icon" title="<?=lang_btn_disable?>"></a>

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


<?php include APP_ROOT . 'includes/footer.php'; ?>

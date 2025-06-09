<?php

require_once '../../config/bootstrap.php';


//require_admin();


$ops = ['edit_article','disable_article','enable_article','create_article'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:".APP_URL. "public/login.php");
    exit;
}



$db = new Database();





switch ($_POST['form_id'] ?? null) {

    case 'add_article':
        if (!isset($_POST['action']) || !in_array($_POST['action'], ['draft', 'submit'])) {
            die('⚠️ Invalid action.');
        }

        if ($_POST['action'] === 'draft') {
            // Handle saving as draft
            $status = 'draft';

        } elseif ($_POST['action'] === 'submit') {
            // Handle submitting for approval
            $status = 'pending';
        }


        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $category_id = $_POST['category_id'];
        $publish_at = $_POST['publish_at'] ?? time();
        $user_id = $_SESSION['user']['id'];

        if (!$title || !$content || !$category_id) {
            $errors[] = 'Toate câmpurile sunt obligatorii.';
        } else {

            $clean_content = clean_html($content);
            $clean_content = removeImageCaptionText($clean_content);

            $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at]);
            // Log the creation
            logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created the article:'. $title);
            
            // get article id already saved in db
            $aid = getArticleIdByTitle($title);

            // Send notification to moderators and admins
            sendNotificationToRole('moderator', 'info','Article <a href="view_article.php?id='. $aid.'">'. $title.'</a>'.' has been submitted for approval.');
            sendNotificationToRole('admin', 'info', 'Article <a href="view_article.php?id='. $aid.'">'. $title.'</a>'.' has been submitted for approval.');

            header('Location: articles.php');
            exit;
        }
        break;

    case 'edit_article':

        break;
    
}





$perPage = 5; // articole pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Total articole (pt paginare)
$totalStmt = $db->query("SELECT COUNT(*) FROM articles");
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);



// update publish date
if(isset($_GET['action'])){

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
}


if (isset($_GET['approve'])) {

    $articleId = $_GET['approve'];
    $stmt = $db->prepare("UPDATE articles SET status = 'approved' WHERE id = ?");
    $stmt->execute([$articleId]);
    
    // save activity log
    logActivity($_SESSION['user']['id'], 'article_approved', 'User ' .$_SESSION['user']['username'] .' approved an article');

    // send notification to author
    $stmt = $db->prepare("SELECT user_id, title FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();
    
    $authorId = $article['user_id'];

    sendNotification($authorId, 'Article Approved','Your article <a href="article.php?id='.$articleId.'">'. $article['title']. '</a> has been approved.','info');
    header("Location: articles.php");
    exit;

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

$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1");
$categories = $stmt->fetchAll();



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





<div class="category-container" >
    
    <div class="category-box-2" style="width: fit-content">
        
    
        <div class="operations-bar">
                <div>
                    <button class="btn-flat btn-toggle-form" onclick="toggleAddFormHide('form-add-article',this)" title="Add Article">
                    <img src="../../assets/icons/icon-create-article.svg" alt="Add Article" class="op-icon">
                     <?=lang_create_article?>&nbsp;&nbsp;      
                    </button>
                </div>
                <div>
                    <button class="btn-flat btn-disabled btn-toggle-form" onclick="toggleAddFormHide('form-edit-article',this)" title="Edit Article">
                    <img src="../../assets/icons/icon-add-icons.svg" alt="Edit Article" class="op-icon">
                    <?=lang_edit_article?> &nbsp;&nbsp; 
                    </button>
                    
                </div>
                
                
            </div>
        <div class="form-container-1">
            <div id="form-add-article" class="form-box" style="display: none; width: 100%; padding: 30px;">
                    <form method="POST" id="add_article" enctype="multipart/form-data" class="article-form">

            

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                <div class="form-group" style="display: flex;">
                                            <label for="title" style=" width: 30%;"><?=lang_art_title?>:</label>
                                            <input style="width: 70%" type="text" id="title" name="title" placeholder="<?=lang_art_title?>" required>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                        
                                        <div class="form-group" style="display: flex; flex-direction: row;">
                                            <label style="width: 30%" for="category"><?=lang_article_category?>:</label>
                                            <select name="category_id" id="add_category_select" class="select2-icon" style="width:70%" placeholder="<?=lang_cat_select?>" required>
                                                <option value="">--<?=lang_cat_select?> --</option>
                                                <?php foreach ($categories as $c): ?>
                                                    <option value="<?= $c['id'] ?>" data-img="/mykdb/assets/icons/categories/<?= $c['icon']?>"><?= escape($c['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div style="display: flex; flex-direction: row; gap: 10px;">
                                            <label style="width:30%" for="publish_at"><?=lang_art_publish_at?>:</label>
                                            <input style="width:70%" type="datetime-local" name="publish_at" id="publish_at"
                                                class="form-control" value="<?= isset($article['publish_at']) ? date('Y-m-d\TH:i', strtotime($article['publish_at'])) : '' ?>">

                                        </div>
                                </div>
                                <div style="padding: 20px" class="form-group">
                                            <label style="align: left;" for="content"><?=lang_create_article_content?></label>
                                            <input id="content" type="hidden" name="content">
                                            <trix-editor input="content"></trix-editor>
                                </div>
                          
                            <div>        


                                        <button type="submit" name="action" value="draft" class="btn btn-outline-grey"><?=lang_create_article_draft?></button>
                                        <button type="submit" name="action" value="submit" class="btn btn-outline-grey"><?=lang_create_article_submit?></button>    
                                        
                                        <input type="hidden" name="form_id" value="add_article">
                            </div>
                        </div>
                    </form>
            </div>
            <div id="form-edit-article" class="form-box" style="display: none; width: 100%;">





            </div>


        </div>




    </div>


    <div class="category-box-1" style="width: 80%;">
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
                                        <a href="?approve=<?= $a['id'] ?>&publish_at=<?=$a['publish_at']?>" ><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?=lang_btn_approve?>"></a>
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
                        <?php if ($totalPages > 1): ?>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                                <div id="pagination-results">
                                                            <?php 
                                                            
                                                                echo renderPagination($page, $totalPages,[]);
                                                            
                                                            ?>
                                                </div>            
                                        </td>
                                    </tr>
                                </tfoot>
                        <?php endif; ?>
                    </table>

                        
                    
    </div>
</div>

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

// Initializare Select2 pentru selectoare

$(document).ready(function () {
  function formatWithIcon(option) {
    if (!option.id) return option.text;

    const img = $(option.element).data('img');
    return $(
      `<span><img src="${img}" class="select2-option-img" width="20" style="margin-right:8px;" />${option.text}</span>`
    );
  }

 

  // Article's CATEGORY select
  $('#add_category_select').select2({
    placeholder: "<?=lang_cat_select?>",
    templateResult: formatWithIcon,
    templateSelection: formatWithIcon,
    allowClear: true
  });

 
});

</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

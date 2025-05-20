<?php
require_once '../../config/bootstrap.php';


$ops=['approve_comment','edit_comment','delete_comment','reject_comment'];

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

//$commentId = (int)$_POST['comment_id'];

    if (isset($_GET['approve'])) {
        $stmt = $db->prepare("UPDATE article_comments SET status = 'approved' WHERE id = ?");
        $stmt->execute([$_GET['approve']]);
        logActivity($_SESSION['user']['id'], 'approve_comment', 'User ' .$_SESSION['user']['username'] .' approved a comment');
        header("Location: comments.php");
        exit;
    }

    if (isset($_GET['reject'])) {
        $stmt = $db->prepare("UPDATE article_comments SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$_GET['reject']]);
        logActivity($_SESSION['user']['id'], 'reject_comment', 'User ' .$_SESSION['user']['username'] .' rejected a comment');
        header("Location: comments.php");
        exit;
    }
    if (isset($_GET['delete'])) {
        $stmt = $db->prepare("DELETE FROM article_comments WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        logActivity($_SESSION['user']['id'], 'delete_comment', 'User ' .$_SESSION['user']['username'] .' deleted a comment');
        header("Location: comments.php");
        exit;
    }


$stmt=$db->prepare("SELECT 
                            c.id, 
                            c.content, 
                            c.created_at, 
                            c.status, 
                            u.username, 
                            a.title, 
                            a.id AS article_id
                        FROM article_comments c
                        JOIN users u ON u.id = c.user_id
                        JOIN articles a ON a.id = c.article_id
                        ORDER BY c.created_at DESC
                    ");

$stmt->execute();
$comments = $stmt->fetchAll();






?>
<?php include APP_ROOT . 'includes/header.php'; ?>

<h2>Administrare comentarii</h2>

<?php if (empty($comments)): ?>
  <p><?=lang_com_msg_nocommw?></p>
<?php else: ?>
  <table class="articles-table" width="90%">
    <thead>
      <tr>
        <th><?=lang_com_article?></th>
        <th><?=lang_com_user?></th>
        <th><?=lang_com_comm?></th>
        <th><?=lang_com_data?></th>
        <th><?=lang_com_status?></th>
        <th><?=lang_com_actions?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($comments as $c): ?>
        <tr>
          <td width="20%">
            <a href="../view_article.php?id=<?= $c['article_id'] ?>" target="_blank">
              <?= htmlspecialchars($c['title']) ?>
            </a>
          </td>
          <td width="10%"><?= htmlspecialchars($c['username']) ?></td>
          <td width="40%"><?= nl2br(htmlspecialchars($c['content'])) ?></td>
          <td width="10%"><?= date('Y-m-d H:i', strtotime($c['created_at'])) ?></td>
          <td><?= htmlspecialchars($c['status']) ?></td>
          <td width="20%" align="center">
            <form method="post" style="display:inline;">
              <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                <?php
                    $op=['approve_comment'];

                    if (hasPermission($_SESSION['user']['id'],$op)){
                ?>
                        <!-- afisez butonu "Aproba" daca statusul este "pending" -->
                        <?php if ($c['status'] == 'pending' || $c['status'] == 'rejected'){ ?>
                            <a href="?approve=<?= $c['id']?>" ><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-approve.svg" title="<?=lang_btn_approve?>"></a>
                        <?php } ?>
                <?php } ?>
                
                <?php
                    $op=['edit_comment'];

                    if (hasPermission($_SESSION['user']['id'],$op)){
                ?>  
                        <a href="edit_comment.php?cid=<?= $c['id'] ?>"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-edit.svg" title="<?=lang_btn_edit?>"></a>
                        
                <?php } ?>
              
                <?php
                    $op=['reject_comment'];

                    if (hasPermission($_SESSION['user']['id'],$op)){
                ?>
                    <!--afisez butonul Respinge daca comentriul nu este deja rejectat -->
                    <?php if ( $c['status'] == 'approved'){ ?>
                        <a href="?reject=<?= $c['id'] ?>"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-reject.svg" title="<?=lang_btn_reject?>"></a>
                    <?php } ?>

                <?php } ?>
              
              <?php
                    $op=['delete_comment'];

                    if (hasPermission($_SESSION['user']['id'],$op)){
                ?>
                        <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Sigur?')"><img class="op-icon" src="<?=APP_URL?>assets/icons/icon-delete.svg" title="<?=lang_btn_delete?>"></a>
                <?php } ?>
              
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>





<?php include APP_ROOT . 'includes/footer.php'; ?>

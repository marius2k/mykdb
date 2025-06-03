<?php



require_once '../config/bootstrap.php';

require_login();

$ops=['view_own_activity','view_all_activity'];

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

$userId = $_SESSION['user']['id'];

$drafts = $db->fetchAll("
  SELECT id, title, created_at 
  FROM articles 
  WHERE user_id = ? AND status = 'draft'
  ORDER BY created_at DESC
", [$userId]);




?>
<?php include APP_ROOT . 'includes/header.php'; ?>

<?php if ($drafts): ?>

<div class="custom-box" style="width: 50%;">
    <span class="corner-label">
        <?=lang_articles_in_draft?>
    </span>
    <div class="box-content">
        <ul class="list-group">
        <?php foreach ($drafts as $draft): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($draft['title']) ?></strong><br>
                <small class="text-muted">creat la <?= date('Y-m-d H:i', strtotime($draft['created_at'])) ?></small>
            </div>
            <div class="btn-group">

                <a href="edit_article.php?id=<?= $draft['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-edit.svg" class="op-icon" title="<?=lang_btn_edit?>"></a>
            
                <a href="submit_article.php?article_id=<?= $draft['id'] ?>"><img src="<?=APP_URL?>assets/icons/icon-send-approval.svg" class="op-icon" title="<?=lang_btn_send_approval?>"></a>

            </div>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php include APP_ROOT . 'includes/footer.php'; ?>


<script>
 // Când DOM-ul este gata, inițializăm toate box-urile
  document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box');
    allCustomBoxes.forEach(box => {
      initializeCustomBox(box);
    });

    // Exemplu de inițializare cu o culoare de fundal specifică, dacă ar fi cazul
    // const specificBox = document.querySelector('.another-box-style');
    // if (specificBox) {
    //   initializeCustomBox(specificBox, 'lightgray'); // Dacă acest box e pe un fundal gri
    // }
  });

  </script>
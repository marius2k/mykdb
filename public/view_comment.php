<?php

require_once '../config/bootstrap.php';

$ops=['view_comment'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


// Verifică dacă ID-ul comentariului a fost trimis
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $commentId = intval($_GET['id']);

    // Creează o instanță a clasei Database
    $db = new Database();

    try {
        // Pregătește interogarea SQL pentru a obține detaliile comentariului
        $sql = "SELECT ac.*, u.username FROM article_comments ac
                JOIN users u ON ac.user_id = u.id
                WHERE ac.id = :id";
        $stmt = $db->getPdo()->prepare($sql);
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        // Verifică dacă comentariul există
        if ($stmt->rowCount() > 0) {
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['message'] = 'Comentariul nu a fost găsit.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Eroare de bază de date: ' . htmlspecialchars($e->getMessage());
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    $_SESSION['message'] = 'ID-ul comentariului nu este valid.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
<?php include APP_ROOT.'includes/header.php'; ?>

    <div class="container">
        <h1>Vizualizare Comentariu</h1>
        <div class="comment-box">
            <p><strong>Comentariu:</strong></p>
            <p><?php echo htmlspecialchars($comment['content']); ?></p>
            <p><strong>Autor:</strong> <?php echo htmlspecialchars($comment['username']); ?></p>
            <p><strong>Creat la:</strong> <?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></p>
        </div>

        <div class="action-buttons">
            <form action="comment_action.php" method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $commentId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" value="'<?php echo $comment['user_id'] ?>'">
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn-icon"><img src="<?=APP_URL?>assets/icons/icon-approve.svg" class="op-icon" title="<?= lang('lang_com_approve') ?>" style="width:24;height:auto;"></button>
            </form>
            <form action="comment_action.php" method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $commentId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" value="'<?php echo $comment['user_id'] ?>'">
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn-icon"><img src="<?=APP_URL?>assets/icons/icon-reject.svg" class="op-icon" title="<?= lang('lang_com_approve') ?>" style="width:24;height:auto;"></button>

            </form>
            <form action="comment_action.php" method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $commentId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" value="'<?php echo $comment['user_id'] ?>'">
                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn-icon" onclick="return confirm('Sigur vrei să ștergi acest comentariu?');"><img src="<?=APP_URL?>assets/icons/icon-delete.svg" class="op-icon" title="<?= lang('lang_com_approve') ?>" style="width:24;height:auto;"></button>
                

            </form>
        </div>

        <div class="message">
            <?php
            if (isset($_SESSION['message'])) {
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
            }
            ?>
        </div>
    </div>
<?php include APP_ROOT.'includes/footer.php'; ?>
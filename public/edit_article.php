<?php
require_once '../config/bootstrap.php';
require_login();




$id = (int)($_GET['id'] ?? 0);

$ops = ['edit_article'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}

// Obține articolul
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    die("Articol inexistent.");
}

// Doar autorul sau adminul poate edita
/*
if ($_SESSION['user']['id'] !== $article['user_id'] && $_SESSION['user']['role'] !== 'admin') {

    logActivity($_SESSION['user']['id'], 'edit_article', 'User '. $_SESSION['user']['username'].'tried to edit an article without permission');

    die("Nu ai permisiunea să modifici acest articol.");
    
}
*/
// Preia categorii
$db = new Database();
$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Procesare form
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        header('Location: '. APP_URL . 'public/admin/articles.php');
        exit;
    }
    
    if (!isset($_POST['action']) || !in_array($_POST['action'], ['submit', 'draft'])) {
        $errors[] = 'Acțiune necunoscută.';
    }
    
    if ($_POST['action'] === 'submit') {
        $status = 'pending'; // Set status to pending for approval
    } elseif ($_POST['action'] === 'draft') {
        $status = 'draft'; // Set status to draft
    } 

    //$status = $_POST['action'] === 'draft' ? 'draft' : 'pending';


    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $publish_at = $_POST['publish_at'] ?? time();
    if (strlen($title) < 5) {
        $errors[] = 'Titlul trebuie să aibă minim 5 caractere.';
    }
    if (strlen($content) < 20) {
        $errors[] = 'Conținutul trebuie să aibă minim 20 de caractere.';
    }

    if (!$category_id) {
        $errors[] = 'Alege o categorie validă.';
    }

    if (empty($errors)) {
        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content); // dacă ai folosit funcția anterioară
        $updated_at = date('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, status = ?, updated_at = ?, publish_at = ? WHERE id = ?");
        $stmt->execute([$title, $clean_content, $category_id, $status, $updated_at, $publish_at, $id]);
        
        // Log the edit
        logActivity($_SESSION['user']['id'], 'edit_article', 'User '. $_SESSION['user']['username'].' edited an article');
        
        header('Location: '. APP_URL . 'public/admin/articles.php?updated=1');
        exit;
    }
}
?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="form-container">
    <h2>📝 Modifică articolul</h2>

    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $e): ?>
                <p><?= escape($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="title">Titlu articol:</label>
            <input type="text" id="title" name="title" value="<?= escape($article['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="content">Conținut:</label>
            <input id="content" type="hidden" name="content" value="<?= escape($article['content']) ?>">
            <trix-editor input="content"></trix-editor>
        </div>

        <div class="form-group">
            <label for="category">Categorie:</label>
            <select name="category_id" id="category" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $article['category_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= escape($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; flex-direction: row; gap: 10px;">
            <label style="width:30%" for="publish_at"><?=lang_art_publish_at?></label>
            <input style="width:70%" type="datetime-local" name="publish_at" id="publish_at" class="form-control" value="<?= isset($article['publish_at']) ? date('Y-m-d\TH:i', strtotime($article['publish_at'])) : '' ?>">
        </div>
        <div style="display: flex; gap: 10px;">

            <button type="submit" name="action" value="draft" class="btn btn-outline-grey"><?=lang_create_article_draft?></button>
            <button type="submit" name="action" value="submit" class="btn btn-outline-grey"><?=lang_create_article_submit?></button>
            <button type="submit" name="action" value="cancel" class="btn btn-outline-grey"><?=lang_btn_cancel?></button>

        </div>
    </form>
</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>

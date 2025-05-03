<?php
require_once '../config/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

// Obține articolul
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    die("Articol inexistent.");
}

// Doar autorul sau adminul poate edita
if ($_SESSION['user']['id'] !== $article['user_id'] && $_SESSION['user']['role'] !== 'admin') {
    die("Nu ai permisiunea să modifici acest articol.");
}

// Preia categorii
$db = new Database();
$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Procesare form
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = (int)$_POST['category_id'];

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

        $stmt = $db->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, status = 'pending' WHERE id = ?");
        $stmt->execute([$title, $clean_content, $category_id, $id]);

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

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-primary">💾 Salvează modificările</button>
            <a href="admin/articles.php" class="btn-secondary">❌ Renunță</a>
        </div>
    </form>
</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>

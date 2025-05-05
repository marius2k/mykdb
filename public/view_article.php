<?php
require_once '../config/bootstrap.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

$db = new Database();

// Fetch articolul
$stmt = $db->prepare("
    SELECT a.*, u.username, c.name AS category
    FROM articles a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$article = $stmt->fetch();


logActivity($_SESSION['user']['id'], 'article_viewed', 'User ' .$_SESSION['user']['username'] .' viewed the article:'.$article['title']);

if (!$article) {
    echo "Articol inexistent sau neaprobat.";
    exit;
}
?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div style="max-width:900px; margin:20px auto;">
    <h2><?= escape($article['title']) ?></h2>
    <p style="color=#"><em>Autor: <?= escape($article['username']) ?> | Categorie: <?= escape($article['category']) ?> | Publicat: <?= formatDate($article['created_at']) ?> | Actualizat: <?= $article['updated_at']?></em></p>

    <hr>

    <div class="article-preview">
        <?= $article['content'] ?>
    </div>
</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>

<?php



require_once '../config/bootstrap.php';
require_login();




$errors = [];
$user_id = $_SESSION['user']['id'];

$db = new Database();


// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category_id'];

    if (!$title || !$content || !$category_id) {
        $errors[] = 'Toate câmpurile sunt obligatorii.';
    } else {

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $clean_content, $category_id, $user_id]);
        // Log the creation
        logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created the article:'. $_POST['title']);
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch categories for dropdown
$categories = $db->query("SELECT * FROM categories")->fetchAll();
?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<!-- search results -->
<div id="searchResults" style="margin-top:10px;"></div>

<div id="defaultContent">
    <div class="form-container">
            <h2>✍️ Creează un articol nou</h2>

            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $e): ?>
                        <p><?= escape($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="article-form">
                <div class="form-group">
                    <label for="title">Titlu articol:</label>
                    <input type="text" id="title" name="title" placeholder="Titlul articolului" required>
                </div>

                <div class="form-group">
                    <label for="content">Conținut:</label>
                    <input id="content" type="hidden" name="content">
                    <trix-editor input="content"></trix-editor>
                </div>

                <div class="form-group">
                    <label for="category">Categorie:</label>
                    <select name="category_id" id="category" required>
                        <option value="">-- Selectează o categorie --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= escape($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Trimite spre Aprobare</button>
            </form>
    </div>          
<script>
        document.addEventListener('trix-attachment-add', function(event) {
            const attachment = event.attachment;

            if (attachment.file) {
                uploadImage(attachment);
            }
        });

        function uploadImage(attachment) {
            const file = attachment.file;
            const form = new FormData();
            form.append('image', file);

            fetch('upload_image.php', {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.url) {
                    attachment.setAttributes({
                        url: data.url,
                        href: data.url
                    });
                } else {
                    alert('Eroare upload: ' + (data.error || 'necunoscută'));
                }
            })
            .catch(error => {
                console.error('Eroare upload:', error);
                alert('Eroare de rețea la upload!');
            });
        }
</script>


</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>

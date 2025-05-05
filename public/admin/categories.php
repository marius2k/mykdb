<?php

require_once '../../config/bootstrap.php';


require_admin();

$db = new Database();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');

    if (strlen($name) >= 2) {
        $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        header("Location: categories.php");
        exit;
    } else {
        echo "<p style='color:red; text-align:center;'>Numele trebuie să aibă cel puțin 2 caractere.</p>";
    }
}


if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    
    // Log the deletion
    logActivity($_SESSION['user']['id'], 'category_deleted', 'User'.$_SESSION['user']['username']. ' deleted category : ' . $_GET['delete']);
  
    header('Location: categories.php');
    exit;
}


// paginate categories

$perPage = 2;
$totalStmt = $db->query("SELECT COUNT(*) FROM categories");
$totalCategories = $totalStmt->fetchColumn();
$totalPages = ceil($totalCategories / $perPage);

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $perPage;


$stmt = $db->prepare("SELECT * FROM categories LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();


//$categories = $db->query("SELECT * FROM categories")->fetchAll();



?>

<?php include APP_ROOT . 'includes/header.php'; ?>


<h2>📁 Categorii</h2>

<table class="articles-table" id="categories-table">
    <thead>
        <tr>
            <th width="50px" align="center">
              <button class="btn-plus-icon" onclick="toggleAddCategory()" title="Adaugă categorie">
              <img src="../../assets/images/btn_add_icon.png" alt="Add" class="icon-img rotate-on-hover">
              </button>
            </th>
            <th>Nume</th>
            <th>Descriere</th>
            <th>Acțiuni</th>
        </tr>
    </thead>
    <tbody>
        <!-- Rând buton "+" -->
        <!-- 
        <tr id="add-toggle-row">
            <td colspan="4" style="padding: 0;">
                <button class="btn-plus-icon" onclick="toggleAddCategory()" title="Adaugă categorie">➕</button>
            </td>
        </tr>
        -->
        
        <!-- Formular de adăugare, ascuns -->
        <tr id="add-form-row" style="display: none;">
            <form method="POST">
                <td align="center">-></td>
                <td><input type="text" name="name" placeholder="Categorie" required></td>
                <td><input type="text" name="description" placeholder="Descriere"></td>
                <td><button type="submit" class="btn-sm btn-outline-grey">✅ Salvează</button></td>
            </form>
        </tr>

        <!-- Rânduri existente -->
        <?php foreach ($categories as $c): ?>
            <tr>
                <td align="center"><?= $c['id'] ?></td>
                <td><?= escape($c['name']) ?></td>
                <td><?= escape($c['description']) ?></td>
                <td>
                    <div class="action-grid">
                        <a href="edit_category.php?id=<?= $c['id'] ?>" class="btn-sm btn-outline-grey">✏️ Editeaza</a>
                        <a href="delete_category.php?id=<?= $c['id'] ?>" class="btn-sm btn-outline-grey" onclick="return confirm('Ștergi această categorie?')">🗑️ Sterge</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>

    <tfoot>
    <tr>
        <td colspan="4">
            <div class="pagination-footer">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i === $currentPage ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </td>
    </tr>
    </tfoot>

</table>


<script>
function toggleAddCategory() {
    const row = document.getElementById('add-form-row');
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>




<?php include APP_ROOT . 'includes/footer.php'; ?>

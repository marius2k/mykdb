<?php

require_once '../../config/bootstrap.php';


require_admin();

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
}

if (isset($_GET['disable'])) {
    $stmt = $db->prepare("UPDATE articles SET status='pending' WHERE id=?");
    $stmt->execute([$_GET['disable']]);
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

<h2>Articole</h2>
<table class="articles-table">
    <thead>
        <tr>
            <th align="center">#</th>
            <th>Titlu</th>
            <th>Autor</th>
            <th>Categorie</th>
            <th>Status</th>
            <th>Acțiuni</th>
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
            <td>
                
                <a href="../view_article.php?id=<?= $a['id'] ?>" class="btn-sm btn-outline-grey">👁️  Vizualizeaza</a>
                <a href="../edit_article.php?id=<?= $a['id'] ?>" class="btn-sm btn-outline-grey">✏️ Editeaza</a>
                
                <?php if ($a['status'] == 'pending'): ?>
                    <a href="?approve=<?= $a['id'] ?>" class="btn-sm btn-outline-grey">✅ Aproba</a>
                <?php else: ?>
                    <a href="?disable=<?= $a['id'] ?>" class="btn-sm btn-outline-grey" onclick="return confirm('Dezactivezi acest articol?')">🗑️ Dezactiveaza</a>
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

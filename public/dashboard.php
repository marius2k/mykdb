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

$errors = [];
$userId = $_SESSION['user']['id'];
$isAdmin = ($_SESSION['user']['role'] === 'admin');



$perPage = 10; // randuri pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$db = new Database();

if (!isset($filterUserId)) {
    $filterUserId = 0;
}

if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $filterUserId = $_GET['filterUserId'];
} else {
    $filterUserId = $_GET['user_id'] ?? '0';
}

//$filterUserId = $_GET['user_id'] ?? null;



//echo "Filter User ID: " . $filterUserId;

if (!$isAdmin) {

    // Non-admins can only see their own actions

        
   
    $stmt = $db->prepare("
        SELECT l.*, u.username
        FROM activity_log l
        JOIN users u ON l.user_id = u.id
        WHERE l.user_id = :uid
        ORDER BY l.created_at DESC
        LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);


        $stmt->execute();
        $logs = $stmt->fetchAll();
       

        // Total rows (pt paginare)

        $totalStmt = $db->query("SELECT COUNT(*) FROM activity_log WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
        //$totalRows= $totalStmt->execute([$userId]);
        $totalRows = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRows / $perPage);
        //echo " totalRows: " . $totalRows;
        
        //header('Location: dashboard.php');
        //exit;
} else {

    // Admins can see all actions

    if ($filterUserId) {
        
        // If a user ID is provided, filter by that user
        

        // Total rows (pt paginare)
        
        $totalStmt = $db->query("SELECT COUNT(*) FROM activity_log WHERE user_id = ? ORDER BY created_at DESC", [$filterUserId]);
        $totalRows = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRows / $perPage);

        //echo " totalRows: " . $totalRows;

        $stmt = $db->prepare("
            SELECT l.*, u.username
            FROM activity_log l
            JOIN users u ON l.user_id = u.id
            WHERE l.user_id = :uid
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
            ");

        $stmt->bindValue(':uid', $filterUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll();

        
        // Fetch the username for the selected user
        
        $user = $db->fetchSingle("SELECT username FROM users WHERE id = ?", [$filterUserId]);
        
        // Add the username to the first log entry
        if ($user) {
            echo " User selectat: " . $user['username'];
            foreach ($logs as $key => $log) {
                $log[0]['username'] = $user['username'];
            }    
        } else {
            $errors[] = 'Utilizatorul nu a fost găsit.';
        }

        
    } else {
        //if no user ID is provided, show all logs

        // Total rows (pt paginare)
        $totalStmt = $db->query("SELECT COUNT(*) FROM activity_log");
        $totalRows = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRows / $perPage);

        $stmt = $db->prepare("
            SELECT l.*, u.username
            FROM activity_log l
            JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
            ");

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $logs = $stmt->fetchAll();

        
    }
    
    
}

// Fetch all users for the dropdown
$users = $db->fetchAll("SELECT id, username FROM users ORDER BY username");


?>

<?php include APP_ROOT . 'includes/header.php'; ?>


            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $e): ?>
                        <p><?= escape($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            
            <h2><?=lang_db_activity_log?></h2>
            
            <?php 
            if ($isAdmin) { 
                // show filter form if user is admin
            ?>
                <form method="get" class="mb-3">
                    <label><?=lang_db_filter_by_user?></label>
                    <select name="user_id" onchange="this.form.submit()">
                        <option value="0">-- <?=lang_db_filter_all_users?> --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filterUserId == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php } ?>

                <table class="articles-table">
                    <thead>
                        <tr>
                            <th><?=lang_db_log_table_user?></th>
                            <th><?=lang_db_log_table_action?></th>
                            <th><?=lang_db_log_table_agent?></th>
                            <th><?=lang_db_log_table_details?></th>
                            <th><?=lang_db_log_table_data?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $filterUserId === 0 ? $user['username'] : $log['username'] ?></td>
                                <td><?= htmlspecialchars($log['action_type']) ?></td>
                                <td><?= htmlspecialchars($log['user_agent']) ?></td>
                                <td>
                                    <?php
                                    $details = json_decode($log['details'], true);
                                    if (is_array($details)) {
                                        foreach ($details as $k => $v) {
                                            echo "<strong>" . htmlspecialchars($k) . ":</strong> " . htmlspecialchars($v) . "<br>";
                                        }
                                    } else {
                                        echo htmlspecialchars($log['details']);
                                    }
                                    ?>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="6">
                            <div class="pagination-footer">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=<?= $i ?>&filterUserId=<?= $filterUserId ?>" class="<?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </td>
                    </tr>
                    </tfoot>
                </table>

                <form method="post" action="export_activity_log.php">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($filterUserId) ?>">
                    <button type="submit" class="btn btn-success">Export CSV</button>
                </form>

<?php include APP_ROOT . 'includes/footer.php'; ?>

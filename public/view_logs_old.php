<?php



require_once '../config/bootstrap.php';
require_login();

$ops=['view_own_logs','view_all_logs'];

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
$isAdmin = ($_SESSION['user']['role'] === 'admin') || ($_SESSION['user']['role'] === 'superadmin');

//$currentPage= isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;


$perPage = 10; // randuri pe pagină
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
/*
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
*/

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

$userId = $_SESSION['user']['id'] ;
$isAdmin = hasAllPermission($userId, ['view_all_logs', 'view_own_logs', 'delete_all_logs','delete_own_logs','archive_all_logs','archive_own_logs']);

//echo "Filter User ID: " . $filterUserId;
//echo "<br>isAdmin: ". $isAdmin;

// DEFINIM DATELE DE FILTRARE

$filterUserId = $_GET['user_id'] ?? '0'; 
$start = isset($_GET['start_date']) && !empty($_GET['start_date']) ? date('Y-m-d H:i:s', strtotime($_GET['start_date'])) : '2000-01-01 00:00:00';
$end = isset($_GET['end_date']) && !empty($_GET['end_date']) ? date('Y-m-d H:i:s', strtotime($_GET['end_date'])) : date('Y-m-d H:i:s');



if (!$isAdmin) {

    // Non-admins can only see their own actions

        
    $sql="SELECT l.*, u.username
        FROM activity_log l
        JOIN users u ON l.user_id = u.id
        WHERE l.archived = 0
        AND l.user_id = :uid
        AND l.created_at BETWEEN :startd AND :endd  /* ADĂUGAT: Filtre de dată */
        ORDER BY l.created_at DESC
        LIMIT :limit OFFSET :offset";

    //echo "<br> SQL: ". $sql;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':startd', $start, PDO::PARAM_STR); // Corectat la PARAM_STR
    $stmt->bindValue(':endd', $end, PDO::PARAM_STR);   // Corectat la PARAM_STR
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

    // Total rows (pt paginare) - ASIGURĂ-TE CĂ CONDIȚIILE SE POTRIVESC CU CELE DE MAI SUS
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = :uid AND archived = 0 AND created_at BETWEEN :startd AND :endd");
    $totalStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $totalStmt->bindValue(':startd', $start, PDO::PARAM_STR);
    $totalStmt->bindValue(':endd', $end, PDO::PARAM_STR);
    $totalStmt->execute();
    $totalRows = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

} else {

    // Admins can see all actions

    if ($filterUserId && $filterUserId != '0') { // Important: '0' ar trebui să însemne "toți utilizatorii"
        // If a user ID is provided, filter by that user

        // Total rows (pt paginare)
        $totalStmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = :uid AND archived = 0 AND created_at BETWEEN :startd AND :endd");
        $totalStmt->bindValue(':uid', $filterUserId, PDO::PARAM_INT);
        $totalStmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $totalStmt->bindValue(':endd', $end, PDO::PARAM_STR);
        $totalStmt->execute();
        $totalRows = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRows / $perPage);

        // SQL pentru log-uri
        $sql="SELECT l.*, u.username 
            FROM activity_log l
            JOIN users u ON l.user_id = u.id
            WHERE l.archived = 0
            AND l.user_id = :uid
            AND l.created_at BETWEEN :startd AND :endd
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $stmt->bindValue(':endd', $end, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $filterUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        // Fetch the username for the selected user (dacă este necesar, pare a fi folosit doar pentru un mesaj)
        // L-aș muta mai jos, după ce 'logs' este populat
        // Aici este o posibilă confuzie: $filterUserId === 0 ? $user['username'] : $log['username']
        // În acest bloc, $filterUserId este deja setat, deci $user['username'] ar fi pentru userul filtrat.
        // Asigură-te că $user este de asemenea setat corect.
        $user_filtered = $db->fetchSingle("SELECT username FROM users WHERE id = ?", [$filterUserId]);
        if ($user_filtered) {
             // Această buclă nu este necesară. Username-ul vine din JOIN.
             // foreach ($logs as $key => $log) { $log[0]['username'] = $user['username']; }
        } else {
             $errors[] = 'Utilizatorul filtrat nu a fost găsit.';
        }

        
    } else {
        // Admin, show all logs (no specific user filter, but date filters might apply)
        
        // Total rows (pt paginare) - ADĂUGAT: Filtre de dată
        $totalStmt = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE archived = 0 AND created_at BETWEEN :startd AND :endd");
        $totalStmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $totalStmt->bindValue(':endd', $end, PDO::PARAM_STR);
        $totalStmt->execute();
        $totalRows = $totalStmt->fetchColumn();
        $totalPages = ceil($totalRows / $perPage);

        $stmt = $db->prepare("
            SELECT l.*, u.username
            FROM activity_log l
            JOIN users u ON l.user_id = u.id
            WHERE l.archived = 0
            AND l.created_at BETWEEN :startd AND :endd  /* ADĂUGAT: Filtre de dată */
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
            ");
        
        $stmt->bindValue(':startd', $start, PDO::PARAM_STR);
        $stmt->bindValue(':endd', $end, PDO::PARAM_STR);
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

            
                      
            <?php 
            if ($isAdmin) { 
                // show filter form if user is admin
            ?>
        <div style="display: flex; justify-content: center; width: 100%;">
            <div class="custom-box-1">
                <div class="corner-label-1">Filter by</div>
                <div class="box-content-1" >
                    <form id="logFilterForm" method="get" class="mb-3" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                            <div>
                                <label><?= lang('lang_log_filter_user') ?></label><br>
                                <select id="filterUserId" name="user_id" onchange="triggerLogFilter()">
                                <option value="0">-- <?= lang('lang_db_filter_all_users') ?> --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $filterUserId == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="start_date"><?= lang('lang_log_filter_start_date') ?? 'De la data' ?></label><br>
                                <input type="datetime-local" id="start_date" name="start_date"
                                value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>"
                                onchange="triggerLogFilter()">
                            </div>

                            <div>
                                <label for="end_date"><?= lang('lang_log_filter_end_date') ?? 'Până la data' ?></label><br>
                                <input type="datetime-local" id="end_date" name="end_date"
                                value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>"
                                onchange="triggerLogFilter()">
                            </div>
                            <div>
                                    <a href="#" id="resetFiltersLink">
                                        <img src="<?=APP_URL?>assets/icons/icon-reset.svg" title="<?= lang('lang_log_filter_reset') ?>" alt="Reset Filters" style="width: 25px; height: auto;">
                                    </a>                            
                            </div>
                    </form>
                    
                </div>
                <?php } ?>
            </div>
        </div>
        

            <form id="bulkLogForm" method="POST">
                <table class="articles-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll" onclick="toggleAllLogs(this)"></th>
                            <th><?= lang('lang_db_log_table_user') ?></th>
                            <th><?= lang('lang_db_log_table_action') ?></th>
                            <th><?= lang('lang_db_log_table_agent') ?></th>
                            <th><?= lang('lang_db_log_table_details') ?></th>
                            <th><?= lang('lang_db_log_table_data') ?></th>
                            <th width="100px">Archive/Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><input type="checkbox" name="log_ids[]" value="<?= $log['id'] ?>"></td>
                                <td><?= $filterUserId === 0 ? $user['username'] : $log['username'] ?></td>
                                <td><?= htmlspecialchars($log['action_type']) ?></td>
                                <td><?= truncateText(htmlspecialchars($log['user_agent']),80) ?></td>
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
                                <td align="center">
                                    <?php if ($log['user_id'] == $userId || $isAdmin): ?>
                                        <a href="#" onclick="archiveLog(<?= $log['id'] ?>)"><img src="<?= APP_URL?>assets/icons/icon-archive.svg" width="25" height="auto"></a>&nbsp;
                                        <a href="#" onclick="deleteLog(<?= $log['id'] ?>)"><img src="<?= APP_URL?>assets/icons/icon-delete.svg" width="25" height="auto"></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                   <?php if ($totalPages > 1): ?>
                        <tfoot>
                        <tr>
                            <td colspan="7">
                                <div id="pagination-results">
                                        <?php 
                                            echo renderPagination2($currentPage, $totalPages, [
                                                                    'user_id' => $filterUserId,
                                                                    'start_date' => $_GET['start_date'] ?? '',
                                                                    'end_date' => $_GET['end_date'] ?? ''
                                                                    ]);
                                                                
                                        ?>
                                </div>      
                            </td>
                        </tr>
                        </tfoot>
                    <?php endif; ?>

                </table>

                <br><br>
                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-outline-grey" onclick="submitBulkLogs('archive')"><?= lang('lang_log_archive_selected') ?></button>&nbsp;&nbsp;
                    <button type="button" class="btn btn-outline-grey" onclick="submitBulkLogs('delete')"><?= lang('lang_log_delete_selected') ?></button>
                </div>
            </form>

                   <br>
                <form method="post" action="export_activity_log.php">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($filterUserId) ?>">
                    <button type="submit" class="btn-sm btn-disabled">Export CSV</button>
                </form>

<?php include APP_ROOT . 'includes/footer.php'; ?>

<script>


 function triggerLogFilter() {
  const userId = document.getElementById('filterUserId').value;
  const start = document.getElementById('start_date').value;
  const end = document.getElementById('end_date').value;

  // 🧠 Salvăm în localStorage (pentru persistență la reîncărcarea paginii, dacă userul pleacă și revine)
  localStorage.setItem('logFilterUserId', userId);
  localStorage.setItem('logFilterStartDate', start);
  localStorage.setItem('logFilterEndDate', end);

  // Redirecționează cu parametrii în URL
  const params = new URLSearchParams();
  if (userId && userId !== '0') params.append('user_id', userId); // Păstrează 'user_id'
  if (start) params.append('start_date', start);
  if (end) params.append('end_date', end);

  window.location.href = '?' + params.toString(); // Aceasta este corect
}

function resetLogFilters() {
  localStorage.removeItem('logFilterUserId');
  localStorage.removeItem('logFilterStartDate');
  localStorage.removeItem('logFilterEndDate');
  window.location.href = '<?= basename($_SERVER['PHP_SELF']) ?>';
}




 
  // Când DOM-ul este gata, inițializăm toate box-urile
document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });

   
    const resetLink = document.getElementById('resetFiltersLink'); 
        
    if (resetLink) {
            resetLink.addEventListener('click', (e) => {
                e.preventDefault(); // Foarte important: Opresc comportamentul implicit al link-ului (să navigheze la "#")
                resetLogFilters(); // Apelez funcția de resetare
            });
    }

    const savedUserId = localStorage.getItem('logFilterUserId');
    const savedStart = localStorage.getItem('logFilterStartDate');
    const savedEnd = localStorage.getItem('logFilterEndDate');

    if (savedUserId !== null) document.getElementById('filterUserId').value = savedUserId;
    if (savedStart) document.getElementById('start_date').value = savedStart;
    if (savedEnd) document.getElementById('end_date').value = savedEnd;

});


    
  </script>
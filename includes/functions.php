<?php

// language translation
function lang($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
/**
 * Escape HTML special characters
 */
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a MySQL timestamp to readable date
 */
function formatDate($datetime) {
    return date("d.m.Y H:i", strtotime($datetime));
}

/**
 * Shorten long text (used in previews)
 */
function shortenText($text, $max = 200) {
    return strlen($text) > $max 
        ? substr($text, 0, $max) . '...' 
        : $text;
}

function truncateText($text, $maxChars, $ellipsis = '...') {
    if (mb_strlen($text) <= $maxChars) {
        return $text;
    }

    // Taie textul la ultimul spațiu dinainte de $maxChars
    $truncated = mb_substr($text, 0, $maxChars);
    $lastSpace = mb_strrpos($truncated, ' ');

    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }

    return rtrim($truncated) . $ellipsis;
}

/**
 * Get all categories as array (id => name)
 */
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM categories");
    $cats = [];
    foreach ($stmt as $cat) {
        $cats[$cat['id']] = $cat['name'];
    }
    return $cats;
}

/**
 * Get username from user ID
 */
function getUserNameById($id) {

    $db = new Database();

    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

// return user_id of all users with a specific role name
function getUserIdByRoleName(string $role): array {
    $db = new Database();

    // Obține ID-ul rolului
    $roleData = $db->fetchSingle("SELECT id FROM roles WHERE name = ?", [$role]);
    if (!$roleData) {
        return []; // rolul nu există
    }

    $roleId = $roleData['id'];

    // Obține toți userii care au acel rol
    $users = $db->fetchAll("SELECT id FROM users WHERE role_id = ?", [$roleId]);

    // Extragem doar valorile id
    return array_column($users, 'id');
}


// return role name of a user by user_id

function getUserRoleById(int $userId,): ?string {
    
    $db = new Database();
    
    $sql = "
        SELECT r.name as label
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = :user_id
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['label'] ?? null;
}


// send notification to all users with a specific role
// returns number of notifications sent
// Example usage:
//          $count = sendNotificationToRole('moderator', 'info', 'Un articol nou a fost trimis spre aprobare.');
//          echo "$count moderatori au primit notificarea.";

function sendNotificationToRole(string $roleName, string $type, string $message): int {
    $db = new Database();

    // 1. Obține user_id-urile după rol
    $userIds = getUserIdByRoleName($roleName);

    if (empty($userIds)) {
        return 0; // nimeni cu rolul respectiv
    }

    // 2. Pregătim inserția bulk
    $placeholders = [];
    $values = [];
    $now = date('Y-m-d H:i:s');

    foreach ($userIds as $userId) {
        $placeholders[] = "(?, ?, ?, ?, 0)";
        $values[] = $userId;
        $values[] = $type;
        $values[] = $message;
        $values[] = $now;
    }

    $sql = "INSERT INTO notifications (user_id, type, message, created_at, is_read) VALUES " . implode(',', $placeholders);
    $db->query($sql, $values);

    return count($userIds); // număr notificări trimise
}


// returneaza un array cu toti userii activi
function getAllActiveUsers():array{

    $db = new Database();

    $stmt = $db->prepare("SELECT * FROM users WHERE status = 'active'");
    $stmt->execute();
    $users = $stmt->fetchAll();    

    return $users;
}

// get all roles from DB
// returns an array with all roles
function getAllRoles(): array {
    $db = new Database();
    $stmt = $db->prepare("SELECT * FROM roles");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $roles;
}

function loadConfig(){

    if (!defined('APP_ROOT')) {
        define('APP_ROOT', '/opt/lampp/htdocs/mykdb/');
    }
    if (!defined('APP_URL')) {
        define('APP_URL', 'http://localhost/mykdb/');
    }
    exit;

}
// Initialize guest session if not already set
// This function should be called at the start
//
function initGuestSession(){

    $db = new Database();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    
        // Caută userul guest în DB
        $stmt = $db->prepare("SELECT id, username, role_id FROM users WHERE username = 'guest' LIMIT 1");
        $stmt->execute();
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guest) {
            $_SESSION['user'] = [
                'id'       => (int)$guest['id'],
                'username' => $guest['username'],
                'first_name' => 'Guest', // Poți seta un nume generic
                'last_name' => '',
                'email'    => '', // Email gol pentru guest
                'role_id'  => (int)$guest['role_id'],
                'role'     => 'guest' // alternativ: folosește JOIN pe roles
            ];
        } else {
            // fallback în caz că guest nu există în DB
            $_SESSION['user'] = [
                'id'       => 0,
                'username' => 'guest',
                'role_id'  => null,
                'role'     => 'guest'
            ];
        }
    
        //echo "Guest session initialized: " . $_SESSION['user']['id']. "<br>";

    // Setări suplimentare default
    //$_SESSION['language'] ??= 'ro';
    //$_SESSION['theme']    ??= 'light';
}


// log article views in db
function logArticleView($articleId) {

    $db = new Database();
    $userId = $_SESSION['user']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $db->query(
        "INSERT INTO article_views (article_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
        [$articleId, $userId, $ip, $ua]
    );
}

function SystemStatus(){

    //echo '<div class="backgroubd: linear-gradient(to right,rgb(25, 108, 141),rgb(67, 190, 238)); width: 100%; display: flex; align-items: center; justify-content: space-between;">';
            
        echo '<div class="width: 100%; display: flex; align-items: center; justify-content: space-between; small">';
                $db = new Database();
                $stats = $db->fetchSingle("
                    SELECT 
                        (SELECT COUNT(*) FROM users) as users,
                        (SELECT COUNT(*) FROM articles WHERE status='approved') as aaproved,
                        (SELECT COUNT(*) FROM articles WHERE status='pending') as apending                        
                ");
                
                echo '  System contains: '.$stats['users'].' users | '.$stats['aaproved'].' articles approved | '.$stats['apending'].' articles in pending';
        echo '</div>';
    //echo '</div>';

}
function clean_html($html) {
    $allowed_tags = '<b><i><u><strong><em><ul><ol><li><p><br><a><h1><h2><h3><h4><img><figure>';
    return strip_tags($html, $allowed_tags);
}


function truncateHtmlWithImages($html, $limit = 500) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // suprimă warning-uri HTML
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    $body = $dom->getElementsByTagName('body')->item(0);
    $output = '';
    $length = 0;

    foreach ($body->childNodes as $node) {
        if ($length >= $limit) break;

        $frag = $dom->saveHTML($node);
        $textLength = mb_strlen(strip_tags($frag));

        if (($length + $textLength) > $limit) {
            $cut = $limit - $length;
            $textOnly = strip_tags($frag);
            $frag = mb_substr($textOnly, 0, $cut) . '...';
        }

        $output .= $frag;
        $length += $textLength;
    }

    return $output;
}

function removeImageCaptionText($html) {
    return preg_replace('/(<a[^>]+>\s*<img[^>]+>)([^<]+)?(<\/a>)/i', '$1$3', $html);
}

function full_name($user) {
    return trim($user['first_name'] . ' ' . $user['last_name']);
}



/**
 * Log user activity
 *
 * @param int|null $userId User ID (optional)
 * @param string $actionType Type of action (e.g., 'login', 'logout', 'update')
 * @param array $details Additional details (optional)
 */
function logActivity(int $userId = null, string $actionType, string $details = null) {
    $db = new Database();
    
    $data = [
        'user_id' => $userId,
        'action_type' => $actionType,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details ? json_encode($details) : null
    ];
    
    $db->insert('activity_log', $data);
}
/**
 * Get user setting from session
 *
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getUserSetting(string $key, $default = null) {
    return $_SESSION['settings'][$key] ?? $default;
}

/**
 * Generate navigation bar based on user role
 *
 * @param string $role User role (admin, user, guest)
 * @return string HTML for the navigation bar
 */
function generateNavBar($role = 'guest') {

    $currentPage = basename($_SERVER['SCRIPT_NAME']);
   
    //echo "Curent Page:".$currentPage;


     $nav = '';

    switch ($role) {
        case 'superadmin':
            $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang_home .'</a>
                <a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang_dashboard . '</a>
                <a href="'.APP_URL.'public/admin/users.php"'.($currentPage === 'users.php' ? ' class="bi bi-person-fill me-2 active"> ' : ' class="bi bi-person-fill me-2"> '). lang_users. '</a>
                <a href="'.APP_URL.'public/admin/categories.php"'.($currentPage === 'categories.php' ? ' class="bi bi-diagram-3-fill me-2 active"> ' : ' class="bi bi-diagram-3-fill me-2"> ').lang_categories.'</a>
                <a href="'.APP_URL.'public/admin/articles.php"'.($currentPage === 'articles.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang_articles.'</a>
                <a href="'.APP_URL.'public/admin/acl_edit.php"'.($currentPage === 'acl_edit.php' ? ' class="bi bi-gear-fill me-2 active"> ' : ' class="bi bi-gear-fill me-2"> ').lang_edit_acl.'</a>
                <a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang_logout .'('.escape($_SESSION['user']['username']).')</a>';
            break;
        case 'admin':
        case 'moderator':
        case 'editor':
        case 'contributor':
            $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang_home .'</a>
                <a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang_dashboard . '</a>
                <a href="'.APP_URL.'public/admin/users.php"'.($currentPage === 'users.php' ? ' class="bi bi-person-fill me-2 active"> ' : ' class="bi bi-person-fill me-2"> '). lang_users. '</a>
                <a href="'.APP_URL.'public/admin/categories.php"'.($currentPage === 'categories.php' ? ' class="bi bi-diagram-3-fill me-2 active"> ' : ' class="bi bi-diagram-3-fill me-2"> ').lang_categories.'</a>
                <a href="'.APP_URL.'public/admin/articles.php"'.($currentPage === 'articles.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang_articles.'</a>
                <a href="'.APP_URL.'public/settings.php"'.($currentPage === 'settings.php' ? ' class="bi bi-gear-fill me-2 active"> ' : ' class="bi bi-gear-fill me-2"> ').lang_settings.'</a>
                <a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang_logout .'('.escape($_SESSION['user']['username']).')</a>';
            break;

        case 'guest':
            $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active"> ' : ' class="bi bi-house-fill me-2"> ').lang_home.'</a>
                <a href="'.APP_URL.'public/login.php"'.($currentPage === 'login.php' ? ' class="bi bi-box-arrow-in-right me-2 active"> ' : ' class="bi bi-box-arrow-in-right me-2"> ').lang_login.'</a>
                <a href="'.APP_URL.'public/register.php"'.($currentPage === 'register.php' ? ' class="bi bi-r-square-fill me-2 active"> ' : ' class="bi bi-r-square-fill me-2"> ').lang_register.'</a>';
            break;
    }

    //echo "NavBar:".$nav;

    return $nav;
}

/*
    generate the navigation bar (NavBar), based on logged user 
    $uid - user id of logged user; 
*/
function generateNavBar2($uid) {

    $currentPage = basename($_SERVER['SCRIPT_NAME']);
   
        

    $nav = '';

    if(($_SESSION['user']['role']=== 'guest')) {
        // Guest user navigation
        // If user is not logged in, show only home, login and register links
        $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active"> ' : ' class="bi bi-house-fill me-2"> ').lang('lang_home').'</a>
                <a href="'.APP_URL.'public/login.php"'.($currentPage === 'login.php' ? ' class="bi bi-box-arrow-in-right me-2 active"> ' : ' class="bi bi-box-arrow-in-right me-2"> ').lang('lang_login').'</a>
                <a href="'.APP_URL.'public/register.php"'.($currentPage === 'register.php' ? ' class="bi bi-r-square-fill me-2 active"> ' : ' class="bi bi-r-square-fill me-2"> ').lang('lang_register').'</a>';
        return $nav;
    }


    $ops=['view_article',
          'search'
        ];

    if(hasPermission($uid,$ops)){
        $nav .= '<a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang('lang_home') .'</a>';
    }

    $ops=['view_dashboard'];

    if (hasPermission($uid,$ops)){
        $nav.='<a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang('lang_dashboard') . '</a>';
    }


    // check users allowed for view activity logs;
    $ops=['view_own_logs',
          'view_all_logs'
        ];

    if (hasPermission($uid,$ops)){
        $nav.='<a href="'.APP_URL.'public/logs.php"'.($currentPage === 'logs.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang('lang_logs') . '</a>';
    }

    // check users allowed for users management (file: admin/users.php);

    $ops=['add_user',
          'edit_user',
          'enable_user',
          'disable_user',
          'delete_user',
          'modify_user',
          'modify_own_user'];
    
    if(hasPermission($uid,$ops)){

        $nav.='<a href="'.APP_URL.'public/admin/users.php"'.($currentPage === 'users.php' ? ' class="bi bi-person-fill me-2 active"> ' : ' class="bi bi-person-fill me-2"> '). lang('lang_users'). '</a>';
    }

    //check users allowed to manage categories (file: admin/categories.php)

    $ops=['add_category',
          'edit_category'
        ];

    if (hasPermission($uid,$ops)){

        $nav.='<a href="'.APP_URL.'public/admin/categories.php"'.($currentPage === 'categories.php' ? ' class="bi bi-diagram-3-fill me-2 active"> ' : ' class="bi bi-diagram-3-fill me-2"> ').lang('lang_categories').'</a>';

    }

    //check users allowed to manage articles
    $ops = ['edit_article',
            'create_article',
            'edit_own_article',
            'publish_article',
            'disable_article',
            'enable_article',
            'approve_article',
            'delete_article',
            'export_article'
            ];

    if (hasPermission($uid,$ops)){

        $nav.='<a href="'.APP_URL.'public/admin/articles.php"'.($currentPage === 'articles.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang('lang_articles').'</a>';
    }

    // check users allowed to manage comments
    $ops = ['add_comment',
            'approve_comment',
            'delete_comment',
            'edit_comment'
            ];

    if (hasPermission($uid,$ops)){


        $nav.='<a href="'.APP_URL.'public/admin/comments.php"'.($currentPage === 'comments.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang('lang_com_comments').'</a>';
    }


    //check users allowed to edit ACL (file:admin/acl_edit.php)

    $ops=['edit_acl'];

    if(hasPermission($uid,$ops)){
        $nav.='<a href="'.APP_URL.'public/admin/acl_edit.php"'.($currentPage === 'acl_edit.php' ? ' class="bi bi-gear-fill me-2 active"> ' : ' class="bi bi-gear-fill me-2"> ').lang('lang_edit_acl').'</a>';
    }

    
    $ops=['register'];

    if(hasPermission($uid,$ops)){
        $nav.= '<a href="'.APP_URL.'public/register.php"'.($currentPage === 'register.php' ? ' class="bi bi-r-square-fill me-2 active"> ' : ' class="bi bi-r-square-fill me-2"> ').lang('lang_register').'</a>';
    }    

    
    // menu bar for 'guest' users

    $nav.='<a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang('lang_logout') .'('.escape($_SESSION['user']['username']).')</a>';

    return $nav;
}


function generateAvatarMenu($uid) {

    $menu = '';

    $role = getUserRoleById($uid);
    
    if ($role == 'guest') {
        
        $menu .= '<li>
                        <a class="dropdown-item" href="' . APP_URL . 'public/register.php">
                            <i class="bi bi-person-fill me-2"></i>'.lang('lang_register') . '</a>
                    </li>';
        $menu .='<li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item " href="'.APP_URL.'public/login.php">
                            <i class="bi bi-box-arrow-right me-2"></i>'.lang('lang_login').'</a>
                    </li>';

        return $menu;


    }


    // check if user is allowed to view his profile
    // check if user is allowed to edit his profile;

    //$ops = ['modify_own_user'];
    if (hasPermission($uid,['modify_own_user'])) {

        $menu .= '<li>
                        <a class="dropdown-item" href="' . APP_URL . 'public/profile.php">
                            <i class="bi bi-person-fill me-2"></i>'.lang('lang_profile').'</a>
                    </li>';
    }


    //check if user is allowed to edit ACL
    //$ops = ['edit_acl'];

    if (hasPermission($uid,['edit_acl'])) {
     
            $menu .='<li>
                        <a class="dropdown-item" href="'.APP_URL.'public/admin/acl_edit.php">
                            <i class="bi bi-box-arrow-in-right me-2"></i>'.lang('lang_edit_acl').'</a>
                    </li>';
    }


            $menu .='<li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="'.APP_URL.'public/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>'.lang('lang_logout').'</a>
                    </li>';

    return $menu;
}

// Checking if the logged user's role has ONE operation in $requiredOps
// $requiredOps - array contans all the operation done on a section 

function hasPermission(int $user_id, array $requiredOps): bool {
    
    if (!$user_id || empty($requiredOps)) return false;


    static $userPermissions = []; // contains the operations allowed to user

    $db=new Database();

   
    $uid = $user_id;
   

    // Cache per user
    if (!isset($userPermissions[$uid])) {
    
        

        $sql = "
            SELECT o.name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN role_permissions rp ON rp.role_id = r.id
            JOIN operations o ON o.id = rp.operation_id
            WHERE u.id = ?
        ";
        $results = $db->fetchAll($sql, [$uid]);
        $userPermissions[$user_id] = array_column($results, 'name');
    }

    // Verificăm dacă are cel puțin o operație permisă
    foreach ($requiredOps as $op) {
        if (in_array($op, $userPermissions[$uid])) {
            return true;
        }
    }

    return false;
}

// check if user has ALL operations in $requiredOps;
function hasAllPermission(int $user_id, array $requiredOps): bool {
    
    if (!$user_id || empty($requiredOps)) return false;


    static $userPermissions = []; // contains the operations allowed to user

    $db=new Database();

   
    $uid = $user_id;
   

    // Cache per user
    if (!isset($userPermissions[$uid])) {
    
        

        $sql = "
            SELECT o.name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN role_permissions rp ON rp.role_id = r.id
            JOIN operations o ON o.id = rp.operation_id
            WHERE u.id = ?
        ";
        $results = $db->fetchAll($sql, [$uid]);
        $userPermissions[$user_id] = array_column($results, 'name');
    }

    // Verificăm dacă are cel puțin o operație permisă
    foreach ($requiredOps as $op) {
        if (!in_array($op, $userPermissions[$uid])) {
            return false;
        }
    }

    return true;
}


function getCommentCount(int $articleId): int {
    global $db;

    $sql = "SELECT COUNT(*) FROM article_comments WHERE article_id = ? AND status = 'approved'";
    $result = $db->fetchSingle($sql, [$articleId]);

    return (int) $result['COUNT(*)'];
}

/**
 * Returnează numărul de vizualizări pentru un articol
 * @param int $articleId
 * @return int
 */
function getArticleViewsCount(int $articleId): int
{
    $db = new Database();
    $stmt = $db->prepare("SELECT COUNT(*) FROM article_views WHERE article_id = ?");
    $stmt->execute([$articleId]);
    return (int)$stmt->fetchColumn();
}
function getViewsCount(int $articleId): int {
    global $db;

    $sql = "SELECT views FROM article WHERE article_id = ? AND status = 'approved'";
    $result = $db->fetchSingle($sql, [$articleId]);

    return (int) $result['views'];
}


function getArticleLikesDislikes(int $aid): array {
    
    $db=new Database();

    $sql = "
        SELECT vote_type, COUNT(*) AS total
        FROM article_likes
        WHERE article_id = ?
        GROUP BY vote_type
    ";

    $rows = $db->fetchAll($sql, [$aid]);

    $counts = ['like' => 0, 'dislike' => 0];
    foreach ($rows as $row) {
        $counts[$row['vote_type']] = (int)$row['total'];
    }

    return $counts;
}
// return number of articles per day for last 7 days
// return array with date as key and number of articles as value
// if no articles for a day, set value to 0 (use 0 instead of null)
// use date('Y-m-d') to format date

function getArticlesByLastDays(int $days): array {
    $db = new Database();

    $sql = "
        SELECT DATE(created_at) as date, COUNT(*) as total
        FROM articles
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    // inițializare cu 0 pentru toate zilele
    $data = [];
    for ($i = $days-1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $data[$day] = 0;
    }

    foreach ($rows as $row) {
        $data[$row['date']] = (int)$row['total'];
    }

    return $data;
}

function getCommentsByLastDays(int $days): array {
    $db = new Database();

    $sql = "
        SELECT DATE(created_at) as date, COUNT(*) as total
        FROM article_comments
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    // inițializare cu 0 pentru toate zilele
    $data = [];
    for ($i = $days-1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $data[$day] = 0;
    }

    foreach ($rows as $row) {
        $data[$row['date']] = (int)$row['total'];
    }

    return $data;
}

// get total comments within a given interval (in hours)
// ex: getTotalComments(24) - returns total comments from last 24 hours

function getTotalComments(int $interval): int {
    $db = new Database(); // instanța clasei tale de DB

    $sql = "
        SELECT COUNT(*) 
        FROM article_comments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
        AND status = 'approved'
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':hours', $interval, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}


// returns total active users within a given interval (in hours)
// ex: getActiveUsers(24) - returns total users active in last 24 hours
// 
// Usage:
// $activeUsers = getActiveUsers(24); // utilizatori activi în ultimele 24 ore
// echo "Utilizatori activi (24h): $activeUsers";

function getActiveUsers(int $interval): int {
    $db = new Database(); // presupune că ai clasa Database deja inclusă

    $sql = "
        SELECT COUNT(DISTINCT user_id)
        FROM activity_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':hours', $interval, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

// returns total success login counts within a given interval (in hours)
// ex: getLoginCount(24) - returns total logins in last 24 hours
// 
// Usage:
// $loginCount = getLoginSuccessCount(24); // logins within the last 24 hours
// echo "Logins(24h): $loginCount";
function getLoginSuccessCount(int $interval): int {
    $db = new Database(); // instanță a clasei tale de conexiune DB

    $sql = "
        SELECT COUNT(*) 
        FROM activity_log
        WHERE action_type = 'login_success'
        AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':hours', $interval, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

// returns total failed login counts within a given interval (in hours)
// ex: getLoginFailedCount(24) - returns total failed logins in last 24 hours
// 
// Usage:
// $failedLogins = getLoginFailedCount(24); // failed logins within the last 24 hours
// echo "Failed Logins(24h): $failedLogins";
function getLoginFailedCount(int $interval): int {
    $db = new Database(); // instanță a clasei tale de conexiune DB

    $sql = "
        SELECT COUNT(*) 
        FROM activity_log
        WHERE action_type = 'login_failed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':hours', $interval, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

// După ce am aplicat setările (ex: salvate în DB) ma intorc la pagina de unde am venit
function get_back($redirect){

        //$redirectTo = '/index.php'; // fallback implicit

        $r = $redirect;
        $url = filter_var($r, FILTER_SANITIZE_URL);

        // Validare basică: trebuie să înceapă cu "/" ca să nu fie redirect extern
        if (strpos($url, '/') === 0) {
            $redirectTo = $url;
        }
        
        //echo "Redirect to: ".$_POST['redirect'];
        header("Location: $redirectTo");
        //exit;
}



function getUserVote(int $articleId, int $userId): ?string {
    
    $db = new Database();

    $row = $db->fetchSingle("SELECT vote_type FROM article_likes WHERE article_id = ? AND user_id = ?", [$articleId, $userId]);
    return $row['vote_type'] ?? null; // 'like', 'dislike' sau null
}

// preview category icon
function renderCategoryIconPreview(string $icon = ''): string {
  if (!$icon) return '';
  if (preg_match('/\.(png|svg)$/i', $icon)) {
    return '<img src="/mykdb/assets/icons/' . htmlspecialchars($icon) . '" style="width:20px;">';
  } else {
    return '<span style="font-size:18px;">' . htmlspecialchars($icon) . '</span>';
  }
}

// check if an icon is already added into DB; 
// used for icons management in Categories section 
function iconExists(string $filename): bool {

    $db =new Database(); 

    $result = $db->fetchAll(
        "SELECT id FROM categories_icons WHERE filename = ? LIMIT 1",
        [$filename]
    );

    return !empty($result); // returnează true dacă există, false dacă nu
}


function renderPagination(int $currentPage, int $totalPages, array $params = []): string {
    if ($totalPages <= 1) return ''; // nimic de afișat

    $html = '<div class="pagination">';
    $queryPrev = http_build_query(array_merge($params, ['page' => max(1, $currentPage - 1)]));
    $queryNext = http_build_query(array_merge($params, ['page' => min($totalPages, $currentPage + 1)]));

    // Prev
    if ($currentPage > 1) {
        $html .= '<a class="page-link prev" href="?' . $queryPrev . '">&laquo; Prev</a>';
    }

    $dotsShown = false;

    for ($i = 1; $i <= $totalPages; $i++) {
        $show = (
            $i <= 1 ||
            $i > $totalPages - 1 ||
            abs($i - $currentPage) <= 1
        );

        if ($show) {
            $dotsShown = false;
            $query = http_build_query(array_merge($params, ['page' => $i]));
            $active = $i === $currentPage ? ' active' : '';
            $html .= '<a class="page-link' . $active . '" href="?' . $query . '">' . $i . '</a>';
        } elseif (!$dotsShown) {
            $html .= '<span class="dots">...</span>';
            $dotsShown = true;
        }
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a class="page-link next" href="?' . $queryNext . '">Next &raquo;</a>';
    }

    $html .= '</div>';
    return $html;
}


/**
 * Generează link-urile de paginare limitate pentru navigare.
 *
 * @param int $currentPage Pagina curentă.
 * @param int $totalPages Numărul total de pagini.
 * @param array $queryParams Un array de parametri GET suplimentari de menținut în URL.
 * @return string HTML-ul pentru paginare.
 */
function renderPagination2($currentPage, $totalPages, $queryParams = []) {
    $html = '<nav aria-label="Page navigation example"><ul class="pagination">';
    $url = basename($_SERVER['PHP_SELF']); // Ia numele scriptului curent (ex: view_logs.php)

    // Colectăm toți parametrii existenți din $_GET pentru a-i menține în link-uri
    $existingQueryParams = $_GET;
    unset($existingQueryParams['page']); // Eliminăm parametrul 'page' pentru a-l adăuga corect mai târziu

    // Combinăm parametrii existenți cu cei specifici paginării și filtrelor
    $allQueryParams = array_merge($existingQueryParams, $queryParams);

    // Numărul de link-uri de pagină de afișat în jurul paginii curente (ex: 1 la stânga, 1 la dreapta)
    $numLinksAroundCurrent = 1; 
    // Numărul maxim de pagini afișate în total (inclusiv pagina curentă)
    $maxPagesToShow = (2 * $numLinksAroundCurrent) + 1; // Va fi 3: [curent-1], [curent], [curent+1]

    // --- Link "Previous" ---
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $allQueryParams['page'] = $prevPage;
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?' . http_build_query($allQueryParams) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a></li>';
    }

    // --- Logică pentru link-urile de pagină ---

    // Definirea intervalului de pagini de afișat în jurul paginii curente
    $start_page = max(1, $currentPage - $numLinksAroundCurrent);
    $end_page = min($totalPages, $currentPage + $numLinksAroundCurrent);

    // Ajustarea intervalului dacă se află la început sau la sfârșit, pentru a menține $maxPagesToShow total
    if ($start_page == 1) {
        $end_page = min($totalPages, $maxPagesToShow);
    }
    if ($end_page == $totalPages) {
        $start_page = max(1, $totalPages - $maxPagesToShow + 1);
    }

    // Întotdeauna afișăm pagina 1, dacă nu este deja în intervalul nostru central
    if ($start_page > 1) {
        $allQueryParams['page'] = 1;
        $html .= '<li class="page-item ' . (($currentPage == 1) ? 'active' : '') . '"><a class="page-link" href="' . $url . '?' . http_build_query($allQueryParams) . '">1</a></li>';
        // Afișăm elipsis dacă există un "salt" între pagina 1 și prima pagină din intervalul central
        if ($start_page > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">...</a></li>';
        }
    }

    // Generăm link-uri pentru paginile din intervalul determinat (ex: c-1, c, c+1)
    for ($i = $start_page; $i <= $end_page; $i++) {
        // Asigurăm că nu afișăm pagina 1 din nou dacă a fost deja afișată explicit
        if ($i == 1 && $start_page > 1) continue; 
        // Asigurăm că nu afișăm ultima pagină din nou dacă va fi afișată explicit
        if ($i == $totalPages && $end_page < $totalPages) continue;

        $allQueryParams['page'] = $i;
        $activeClass = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $url . '?' . http_build_query($allQueryParams) . '">' . $i . '</a></li>';
    }

    // Întotdeauna afișăm ultima pagină, dacă nu este deja în intervalul nostru central
    if ($end_page < $totalPages) {
        // Afișăm elipsis dacă există un "salt" între ultima pagină din intervalul central și ultima pagină
        if ($end_page < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">...</a></li>';
        }
        $allQueryParams['page'] = $totalPages;
        $html .= '<li class="page-item ' . (($currentPage == $totalPages) ? 'active' : '') . '"><a class="page-link" href="' . $url . '?' . http_build_query($allQueryParams) . '">' . $totalPages . '</a></li>';
    }

    // --- Link "Next" ---
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $allQueryParams['page'] = $nextPage;
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?' . http_build_query($allQueryParams) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Next</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

// retrieve top 5 viewed articles from the database
// @param int $days - last days
// @return HTML string
function getTop5ViewedArticles(int $days): string 
{
    if ($days <= 0) {
        return '<p>⚠️ Parametrul trebuie să fie un număr pozitiv de zile.</p>';
    }

    $db = new Database();

    $sql = "SELECT a.id, a.title, c.icon, COUNT(v.id) AS views
            FROM articles a
            JOIN article_views v ON a.id = v.article_id
            JOIN categories c ON a.category_id = c.id
            WHERE v.viewed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
              AND a.status = 'approved'
              AND (a.publish_at IS NULL OR a.publish_at <= NOW())
              AND c.is_active = 1
            GROUP BY a.id
            ORDER BY views DESC
            LIMIT 5";

    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (!$results) return '<p>📭 Nu există articole vizualizate în ultimele ' . $days . ' zile.</p>';

        $html = '<ul class="top-articles viewed">';
        foreach ($results as $row) {
            $iconPath = APP_URL . 'assets/icons/categories/' . htmlspecialchars($row['icon']);
            $titleShort = truncateText($row['title'], 35, '...');
            $html .= '<li class="grid-li">
                        <span><img src="' . $iconPath . '" class="li-icon" alt=""></span>
                        <span><a href="view_article.php?id=' . $row['id'] . '">' . htmlspecialchars($titleShort) . '</a></span>
                        <span class="span-1">(' . $row['views'] . ')</span>
                      </li>';
        }
        $html .= '</ul>';
        return $html;

    } catch (PDOException $e) {
        return '<p>🔥 Eroare la interogarea articolelor vizualizate: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

/**
 * Returnează topul celor mai vizualizate articole all time.
 * @param int $limit Numărul de articole dorit în top (ex: 5, 10)
 * @return string HTML cu lista articolelor
 */
function getTopViewedArticles(int $limit = 5): string 
{
    if ($limit <= 0) {
        return '<p>Parameter must be a positive integer.</p>';
    }

    $db = new Database();
    $sql = "SELECT a.id, a.title, c.icon, COUNT(v.id) AS views
            FROM articles a
            LEFT JOIN article_views v ON a.id = v.article_id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'approved'
              AND (a.publish_at IS NULL OR a.publish_at <= NOW())
              AND c.is_active = 1
            GROUP BY a.id, a.title, c.icon
            ORDER BY views DESC
            LIMIT :limit";

    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (!$results) return '<p>Nicio vizualizare înregistrată.</p>';

        $html = '<ul class="top-articles viewed">';
        foreach ($results as $row) {
            $iconPath = APP_URL . 'assets/icons/categories/' . htmlspecialchars($row['icon']);
            $titleShort = truncateText($row['title'], 30, '...');
            $html .= '<li class="grid-li">
                        <span><img src="' . $iconPath . '" class="li-icon" alt=""></span>
                        <span><a href="view_article.php?id=' . $row['id'] . '">' . htmlspecialchars($titleShort) . '</a></span>
                        <span class="span-1">(' . $row['views'] . ')</span>
                      </li>';
        }
        $html .= '</ul>';
        return $html;

    } catch (PDOException $e) {
        return '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// returns the top 5 most commented articles in the last $days days
// returns an HTML string with the list of articles
function getTop5CommentedArticles(int $days): string 
{
    if ($days <= 0) {
        return '<p>Days must be a positive integer.</p>';
    }

    $db = new Database();
    $sql = "SELECT a.id, a.title, c.icon, COUNT(ac.id) AS comment_count
            FROM articles a
            JOIN article_comments ac ON a.id = ac.article_id
            JOIN categories c ON a.category_id = c.id
            WHERE ac.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            AND ac.status = 'approved'
            AND a.status = 'approved'
            GROUP BY a.id, a.title, c.icon
            ORDER BY comment_count DESC
            LIMIT 5";

    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (!$results) return '<p>No comments in this period.</p>';

        $html = '<ul class="top-articles commented">';
        foreach ($results as $article) {
            $titleShort = truncateText($article['title'], 30, '...');
            $icon  = htmlspecialchars($article['icon']);
            $id    = (int)$article['id'];
            $count = (int)$article['comment_count'];

            $html .= '<li class="grid-li">
                        <span><img src="'.APP_URL."assets/icons/categories/". $icon . '" class="li-icon" alt=""></span>
                        <span><a href="view_article.php?id=' . $id. '">' . htmlspecialchars($titleShort) . '</a></span>
                        <span class="span-1">(' . $count . ')</span>
                      </li>';
        }
        $html .= '</ul>';
        return $html;

    } catch (PDOException $e) {
        return '<p>Error fetching top commented articles: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// returns the top 5 most liked articles in the last $days days
// returns an HTML string with the list of articles
function getTop5LikedArticles(int $days): string 
{
    if ($days <= 0) {
        return '<p>Parameter must be a positive integer.</p>';
    }

    $db = new Database();

    $sql = "SELECT a.id, a.title, c.icon, COUNT(l.id) AS likes
            FROM articles a
            JOIN article_likes l ON a.id = l.article_id
            JOIN categories c ON a.category_id = c.id
            WHERE l.vote_type = 'like'
              AND l.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
              AND a.status = 'approved'
              AND (a.publish_at IS NULL OR a.publish_at <= NOW())
              AND c.is_active = 1
            GROUP BY a.id
            ORDER BY likes DESC
            LIMIT 5";

    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (!$results) return '<p>No liked articles found in the last ' . $days . ' zile.</p>';

        $html = '<ul class="top-articles liked">';
        foreach ($results as $row) {
            $iconPath = APP_URL . 'assets/icons/categories/' . htmlspecialchars($row['icon']);
            $titleShort = truncateText($row['title'], 30, '...');
            $html .= '<li class="grid-li">
                        <span><img src="' . $iconPath . '" class="li-icon" alt=""></span>
                        <span><a href="view_article.php?id=' . $row['id'] . '">' . htmlspecialchars($titleShort) . '</a></span>
                        <span class="span-1">(' . $row['likes'] . ')</span>
                      </li>';
        }
        $html .= '</ul>';
        return $html;

    } catch (PDOException $e) {
        return '<p>Error fetching top liked articles: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// returns the top $limit most liked articles
// returns an HTML string with the list of 
// articles or an error message if something goes wrong 
function getTopLikedArticles(int $limit = 5): string 
{
     if ($limit <= 0) {
        return '<p>Parameter must be a positive integer.</p>';
    }
    $db = new Database();

    $sql = "SELECT a.id, a.title, c.icon, COUNT(l.id) AS likes
            FROM articles a
            JOIN article_likes l ON a.id = l.article_id
            JOIN categories c ON a.category_id = c.id
            WHERE l.vote_type = 'like'
              AND a.status = 'approved'
              AND (a.publish_at IS NULL OR a.publish_at <= NOW())
              AND c.is_active = 1
            GROUP BY a.id
            ORDER BY likes DESC
            LIMIT :limit";

try {
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();

    if (!$results) return '<p>Nu există articole apreciate încă.</p>';

    $html = '<ul class="top-articles liked">';
    foreach ($results as $row) {
        $iconPath = APP_URL.'assets/icons/categories/' . $row['icon'];
        $titleShort = truncateText($row['title'], 30, '...');
        $html .= '<li class="grid-li">
                    <span><img src="' . $iconPath . '" class="li-icon" alt=""></span>
                    <span><a href="view_article.php?id=' . $row['id'] . '">' . htmlspecialchars($titleShort) . '</a></span>
                    <span class="span-1">('.$row['likes'].')</span>
                  </li>';
    }
    $html .= '</ul>';
    return $html;
    } catch (PDOException $e) {
        return '<p>Error fetching top liked articles: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
}


// Adaugă notificare pentru un user
function sendNotification($userId, $title, $message, $type = 'info') {
    $db = new Database();
    $db->query("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)", [
        $userId, $title, $message, $type
    ]);
}

// Marcare ca citită
function markNotificationRead($notifId, $userId) {
    $db = new Database();
    $db->query("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notifId, $userId]);
}

// returnează ID-ul autorului unui comentariu
function getCommentAuthorId(int $commentId): ?int {
    $db = new Database();

    $sql = "SELECT user_id FROM article_comments WHERE id = ?";
    $result = $db->fetchSingle($sql, [$commentId]);

    return $result ? (int)$result['user_id'] : null;
}

function getArticleAuthorId(int $aid): ?int {
    $db = new Database();

    $sql = "SELECT user_id FROM articles WHERE id = ?";
    $result = $db->fetchSingle($sql, [$aid]);

    return $result['user_id'] ?? null;
}

// returns the ID of an article by its title
function getArticleIdByTitle(int $title): ?int {
    $db = new Database();

    $sql = "SELECT id FROM articles WHERE title = ?";
    $result = $db->fetchSingle($sql, [$title]);

    return $result['id'] ?? null;
}


// returns an array of articles with status "pending"
function getPendingArticles(): array {

    // create a db instance
    $db = new Database();


    try {
        // Pregătește interogarea SQL pentru a obține articolele cu status "pending"
        $stmt = $db->prepare("
            SELECT a.*, u.username, c.name AS category
            FROM articles a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'pending'
            ORDER BY a.created_at DESC
        ");

        // Execută interogarea
        $stmt->execute();

        // Returnează rezultatele ca un array asociativ
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tratează eroarea și returnează un mesaj de eroare
        return ['error' => 'Error in fetching pending articles: ' . $e->getMessage()];
    }
}

// returns an array of comments with status "pending"
function getPendingComments(): array {
     
    // Crează o instanță a clasei Database
     $db=new Database();

    try {
        // Pregătește interogarea SQL pentru a obține comentariile cu status "pending"
        $stmt = $db->prepare("
            SELECT c.*, u.username, a.title AS article_title
            FROM article_comments c
            JOIN users u ON c.user_id = u.id
            JOIN articles a ON c.article_id = a.id
            WHERE c.status = 'pending'
            ORDER BY c.created_at DESC
        ");

        // Execută interogarea
        $stmt->execute();

        // Returnează rezultatele ca un array asociativ
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tratează eroarea și returnează un mesaj de eroare
        return ['error' => 'Error in fetching pending comments: ' . $e->getMessage()];
    }
}

// get all unread notifications for a user (uid)
function getUnreadNotifications($uid) {
    // Crează o instanță a clasei Database
    $db = new Database();

    // Pregătește interogarea SQL pentru a obține notificările necitite
    $query = "SELECT * FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC";
    
    // Execută interogarea
    $stmt = $db->prepare($query);
    $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->execute();

    // Obține rezultatele
    $unreadNotifications = $stmt->fetchAll();

    return $unreadNotifications;
}

function getDraftsArticles($uid):array {
    $db = new Database();

    $query = "SELECT * FROM articles WHERE user_id = :uid AND status = 'draft' ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->execute();

    $draftsArticles = $stmt->fetchAll();

    return $draftsArticles;
}

//************************************************************************************************************* */
// Return HTML output for notifications
// @Param: $notifications - array of notifications with attributes: id, type, message, created_at, and is_read
// @Param: $t - translation string for the title 
// @Return: HTML string
//************************************************************************************************************* */
function renderNotifications(array $notifications,array $t ): string {
    // Verifică dacă array-ul de notificări este gol
    if (empty($notifications)) {
        return '
        <div class="custom-box-1">
            <span class="corner-label-1">' . $t['lang_db_notif'] . ' (0)</span>
            <div class="box-content-1" style="color: #888; padding: 20px;">
                <ul class="list-group">'. $t['lang_db_no_notif'].'</ul>
            </div>
        </div>';
    } else {
        // Începe generarea HTML-ului pentru notificări
        $html = '<div class="custom-box-1">
                    <span class="corner-label-1">' . $t['lang_db_notif'] . ' (' . count($notifications) . ')</span>
                    <div class="box-content-1" style="color: #888; padding: 20px;">
                        <table width="100%">
                            <thead>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <th style="padding: 5px;">'.$t['lang_db_notif_type'].'</th>
                                    <th style="padding: 5px;">'.$t['lang_db_notif_message'].'</th>
                                    <th style="padding: 5px;">'.$t['lang_db_notif_date'].'</th>
                                    <th style="padding: 5px;">'.$t['lang_db_notif_actions'].'</th>
                                </tr>
                            </thead>
                            <tbody>';

        // Parcurge notificările și generează rândurile tabelului
        foreach ($notifications as $n) {
            $color = '';

            // Determină culoarea în funcție de tipul notificării
            switch ($n['type']) {
                case 'info':
                    $color = 'blue';
                    break;
                case 'success':
                    $color = 'green';
                    break;
                case 'warning':
                    $color = 'orange';
                    break;
                case 'error':
                    $color = 'red';
                    break;
                default:
                    $color = 'black';
            }

            // Adaugă rândul pentru notificare
            $html .= '<tr id="notif-' . $n['id'] . '" style="color: ' . $color . '; font-size: 12px;border-bottom: 1px solid #ddd;" >
                        <td>' . htmlspecialchars($n['type']) . '</td>
                        <td>' . $n['message'] . '</td>
                        <td>' . date('Y-m-d H:i', strtotime($n['created_at'])) . '</td>
                        <td>';

            // Afișează acțiunile pentru notificare
            if (!$n['is_read']) {
                $html .= '<a href="#" title="'.$t['lang_db_notif_mark_as_read'].'" onclick="markRead(' . $n['id'] . '); return false;">
                            <img src="' . APP_URL . 'assets/icons/icon-mark-read.svg" class="op-icon">
                          </a>';
            }
            $html .= '<a href="#" title="'. $t['lang_db_notif_delete'].'" onclick="deleteNotif(' . $n['id'] . '); return false;">
                        <img src="' . APP_URL . 'assets/icons/icon-delete.svg" class="op-icon">
                      </a>
                      </td>
                      </tr>';
        }

        // Încheie tabelul și div-ul
        $html .= '</tbody>
                  </table>
                  </div>
                  </div>';

        return $html;
    }
}
// ***********************************************************************************************
// display articles in draft
// @Param: $drafts - array of draft articles with atributesid, title, and created_at.
// @Param: $t[] - translation strings for the title and other labels
// @Return: HTML string
// ***********************************************************************************************
function renderDraftsArticles(array $drafts, array $t): string {
    

    
    // Verifică dacă array-ul de drafturi este gol
    if (empty($drafts)) {
        return '
        <div class="custom-box-1">
            <span class="corner-label-1">' . $t['lang_articles_in_draft'] . '</span>
            <div class="box-content-1" style="color: #888; padding: 20px;">
                <ul class="list-group">' . $t['lang_no_articles_in_draft'] . '
                </ul>
            </div>
        </div>';
    } else {
        // Începe generarea HTML-ului pentru drafturi
        $html = '<div class="custom-box-1">
                    <span class="corner-label-1">' . $t['lang_articles_in_draft']. ' (' . count($drafts) . ')</span>
                    <div class="box-content-1" style="padding: 15px;">
                        <ul class="list-group">';

        // Parcurge drafturile și generează elementele listei
        foreach ($drafts as $draft) {
            $html .= '<li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 10px; font-size: 14px;">
                        <div>
                            <strong>' . htmlspecialchars($draft['title']) . '</strong><br>
                            <small class="text-muted">creat la ' . date('Y-m-d H:i', strtotime($draft['created_at'])) . '</small>
                        </div>
                        <div class="btn-group">
                            <a href="edit_article.php?id=' . $draft['id'] . '">
                                <img src="' . APP_URL . 'assets/icons/icon-edit.svg" class="op-icon" title="' . $t['lang_btn_edit']. '">
                            </a>
                            <a href="submit_article.php?article_id=' . $draft['id'] . '">
                                <img src="' . APP_URL . 'assets/icons/icon-send-approval.svg" class="op-icon" title="' . $t['lang_btn_send_approval'] . '">
                            </a>
                        </div>
                    </li>';
        }

        // Încheie lista și div-ul
        $html .= '</ul>
                  </div>
                  </div>';

        return $html;
    }
}

// ***********************************************************************************************
// display pending articles
// @Param: $pendingArticles - array of pending articles with atributesid, title, and created_at.
// @Return: HTML string
// ***********************************************************************************************
function renderPendingArticles(array $pendingArticles,array $t): string {

    
    // Verifică dacă array-ul de articole este gol
    if (empty($pendingArticles)) {
        return '
        <div class="custom-box-1">
            <span class="corner-label-1" >'.$t['lang_articles_in_pending'].'</span>
            <div class="box-content-1" style="color: #888; padding: 20px;">
                <ul class="list-group">
                    '.$t['lang_no_articles_in_pending'].'.
                </ul>
            </div>
        </div>';
    } else {
        // Începe generarea HTML-ului pentru articolele în așteptare
        $html = '<div class="custom-box-1">
                    <span class="corner-label-1">'.$t['lang_articles_in_pending'].' (' . count($pendingArticles) . ')</span>
                    <div class="box-content-1" style="padding: 15px;">
                        <ul class="list-group">';

        // Parcurge articolele și generează elementele listei
        foreach ($pendingArticles as $article) {
            $html .= '<li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 10px; font-size: 14px;">
                        <div style="align-items: left;">
                            <a href="view_article.php?id='.$article['id'].'">' . htmlspecialchars($article['title']) . '</a><br>
                            <small class="text-muted">Autor: ' . htmlspecialchars($article['username']) . ' | creat la ' . date('Y-m-d H:i', strtotime($article['created_at'])) . '</small>
                        </div>
                        <div style="align-items: right;">
                            
                            <a href="approve_article.php?id=' . $article['id'] . '">
                                <img src="' . APP_URL . 'assets/icons/icon-approve.svg" class="op-icon" title="'.$t['lang_btn_send_approval'].'">
                            </a>
                            <a href="reject_article.php?id=' . $article['id'] . '">
                                <img src="' . APP_URL . 'assets/icons/icon-art-reject.svg" class="op-icon" title="'.$t['lang_article_reject'].'">
                            </a>
                        </div>
                    </li>';
        }

        // Încheie lista și div-ul
        $html .= '</ul>
                  </div>
                  </div>';

        return $html;
    }
}

// ***********************************************************************************************
// return html for pending comments
// @Param: $pendingComments - array of pending comments 
// @Return: HTML string
// ***********************************************************************************************
function renderPendingComments(array $pendingComments,array $t): string {
    
    global $csrf_token;

    // Verifică dacă array-ul de comentarii este gol
    if (empty($pendingComments)) {
        return '
        <div class="custom-box-1">
            <span class="corner-label-1">'.$t['lang_com_in_pending'].'</span>
            <div class="box-content-1" style="color: #888; padding: 20px;">
                <ul class="list-group">
                 '.$t['lang_no_pending_comments'].'   
                </ul>
            </div>
        </div>';
    } else {
        // Începe generarea HTML-ului pentru comentariile în așteptare
        $html = '<div class="custom-box-1">
                    <span class="corner-label-1">'.$t['lang_com_in_pending'].' (' . count($pendingComments) . ')</span>
                    <div class="box-content-1" style="padding: 15px;">
                        <ul class="list-group">';

        // Parcurge comentariile și generează elementele listei
        foreach ($pendingComments as $comment) {
            $html .= '<li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 10px; font-size: 14px;">
                        <div>
                            <a href="view_comment.php?id=' . $comment['id'] . '">' . truncateText($comment['content'],80) . '</a><br>
                            <small style="font-size: 12px;">Autor: ' . htmlspecialchars($comment['username']) . ' | creat la ' . date('Y-m-d H:i', strtotime($comment['created_at'])) . '</small>
                        </div>
                        <div style="align-items: right;">
                            <form action="comment_action.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="'.$comment['id'].'">
                                <input type="hidden" name="user_id" value="'.$comment['user_id'].'">
                                <input type="hidden" name="csrf_token" value="'.$csrf_token.'">
                                <input type="hidden" name="redirect_to" value="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-icon"><img src="'.APP_URL.'assets/icons/icon-approve.svg" class="op-icon" title="'.$t['lang_com_approve'].'" style="width:24;height:auto;"></button>
                            </form>
                            <form action="comment_action.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="'.$comment['id'].'">
                                <input type="hidden" name="user_id" value="'.$comment['user_id'].'">
                                <input type="hidden" name="csrf_token" value="'.$csrf_token.'">
                                <input type="hidden" name="redirect_to" value="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-icon"><img src="'.APP_URL.'assets/icons/icon-delete.svg" class="op-icon" title="'.$t['lang_com_reject'].'" style="width:24;height:auto;"></button>
                            </form>
                           
                        </div>
                    </li>';
        }

        // Încheie lista și div-ul
        $html .= '</ul>
                  </div>
                  </div>';

        return $html;
    }
}




// @Return: HTML string generating the dashboard for super admins

function renderSuperAdminDashboard(): string {
    // Declară variabilele globale
    global $drafts, $notifications;

    // Începe generarea HTML-ului pentru dashboard
    $html = '<div class="dashboard-wrapper">';

    // Coloana stânga
    $html .= '<div class="dashboard-left">';

    // Afișează drafturile
    //$html .= renderDrafts($drafts,'');

    // Afișează notificările
    //$html .= renderNotifications($notifications);

    $html .= '</div>'; // Încheie coloana stânga

    // Coloana dreapta
    $html .= '<div class="dashboard-right">';

    // Top 5 articole cele mai vizualizate
    $artViewDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_views') . $artViewDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTopViewedArticles(5) . '
                </div>
              </div>';

    // Top 5 articole cele mai apreciate
    $artLikeDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_likes') . $artLikeDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTop5LikedArticles($artLikeDays) . '
                </div>
              </div>';

    // Top 5 articole cele mai comentate
    $artCommDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_commented') . $artCommDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTop5CommentedArticles($artCommDays) . '
                </div>
              </div>';

    // Grafice pentru articole
    $html .= '<div class="custom-box-1" style="padding: 10px; gap: 0px;">
                <span class="corner-label-1">' . lang('lang_db_articles') . '</span>
                <div class="box-content-1" style="padding: 10px; width:100%; max-width:400px; height:auto; position:relative; margin: 0 auto;">
                    <canvas id="articlesChart" height="130"></canvas>
                </div>
              </div>';

    // Grafice pentru comentarii
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_recent_comments') . '</span>
                <div class="box-content-1" style="padding:10px; width:100%; max-width:400px; height:auto; position:relative; margin: 0 auto;">
                    <canvas id="commentsChart" height="130"></canvas>
                </div>
              </div>';

    // Operațiuni
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">Operatiuni</span>
                <ul>
                  <li>18 create</li>
                  <li>7 editate</li>
                  <li>2 șterse</li>
                </ul>
              </div>';

    // Loguri
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">Loguri</span>
                <ul>
                  <li>104 azi</li>
                  <li>7 arhivate</li>
                  <li>3 șterse</li>
                </ul>
              </div>';

    // Exporturi
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">Exporturi</span>
                <ul>
                  <li>1 CSV azi</li>
                  <li>3 backup-uri</li>
                  <li>Ultimul: 2024-05-01</li>
                </ul>
              </div>';

    $html .= '</div>'; // Încheie coloana dreapta
    $html .= '</div>'; // Încheie wrapper-ul dashboard

    return $html;
}

// @Return: HTML string generating the dashboard for admins
function renderAdminDashboard(): string {
    // Declară variabilele globale
    global $drafts, $notifications;

    // Începe generarea HTML-ului pentru dashboard
    $html = '<div class="dashboard-wrapper">';

    // Coloana stânga
    $html .= '<div class="dashboard-left">';

    // Afișează drafturile
    //$html .= renderDrafts($drafts,'');

    // Afișează notificările
    //$html .= renderNotifications($notifications);

    $html .= '</div>'; // Încheie coloana stânga

    // Coloana dreapta
    $html .= '<div class="dashboard-right">';

    // Top 5 articole cele mai vizualizate
    $artViewDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_views') . $artViewDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTopViewedArticles(5) . '
                </div>
              </div>';

    // Top 5 articole cele mai apreciate
    $artLikeDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_likes') . $artLikeDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTop5LikedArticles($artLikeDays) . '
                </div>
              </div>';

    // Top 5 articole cele mai comentate
    $artCommDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_commented') . $artCommDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTop5CommentedArticles($artCommDays) . '
                </div>
              </div>';

    // Grafice pentru articole
    $html .= '<div class="custom-box-1" style="padding: 10px; gap: 0px;">
                <span class="corner-label-1">' . lang('lang_db_articles') . '</span>
                <div class="box-content-1" style="padding: 10px; width:100%; max-width:400px; height:auto; position:relative; margin: 0 auto;">
                    <canvas id="articlesChart" height="130"></canvas>
                </div>
              </div>';

    // Grafice pentru comentarii
    $html .= '<div class="custom-box-1">  
                <span class="corner-label-1">' . lang('lang_db_recent_comments') . '</span>
                <div class="box-content-1" style="padding:10px; width:100%; max-width:400px; height:auto; position:relative; margin: 0 auto;">
                    <canvas id="commentsChart" height="130"></canvas>
                </div>
              </div>';

    $html .= '</div>'; // Încheie coloana dreapta
    $html .= '</div>'; // Încheie wrapper-ul dashboard

    return $html;
}


// @Return: HTML string generating the dashboard for admins
function renderModeratorDashboard(): string {
    // Declară variabilele globale
    global $pendingArticles, $pendingComments, $notifications;

    // Începe generarea HTML-ului pentru dashboard
    $html = '<div class="dashboard-wrapper">';

    // Coloana stânga
    $html .= '<div class="dashboard-left">';

    // Afișează articolele în așteptare
    //$html .= renderPendingArticles($pendingArticles);

    // Afișează comentariile în așteptare
    //$html .= renderPendingComments($pendingComments);

    // Afiseaza notificarile
    //$html .= renderNotifications($notifications);

    $html .= '</div>'; // Încheie coloana stânga

    // Coloana dreapta
    $html .= '<div class="dashboard-right">';

    // Top 5 articole cele mai vizualizate
    $artViewDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_views') . $artViewDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTopViewedArticles(5) . '
                </div>
              </div>';

    // Top 5 articole cele mai apreciate
    $artLikeDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_likes') . $artLikeDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTop5LikedArticles($artLikeDays) . '
                </div>
              </div>';

    // Top 5 articole cele mai comentate
    $artCommDays = 30;
    $html .= '<div class="custom-box-1">
                <span class="corner-label-1">' . lang('lang_db_top5_commented') . $artCommDays . lang('lang_db_art_days') . '</span>
                <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    ' . getTop5CommentedArticles($artCommDays) . '
                </div>
              </div>';

    // Grafice pentru articole
    $html .= '<div class="custom-box-1" style="padding: 10px; gap: 0px;">
                <span class="corner-label-1">' . lang('lang_db_articles') . '</span>
                <div class="box-content-1" style="padding: 10px; width:100%; max-width:400px; height:auto; position:relative; margin: 0 auto;">
                    <canvas id="articlesChart" height="130"></canvas>
                </div>
              </div>';

    // Grafice pentru comentarii
    $html .= '<div class="custom-box-1">  
                <span class="corner-label-1">' . lang('lang_db_recent_comments') . '</span>
                <div class="box-content-1" style="padding:10px; width:100%; max-width:400px; height:auto; position:relative; margin: 0 auto;">
                    <canvas id="commentsChart" height="130"></canvas>
                </div>
              </div>';

    $html .= '</div>'; // Încheie coloana dreapta
    $html .= '</div>'; // Încheie wrapper-ul dashboard

    return $html;
}



function renderDashboardBox($box, $data) {

    switch ($box) {
        case 'drafts':
            
            $lang_text = [
                'lang_no_articles_in_draft' => lang('lang_no_articles_in_draft'),
                'lang_articles_in_draft' => lang('lang_articles_in_draft'),
                'lang_btn_edit' => lang('lang_btn_edit'),
                'lang_btn_send_approval' => lang('lang_btn_send_approval')
            ];
            return renderDraftsArticles($data['drafts'],$lang_text);
        case 'notifications':
            $lang_text = [
                'lang_db_notif' => lang('lang_db_notif'),
                'lang_db_no_notif' => lang('lang_db_no_notif'),
                'lang_db_notif_type' => lang('lang_db_notif_type'),
                'lang_db_notif_message' => lang('lang_db_notif_message'),
                'lang_db_notif_date' => lang('lang_db_notif_date'),
                'lang_db_notif_actions' => lang('lang_db_notif_actions'),
                'lang_db_notif_mark_as_read' => lang('lang_db_notif_mark_as_read'),
                'lang_db_notif_delete' => lang('lang_db_notif_delete'),
                'lang_db_no_notifications' => lang('lang_db_no_notifications')
            ];
           
            return renderNotifications($data['notifications'],$lang_text);
        case 'pendingArticles':
            $lang_text = [
                'lang_articles_in_pending' => lang('lang_articles_in_pending'),
                'lang_no_articles_in_pending' => lang('lang_no_articles_in_pending'),
                'lang_article_approve' => lang('lang_article_approve'),
                'lang_article_reject' => lang('lang_article_reject')
            ];
            return renderPendingArticles($data['pendingArticles'], $lang_text);
        case 'pendingComments':
            $lang_text = [
                'lang_com_in_pending' => lang('lang_com_in_pending'),
                'lang_no_com_in_pending' => lang('lang_no_com_in_pending'),
                'lang_com_approve' => lang('lang_com_approve'),
                'lang_com_reject' => lang('lang_com_reject')
            ];
            return renderPendingComments($data['pendingComments'],$lang_text);
        case 'topViewed':
            return '<div class="custom-box-1"><span class="corner-label-1" id="art_top_view"></span><div class="box-content-1" style="padding-top:20px;">'.$data['topViewed'].'</div></div>';
        case 'topLiked':
            return '<div class="custom-box-1"><span class="corner-label-1" id="art_top_like"></span><div class="box-content-1" style="padding-top:20px;">'.$data['topLiked'].'</div></div>';
        case 'topCommented':
            return '<div class="custom-box-1"><span class="corner-label-1" id="art_top_com"></span><div class="box-content-1" style="padding-top:20px;">'.$data['topCommented'].'</div></div>';
        case 'articlesChart':
            return '<div class="custom-box-1"><span class="corner-label-1">Articole</span><div class="box-content-1"><canvas id="articlesChart" height="130"></canvas></div></div>';
        case 'commentsChart':
            return '<div class="custom-box-1"><span class="corner-label-1">Comentarii</span><div class="box-content-1"><canvas id="commentsChart" height="130"></canvas></div></div>';
        case 'operations':
            return '<div class="custom-box-1"><span class="corner-label-1">Operațiuni</span><ul><li>18 create</li><li>7 editate</li><li>2 șterse</li></ul></div>';
        case 'logs':
            return '<div class="custom-box-1"><span class="corner-label-1">Loguri</span><ul><li>104 azi</li><li>7 arhivate</li><li>3 șterse</li></ul></div>';
        case 'exports':
            return '<div class="custom-box-1"><span class="corner-label-1">Exporturi</span><ul><li>1 CSV azi</li><li>3 backup-uri</li><li>Ultimul: 2024-05-01</li></ul></div>';
        default:
            return '';
    }
}


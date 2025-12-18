<?php

// Load analytics tracker class
require_once __DIR__ . '/../classes/analytics_tracker.php';

// language translation
function lang($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
/**
 * Escape HTML special characters
 */
function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format a MySQL timestamp to readable date
 */
function formatDate($datetime) {
    if (empty($datetime)) {
        return '';
    }
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '';
    }
    return date("d.m.Y H:i", $timestamp);
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
function logArticleView($articleId, $viewSource = 'public') {
    $db = new Database();
    $userId = $_SESSION['user']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Validate view source
    $validSources = ['public', 'admin_preview', 'admin_edit', 'admin_analytics'];
    if (!in_array($viewSource, $validSources)) {
        $viewSource = 'public';
    }

    $db->query(
        "INSERT INTO article_views (article_id, user_id, ip_address, user_agent, view_source) VALUES (?, ?, ?, ?, ?)",
        [$articleId, $userId, $ip, $ua, $viewSource]
    );

    //error_log("Article view logged for article ID $articleId by user ID " . ($userId ?? 'guest') . " with source: $viewSource");
}

function SystemStatus(){

    //echo '<div class="backgroubd: linear-gradient(to right,rgb(25, 108, 141),rgb(67, 190, 238)); width: 100%; display: flex; align-items: center; justify-content: space-between;">';
            
        echo '<div class="width: 100%; display: flex; align-items: center; justify-content: space-between; small">';
                $db = new Database();
                $stats = $db->fetchSingle("
                    SELECT 
                        (SELECT COUNT(*) FROM users) as users,
                        (SELECT COUNT(*) FROM articles WHERE status='published') as aaproved,
                        (SELECT COUNT(*) FROM articles WHERE status='pending') as apending                        
                ");
                
                echo '  System contains: '.$stats['users'].' users | '.$stats['aaproved'].' articles published | '.$stats['apending'].' articles in pending';
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
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang('lang_home') .'</a>
                <a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang('lang_dashboard') . '</a>
                <a href="'.APP_URL.'public/admin/users.php"'.($currentPage === 'users.php' ? ' class="bi bi-person-fill me-2 active"> ' : ' class="bi bi-person-fill me-2"> '). lang('lang_users'). '</a>
                <a href="'.APP_URL.'public/admin/categories.php"'.($currentPage === 'categories.php' ? ' class="bi bi-diagram-3-fill me-2 active"> ' : ' class="bi bi-diagram-3-fill me-2"> ').lang('lang_categories').'</a>
                <a href="'.APP_URL.'public/admin/articles.php"'.($currentPage === 'articles.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang('lang_articles').'</a>
                <a href="'.APP_URL.'public/admin/acl_edit.php"'.($currentPage === 'acl_edit.php' ? ' class="bi bi-gear-fill me-2 active"> ' : ' class="bi bi-gear-fill me-2"> ').lang('lang_edit_acl').'</a>
                <a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang('lang_logout') .'('.escape($_SESSION['user']['username']).')</a>';
            break;
        case 'admin':
        case 'moderator':
        case 'editor':
        case 'contributor':
            $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang('lang_home') .'</a>
                <a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang('lang_dashboard') . '</a>
                <a href="'.APP_URL.'public/admin/users.php"'.($currentPage === 'users.php' ? ' class="bi bi-person-fill me-2 active"> ' : ' class="bi bi-person-fill me-2"> '). lang('lang_users'). '</a>
                <a href="'.APP_URL.'public/admin/categories.php"'.($currentPage === 'categories.php' ? ' class="bi bi-diagram-3-fill me-2 active"> ' : ' class="bi bi-diagram-3-fill me-2"> ').lang('lang_categories').'</a>
                <a href="'.APP_URL.'public/admin/articles.php"'.($currentPage === 'articles.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang('lang_articles').'</a>
                <a href="'.APP_URL.'public/settings.php"'.($currentPage === 'settings.php' ? ' class="bi bi-gear-fill me-2 active"> ' : ' class="bi bi-gear-fill me-2"> ').lang('lang_settings').'</a>
                <a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang('lang_logout') .'('.escape($_SESSION['user']['username']).')</a>';
            break;

        case 'guest':
            $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active"> ' : ' class="bi bi-house-fill me-2"> ').lang('lang_home').'</a>
                <a href="'.APP_URL.'public/login.php"'.($currentPage === 'login.php' ? ' class="bi bi-box-arrow-in-right me-2 active"> ' : ' class="bi bi-box-arrow-in-right me-2"> ').lang('lang_login').'</a>
                <a href="'.APP_URL.'public/register.php"'.($currentPage === 'register.php' ? ' class="bi bi-r-square-fill me-2 active"> ' : ' class="bi bi-r-square-fill me-2"> ').lang('lang_register').'</a>';
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

    if(!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? 'guest') === 'guest') {
        // Guest user navigation
        // If user is not logged in, show only home, login and register links
        $nav .= '
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active"> ' : ' class="bi bi-house-fill me-2"> ').lang('lang_home').'</a>
                <a href="'.APP_URL.'public/login.php"'.($currentPage === 'login.php' ? ' class="bi bi-box-arrow-in-right me-2 active" id="openLoginModal"> ' : ' class="bi bi-box-arrow-in-right me-2" id="openLoginModal"> ').lang('lang_login').'</a>
                <a href="#" id="openRegisterModal1" class="openRegisterModal bi bi-r-square-fill me-2"> '.lang('lang_register').'</a>';
        return $nav;
    }

    // Home menu
    $ops=['view_article', 'search'];
    if(hasPermission($uid,$ops)){
        $nav .= '<a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang('lang_home') .'</a>';
    }

    // Dashboard menu
    $ops=['view_dashboard'];
    if (hasPermission($uid,$ops)){
        $nav.='<a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang('lang_dashboard') . '</a>';
    }

    // Admin dropdown menu
    $adminMenuItems = '';
    $hasAdminAccess = false;
    
    // Logs
    $ops=['view_own_logs', 'view_all_logs'];
    if (hasPermission($uid,$ops)){
        $adminMenuItems .= '<a href="'.APP_URL.'public/logs.php" class="dropdown-item">
                                <img src="'.APP_URL.'assets/icons/icon-logs.svg" class="submenu-icon"> '.lang('lang_logs').'
                            </a>';
        $hasAdminAccess = true;
    }

    // Users
    $ops=['add_user', 'edit_user', 'enable_user', 'disable_user', 'delete_user', 'modify_user'];
    if(hasPermission($uid,$ops)){
        $adminMenuItems .= '<div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle admin-submenu-trigger" href="#" onclick="return false;">
                <img src="'.APP_URL.'assets/icons/icon-user.svg" class="submenu-icon"> '.lang('lang_users').'
                <img src="'.APP_URL.'assets/icons/icon-play-arrow.svg" class="submenu-arrow" style="float: right; width: 12px; height: 12px; margin-top: 8px;">
            </a>
            <div class="dropdown-menu users-submenu">
                <a href="'.APP_URL.'public/admin/users.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-view.svg" class="submenu-icon"> '.lang('lang_manage_users').'
                </a>';
                
                // User Analytics - visible only for editor, admin, and superadmin
                $userRole = $_SESSION['user']['role'] ?? '';
                if (in_array($userRole, ['editor', 'admin', 'superadmin'])) {
                    $adminMenuItems .= '<a href="'.APP_URL.'public/admin/user_analytics.php" class="dropdown-item">
                            <img src="'.APP_URL.'assets/icons/icon-user-analytics.svg" class="submenu-icon"> '.lang('lang_user_analytics').'
                        </a>';
                }
                
        $adminMenuItems .= '</div></div>';
        $hasAdminAccess = true;
    }

    // Categories
    $ops=['add_category', 'edit_category'];
    if (hasPermission($uid,$ops)){
        $adminMenuItems .= '<div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle admin-submenu-trigger" href="#" onclick="return false;">
                <img src="'.APP_URL.'assets/icons/icon-add-category.svg" class="submenu-icon"> '.lang('lang_categories').'
                <img src="'.APP_URL.'assets/icons/icon-play-arrow.svg" class="submenu-arrow" style="float: right; width: 12px; height: 12px; margin-top: 8px;">
            </a>
            <div class="dropdown-menu categories-submenu">
                <a href="'.APP_URL.'public/admin/categories.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-view.svg" class="submenu-icon"> View Categories
                </a>
                <a href="'.APP_URL.'public/admin/categories.php?modal=add" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-add-category.svg" class="submenu-icon"> '.lang('lang_cat_add').'
                </a>
                <a href="'.APP_URL.'public/admin/categories.php?modal=icon" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-add-icons.svg" class="submenu-icon"> '.lang('lang_cat_add_icon').'
                </a>
                <a href="'.APP_URL.'public/admin/categories.php?modal=enable" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-enable-cat.svg" class="submenu-icon"> '.lang('lang_cat_enable').'
                </a>
                <a href="'.APP_URL.'public/admin/categories.php?modal=disable" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-disable-cat.svg" class="submenu-icon"> '.lang('lang_cat_disable').'
                </a>
            </div>
        </div>';
        $hasAdminAccess = true;
    }

    // Articles
    $ops = ['edit_article', 'create_article', 'edit_own_article', 'publish_article', 'disable_article', 'enable_article', 'approve_article', 'delete_article', 'export_article'];
    if (hasPermission($uid,$ops)){
        $adminMenuItems .= '<div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle admin-submenu-trigger" href="#" onclick="return false;">
                <img src="'.APP_URL.'assets/icons/icon-create-article.svg" class="submenu-icon"> '.lang('lang_articles').' 
                <img src="'.APP_URL.'assets/icons/icon-play-arrow.svg" class="submenu-arrow" style="float: right; width: 12px; height: 12px; margin-top: 8px;">
            </a>
            <div class="dropdown-menu articles-submenu">
                <a href="'.APP_URL.'public/admin/articles.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-view.svg" class="submenu-icon"> View Articles
                </a>';
        
        // Analytics - visible only for moderator, editor, admin, and superadmin 
        $userRole = $_SESSION['user']['role'] ?? '';
        if (in_array($userRole, ['moderator', 'editor', 'admin', 'superadmin'])) {
            $adminMenuItems .= '<a href="'.APP_URL.'public/admin/articles_analytics.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-analytics.svg" class="submenu-icon"> Article Analytics
                </a>';
        } 
        
        $adminMenuItems .= '';
            
        // Create Article - vizibil pentru toate rolurile, enabled doar pentru Contributor
        $userRole = $_SESSION['user']['role'] ?? '';
        if ($userRole === 'contributor') {
            // Contributor - enabled
            $adminMenuItems .= '<a href="'.APP_URL.'public/admin/articles.php?modal=create" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-create-article.svg" class="submenu-icon"> '.lang('lang_create_article').'
                </a>';
        } 
        
        $adminMenuItems .= '</div>
        </div>';
        $hasAdminAccess = true;
    }

    // Tags
    $ops = ['edit_article', 'create_article', 'approve_article'];
    if (hasPermission($uid,$ops)){
        $adminMenuItems .= '<div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle admin-submenu-trigger" href="#" onclick="return false;">
                <img src="'.APP_URL.'assets/icons/icon-add.svg" class="submenu-icon"> '.lang('lang_tags').' 
                <img src="'.APP_URL.'assets/icons/icon-play-arrow.svg" class="submenu-arrow" style="float: right; width: 12px; height: 12px; margin-top: 8px;">
            </a>
            <div class="dropdown-menu tags-submenu">
                <a href="'.APP_URL.'public/admin/tags.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-view.svg" class="submenu-icon"> View Tags
                </a>
                <a href="'.APP_URL.'public/admin/tags.php?action=add" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-add.svg" class="submenu-icon"> '.lang('lang_add_tag').'
                </a>
                <a href="'.APP_URL.'public/admin/tags.php?action=stats" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-view.svg" class="submenu-icon"> '.lang('lang_tag_statistics').'
                </a>
                <a href="'.APP_URL.'public/admin/tags.php?action=clean" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-delete.svg" class="submenu-icon"> '.lang('lang_clean_unused_tags').'
                </a>
            </div>
        </div>';
        $hasAdminAccess = true;
    }

    // Comments
    $ops = ['add_comment', 'approve_comment', 'delete_comment', 'edit_comment'];
    if (hasPermission($uid,$ops)){
        $adminMenuItems .= '<a href="'.APP_URL.'public/admin/comments.php" class="dropdown-item">
                                <img src="'.APP_URL.'assets/icons/icon-bell.svg" class="submenu-icon"> '.lang('lang_com_comments').'
                            </a>';
        $hasAdminAccess = true;
    }

     // Analytics submenu
    $userRole = $_SESSION['user']['role'] ?? '';
    if (in_array($userRole, ['editor', 'admin', 'superadmin'])) {
        $adminMenuItems .= '<div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle admin-submenu-trigger" href="#" onclick="return false;">
                <img src="'.APP_URL.'assets/icons/icon-analytics.svg" class="submenu-icon"> Analytics
                <img src="'.APP_URL.'assets/icons/icon-play-arrow.svg" class="submenu-arrow" style="float: right; width: 12px; height: 12px; margin-top: 8px;">
            </a>
            <div class="dropdown-menu analytics-submenu">
                <a href="'.APP_URL.'public/admin/user_analytics.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-user-analytics.svg" class="submenu-icon"> User Analytics
                </a>
                <a href="'.APP_URL.'public/admin/articles_analytics.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-analytics.svg" class="submenu-icon"> Article Analytics
                </a>
                <a href="'.APP_URL.'public/admin/search_analytics.php" class="dropdown-item">
                    <img src="'.APP_URL.'assets/icons/icon-menu-src-analytics.svg" class="submenu-icon"> Search Analytics
                </a>
            </div>
        </div>';
        $hasAdminAccess = true;
    }


    // ACL
    $ops=['edit_acl'];
    if(hasPermission($uid,$ops)){
        $adminMenuItems .= '<a href="'.APP_URL.'public/admin/acl_edit.php" class="dropdown-item">
                                <img src="'.APP_URL.'assets/icons/icon-user-change-role.svg" class="submenu-icon"> '.lang('lang_edit_acl').'
                            </a>';
        $hasAdminAccess = true;
    }

    // Add Admin dropdown if user has any admin permissions
    if ($hasAdminAccess) {
        $isAdminPageActive = in_array($currentPage, ['logs.php', 'users.php', 'categories.php', 'articles.php', 'articles_analytics.php', 'user_analytics.php', 'tags.php', 'comments.php', 'acl_edit.php']);
        $nav .= '<div class="dropdown">
                    <a href="#" class="bi bi-gear-fill me-2 dropdown-toggle'.($isAdminPageActive ? ' active' : '').'">
                        Admin
                    </a>
                    <div class="dropdown-menu">
                        '.$adminMenuItems.'
                    </div>
                 </div>';
    }



    // Register (if allowed)
    $ops=['register'];
    if(hasPermission($uid,$ops)){
        $nav.= '<a href="#" id="openRegisterModal" class="bi bi-r-square-fill me-2">'.lang('lang_register').'</a>';
    }    

    // Logout
    $nav.='<a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang('lang_logout') .'('.escape($_SESSION['user']['username'] ?? 'Guest').')</a>';

    return $nav;
}


function generateAvatarMenu($uid) {

    $menu = '';

    $role = getUserRoleById($uid);
    
    if ($role == 'guest') {
        
        $menu .= '<li>
                        <a class="openRegisterModal dropdown-item" href="#" id="openRegisterModal2">
                            <i class="bi bi-person-fill me-2"></i>'.lang('lang_register') . '</a>
                    </li>';
        $menu .='<li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="'.APP_URL.'public/login.php">
                            <i class="bi bi-box-arrow-right me-2"></i>'.lang('lang_login').'</a>
                    </li>';

        return $menu;


    }


    // check if user is allowed to view his profile
    

    //$ops = ['modify_own_user'];
    if (hasPermission($uid,['modify_own_data'])) {

        $menu .= '<li>
                        <a class="dropdown-item" href="' . APP_URL . 'public/profile.php">
                            <i class="bi bi-person-fill me-2"></i>'.lang('lang_profile').'</a>
                    </li>';
    }

    $menu .= '<li>
                        <a class="dropdown-item" href="' . APP_URL . 'public/favorites.php">
                            <i class="bi bi-bookmark-fill me-2"></i>'.lang('lang_favorites').'</a>
                    </li>';
    
    // Settings submenu in profile dropdown
    $currentTheme = $_SESSION['settings']['theme'] ?? 'light';
    $currentLang = $_SESSION['settings']['lang'] ?? 'en';
    
    $menu .= '<li><hr class="dropdown-divider"></li>';
    
    // Theme settings
    $menu .= '<li class="dropdown-submenu">
                <a class="dropdown-item dropdown-toggle" href="#">
                    <i class="bi bi-palette-fill me-2"></i>'.lang('lang_select_theme').'
                </a>
                <div class="dropdown-menu theme-submenu">
                    <a href="'.APP_URL.'public/update_settings.php?theme=light&redirect_back='.urlencode($_SERVER['REQUEST_URI']).'" class="dropdown-item'.($currentTheme === 'light' ? ' active' : '').'">'.lang('lang_theme_light').'</a>
                    <a href="'.APP_URL.'public/update_settings.php?theme=dark&redirect_back='.urlencode($_SERVER['REQUEST_URI']).'" class="dropdown-item'.($currentTheme === 'dark' ? ' active' : '').'">'.lang('lang_theme_dark').'</a>
                    <a href="'.APP_URL.'public/update_settings.php?theme=gray&redirect_back='.urlencode($_SERVER['REQUEST_URI']).'" class="dropdown-item'.($currentTheme === 'gray' ? ' active' : '').'">'.lang('lang_theme_gray').'</a>
                </div>
              </li>';
    
    // Language settings
    $menu .= '<li class="dropdown-submenu">
                <a class="dropdown-item dropdown-toggle" href="#">
                    <i class="bi bi-translate me-2"></i>'.lang('lang_select_language').'
                </a>
                <div class="dropdown-menu language-submenu">
                    <a href="'.APP_URL.'public/update_settings.php?lang=en&redirect_back='.urlencode($_SERVER['REQUEST_URI']).'" class="dropdown-item'.($currentLang === 'en' ? ' active' : '').'">'.lang('lang_select_english').'</a>
                    <a href="'.APP_URL.'public/update_settings.php?lang=ro&redirect_back='.urlencode($_SERVER['REQUEST_URI']).'" class="dropdown-item'.($currentLang === 'ro' ? ' active' : '').'">'.lang('lang_select_romanian').'</a>
                </div>
              </li>';
    

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
              AND a.status = 'published'
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
            WHERE a.status = 'published'
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
            AND a.status = 'published'
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
              AND a.status = 'published'
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
              AND a.status = 'published'
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
function sendNotificationToUser($userId, $title, $message, $type = 'info') {
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


// return the author ID of an article
// params:  $aid - article ID, 
//          $table - 'articles' or 'article_versions'
function getArticleAuthorId(int $aid, $table): ?int {
    
    $db = new Database();

    switch ($table) {
        case 'articles':
            $sql = "SELECT user_id FROM articles WHERE id = ?";
            $result = $db->fetchSingle($sql, [$aid]);
            break;            
        case 'article_versions':
            $sql= "SELECT author_id AS user_id FROM article_versions WHERE id = ?";
            $result = $db->fetchSingle($sql, [$aid]);
            break;

        default:
            throw new InvalidArgumentException("Invalid table name: $table");
    }

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
            SELECT av.article_id, av.version_number, av.title, av.created_at, av.author_id, u.username, c.name AS category
            FROM article_versions av
            JOIN users u ON av.author_id = u.id
            LEFT JOIN categories c ON av.category_id = c.id
            WHERE av.status = 'pending'
            ORDER BY av.created_at DESC
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

        $query = "SELECT * FROM article_versions WHERE author_id = :uid AND status = 'draft' ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->execute();

        $draftsArticles = $stmt->fetchAll();

        //debug
        //error_log(print_r("test: ". $draftsArticles[0], true));
        return $draftsArticles;
    }

//************************************************************************************************************* */
// Return HTML output for notifications
// @Param: $notifications - array of notifications with attributes: id, type, message, created_at, and is_read
// @Param: $t - translation string for the title 
// @Return: HTML string
//************************************************************************************************************* */
function renderNotifications(array $notifications,array $t ): string {
    
    // Create a unique ID for this section
    $rootId = 'notifications-root-' . uniqid();
    
    // Verifică dacă array-ul de notificări este gol
    if (empty($notifications)) {
        $bodyContent = '<div style="color: #888; padding: 10px;">' . $t['lang_db_no_notif'] . '</div>';
    } else {
        // Generate the notifications table
        $bodyContent = '<table width="100%" style="border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <th style="padding: 2px; text-align: left;">'.$t['lang_db_notif_type'].'</th>
                                    <th style="padding: 2px; text-align: left;">'.$t['lang_db_notif_message'].'</th>
                                    <th style="padding: 2px; text-align: left;">'.$t['lang_db_notif_date'].'</th>
                                    <th style="padding: 2px; width: 90px; text-align: center;">'.$t['lang_db_notif_actions'].'</th>
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
            $bodyContent .= '<tr id="notif-' . $n['id'] . '" style="color: ' . $color . '; font-size: 12px; border-bottom: 1px solid #e9ecef;" >
                        <td style="padding: 8px;">' . htmlspecialchars($n['type']) . '</td>
                        <td style="padding: 8px;">' . $n['message'] . '</td>
                        <td style="padding: 8px;">' . date('Y-m-d H:i', strtotime($n['created_at'])) . '</td>
                        <td style="padding: 8px; width: 90px; text-align: center;">';

            // Afișează acțiunile pentru notificare
            if (!$n['is_read']) {
                $bodyContent .= '<a href="#" title="'.$t['lang_db_notif_mark_as_read'].'" onclick="markRead(' . $n['id'] . '); return false;">
                            <img src="' . APP_URL . 'assets/icons/icon-mark-read.svg" class="op-icon">
                          </a>';
            }
            $bodyContent .= '<a href="#" title="'. $t['lang_db_notif_delete'].'" onclick="deleteNotif(' . $n['id'] . '); return false;">
                        <img src="' . APP_URL . 'assets/icons/icon-delete.svg" class="op-icon">
                      </a>
                      </td>
                      </tr>';
        }

        // Încheie tabelul
        $bodyContent .= '</tbody></table>';
    }
    
    $title = $t['lang_db_notif'] . (!empty($notifications) ? ' (' . count($notifications) . ')' : ' (0)');
    
    // Return the root div and a script to render the CardInfoBox
    $html = '<div id="' . $rootId . '"></div>';
    $html .= '<script>
        (function() {
            const root = ReactDOM.createRoot(document.getElementById("' . $rootId . '"));
            const bodyContent = ' . json_encode($bodyContent) . ';
            
            root.render(
                React.createElement(CardInfoBox, {
                    icon: "' . APP_URL . 'assets/icons/icon-notif-articles.svg",
                    title: ' . json_encode($title) . ',
                    body: React.createElement("div", {
                        dangerouslySetInnerHTML: { __html: bodyContent }
                    }),
                    collapsible: true,
                    defaultOpen: false
                })
            );
        })();
    </script>';
    
    return $html;
}
// ***********************************************************************************************
// display articles in draft
// @Param: $drafts - array of draft articles with atributesid, title, and created_at.
// @Param: $t[] - translation strings for the title and other labels
// @Return: HTML string
// ***********************************************************************************************
function renderDraftsArticles(array $drafts, array $t): string {
    
    // Create a unique ID for this section
    $rootId = 'drafts-articles-root-' . uniqid();
    
    // Verifică dacă array-ul de drafturi este gol
    if (empty($drafts)) {
        $bodyContent = '<div style="color: #888; padding: 20px;">' . $t['lang_no_articles_in_draft'] . '</div>';
    } else {
        // Generate the list of draft articles
        $bodyContent = '<ul class="list-group" style="list-style: none; padding: 0; margin: 0;">';
        
        foreach ($drafts as $draft) {
            $bodyContent .= '<li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 10px; font-size: 14px; border-bottom: 1px solid #e9ecef;">
                        <div>
                            <a href="view_article.php?id=' . $draft['article_id'] . '&version=' . $draft['version_number'] . '">' . htmlspecialchars($draft['title']) . '</a><br>
                            <small class="text-muted">'.$t['lang_art_created'].' ' . date('Y-m-d H:i', strtotime($draft['created_at'])) . '</small>
                        </div>
                        <div class="btn-group">
                            <a href="#" onclick="openEditArticleModal('.$draft['article_id'].','.$draft['version_number'].');return false;">
                                <img src="' . APP_URL . 'assets/icons/icon-edit.svg" class="op-icon" title="' . $t['lang_btn_edit']. '">
                            </a>
                            <a href="#" onclick="submitForApproval(' . $draft['article_id'] . ', ' . ($draft['version_number'] ?? 1) . '); return false;">
                                <img src="' . APP_URL . 'assets/icons/icon-send-approval.svg" class="op-icon" title="' . $t['lang_btn_send_approval'] . '">
                            </a>
                        </div>
                    </li>';
        }
        
        $bodyContent .= '</ul>';
    }
    
    $title = $t['lang_articles_in_draft'] . (!empty($drafts) ? ' (' . count($drafts) . ')' : '');
    $escapedBody = htmlspecialchars($bodyContent, ENT_QUOTES, 'UTF-8');
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    
    // Return the root div and a script to render the CardInfoBox
    $html = '<div id="' . $rootId . '"></div>';
    $html .= '<script>
        (function() {
            const root = ReactDOM.createRoot(document.getElementById("' . $rootId . '"));
            const bodyContent = ' . json_encode($bodyContent) . ';
            
            root.render(
                React.createElement(CardInfoBox, {
                    icon: "' . APP_URL . 'assets/icons/icon-draft-articles.svg",
                    title: ' . json_encode($title) . ',
                    body: React.createElement("div", {
                        dangerouslySetInnerHTML: { __html: bodyContent }
                    }),
                    collapsible: true,
                    defaultOpen: false
                })
            );
        })();
    </script>';
    
    return $html;
}

// ***********************************************************************************************
// display pending articles
// @Param: $pendingArticles - array of pending articles with atributesid, title, and created_at.
// @Return: HTML string
// ***********************************************************************************************
function renderPendingArticles(array $pendingArticles,array $t): string {

    // Create a unique ID for this section
    $rootId = 'pending-articles-root-' . uniqid();
    
    // Verifică dacă array-ul de articole este gol
    if (empty($pendingArticles)) {
        $bodyContent = '<div style="color: #888; padding: 20px;">' . $t['lang_no_articles_in_pending'] . '</div>';
    } else {
        // Generate the list of pending articles
        $bodyContent = '<ul class="list-group" style="list-style: none; padding: 0; margin: 0;">';
        
        foreach ($pendingArticles as $article) {
            $bodyContent .= '<li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 10px; font-size: 14px; border-bottom: 1px solid #e9ecef;">
                        <div style="align-items: left;">
                            <a href="view_article.php?id='.$article['article_id'].'&version='.$article['version_number'].'">' . htmlspecialchars($article['title']) . '</a><br>
                            <small class="text-muted">Autor: ' . htmlspecialchars($article['username']) . ' | creat la ' . date('Y-m-d H:i', strtotime($article['created_at'])) . '</small>
                        </div>
                        <div style="align-items: right;">
                            <a href="#" onclick="approveArticle('.$article['article_id'].','.$article['version_number'].');return false;">
                                <img src="' . APP_URL . 'assets/icons/icon-edit.svg" class="op-icon" title="' . $t['lang_article_approve']. '">
                            </a>
                            <a href="#" onclick="rejectArticle('.$article['article_id'].','.$article['version_number'].');return false;">
                                <img src="' . APP_URL . 'assets/icons/icon-art-reject.svg" class="op-icon" title="' . $t['lang_article_reject']. '">
                            </a>
                        </div>
                    </li>';
        }
        
        $bodyContent .= '</ul>';
    }
    
    $title = $t['lang_articles_in_pending'] . (!empty($pendingArticles) ? ' (' . count($pendingArticles) . ')' : '');
    
    // Return the root div and a script to render the CardInfoBox
    $html = '<div id="' . $rootId . '"></div>';
    $html .= '<script>
        (function() {
            const root = ReactDOM.createRoot(document.getElementById("' . $rootId . '"));
            const bodyContent = ' . json_encode($bodyContent) . ';
            
            root.render(
                React.createElement(CardInfoBox, {
                    icon: "' . APP_URL . 'assets/icons/icon-pending-articles.svg",
                    title: ' . json_encode($title) . ',
                    body: React.createElement("div", {
                        dangerouslySetInnerHTML: { __html: bodyContent }
                    }),
                    collapsible: true,
                    defaultOpen: true
                })
            );
        })();
    </script>';
    
    return $html;
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
                 '.$t['lang_no_com_in_pending'].'   
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
                            <img src="'.APP_URL.'assets/icons/icon-approve.svg" class="op-icon" title="'.$t['lang_com_approve'].'" onclick="approveComment('.$comment['id'].'); return false;">
                            <img src="'.APP_URL.'assets/icons/icon-delete.svg" class="op-icon" title="'.$t['lang_com_reject'].'"  onclick="deleteComment('.$comment['id'].'); return false;">
                           
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
                'lang_btn_send_approval' => lang('lang_btn_send_approval'),
                'lang_art_created' => lang('lang_art_created')
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
            //error_log('pendingArticles in render: ' . print_r($data['pendingArticles'], true));
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
// Function to save article rating
// @Param: $articleId - ID of the article being rated
// @Param: $userId - ID of the user rating the article
// @Param: $stars - Number of stars given by the user
// @Param: $wasHelpful - Optional, boolean indicating if the article was helpful
// @Param: $comment - Optional, comment provided by the user
// @Return: void
// @Note: This function saves the rating in the database, updating if the user has already rated the article

function saveArticleRating($articleId, $userId, $stars, $wasHelpful = null, $comment = null) {
    $db = new Database();
    $stmt = $db->prepare("INSERT INTO article_ratings (article_id, user_id, stars, was_helpful, comment)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE stars=?, was_helpful=?, comment=?");
    $stmt->execute([$articleId, $userId, $stars, $wasHelpful, $comment, $stars, $wasHelpful, $comment]);
}


// check if an article is bookmarked by a user
// @Param: $articleId - ID of the article to check
// @Param: $userId - ID of the user to check
// @Return: boolean - true if the article is bookmarked, false otherwise
function is_article_bookmarked($articleId, $userId) {
    if (!$userId || !$articleId) return false;
    $db = new Database();
    $row = $db->fetchSingle("SELECT id FROM user_bookmarks WHERE user_id = ? AND article_id = ?", [$userId, $articleId]);
    return $row ? true : false;
}

// Helper function to validate action permissions
function canPerformAction2($action, $userRole, $articleStatus, $authorId, $currentUserId, $isOnlineVersion = false) {
    $isOwner = (int)$authorId === (int)$currentUserId;
    $status = strtolower(trim($articleStatus));
    
    switch ($userRole) {
        case 'contributor':
            switch ($action) {
                case 'view':
                    return $status === 'approved' || $status === 'disabled' || ($isOwner && ($status === 'draft' || $status === 'pending'));
                case 'edit':
                    // Contributor: doar propriile draft + versiuni online disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled' && $isOwner;
                    }
                    return $status === 'draft' && $isOwner;
                case 'approve':
                case 'publish':
                case 'disable':
                case 'restore':
                    return false;
                case 'delete':
                    return $status === 'draft' && $isOwner;
                default:
                    return false;
            }
            
        case 'editor':
            switch ($action) {
                case 'view':
                    return true;
                case 'edit':
                    // Editor: draft, pending + versiuni online disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled';
                    }
                    return $status === 'draft' || $status === 'pending' || $status === 'approved';
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'approved' && $isOnlineVersion;
                case 'restore':
                    return $status === 'disabled' && $isOnlineVersion;
                case 'delete':
                    return $status === 'draft' || $status === 'pending';
                default:
                    return false;
            }
            
        case 'moderator':
            switch ($action) {
                case 'view':
                    return true;
                case 'edit':
                    // Moderator: draft, pending + versiuni online disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled';
                    }
                    return $status === 'draft' || $status === 'pending';
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'approved' && $isOnlineVersion;
                case 'restore':
                    return $status === 'disabled' && $isOnlineVersion;
                case 'delete':
                    return $status === 'draft' || $status === 'pending';
                default:
                    return false;
            }
            
        case 'admin':
        case 'superadmin':
            switch ($action) {
                case 'view':
                    return true;
                case 'edit':
                    // Admin/Superadmin: toate versiunile + versiuni online doar dacă sunt disabled
                    if ($isOnlineVersion) {
                        return $status === 'disabled';
                    }
                    return true;
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'approved' && $isOnlineVersion;
                case 'restore':
                    return $status === 'disabled' && $isOnlineVersion;
                case 'delete':
                    return true;
                default:
                    return false;
            }
            
        default:
            return false;
    }
}

// Helper function to get article data with permission check
function getArticleWithPermissionCheck2($articleId, $action, $version = null) {
    global $db;
    
    $userRole = $_SESSION['user']['role'] ?? '';
    $currentUserId = $_SESSION['user']['id'] ?? 0;
    
    if ($version) {
        // Get specific version data from article_versions only
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id
            FROM article_versions av 
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
        
        if (!$articleData) {
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
        
    } else {
        // Get the online version or latest version if no online exists
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id
            FROM article_versions av 
            WHERE av.article_id = ? 
            AND (av.is_online = 1 OR av.article_id NOT IN (
                SELECT DISTINCT article_id FROM article_versions WHERE is_online = 1
            ))
            ORDER BY av.is_online DESC, av.version_number DESC
            LIMIT 1
        ", [$articleId]);
        
        if (!$articleData) {
            // Fallback: try to get from articles table if exists
            $articleData = $db->fetchSingle("SELECT *, user_id as owner_id FROM articles WHERE id = ?", [$articleId]);
            if (!$articleData) {
                return null;
            }
            $isOnlineVersion = true;
        } else {
            $isOnlineVersion = (int)$articleData['is_online'] === 1;
        }
    }
    
    // Check permissions
    if (!canPerformAction2($action, $userRole, $articleData['status'], $articleData['owner_id'], $currentUserId, $isOnlineVersion)) {
        return false; // Permission denied
    }
    
    return $articleData;
}

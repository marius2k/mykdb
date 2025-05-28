<?php

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
function getUserById($id) {

    $db = new Database();

    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

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
    
        echo "Guest session initialized: " . $_SESSION['user']['id']. "<br>";

    // Setări suplimentare default
    //$_SESSION['language'] ??= 'ro';
    //$_SESSION['theme']    ??= 'light';
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
                <a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active"> ' : ' class="bi bi-house-fill me-2"> ').lang_home.'</a>
                <a href="'.APP_URL.'public/login.php"'.($currentPage === 'login.php' ? ' class="bi bi-box-arrow-in-right me-2 active"> ' : ' class="bi bi-box-arrow-in-right me-2"> ').lang_login.'</a>
                <a href="'.APP_URL.'public/register.php"'.($currentPage === 'register.php' ? ' class="bi bi-r-square-fill me-2 active"> ' : ' class="bi bi-r-square-fill me-2"> ').lang_register.'</a>';
        return $nav;
    }


    $ops=['view_article',
          'search'
        ];

    if(hasPermission($uid,$ops)){
        $nav .= '<a href="'.APP_URL.'public/index.php"'.($currentPage === 'index.php' ? ' class="bi bi-house-fill me-2 active" > ' : ' class="bi bi-house-fill me-2"> '). lang_home .'</a>';
    }

    // check users allowed for view activity logs;

    $ops=['view_own_activity',
          'view_all_activity'
        ];

    if (hasPermission($uid,$ops)){
        $nav.='<a href="'.APP_URL.'public/dashboard.php"'.($currentPage === 'dashboard.php' ? ' class="bi bi-book-fill me-2 active"> ' : ' class="bi bi-book-fill me-2"> '). lang_dashboard . '</a>';
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

        $nav.='<a href="'.APP_URL.'public/admin/users.php"'.($currentPage === 'users.php' ? ' class="bi bi-person-fill me-2 active"> ' : ' class="bi bi-person-fill me-2"> '). lang_users. '</a>';
    }

    //check users allowed to manage categories (file: admin/categories.php)

    $ops=['add_category',
          'edit_category'
        ];

    if (hasPermission($uid,$ops)){

        $nav.='<a href="'.APP_URL.'public/admin/categories.php"'.($currentPage === 'categories.php' ? ' class="bi bi-diagram-3-fill me-2 active"> ' : ' class="bi bi-diagram-3-fill me-2"> ').lang_categories.'</a>';

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

        $nav.='<a href="'.APP_URL.'public/admin/articles.php"'.($currentPage === 'articles.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang_articles.'</a>';
    }

    // check users allowed to manage comments
    $ops = ['add_comment',
            'approve_comment',
            'delete_comment',
            'edit_comment'
            ];

    if (hasPermission($uid,$ops)){


        $nav.='<a href="'.APP_URL.'public/admin/comments.php"'.($currentPage === 'comments.php' ? ' class="bi bi-file-earmark-text-fill me-2 active"> ' : ' class="bi bi-file-earmark-text-fill me-2"> ').lang_com_comments.'</a>';
    }


    //check users allowed to edit ACL (file:admin/acl_edit.php)

    $ops=['edit_acl'];

    if(hasPermission($uid,$ops)){
        $nav.='<a href="'.APP_URL.'public/admin/acl_edit.php"'.($currentPage === 'acl_edit.php' ? ' class="bi bi-gear-fill me-2 active"> ' : ' class="bi bi-gear-fill me-2"> ').lang_edit_acl.'</a>';
    }

    
    $ops=['register'];

    if(hasPermission($uid,$ops)){
        $nav.= '<a href="'.APP_URL.'public/register.php"'.($currentPage === 'register.php' ? ' class="bi bi-r-square-fill me-2 active"> ' : ' class="bi bi-r-square-fill me-2"> ').lang_register.'</a>';
    }    

    
    // menu bar for 'guest' users

    $nav.='<a href="'.APP_URL.'public/logout.php" class="bi bi-box-arrow-right me-2"> '. lang_logout .'('.escape($_SESSION['user']['username']).')</a>';

    return $nav;
}


function generateAvatarMenu($uid) {

    $menu = '';

    $role = getUserRoleById($uid);
    
    if ($role == 'guest') {
        
        $menu .= '<li>
                        <a class="dropdown-item" href="' . APP_URL . 'public/register.php">
                            <i class="bi bi-person-fill me-2"></i>'.lang_register . '</a>
                    </li>';
        $menu .='<li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item " href="'.APP_URL.'public/login.php">
                            <i class="bi bi-box-arrow-right me-2"></i>'.lang_login.'</a>
                    </li>';

        return $menu;


    }


    // check if user is allowed to view his profile
    // check if user is allowed to edit his profile;

    //$ops = ['modify_own_user'];
    if (hasPermission($uid,['modify_own_user'])) {

        $menu .= '<li>
                        <a class="dropdown-item" href="' . APP_URL . 'public/profile.php">
                            <i class="bi bi-person-fill me-2"></i>'.lang_profile . '</a>
                    </li>';
    }


    //check if user is allowed to edit ACL
    //$ops = ['edit_acl'];

    if (hasPermission($uid,['edit_acl'])) {
     
            $menu .='<li>
                        <a class="dropdown-item" href="'.APP_URL.'public/admin/acl_edit.php">
                            <i class="bi bi-box-arrow-in-right me-2"></i>'.lang_edit_acl.'</a>
                    </li>';
    }


            $menu .='<li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="'.APP_URL.'public/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>'.lang_logout.'</a>
                    </li>';

    return $menu;
}

// Checking if the logged user's role is authorized for $requiredOps
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


function lang(string $key): string {
    global $translations;
    return $translations[$key] ?? $key;
}

function getCommentCount(int $articleId): int {
    global $db;

    $sql = "SELECT COUNT(*) FROM article_comments WHERE article_id = ? AND status = 'approved'";
    $result = $db->fetchSingle($sql, [$articleId]);

    return (int) $result['COUNT(*)'];
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


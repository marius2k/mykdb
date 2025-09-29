<?php

/*
// Debug - scrie în log că fișierul a fost accesat
error_log("=== bkd_articles.php START === " . date('Y-m-d H:i:s'));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

*/

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['edit_article','disable_article','enable_article','create_article','approve_article','restore_article','publish_article'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}


$db = new Database();

// Helper function to validate action permissions
function canPerformAction($action, $userRole, $articleStatus, $authorId, $currentUserId, $isOnlineVersion = false) {
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
function getArticleWithPermissionCheck($articleId, $action, $version = null) {
    global $db;
    
    $userRole = $_SESSION['user']['role'] ?? '';
    $currentUserId = $_SESSION['user']['id'] ?? 0;
    
    if ($version) {
        // Get specific version data
        $articleData = $db->fetchSingle("
            SELECT av.*, a.user_id as owner_id 
            FROM article_versions av 
            JOIN articles a ON av.article_id = a.id 
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
        
        if (!$articleData) {
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
        
    } else {
        // Get main article data
        $articleData = $db->fetchSingle("SELECT * FROM articles WHERE id = ?", [$articleId]);
        
        if (!$articleData) {
            return null;
        }
        
        $articleData['owner_id'] = $articleData['user_id'];
        $isOnlineVersion = true; // Main article is always considered online
    }
    
    // Check permissions
    if (!canPerformAction($action, $userRole, $articleData['status'], $articleData['owner_id'], $currentUserId, $isOnlineVersion)) {
        return false; // Permission denied
    }
    
    return $articleData;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_versions') {
    $articleId = (int)($_GET['id'] ?? 0);
    
    // Check view permission for this article
    $articleCheck = getArticleWithPermissionCheck($articleId, 'view');
    if ($articleCheck === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Nu aveți permisiunea să vizualizați acest articol']);
        exit;
    }
    if ($articleCheck === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Articolul nu a fost găsit']);
        exit;
    }
    
    $versions = $db->fetchAll("SELECT version_number, status, change_note, created_at, is_online FROM article_versions WHERE article_id = ? ORDER BY version_number DESC", [$articleId]);
    echo json_encode(['success' => true, 'versions' => $versions]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_version_history') {
    $articleId = (int)($_GET['id'] ?? 0);
    
    // Check view permission for this article
    $articleCheck = getArticleWithPermissionCheck($articleId, 'view');
    if ($articleCheck === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Nu aveți permisiunea să vizualizați istoricul acestui articol']);
        exit;
    }
    if ($articleCheck === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Articolul nu a fost găsit']);
        exit;
    }
    
    $history = $db->fetchAll("
        SELECT av.version_number, av.status, av.change_note, av.created_at, av.updated_at, av.is_online, u.username as author 
        FROM article_versions av 
        LEFT JOIN users u ON av.author_id = u.id 
        WHERE av.article_id = ? 
        ORDER BY av.version_number DESC
    ", [$articleId]);
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_version') {
    $articleId = (int)($_GET['id'] ?? 0);
    $version = (int)($_GET['version'] ?? 1);
    
    // Check view permission for this specific version
    $versionData = getArticleWithPermissionCheck($articleId, 'view', $version);
    if ($versionData === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Nu aveți permisiunea să vizualizați această versiune']);
        exit;
    }
    if ($versionData === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Versiunea nu a fost găsită']);
        exit;
    }
    
    echo json_encode(['success' => true] + $versionData);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_article') {
    $articleId = (int)($_GET['id'] ?? 0);
    
    // Check view permission for this article
    $articleData = getArticleWithPermissionCheck($articleId, 'view');
    if ($articleData === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Nu aveți permisiunea să vizualizați acest articol']);
        exit;
    }
    if ($articleData === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Articolul nu a fost găsit']);
        exit;
    }
    
    // Încarcă tagurile articolului
    $tags = $db->fetchAll("SELECT t.name FROM tags t
        JOIN article_tags at ON at.tag_id = t.id
        WHERE at.article_id = ?", [$articleId]);
    $articleData['tags'] = array_column($tags, 'name');
    echo json_encode(['success' => true, 'article' => $articleData]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';

    $userRole = $_SESSION['user']['role'] ?? '';
    $currentUserId = $_SESSION['user']['id'] ?? 0;
    
    // Apply role-based filtering for contributors
    $roleFilter = '';
    $roleParams = [];
    
    if ($userRole === 'contributor') {
        // Contributors can only see approved and disabled articles + their own draft/pending
        $roleFilter = " AND (a.status = 'approved' OR a.status = 'disabled' OR (a.user_id = :current_user_id AND a.status IN ('draft', 'pending')))";
        $roleParams[':current_user_id'] = $currentUserId;
    }

    $where = '';
    $params = [];
    if ($searchValue) {
        $where = "WHERE a.title LIKE :search1 OR u.username LIKE :search2 OR c.name LIKE :search3";
        $params = [
            ':search1' => "%$searchValue%",
            ':search2' => "%$searchValue%",
            ':search3' => "%$searchValue%"
        ];
    }
    
    // Combine search and role filters
    if ($where && $roleFilter) {
        $where .= $roleFilter;
    } elseif ($roleFilter) {
        $where = "WHERE " . ltrim($roleFilter, ' AND ');
    }
    
    $params = array_merge($params, $roleParams);

    $totalStmt = $db->query("SELECT COUNT(*) FROM articles");
    $totalArticles = $totalStmt->fetchColumn();

    $filteredStmt = $db->prepare("
        SELECT COUNT(*) FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        $where
    ");
    foreach ($params as $k => $v){
        $filteredStmt->bindValue($k, $v);
    }
    $filteredStmt->execute();
    $filtered = $filteredStmt->fetchColumn();
/*
    $stmt = $db->prepare("
        SELECT a.id as article_id, a.title, a.status, a.publish_at, a.updated_at, a.user_id, a.version as current_version,
               u.username, c.name as category,
               GROUP_CONCAT(DISTINCT av.version_number ORDER BY av.version_number DESC) as versions_list
        FROM articles a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN article_versions av ON a.id = av.article_id
        $where
        GROUP BY a.id
        ORDER BY a.updated_at DESC
        LIMIT :limit OFFSET :offset
    ");
*/

    $stmt = $db->prepare("
        SELECT 
            av.article_id,
            av.title, 
            av.status, 
            av.publish_at, 
            av.updated_at, 
            av.author_id as user_id, 
            av.version_number as current_version,
            u.username, 
            c.name as category,
            GROUP_CONCAT(DISTINCT av2.version_number ORDER BY av2.version_number DESC) as versions_list
        FROM article_versions av
        LEFT JOIN users u ON av.author_id = u.id
        LEFT JOIN categories c ON av.category_id = c.id
        LEFT JOIN article_versions av2 ON av.article_id = av2.article_id
        WHERE av.is_online = 1 OR (av.article_id NOT IN (
            SELECT DISTINCT article_id FROM article_versions WHERE is_online = 1
        ) AND av.version_number = (
            SELECT MAX(version_number) 
            FROM article_versions av3 
            WHERE av3.article_id = av.article_id
        ))
        $where
        GROUP BY av.article_id, av.version_number
        ORDER BY av.updated_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rownum = $start + 1;
    foreach ($articles as &$a) {
        $a['rownum'] = $rownum++;
        $a['versions'] = [];
        if ($a['versions_list']) {
            foreach (explode(',', $a['versions_list']) as $v) {
                $a['versions'][] = ['version_number' => (int)$v];
            }
        }
        unset($a['versions_list']);
    }

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalArticles,
        "recordsFiltered" => $filtered,
        "data" => $articles
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Creare articol nou
    if ($action === 'add_article') {
        // Contributors and above can create articles
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, ['contributor', 'editor', 'moderator', 'admin', 'superadmin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să creați articole']);
            exit;
        }
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
        $user_id = $_SESSION['user']['id'];
        $created_at = date('Y-m-d H:i:s');
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        $change_note = trim($_POST['change_note'] ?? '');

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;   
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        // Inserează articolul în tabelul articles
        $stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at, created_at, updated_at, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at, $created_at, $created_at]);
        $aid = $db->lastInsertedId();

        // Salvează versiunea inițială în article_versions (v1) - nu este online încă
        $initial_note = $change_note ?: 'Versiune inițială';
        $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, 1, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)",
            [$aid, $title, $clean_content, $category_id, $user_id, $status, $initial_note]);

        // Salvează tagurile
        if ($aid && $tagList) {
            foreach ($tagList as $tagName) {
                $tagStmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                $tagStmt->execute([$tagName]);
                $tagId = $tagStmt->fetchColumn();
                if (!$tagId) {
                    $db->query("INSERT INTO tags (name) VALUES (?)", [$tagName]);
                    $tagId = $db->lastInsertedId();
                }
                $db->query("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)", [$aid, $tagId]);
            }
        }

        logActivity($user_id, 'create_article', 'User '. $_SESSION['user']['username'].' created article: '. $title);
        echo json_encode(['success' => true, 'article_id' => $aid]);
        exit;
    }

    // Editare articol
    if ($action === 'edit_article') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $baseVersion = (int)($_POST['base_version'] ?? 1);
        $isEditingOnlineVersion = (int)($_POST['is_editing_online_version'] ?? 0);
        
        // Check edit permission for this specific version
        $versionData = getArticleWithPermissionCheck($articleId, 'edit', $baseVersion);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să editați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $publish_at = $_POST['publish_at'] ?? null;
        $status = $_POST['submit_type'] === 'draft' ? 'draft' : 'pending';
        $user_id = $_SESSION['user']['id'];
        $updated_at = date('Y-m-d H:i:s');
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        $change_note = trim($_POST['change_note'] ?? '');

        if (!$title || !$content || !$category_id) {
            echo json_encode(['error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }

        $clean_content = clean_html($content);
        $clean_content = removeImageCaptionText($clean_content);

        if ($isEditingOnlineVersion === 1) {
            // REGULA 5: Editarea versiunii online generează o versiune nouă
            
            // Obține ultima versiune
            $lastVersion = $db->fetchSingle("SELECT MAX(version_number) as v FROM article_versions WHERE article_id = ?", [$articleId]);
            $nextVersion = ($lastVersion && $lastVersion['v']) ? $lastVersion['v'] + 1 : 1;
            
            // Creează nota de modificare
            if ($change_note) {
                $edit_note = $change_note;
            } else {
                $edit_note = "Modificare bazată pe versiunea ONLINE {$baseVersion} → versiunea {$nextVersion}";
            }
            
            // Creează noua versiune în article_versions (NU este online)
            $db->query("INSERT INTO article_versions (article_id, version_number, title, content, category_id, author_id, status, is_online, created_at, updated_at, change_note) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)",
                [$articleId, $nextVersion, $title, $clean_content, $category_id, $user_id, $status, $edit_note]);

            // NU actualizează tabelul articles - versiunea online rămâne neschimbată
            // Doar dacă se aprobă noua versiune, aceasta va deveni online
            
            logActivity($user_id, 'edit_online_version', 'User '. $_SESSION['user']['username'].' edited ONLINE version '. $baseVersion .' of article ID '. $articleId .', created new version '. $nextVersion);
            
            echo json_encode([
                'success' => true, 
                'was_editing_online_version' => 1,
                'base_version' => $baseVersion,
                'new_version' => $nextVersion,
                'message' => 'A fost creată o versiune nouă bazată pe versiunea online'
            ]);
            
        } else {
            // REGULA 1: Editarea unei versiuni care NU este online
            // Regula 4: Editarea unei versiuni aprobate o scoate din statusul Approved
            
            // Actualizează versiunea existentă în article_versions
            $db->query("UPDATE article_versions SET title = ?, content = ?, category_id = ?, status = ?, updated_at = NOW(), change_note = ? WHERE article_id = ? AND version_number = ?",
                [$title, $clean_content, $category_id, $status, $change_note ?: "Modificare versiune {$baseVersion}", $articleId, $baseVersion]);
            
            logActivity($user_id, 'edit_version', 'User '. $_SESSION['user']['username'].' edited NON-ONLINE version '. $baseVersion .' of article ID '. $articleId);
            
            echo json_encode([
                'success' => true, 
                'was_editing_online_version' => 0,
                'base_version' => $baseVersion,
                'message' => 'Versiunea a fost actualizată'
            ]);
        }

        // Actualizează tagurile (pentru ambele cazuri)
        $db->query("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
        if ($articleId && $tagList) {
            foreach ($tagList as $tagName) {
                $tagStmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                $tagStmt->execute([$tagName]);
                $tagId = $tagStmt->fetchColumn();
                if (!$tagId) {
                    $db->query("INSERT INTO tags (name) VALUES (?)", [$tagName]);
                    $tagId = $db->lastInsertedId();
                }
                $db->query("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)", [$articleId, $tagId]);
            }
        }

        exit;
    }

    // Schimbarea datei de publicare
    if ($action === 'publish_at') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        
        // Check edit permission
        $articleData = getArticleWithPermissionCheck($articleId, 'edit');
        if ($articleData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să modificați acest articol']);
            exit;
        }
        if ($articleData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Articolul nu a fost găsit']);
            exit;
        }
        
        $publishAt = $_POST['publish_at'] ?? null;
        
        // Actualizează în tabelul articles
        $db->query("UPDATE articles SET publish_at = ?, updated_at = NOW() WHERE id = ?", [$publishAt, $articleId]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // Aprobat
    if ($action === 'approve') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 1);
        
        // Check approve permission
        $versionData = getArticleWithPermissionCheck($articleId, 'approve', $version);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să aprobați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        // Additional check: only pending versions can be approved
        if ($versionData['status'] !== 'pending') {
            echo json_encode(['error' => 'Doar versiunile în așteptare pot fi aprobate']);
            exit;
        }
        
        $publishAt = $_POST['publish_at'] ?? null;
        $user_id = $_SESSION['user']['id'];

        // Actualizează statusul versiunii la 'approved'
        $db->query("UPDATE article_versions SET status = 'approved', updated_at = NOW() WHERE article_id = ? AND version_number = ?", [$articleId, $version]);

        // Actualizează și articolul principal dacă această versiune este online
        $isOnline = $db->fetchSingle("SELECT is_online FROM article_versions WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
        if ($isOnline && $isOnline['is_online'] == 1) {
            $db->query("UPDATE articles SET status = 'approved', publish_at = ?, updated_at = NOW() WHERE id = ?", [$publishAt, $articleId]);
        }

        logActivity($user_id, 'approve_article', 'User '. $_SESSION['user']['username'].' approved version '. $version .' of article ID '. $articleId);
        echo json_encode(['success' => true]);
        exit;
    }

    // Publish action
    if ($action === 'publish') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);
        
        // Check publish permission
        $versionData = getArticleWithPermissionCheck($articleId, 'publish', $version);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să publicați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        // Additional checks
        if ($versionData['status'] !== 'approved') {
            echo json_encode(['success' => false, 'error' => 'Doar versiunile aprobate pot fi publicate']);
            exit;
        }
        
        if ($versionData['is_online'] == 1) {
            echo json_encode(['success' => false, 'error' => 'Această versiune este deja publicată']);
            exit;
        }
        
        $user_id = $_SESSION['user']['id'];
        
        try {
            $db->beginTransaction();
            
            // 1. Get the version to be published from article_versions
            $version_data = $db->fetchSingle("
                SELECT av.*, u.username 
                FROM article_versions av 
                LEFT JOIN users u ON av.author_id = u.id 
                WHERE av.article_id = ? AND av.version_number = ?
            ", [$articleId, $version]);
            
            if (!$version_data) {
                throw new Exception('Versiunea specificată nu a fost găsită');
            }
            
            // 2. Check if version status is 'approved'
            if ($version_data['status'] !== 'approved') {
                throw new Exception('Doar versiunile aprobate pot fi publicate');
            }
            
            // 3. Check if this version is already online
            if ($version_data['is_online'] == 1) {
                throw new Exception('Această versiune este deja publicată');
            }
            
            // 4. Check if an online version exists in articles table
            $existing_article = $db->fetchSingle("SELECT id FROM articles WHERE id = ?", [$articleId]);
            
            // 5. Update all versions to set is_online = 0 (remove online status from all versions)
            $db->query("UPDATE article_versions SET is_online = 0 WHERE article_id = ?", [$articleId]);
            
            // 6. Set the selected version as online in article_versions
            $db->query("UPDATE article_versions SET is_online = 1 WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
            
            // 7. Copy/Update the data in articles table
            if ($existing_article) {
                // Update existing article
                $db->query("
                    UPDATE articles SET 
                        title = ?, 
                        content = ?, 
                        category_id = ?, 
                        status = 'approved',
                        version = ?,
                        user_id = ?,
                        publish_at = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ", [
                    $version_data['title'],
                    $version_data['content'],
                    $version_data['category_id'],
                    $version,
                    $version_data['author_id'],
                    $version_data['publish_at'] ?? date('Y-m-d H:i:s'),
                    $articleId
                ]);
            } else {
                // Create new article entry
                $db->query("
                    INSERT INTO articles (
                        id, title, content, category_id, 
                        status, version, user_id, publish_at, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, NOW(), NOW())
                ", [
                    $articleId,
                    $version_data['title'],
                    $version_data['content'],
                    $version_data['category_id'],
                    $version,
                    $version_data['author_id'],
                    $version_data['publish_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
            
            // 8. Log the publish action
            logActivity($user_id, 'publish_article', 'User '. $_SESSION['user']['username'].' published version '. $version .' of article ID '. $articleId);
            
            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Versiunea {$version} a fost publicată cu succes"
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Restore version
    if ($action === 'restore') {

        /*
        error_log("RESTORE ACTION - article_id: " . ($_POST['article_id'] ?? 'NOT SET'));
        error_log("RESTORE ACTION - version: " . ($_POST['version'] ?? 'NOT SET'));
        error_log("RESTORE ACTION - id: " . ($_POST['id'] ?? 'NOT SET'));
        */

        $articleId = (int)($_POST['article_id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);
        
        /*
        error_log("RESTORE ACTION - parsed articleId: " . $articleId);
        error_log("RESTORE ACTION - parsed version: " . $version);
        */

        // Check restore permission
        $versionData = getArticleWithPermissionCheck($articleId, 'restore', $version);
        if ($versionData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să restaurați această versiune']);
            exit;
        }
        if ($versionData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Versiunea nu a fost găsită']);
            exit;
        }
        
        // Additional checks
        if ($versionData['status'] !== 'disabled') {
            echo json_encode(['success' => false, 'error' => 'Doar versiunile dezactivate pot fi restaurate']);
            exit;
        }
        
        if ($versionData['is_online'] != 1) {
            echo json_encode(['success' => false, 'error' => 'Doar versiunile online pot fi restaurate']);
            exit;
        }
        
        $user_id = $_SESSION['user']['id'];

        try {
            $db->beginTransaction();
            
            // Marchează toate versiunile ca nefiind online
            //$db->query("UPDATE article_versions SET is_online = 0 WHERE article_id = ?", [$articleId]);

            // Marchează versiunea selectată approved in tabela article_versions
            $db->query("UPDATE article_versions SET status = 'approved' WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
            

            // Actualizează articolul principal cu datele din versiunea restaurată
            //$db->query("UPDATE articles SET status = 'approved', title = ?, content = ?, category_id = ?, version = ?, updated_at = NOW() WHERE id = ?", 
            //    [$versionData['title'], $versionData['content'], $versionData['category_id'], $version, $articleId]);

            // Actualieaza statusul articolului din pagina principala (tabela articles)
            $db->query("UPDATE articles SET status = 'approved' WHERE id = ?",  [$articleId]);

            $db->commit();
            
            logActivity($user_id, 'restore_article', 'User '. $_SESSION['user']['username'].' restored version '. $version .' of article ID '. $articleId);
            echo json_encode(['success' => true, 'message' => 'Versiunea a fost restaurată cu succes']);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Eroare la restaurarea versiunii: ' . $e->getMessage()]);
        }
        exit;
    }

    // Disable/Enable article
    if ($action === 'disable' || $action === 'enable') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        $user_id = $_SESSION['user']['id'];
        $userRole = $_SESSION['user']['role'] ?? '';
        
        if (!$articleId) {
            echo json_encode(['success' => false, 'error' => 'ID articol invalid']);
            exit;
        }
        
        // Get article data first
        $articleData = $db->fetchSingle("SELECT * FROM articles WHERE id = ?", [$articleId]);
        if (!$articleData) {
            http_response_code(404);
            echo json_encode(['error' => 'Articolul nu a fost găsit']);
            exit;
        }
        
        // Check permissions manually since disable/restore work on main article only
        $actionName = $action === 'disable' ? 'disable' : 'restore';
        $isOnlineVersion = true; // Main article is always considered online
        
        if (!canPerformAction($actionName, $userRole, $articleData['status'], $articleData['user_id'], $user_id, $isOnlineVersion)) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să ' . ($action === 'disable' ? 'dezactivați' : 'activați') . ' acest articol']);
            exit;
        }
        
        // Now use proper status values
        $newStatus = $action === 'disable' ? 'disabled' : 'approved';
        
        try {
            $db->beginTransaction();
            
            // Update articles table using prepare/execute pattern like other working queries
            $stmt1 = $db->prepare("UPDATE articles SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt1->execute([$newStatus, $articleId]);
            
            // Update the corresponding online version in article_versions table
            $stmt2 = $db->prepare("UPDATE article_versions SET status = ?, updated_at = NOW() WHERE article_id = ? AND is_online = 1");
            $stmt2->execute([$newStatus, $articleId]);
            
            $db->commit();
            
            logActivity($user_id, $action.'_article', 'User '. $_SESSION['user']['username'].' '.$action.'d article ID '. $articleId);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Eroare la ' . ($action === 'disable' ? 'dezactivarea' : 'activarea') . ' articolului: ' . $e->getMessage()]);
        }
        exit;
    }

    // Delete article
    if ($action === 'delete') {
        $articleId = (int)($_POST['article_id'] ?? 0);
        
        // Check delete permission
        $articleData = getArticleWithPermissionCheck($articleId, 'delete');
        if ($articleData === false) {
            http_response_code(403);
            echo json_encode(['error' => 'Nu aveți permisiunea să ștergeți acest articol']);
            exit;
        }
        if ($articleData === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Articolul nu a fost găsit']);
            exit;
        }
        
        $user_id = $_SESSION['user']['id'];
        
        try {
            $db->beginTransaction();
            
            // Șterge tagurile articolului
            $db->query("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
            
            // Șterge toate versiunile articolului
            $db->query("DELETE FROM article_versions WHERE article_id = ?", [$articleId]);
            
            // Șterge articolul principal
            $db->query("DELETE FROM articles WHERE id = ?", [$articleId]);
            
            $db->commit();
            
            logActivity($user_id, 'delete_article', 'User '. $_SESSION['user']['username'].' deleted article ID '. $articleId);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Eroare la ștergerea articolului: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['error' => 'Acțiune necunoscută']);
}
?>
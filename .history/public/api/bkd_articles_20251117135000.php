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

$ops = ['edit_article','edit_own_article','disable_article','enable_article','create_article','approve_article','restore_article','publish_article'];
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
    
    // ACTIVEAZĂ LOGGING PENTRU TOATE ROLURILE
    error_log("canPerformAction: action=$action, userRole=$userRole, status=$status, isOwner=" . ($isOwner ? 'true' : 'false') . ", isOnlineVersion=" . ($isOnlineVersion ? 'true' : 'false'));
    

    switch ($userRole) {
        case 'contributor':
 
            error_log("canPerformAction - Contributor: action=$action, status=$status, isOwner=" . ($isOwner ? 'true' : 'false') . ", isOnlineVersion=" . ($isOnlineVersion ? 'true' : 'false'));
 
            switch ($action) {
                case 'view':
                    // VIEW: Published (toate), Disabled (toate), Draft (propriu), Approved (toate), Pending (propriu)
                    return ($status === 'draft' && $isOwner) || 
                        ($status === 'pending' && $isOwner) || 
                        ($status === 'approved') || 
                        ($status === 'published') ||
                        ($status === 'disabled'); 
                case 'edit':
                    // Draft (propriu) - doar versiuni care NU sunt online
                    $result = $status === 'draft' && $isOwner && !$isOnlineVersion;
                    error_log("canPerformAction - Contributor EDIT result: " . ($result ? 'ALLOWED' : 'DENIED'));
                    return $result;
                case 'history':
                    // HISTORY: Published (toate), Disabled (toate), Draft (propriu), Approved (toate), Pending (propriu)
                    return ($status === 'draft' && $isOwner) || 
                        ($status === 'pending' && $isOwner) || 
                        ($status === 'approved') || 
                        ($status === 'published') ||
                        ($status === 'disabled');
                case 'approve':
                case 'publish':
                case 'disable':
                case 'restore':
                    // Nu are permisiunea pentru aceste acțiuni
                    return false;
                case 'delete':
                    // Draft (propriu)
                    return $status === 'draft' && $isOwner && !$isOnlineVersion;
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
                    return $status === 'published' && $isOnlineVersion;
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
                    return ($status === 'draft') || ($status === 'pending');
                case 'approve':
                    return $status === 'pending';
                case 'publish':
                    return $status === 'approved' && !$isOnlineVersion;
                case 'disable':
                    return $status === 'published' && $isOnlineVersion;
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
                    return $status === 'published' && $isOnlineVersion;
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
    
    // Add debug logging
    error_log("getArticleWithPermissionCheck: articleId=$articleId, action=$action, version=" . ($version ?? 'null') . ", userRole=$userRole, currentUserId=$currentUserId");
    
    if ($version) {
        // Get specific version data from article_versions with username and category
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id,
                   u.username,
                   c.name as category
            FROM article_versions av 
            LEFT JOIN users u ON av.author_id = u.id
            LEFT JOIN categories c ON av.category_id = c.id
            WHERE av.article_id = ? AND av.version_number = ?
        ", [$articleId, $version]);
        
        if (!$articleData) {
            error_log("getArticleWithPermissionCheck: Version $version not found for article $articleId");
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
        
    } else {
        // SIMPLIFICĂ - ia versiunea conform regulii de afișare: online first, then latest
        $articleData = $db->fetchSingle("
            SELECT av.*, av.author_id as owner_id, av.author_id as user_id,
                   u.username,
                   c.name as category
            FROM article_versions av 
            LEFT JOIN users u ON av.author_id = u.id
            LEFT JOIN categories c ON av.category_id = c.id
            WHERE av.article_id = ? 
            ORDER BY av.is_online DESC, av.version_number DESC
            LIMIT 1
        ", [$articleId]);
        
        if (!$articleData) {
            error_log("getArticleWithPermissionCheck: Article $articleId not found in article_versions");
            return null;
        }
        
        $isOnlineVersion = (int)$articleData['is_online'] === 1;
        
        error_log("getArticleWithPermissionCheck: Found version=" . $articleData['version_number'] . ", status=" . $articleData['status'] . ", is_online=" . $articleData['is_online'] . ", author_id=" . $articleData['author_id']);
    }
    
    // Determină statusul corect pentru permisiuni
    if ($isOnlineVersion) {
        // Versiune online - folosește statusul din articles (published/disabled)
        $articlesStatus = $db->fetchSingle("SELECT status FROM articles WHERE id = ?", [$articleId]);
        if ($articlesStatus) {
            $articleData['articles_status'] = $articlesStatus['status'];
            $articleData['permission_status'] = $articlesStatus['status'];
            error_log("getArticleWithPermissionCheck: Online version - using articles status: " . $articlesStatus['status']);
        } else {
            $articleData['permission_status'] = $articleData['status'];
            error_log("getArticleWithPermissionCheck: Online version - no articles record, using version status: " . $articleData['status']);
        }
    } else {
        // Versiune offline - folosește statusul din article_versions (draft/pending/approved)
        $articleData['permission_status'] = $articleData['status'];
        error_log("getArticleWithPermissionCheck: Offline version - using version status: " . $articleData['status']);
    }
    
    // Check permissions - folosește permission_status pentru verificare
    $statusForPermission = $articleData['permission_status'] ?? $articleData['status'];
    
    error_log("getArticleWithPermissionCheck: FINAL CHECK - statusForPermission=$statusForPermission, isOnlineVersion=" . ($isOnlineVersion ? 'true' : 'false') . ", isOwner=" . (((int)$articleData['owner_id'] === (int)$currentUserId) ? 'true' : 'false'));
    
    if (!canPerformAction($action, $userRole, $statusForPermission, $articleData['owner_id'], $currentUserId, $isOnlineVersion)) {
        error_log("getArticleWithPermissionCheck: Permission DENIED for action=$action");
        return false;
    }
    
    error_log("getArticleWithPermissionCheck: Permission GRANTED for action=$action");
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
    // Verifică dacă este un request pentru acțiuni specifice
    if (isset($_GET['action'])) {
        // Lasă să continue la acțiunile specifice (get_versions, etc.)
        // Nu executa logica DataTables
    } else {
        // Logica pentru DataTables
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        
        try {
            //error_log("bkd_articles: === GET DataTables Request Start ===");
            
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 10);
            $searchValue = $_GET['search']['value'] ?? '';

            $userRole = $_SESSION['user']['role'] ?? '';
            $currentUserId = $_SESSION['user']['id'] ?? 0;
            
            //error_log("bkd_articles: User Role: $userRole, User ID: $currentUserId, Search: '$searchValue'");

            // Build WHERE conditions for filtering
            $whereConditions = [];
            $params = [];
            
            // Add role-based filtering
            if ($userRole === 'contributor') {
                // Contributor: Doar articolele proprii (toate statusurile)
                $whereConditions[] = "av.author_id = ?";
                $params[] = $currentUserId;
            }

            // Add search filtering
            if (!empty($searchValue)) {
                $whereConditions[] = "(av.title LIKE ? OR u.username LIKE ? OR c.name LIKE ?)";
                $params[] = "%$searchValue%";
                $params[] = "%$searchValue%";
                $params[] = "%$searchValue%";
            }
            
            // Build final WHERE clause
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = "AND " . implode(" AND ", $whereConditions);
            }
            
            //error_log("bkd_articles: WHERE clause: $whereClause");
            //error_log("bkd_articles: Params: " . json_encode($params));

            // Count total unique articles
            //error_log("bkd_articles: === Getting total count ===");
            $totalStmt = $db->prepare("SELECT COUNT(DISTINCT av.article_id) FROM article_versions av WHERE av.article_id IS NOT NULL");
            $totalStmt->execute();
            $totalArticles = $totalStmt->fetchColumn();
            //error_log("bkd_articles: Total unique articles: $totalArticles");

            // Count filtered unique articles
            //error_log("bkd_articles: === Getting filtered count ===");
            $filteredQuery = "
                SELECT COUNT(DISTINCT av.article_id) 
                FROM article_versions av
                INNER JOIN (
                    SELECT 
                        article_id,
                        COALESCE(
                            MAX(CASE WHEN is_online = 1 THEN version_number ELSE NULL END),
                            MAX(version_number)
                        ) as selected_version,
                        MAX(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as has_online
                    FROM article_versions
                    WHERE article_id IS NOT NULL
                    GROUP BY article_id
                ) av_sel ON av.article_id = av_sel.article_id 
                    AND av.version_number = av_sel.selected_version
                    AND (av.is_online = 1 OR av_sel.has_online = 0)
                LEFT JOIN users u ON av.author_id = u.id
                LEFT JOIN categories c ON av.category_id = c.id
                LEFT JOIN articles a ON av.article_id = a.id
                WHERE av.article_id IS NOT NULL
                $whereClause
            ";
            
            $filteredStmt = $db->prepare($filteredQuery);
            $filteredStmt->execute($params);
            $filtered = $filteredStmt->fetchColumn();
            //error_log("bkd_articles: Filtered count: $filtered");

            // Main query - UN SINGUR QUERY pentru toate articolele
            //error_log("bkd_articles: === Executing main query ===");
            $mainQuery = "
                SELECT 
                    av.article_id,
                    av.title, 
                    av.status as version_status,
                    av.created_at, 
                    av.updated_at, 
                    av.author_id as user_id, 
                    av.version_number as current_version,
                    av.is_online,
                    u.username, 
                    c.name as category,
                    a.publish_at,
                    a.status as article_status
                FROM article_versions av
                INNER JOIN (
                    SELECT 
                        article_id,
                        COALESCE(
                            MAX(CASE WHEN is_online = 1 THEN version_number ELSE NULL END),
                            MAX(version_number)
                        ) as selected_version,
                        MAX(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as has_online
                    FROM article_versions
                    WHERE article_id IS NOT NULL
                    GROUP BY article_id
                ) av_sel ON av.article_id = av_sel.article_id 
                    AND av.version_number = av_sel.selected_version
                    AND (av.is_online = 1 OR av_sel.has_online = 0)
                LEFT JOIN users u ON av.author_id = u.id
                LEFT JOIN categories c ON av.category_id = c.id
                LEFT JOIN articles a ON av.article_id = a.id
                WHERE av.article_id IS NOT NULL
                $whereClause
                ORDER BY av.updated_at DESC
                LIMIT ? OFFSET ?
            ";

            //error_log("bkd_articles: Main query: $mainQuery");

            // Add LIMIT and OFFSET to params
            $mainParams = array_merge($params, [$length, $start]);
            
            $stmt = $db->prepare($mainQuery);
            $stmt->execute($mainParams);
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //error_log("bkd_articles: Found " . count($articles) . " articles");
            //error_log("bkd_articles: Articles data: " . json_encode(array_map(function($a) {
            //    return ['article_id' => $a['article_id'], 'title' => $a['title'], 'version' => $a['current_version'], 'is_online' => $a['is_online']];
            //}, $articles)));

            // Process results - get ALL versions for each article and determine display status
            $rownum = $start + 1;
            foreach ($articles as &$a) {
                $a['rownum'] = $rownum++;
                
                // Determină statusul corect pentru afișare
                if ($a['is_online'] == 1) {
                    // Pentru versiunea online, folosește statusul din articles (published/disabled)
                    $a['status'] = $a['article_status'];
                } else {
                    // Pentru versiunile offline, folosește statusul din article_versions (draft/pending/approved)
                    $a['status'] = $a['version_status'];
                }
                
                // Get ALL versions for this article (GROUP BY to get one row per version)
                $versionsStmt = $db->prepare("
                    SELECT 
                        av.version_number, 
                        MAX(av.status) as status, 
                        MAX(av.is_online) as is_online,
                        MAX(CASE 
                            WHEN av.is_online = 1 THEN a.publish_at 
                            ELSE NULL 
                        END) as publish_at
                    FROM article_versions av
                    LEFT JOIN articles a ON av.article_id = a.id
                    WHERE av.article_id = ? 
                    GROUP BY av.version_number
                    ORDER BY av.version_number DESC
                ");
                $versionsStmt->execute([$a['article_id']]);
                $versions = $versionsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $a['versions'] = [];
                foreach ($versions as $v) {
                    $a['versions'][] = [
                        'version_number' => (int)$v['version_number'],
                        'status' => $v['status'],
                        'is_online' => (int)$v['is_online'],
                        'publish_at' => $v['publish_at']
                    ];
                }

                //error_log("bkd_articles: Processed article ID: " . $a['article_id'] . ", Title: " . $a['title'] . ", Display Status: " . $a['status'] . ", Current Version: " . $a['current_version'] . ", Is Online: " . $a['is_online'] . ", Total versions: " . count($a['versions']));
            }

            $response = [
                "draw" => $draw,
                "recordsTotal" => $totalArticles,
                "recordsFiltered" => $filtered,
                "data" => $articles
            ];

            //error_log("bkd_articles: === Final response ===");
           //error_log("bkd_articles: Response structure: draw=$draw, recordsTotal=$totalArticles, recordsFiltered=$filtered, data_count=" . count($articles));

            echo json_encode($response);
            //error_log("bkd_articles: === GET DataTables Request End ===");

        } catch (Exception $e) {
            //error_log("bkd_articles: === EXCEPTION in GET ===");
            //error_log("bkd_articles: Exception message: " . $e->getMessage());
            //error_log("bkd_articles: Exception trace: " . $e->getTraceAsString());

            echo json_encode([
                "draw" => intval($_GET['draw'] ?? 1),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "Database error: " . $e->getMessage()
            ]);
            
        } catch (Error $e) {
            //error_log("bkd_articles: === PHP ERROR in GET ===");
            //error_log("bkd_articles: Error message: " . $e->getMessage());
            //error_log("bkd_articles: Error trace: " . $e->getTraceAsString());

            echo json_encode([
                "draw" => intval($_GET['draw'] ?? 1),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "PHP error: " . $e->getMessage()
            ]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF invalid']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    
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

        // Track approval in analytics
        try {
            $tracker = new AnalyticsTracker();
            $tracker->trackApprove($user_id, $articleId);
        } catch (Exception $e) {
            error_log("Failed to track article approval: " . $e->getMessage());
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
                        status = 'published',
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
                    ) VALUES (?, ?, ?, ?, 'published', ?, ?, ?, NOW(), NOW())
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
            
            // Marchează versiunea selectată ca approved in tabela article_versions
            $db->query("UPDATE article_versions SET status = 'approved' WHERE article_id = ? AND version_number = ?", [$articleId, $version]);
            
            // Actualizează statusul articolului din pagina principala (tabela articles) la 'published'
            $db->query("UPDATE articles SET status = 'published' WHERE id = ?",  [$articleId]);

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
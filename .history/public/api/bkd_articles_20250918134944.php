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
    // Verifică dacă este un request pentru acțiuni specifice
    if (isset($_GET['action'])) {
        // Lasă să continue la acțiunile specifice (get_versions, etc.)
        // Nu executa logica DataTables
    } else {
        // Logica pentru DataTables
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        
        try {
            error_log("bkd_articles: === GET DataTables Request Start ===");
            
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 10);
            $searchValue = $_GET['search']['value'] ?? '';

            $userRole = $_SESSION['user']['role'] ?? '';
            $currentUserId = $_SESSION['user']['id'] ?? 0;
            
            error_log("bkd_articles: User Role: $userRole, User ID: $currentUserId, Search: '$searchValue'");

            // Build WHERE conditions for filtering unique articles
            $whereConditions = [];
            $params = [];
            
            // Add role-based filtering
            if ($userRole === 'contributor') {
                $whereConditions[] = "(av.status = ? OR av.status = ? OR (av.author_id = ? AND av.status IN (?, ?)))";
                $params[] = 'approved';
                $params[] = 'disabled';
                $params[] = $currentUserId;
                $params[] = 'draft';
                $params[] = 'pending';
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
            
            error_log("bkd_articles: WHERE clause: $whereClause");
            error_log("bkd_articles: Params: " . json_encode($params));

            // Total unique articles count (incluzând și articolele nepublicate)
            error_log("bkd_articles: === Getting total count ===");
            $totalStmt = $db->prepare("
                SELECT COUNT(*) FROM (
                    SELECT DISTINCT COALESCE(article_id, CONCAT('unpub_', title, '_', author_id)) as unique_article
                    FROM article_versions
                ) as unique_articles
            ");
            $totalStmt->execute();
            $totalArticles = $totalStmt->fetchColumn();
            error_log("bkd_articles: Total unique articles: $totalArticles");

            // Filtered count - count unique articles that match criteria (publicate și nepublicate)
            error_log("bkd_articles: === Getting filtered count ===");
            $filteredQuery = "
                SELECT COUNT(*) FROM (
                    SELECT DISTINCT COALESCE(av.article_id, CONCAT('unpub_', av.title, '_', av.author_id)) as unique_article
                    FROM article_versions av
                    LEFT JOIN users u ON av.author_id = u.id
                    LEFT JOIN categories c ON av.category_id = c.id
                    WHERE 1=1
                    $whereClause
                ) as filtered_articles
            ";
            
            error_log("bkd_articles: Filtered query: $filteredQuery");
            
            $filteredStmt = $db->prepare($filteredQuery);
            $filteredStmt->execute($params);
            $filtered = $filteredStmt->fetchColumn();
            error_log("bkd_articles: Filtered count: $filtered");

            // Main query - UNION pentru articole publicate și nepublicate
            error_log("bkd_articles: === Executing main query ===");
            $mainQuery = "
                (
                    -- Articole publicate (article_id NOT NULL) - versiunea online sau cea mai recentă
                    SELECT 
                        av.article_id,
                        av.title, 
                        av.status, 
                        av.created_at, 
                        av.updated_at, 
                        av.author_id as user_id, 
                        av.version_number as current_version,
                        av.is_online,
                        av.article_id as real_article_id,
                        u.username, 
                        c.name as category
                    FROM article_versions av
                    LEFT JOIN users u ON av.author_id = u.id
                    LEFT JOIN categories c ON av.category_id = c.id
                    WHERE av.article_id IS NOT NULL
                    AND av.version_number = (
                        SELECT version_number 
                        FROM article_versions av2 
                        WHERE av2.article_id = av.article_id 
                        ORDER BY av2.is_online DESC, av2.version_number DESC 
                        LIMIT 1
                    )
                    " . str_replace('av.', 'av.', $whereClause) . "
                )
                UNION
                (
                    -- Articole nepublicate (article_id IS NULL) - prima versiune per grup de title+author
                    SELECT 
                        CONCAT('unpub_', av.id) as article_id,
                        av.title, 
                        av.status, 
                        av.created_at, 
                        av.updated_at, 
                        av.author_id as user_id, 
                        av.version_number as current_version,
                        av.is_online,
                        NULL as real_article_id,
                        u.username, 
                        c.name as category
                    FROM article_versions av
                    LEFT JOIN users u ON av.author_id = u.id
                    LEFT JOIN categories c ON av.category_id = c.id
                    WHERE av.article_id IS NULL
                    AND av.id = (
                        SELECT av3.id
                        FROM article_versions av3
                        WHERE av3.article_id IS NULL 
                        AND av3.title = av.title 
                        AND av3.author_id = av.author_id
                        ORDER BY av3.version_number ASC, av3.created_at ASC
                        LIMIT 1
                    )
                    " . str_replace('av.', 'av.', $whereClause) . "
                )
                ORDER BY updated_at DESC
                LIMIT ? OFFSET ?
            ";

            error_log("bkd_articles: Main query: $mainQuery");

            // Add LIMIT and OFFSET to params pentru ambele părți ale UNION
            $mainParams = array_merge($params, $params, [$length, $start]);
            
            $stmt = $db->prepare($mainQuery);
            $stmt->execute($mainParams);
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("bkd_articles: Found " . count($articles) . " unique articles");

            // Process results - get ALL versions for each article
            $rownum = $start + 1;
            foreach ($articles as &$a) {
                $a['rownum'] = $rownum++;
                
                // Pentru articolele nepublicate, folosește un criteriu diferit pentru a găsi versiunile
                if ($a['real_article_id'] === null) {
                    // Articol nepublicat - găsește versiunile după title și author
                    $versionsStmt = $db->prepare("
                        SELECT version_number, status, is_online 
                        FROM article_versions 
                        WHERE article_id IS NULL 
                        AND title = ? 
                        AND author_id = ?
                        ORDER BY version_number DESC
                    ");
                    $versionsStmt->execute([$a['title'], $a['user_id']]);
                } else {
                    // Articol publicat - găsește versiunile după article_id
                    $versionsStmt = $db->prepare("
                        SELECT version_number, status, is_online 
                        FROM article_versions 
                        WHERE article_id = ? 
                        ORDER BY version_number DESC
                    ");
                    $versionsStmt->execute([$a['real_article_id']]);
                }
                
                $versions = $versionsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $a['versions'] = [];
                foreach ($versions as $v) {
                    $a['versions'][] = [
                        'version_number' => (int)$v['version_number'],
                        'status' => $v['status'],
                        'is_online' => (int)$v['is_online']
                    ];
                }

                error_log("bkd_articles: Processed article ID: " . $a['article_id'] . ", Title: " . $a['title'] . ", Current Version: " . $a['current_version'] . ", Is Online: " . $a['is_online'] . ", Total versions: " . count($a['versions']));
            }

            $response = [
                "draw" => $draw,
                "recordsTotal" => $totalArticles,
                "recordsFiltered" => $filtered,
                "data" => $articles
            ];

            error_log("bkd_articles: === Final response ===");
            error_log("bkd_articles: Response structure: draw=$draw, recordsTotal=$totalArticles, recordsFiltered=$filtered, data_count=" . count($articles));

            echo json_encode($response);
            error_log("bkd_articles: === GET DataTables Request End ===");

        } catch (Exception $e) {
            error_log("bkd_articles: === EXCEPTION in GET ===");
            error_log("bkd_articles: Exception message: " . $e->getMessage());
            error_log("bkd_articles: Exception trace: " . $e->getTraceAsString());

            echo json_encode([
                "draw" => intval($_GET['draw'] ?? 1),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "Database error: " . $e->getMessage()
            ]);
            
        } catch (Error $e) {
            error_log("bkd_articles: === PHP ERROR in GET ===");
            error_log("bkd_articles: Error message: " . $e->getMessage());
            error_log("bkd_articles: Error trace: " . $e->getTraceAsString());

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
        //$stmt = $db->prepare("INSERT INTO articles (title, content, category_id, user_id, status, publish_at, created_at, updated_at, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        //$stmt->execute([$title, $clean_content, $category_id, $user_id, $status, $publish_at, $created_at, $created_at]);
        //$aid = $db->lastInsertedId();

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
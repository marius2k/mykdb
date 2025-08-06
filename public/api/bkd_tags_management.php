<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

// Verifică autentificarea
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Verifică permisiuni pentru management tags
// $requiredPermissions = ['manage_tags']; 
// if (!hasPermission($_SESSION['user']['id'], $requiredPermissions)) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'error' => 'Permission denied']);
//     exit;
// }

$db = new Database();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// GET: Lista tagurilor cu statistici
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($action === 'list') {
        try {
            $tags = $db->fetchAll("
                SELECT t.*, 
                       COUNT(at.article_id) as usage_count,
                       MAX(a.created_at) as last_used
                FROM tags t 
                LEFT JOIN article_tags at ON t.id = at.tag_id 
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'approved'
                GROUP BY t.id, t.name, t.description, t.created_at, t.updated_at
                ORDER BY usage_count DESC, t.name ASC
            ");
            
            echo json_encode([
                'success' => true,
                'tags' => $tags
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // DataTable server-side processing
    if ($action === 'datatable') {
        try {
            // Parametri DataTable
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 10);
            $search = $_GET['search']['value'] ?? '';
            
            // Coloanele disponibile pentru sortare
            $columns = ['rownum', 'name', 'description', 'usage_count', 'last_used', 'actions'];
            $orderColumn = $_GET['order'][0]['column'] ?? 3; // default usage_count
            $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
            $orderBy = $columns[$orderColumn] ?? 'usage_count';
            
            // Construiește query-ul de bază cu informații complete
            $baseQuery = "
                FROM tags t 
                LEFT JOIN article_tags at ON t.id = at.tag_id 
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'approved'
            ";
            
            $whereClause = "";
            $params = [];
            
            // Adaugă filtrul de căutare
            if (!empty($search)) {
                $whereClause = "WHERE (t.name LIKE ? OR t.description LIKE ?)";
                $params = ["%$search%", "%$search%"];
            }
            
            $groupBy = "GROUP BY t.id, t.name, t.description, t.created_at, t.updated_at";
            
            // Query pentru numărul total de înregistrări (fără filtrare)
            $totalQuery = "SELECT COUNT(DISTINCT t.id) as total FROM tags t";
            $totalRecords = $db->fetchSingle($totalQuery)['total'];
            
            // Query pentru numărul de înregistrări filtrate
            if (!empty($search)) {
                $filteredQuery = "SELECT COUNT(DISTINCT t.id) as total FROM tags t $whereClause";
                $filteredRecords = $db->fetchSingle($filteredQuery, $params)['total'];
            } else {
                $filteredRecords = $totalRecords;
            }
            
            // Construiește ordinea pentru query
            $orderByClause = "";
            switch ($orderBy) {
                case 'name':
                    $orderByClause = "ORDER BY t.name $orderDir";
                    break;
                case 'usage_count':
                    $orderByClause = "ORDER BY usage_count $orderDir, t.name ASC";
                    break;
                case 'last_used':
                    $orderByClause = "ORDER BY last_used $orderDir";
                    break;
                default:
                    $orderByClause = "ORDER BY usage_count DESC, t.name ASC";
            }
            
            // Query principal pentru datele paginii curente
            $dataQuery = "
                SELECT t.id, t.name, t.description, t.created_at, t.updated_at,
                       COUNT(at.article_id) as usage_count,
                       MAX(a.created_at) as last_used
                $baseQuery
                $whereClause
                $groupBy
                $orderByClause
                LIMIT $length OFFSET $start
            ";
            
            $tags = $db->fetchAll($dataQuery, $params);
            
            // Procesează datele pentru DataTable
            $data = [];
            $rownum = $start + 1;
            
            foreach ($tags as $tag) {
                $data[] = [
                    'rownum' => $rownum++,
                    'id' => $tag['id'],
                    'name' => $tag['name'],
                    'description' => $tag['description'],
                    'usage_count' => intval($tag['usage_count']),
                    'last_used' => $tag['last_used'] ? date('Y-m-d H:i', strtotime($tag['last_used'])) : null,
                    'created_at' => $tag['created_at'],
                    'updated_at' => $tag['updated_at']
                ];
            }
            
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'draw' => intval($_GET['draw'] ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Obține un singur tag pentru editare
    if ($action === 'get') {
        $tagId = intval($_GET['id'] ?? 0);
        
        if ($tagId <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid tag ID'
            ]);
            exit;
        }
        
        try {
            $tag = $db->fetchSingle("
                SELECT t.*, COUNT(at.article_id) as usage_count
                FROM tags t 
                LEFT JOIN article_tags at ON t.id = at.tag_id 
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'approved'
                WHERE t.id = ?
                GROUP BY t.id, t.name, t.description, t.created_at, t.updated_at
            ", [$tagId]);
            
            if (!$tag) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Tag not found'
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'tag' => $tag
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($action === 'stats') {
        try {
            // Cele mai folosite taguri
            $mostUsed = $db->fetchAll("
                SELECT t.name, COUNT(at.article_id) as usage_count
                FROM tags t 
                LEFT JOIN article_tags at ON t.id = at.tag_id 
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'approved'
                GROUP BY t.id, t.name
                HAVING usage_count > 0
                ORDER BY usage_count DESC 
                LIMIT 10
            ");
            
            // Taguri recente
            $recent = $db->fetchAll("
                SELECT name, created_at
                FROM tags 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            
            // Distribuția utilizării
            $distribution = $db->fetchSingle("
                SELECT 
                    SUM(CASE WHEN usage_count >= 10 THEN 1 ELSE 0 END) as heavy,
                    SUM(CASE WHEN usage_count BETWEEN 1 AND 9 THEN 1 ELSE 0 END) as moderate,
                    SUM(CASE WHEN usage_count = 0 THEN 1 ELSE 0 END) as unused
                FROM (
                    SELECT COUNT(at.article_id) as usage_count
                    FROM tags t 
                    LEFT JOIN article_tags at ON t.id = at.tag_id 
                    LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'approved'
                    GROUP BY t.id
                ) as tag_stats
            ");
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'most_used' => $mostUsed,
                    'recent' => $recent,
                    'distribution' => $distribution
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // GET pentru autocomplete (compatibilitate cu API-ul existent)
    if ($action === 'search' || isset($_GET['search'])) {
        $search = $_GET['search'] ?? '';
        
        try {
            if ($search) {
                $tags = $db->fetchAll(
                    "SELECT name FROM tags WHERE name LIKE ? ORDER BY name LIMIT 10", 
                    [$search . '%']
                );
            } else {
                $tags = $db->fetchAll("
                    SELECT t.name, COUNT(at.tag_id) as usage_count 
                    FROM tags t 
                    LEFT JOIN article_tags at ON t.id = at.tag_id 
                    GROUP BY t.id, t.name 
                    ORDER BY usage_count DESC, t.name ASC
                    LIMIT 50
                ");
            }
            
            echo json_encode([
                'success' => true,
                'tags' => array_column($tags, 'name')
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// POST: Acțiuni de management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verifică CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
        exit;
    }
    
    switch ($action) {
        case 'create':
            createTag();
            break;
            
        case 'update':
            updateTag();
            break;
            
        case 'delete':
            deleteTag();
            break;
            
        case 'merge':
            mergeTags();
            break;
            
        case 'clean_unused':
            cleanUnusedTags();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

function createTag() {
    global $db;
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Tag name is required']);
        return;
    }
    
    // Validare nume tag
    if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $name)) {
        echo json_encode(['success' => false, 'error' => 'Tag name contains invalid characters']);
        return;
    }
    
    if (strlen($name) < 2 || strlen($name) > 50) {
        echo json_encode(['success' => false, 'error' => 'Tag name must be between 2 and 50 characters']);
        return;
    }
    
    try {
        // Verifică dacă tagul există deja
        $existing = $db->fetchSingle("SELECT id FROM tags WHERE name = ?", [$name]);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Tag already exists']);
            return;
        }
        
        // Inserează noul tag
        $tagData = ['name' => $name];
        if (!empty($description)) {
            $tagData['description'] = $description;
        }
        
        $tagId = $db->insert('tags', $tagData);
        if (!$tagId) {
            echo json_encode(['success' => false, 'error' => 'Failed to create tag']);
            return;
        }
        
        logActivity($_SESSION['user']['id'], 'tag_created', 'Created tag: ' . $name);
        
        echo json_encode(['success' => true, 'message' => 'Tag created successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateTag() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid tag ID']);
        return;
    }
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Tag name is required']);
        return;
    }
    
    // Validare nume tag
    if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $name)) {
        echo json_encode(['success' => false, 'error' => 'Tag name contains invalid characters']);
        return;
    }
    
    if (strlen($name) < 2 || strlen($name) > 50) {
        echo json_encode(['success' => false, 'error' => 'Tag name must be between 2 and 50 characters']);
        return;
    }
    
    try {
        // Verifică dacă tagul există
        $tag = $db->fetchSingle("SELECT name FROM tags WHERE id = ?", [$id]);
        if (!$tag) {
            echo json_encode(['success' => false, 'error' => 'Tag not found']);
            return;
        }
        
        // Verifică dacă noul nume nu există deja (pentru alt tag)
        $existing = $db->fetchSingle("SELECT id FROM tags WHERE name = ? AND id != ?", [$name, $id]);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Tag name already exists']);
            return;
        }
        
        // Actualizează tag-ul
        $updateData = ['name' => $name];
        if ($description !== null) {
            $updateData['description'] = $description;
        }
        
        $rowsUpdated = $db->update('tags', $updateData, 'id = :id', ['id' => $id]);
        if ($rowsUpdated === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to update tag']);
            return;
        }
        
        logActivity($_SESSION['user']['id'], 'tag_updated', 'Updated tag: ' . $tag['name'] . ' to ' . $name);
        
        echo json_encode(['success' => true, 'message' => 'Tag updated successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteTag() {
    global $db;
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid tag ID']);
        return;
    }
    
    try {
        // Verifică dacă tagul există
        $tag = $db->fetchSingle("SELECT name FROM tags WHERE id = ?", [$id]);
        if (!$tag) {
            echo json_encode(['success' => false, 'error' => 'Tag not found']);
            return;
        }
        
        // Verifică dacă tagul este folosit
        $usage = $db->fetchSingle("SELECT COUNT(*) as count FROM article_tags WHERE tag_id = ?", [$id]);
        if ($usage['count'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete tag that is in use']);
            return;
        }
        
        $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity($_SESSION['user']['id'], 'tag_deleted', 'Deleted tag: ' . $tag['name']);
        
        echo json_encode(['success' => true, 'message' => 'Tag deleted successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function mergeTags() {
    global $db;
    
    $sourceId = intval($_POST['source_id'] ?? 0);
    $targetId = intval($_POST['target_id'] ?? 0);
    
    if ($sourceId <= 0 || $targetId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid tag IDs']);
        return;
    }
    
    if ($sourceId === $targetId) {
        echo json_encode(['success' => false, 'error' => 'Cannot merge tag with itself']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Verifică că ambele taguri există
        $sourceTag = $db->fetchSingle("SELECT name FROM tags WHERE id = ?", [$sourceId]);
        $targetTag = $db->fetchSingle("SELECT name FROM tags WHERE id = ?", [$targetId]);
        
        if (!$sourceTag || !$targetTag) {
            $db->rollback();
            echo json_encode(['success' => false, 'error' => 'One or both tags not found']);
            return;
        }
        
        // Mută toate articolele de la source la target (evitând duplicate)
        $updateStmt = $db->prepare("UPDATE IGNORE article_tags SET tag_id = ? WHERE tag_id = ?");
        $updateStmt->execute([$targetId, $sourceId]);
        
        // Șterge duplicate rămase (dacă există)
        $deleteArtStmt = $db->prepare("DELETE FROM article_tags WHERE tag_id = ?");
        $deleteArtStmt->execute([$sourceId]);
        
        // Șterge tag-ul source
        $deleteTagStmt = $db->prepare("DELETE FROM tags WHERE id = ?");
        $deleteTagStmt->execute([$sourceId]);
        
        $db->commit();
        
        logActivity($_SESSION['user']['id'], 'tags_merged', 'Merged tag "' . $sourceTag['name'] . '" into "' . $targetTag['name'] . '"');
        
        echo json_encode(['success' => true, 'message' => 'Tags merged successfully']);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function cleanUnusedTags() {
    global $db;
    
    try {
        // Găsește tagurile nefolosite
        $unusedTags = $db->fetchAll("
            SELECT t.id, t.name 
            FROM tags t 
            LEFT JOIN article_tags at ON t.id = at.tag_id 
            WHERE at.tag_id IS NULL
        ");
        
        $deletedCount = 0;
        
        if (!empty($unusedTags)) {
            $tagIds = array_column($unusedTags, 'id');
            $tagNames = array_column($unusedTags, 'name');
            
            $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
            $deleteStmt = $db->prepare("DELETE FROM tags WHERE id IN ($placeholders)");
            $deleteStmt->execute($tagIds);
            
            $deletedCount = count($unusedTags);
            
            logActivity($_SESSION['user']['id'], 'tags_cleaned', 'Deleted ' . $deletedCount . ' unused tags: ' . implode(', ', $tagNames));
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Unused tags cleaned',
            'deleted_count' => $deletedCount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

?>

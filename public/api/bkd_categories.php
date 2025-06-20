<?php

require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$ops = ['add_category','edit_category','delete_category','enable_category','disable_category'];
if (!hasPermission($_SESSION['user']['id'],$ops)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = new Database();

// DataTables GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Pentru populare DataTables
    if (isset($_GET['draw'])) {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $searchValue = $_GET['search']['value'] ?? '';

        // WHERE și parametri pentru search
        $where = "WHERE is_active = 1";
       if ($searchValue) {
            $where .= " AND (name LIKE :search1 OR description LIKE :search2)";
        }


        // debug
        //error_log("QUERY: SELECT COUNT(*) FROM categories $where");
        //error_log("SEARCH: $searchValue");

        // Total categorii (fără filtrare)
        $totalStmt = $db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1");
        $totalCategories = $totalStmt->fetchColumn();

        // Total filtrat (cu/ fără search)
        $filteredStmt = $db->prepare("SELECT COUNT(*) FROM categories $where");
        if ($searchValue) {
            $filteredStmt->bindValue(':search1', "%$searchValue%", PDO::PARAM_STR);
            $filteredStmt->bindValue(':search2', "%$searchValue%", PDO::PARAM_STR);
        }   
        $filteredStmt->execute();
        $filtered = $filteredStmt->fetchColumn();

        // Select efectiv cu LIMIT/OFFSET
        $stmt = $db->prepare("SELECT * FROM categories $where ORDER BY id DESC LIMIT :limit OFFSET :offset");
        if ($searchValue) {
            $stmt->bindValue(':search1', "%$searchValue%", PDO::PARAM_STR);
            $stmt->bindValue(':search2', "%$searchValue%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $totalCategories,
            "recordsFiltered" => $filtered,
            "data" => $categories
        ]);
        exit;
    }

    // Pentru populare select-uri din modale
    if (isset($_GET['action']) && $_GET['action'] === 'selects') {
        $icons = $db->fetchAll("SELECT filename, label FROM categories_icons");
        $usedIcons = $db->fetchAll("SELECT icon FROM categories WHERE icon IS NOT NULL");
        $usedFilenames = array_column($usedIcons, 'icon');
        $iconOptions = '<option></option>';
        foreach ($icons as $icon) {
            if (!in_array($icon['filename'], $usedFilenames)) {
                    $iconOptions .= '<option value="'.$icon['filename'].'" data-img="'.APP_URL.'assets/icons/categories/'.$icon['filename'].'">'.$icon['label'].'</option>';
            }
        }

        // Disabled categories cu icon
        $disabled = $db->fetchAll("SELECT id, name, icon FROM categories WHERE is_active = 0");
        $disabledOptions = '<option></option>';
        foreach ($disabled as $cat) {
            $img = $cat['icon'] ? APP_URL.'assets/icons/categories/'.$cat['icon'] : '';
            $disabledOptions .= '<option value="'.$cat['id'].'" data-img="'.$img.'">'.$cat['name'].'</option>';
        }
        // Enabled categories cu icon
        $enabled = $db->fetchAll("SELECT id, name, icon FROM categories WHERE is_active = 1");
        $enabledOptions = '<option></option>';
        foreach ($enabled as $cat) {
            $img = $cat['icon'] ? APP_URL.'assets/icons/categories/'.$cat['icon'] : '';
            $enabledOptions .= '<option value="'.$cat['id'].'" data-img="'.$img.'">'.$cat['name'].'</option>';
        }

        echo json_encode([
            'icons' => $iconOptions,
            'disabled' => $disabledOptions,
            'enabled' => $enabledOptions
        ]);
        exit;
    }
}

// POST pentru acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
    }

    if ($action === 'add_category') {
        $name = trim($_POST['cat_name']);
        $description = trim($_POST['cat_description'] ?? '');
        $icon = trim($_POST['cat_icon']);
        if (strlen($name) < 2) {
            echo json_encode(['error' => 'Numele trebuie să aibă cel puțin 2 caractere.']);
            exit;
        }
        $existing = $db->fetchSingle("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)", [$name]);
        if ($existing) {
            echo json_encode(['error' => "Categoria „{$name}” există deja."]);
            exit;
        }
        $stmt = $db->prepare("INSERT INTO categories (name, icon, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $icon, $description]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_icon') {
        if (!isset($_FILES['icon_filename']['name'])) {
            echo json_encode(['error' => 'Icon is not selected']);
            exit;
        }
        $file = $_FILES['icon_filename'];
        if($file['error'] !== UPLOAD_ERR_OK){
            echo json_encode(['error' => 'Eroare la upload']);
            exit;
        }
        $iconLabel = $_POST['icon_label'];
        $iconFile = $file['name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif','svg'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['error' => 'Format invalid']);
            exit;
        }
        $parentDir = dirname(__DIR__, 2);
        $dest = $parentDir.'/assets/icons/categories/' . $iconFile;
        if (!is_dir($parentDir.'/assets/icons/categories/')) {
            mkdir($parentDir.'/assets/icons/categories/',0777, true);
        }
        move_uploaded_file($file['tmp_name'], $dest);
        $exists = $db->fetchSingle("SELECT id FROM categories_icons WHERE filename = ?", [$iconFile]);
        if (!$exists) {
            $stmt = $db->prepare("INSERT INTO categories_icons (filename, label) VALUES (?, ?)");
            $stmt->execute([$iconFile, $iconLabel]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Icon is already in DB']);
        }
        exit;
    }

    if ($action === 'enable_category') {
        $cid = trim($_POST['category_id']);
        $stmt = $db->prepare("UPDATE categories SET is_active = '1' WHERE id = ?");
        $stmt->execute([$cid]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'disable_category') {
        $cid = trim($_POST['category_id']);
        $stmt = $db->prepare("UPDATE categories SET is_active = '0' WHERE id = ?");
        $stmt->execute([$cid]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_category') {
        $cid = trim($_POST['category_id']);
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$cid]);
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
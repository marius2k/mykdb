<?php

require_once '../../config/bootstrap.php';

// Set JSON response header
header('Content-Type: application/json');

$ops = ['edit_acl'];

// Check permissions
if (!hasPermission($_SESSION['user']['id'], $ops)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

if ($_SESSION['user']['role'] === 'guest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Guest users cannot edit ACL']);
    exit;
}

$db = new Database();

// Get current user role
$currentUserRole = $_SESSION['user']['role_name'] ?? 'guest';
$isSuperadmin = ($currentUserRole === 'superadmin');

// Locked operations (only superadmin can assign these)
$lockedOps = ['edit_acl', 'manage_users', 'delete_user']; // Add your locked operations here

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $roleId = $input['roleId'] ?? null;
    $selectedOps = $input['operations'] ?? [];
    
    if (!$roleId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Role ID is required']);
        exit;
    }
    
    // Verify role exists
    $role = $db->fetchSingle("SELECT * FROM roles WHERE id = ?", [$roleId]);
    if (!$role) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Role not found']);
        exit;
    }
    
    try {
        // Protecție: exclude locked ops dacă nu e superadmin
        if (!$isSuperadmin && !empty($lockedOps)) {
            $lockedIds = $db->fetchAll("SELECT id FROM operations WHERE name IN ('" . implode("','", $lockedOps) . "')");
            $lockedIds = array_column($lockedIds, 'id');
            $selectedOps = array_diff($selectedOps, $lockedIds);
        }
        
        // Delete existing permissions for this role
        $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        
        // Insert new permissions
        foreach ($selectedOps as $opId) {
            $db->query("INSERT INTO role_permissions (role_id, operation_id) VALUES (?, ?)", [$roleId, $opId]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Permisiunile au fost salvate cu succes',
            'updatedCount' => count($selectedOps)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

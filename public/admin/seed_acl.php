<?php




require_once '../../config/bootstrap.php'; // include conexiune DB

$acl = [
    'superadmin' => ['*'],
    'admin' => [
        'search', 'view_article', 'create_article', 'edit_own_article',
        'edit_article', 'publish_article', 'disable_article', 'enable_article',
        'approve_article', 'delete_article', 'export_article',
        'add_comment', 'edit_comment', 'delete_comment', 'approve_comment',
        'add_category', 'edit_category', 'edit_user', 'disable_user', 'enable_user',
        'delete_user', 'modify_user', 'modify_own_user',
        'view_own_activity', 'view_all_activity'
    ],
    'contributor' => ['create_article', 'edit_own_article', 'modify_own_user'],
    'editor' => ['approve_article', 'edit_article', 'modify_own_user'],
    'moderator' => ['disable_user', 'enable_user', 'approve_comment', 'modify_own_user'],
    'guest' => ['view_article', 'search']
];


try {
    $db = new Database();
    // Disable FK for bulk insert
    $db->query("SET foreign_key_checks = 0");

    foreach ($acl as $roleName => $permissions) {
        $role = $db->fetchSingle("SELECT id FROM roles WHERE name = ?", [$roleName]);
        if (!$role) {
            echo "Rol inexistent: $roleName\n";
            continue;
        }

        if (in_array('*', $permissions)) {
            // Superadmin → toate operațiunile
            $allOps = $db->fetchAll("SELECT id FROM operations");
            foreach ($allOps as $op) {
                $db->query("REPLACE INTO role_permissions (role_id, operation_id) VALUES (?, ?)", [
                    $role['id'], $op['id']
                ]);
            }
            echo "✅ Permisiuni complet populate pentru $roleName (ALL)\n";
        } else {
            foreach ($permissions as $perm) {
                $op = $db->fetchSingle("SELECT id FROM operations WHERE name = ?", [$perm]);
                if ($op) {
                    $db->query("REPLACE INTO role_permissions (role_id, operation_id) VALUES (?, ?)", [
                        $role['id'], $op['id']
                    ]);
                } else {
                    echo "⚠️ Operațiune inexistentă: $perm (rol: $roleName)\n";
                }
            }
            echo "✅ Permisiuni setate pentru $roleName\n";
        }
    }

    $db->query("SET foreign_key_checks = 1");
    echo "\n🎉 ACL configurat cu succes!\n";

} catch (Exception $e) {
    echo "❌ Eroare: " . $e->getMessage();
}

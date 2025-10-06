#!/usr/bin/env php
<?php
/**
 * Verification Script for Moderator Approve Article Permissions
 * 
 * This script checks if the moderator role has the approve_article permission.
 * Run this script to verify that the fix has been applied correctly.
 * 
 * Usage: php verify_moderator_permissions.php
 */

// Include the bootstrap file
require_once __DIR__ . '/config/bootstrap.php';

echo "=== Moderator Permission Verification ===\n\n";

$db = new Database();

try {
    // Get moderator role ID
    $moderatorRole = $db->fetchSingle("SELECT id, name, label FROM roles WHERE name = 'moderator'");
    
    if (!$moderatorRole) {
        echo "❌ ERROR: Moderator role not found in database\n";
        exit(1);
    }
    
    echo "✅ Found moderator role:\n";
    echo "   - ID: {$moderatorRole['id']}\n";
    echo "   - Name: {$moderatorRole['name']}\n";
    echo "   - Label: {$moderatorRole['label']}\n\n";
    
    // Get approve_article operation ID
    $approveOp = $db->fetchSingle("SELECT id, name, description FROM operations WHERE name = 'approve_article'");
    
    if (!$approveOp) {
        echo "❌ ERROR: approve_article operation not found in database\n";
        exit(1);
    }
    
    echo "✅ Found approve_article operation:\n";
    echo "   - ID: {$approveOp['id']}\n";
    echo "   - Name: {$approveOp['name']}\n";
    echo "   - Description: {$approveOp['description']}\n\n";
    
    // Check if moderator has approve_article permission
    $permission = $db->fetchSingle(
        "SELECT rp.* FROM role_permissions rp 
         WHERE rp.role_id = ? AND rp.operation_id = ?",
        [$moderatorRole['id'], $approveOp['id']]
    );
    
    if ($permission) {
        echo "✅ SUCCESS: Moderator role HAS approve_article permission!\n";
        echo "   Permission record found in role_permissions table\n\n";
        
        // Get all moderator permissions for reference
        $allPerms = $db->fetchAll(
            "SELECT o.name, o.description 
             FROM role_permissions rp
             JOIN operations o ON o.id = rp.operation_id
             WHERE rp.role_id = ?
             ORDER BY o.name",
            [$moderatorRole['id']]
        );
        
        echo "📋 All moderator permissions (" . count($allPerms) . " total):\n";
        foreach ($allPerms as $perm) {
            echo "   - {$perm['name']}\n";
        }
        echo "\n";
        
        echo "🎉 The fix has been successfully applied!\n";
        echo "   Moderators should now be able to approve pending articles.\n\n";
        
        exit(0);
        
    } else {
        echo "❌ FAIL: Moderator role DOES NOT have approve_article permission!\n";
        echo "   Permission record NOT found in role_permissions table\n\n";
        echo "💡 To fix this, run the following command:\n";
        echo "   php public/admin/seed_acl.php\n\n";
        echo "   Or access it via browser:\n";
        echo "   http://your-domain/mykdb/public/admin/seed_acl.php\n\n";
        
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: Database error occurred\n";
    echo "   Message: {$e->getMessage()}\n\n";
    exit(1);
}

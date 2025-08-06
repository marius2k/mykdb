<?php
/**
 * Test script pentru API-ul de management tag-uri
 * Testează funcționalitățile noi cu câmpurile extended - direct cu DB
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "=== Test Direct Database Tag Management ===\n\n";

try {
    $db = new Database();
    
    // Test 1: Verificăm structura tabelei tags
    echo "1. Verificăm structura tabelei tags:\n";
    $columns = $db->fetchAll("DESCRIBE tags");
    
    $hasDescription = false;
    $hasCreatedAt = false;
    $hasUpdatedAt = false;
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
        if ($column['Field'] === 'description') $hasDescription = true;
        if ($column['Field'] === 'created_at') $hasCreatedAt = true;
        if ($column['Field'] === 'updated_at') $hasUpdatedAt = true;
    }
    
    echo "   Câmpuri noi:\n";
    echo "   - description: " . ($hasDescription ? "✅ Da" : "❌ Nu") . "\n";
    echo "   - created_at: " . ($hasCreatedAt ? "✅ Da" : "❌ Nu") . "\n";
    echo "   - updated_at: " . ($hasUpdatedAt ? "✅ Da" : "❌ Nu") . "\n\n";
    
    // Test 2: Verificăm structura tabelei article_tags
    echo "2. Verificăm structura tabelei article_tags:\n";
    $artTagColumns = $db->fetchAll("DESCRIBE article_tags");
    
    $hasArtCreatedAt = false;
    $hasAssignedBy = false;
    
    foreach ($artTagColumns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
        if ($column['Field'] === 'created_at') $hasArtCreatedAt = true;
        if ($column['Field'] === 'assigned_by') $hasAssignedBy = true;
    }
    
    echo "   Câmpuri noi în article_tags:\n";
    echo "   - created_at: " . ($hasArtCreatedAt ? "✅ Da" : "❌ Nu") . "\n";
    echo "   - assigned_by: " . ($hasAssignedBy ? "✅ Da" : "❌ Nu") . "\n\n";
    
    // Test 3: Testăm inserarea unui tag cu descriere
    echo "3. Testăm inserarea unui tag cu descriere:\n";
    
    // Verifică dacă tagul de test există deja și îl șterge
    $existingTest = $db->fetchSingle("SELECT id FROM tags WHERE name = ?", ['Test Tag Direct']);
    if ($existingTest) {
        $db->query("DELETE FROM tags WHERE id = ?", [$existingTest['id']]);
        echo "   Tag de test existent șters.\n";
    }
    
    // Inserează tag nou cu metodele Database
    $tagData = [
        'name' => 'Test Tag Direct',
        'description' => 'Acesta este un tag de test creat direct din script'
    ];
    
    $newTagId = $db->insert('tags', $tagData);
    
    if ($newTagId) {
        echo "   ✅ Tag creat cu succes - ID: $newTagId\n";
        
        // Verifică că a fost creat corect
        $createdTag = $db->fetchSingle("SELECT * FROM tags WHERE id = ?", [$newTagId]);
        if ($createdTag) {
            echo "   - Nume: {$createdTag['name']}\n";
            echo "   - Descriere: " . ($createdTag['description'] ?? 'NULL') . "\n";
            echo "   - Created_at: " . ($createdTag['created_at'] ?? 'NULL') . "\n";
        }
    } else {
        echo "   ❌ Eroare la crearea tag-ului\n";
    }
    echo "\n";
    
    // Test 4: Testăm actualizarea tag-ului
    if ($newTagId) {
        echo "4. Testăm actualizarea tag-ului:\n";
        
        $updateData = [
            'name' => 'Test Tag Direct Updated',
            'description' => 'Descriere actualizată prin script direct'
        ];
        
        $rowsUpdated = $db->update('tags', $updateData, 'id = :id', ['id' => $newTagId]);
        
        if ($rowsUpdated !== false) {
            echo "   ✅ Tag actualizat cu succes - Rânduri afectate: $rowsUpdated\n";
            
            // Verifică actualizarea
            $updatedTag = $db->fetchSingle("SELECT * FROM tags WHERE id = ?", [$newTagId]);
            if ($updatedTag) {
                echo "   - Nume nou: {$updatedTag['name']}\n";
                echo "   - Descriere nouă: " . ($updatedTag['description'] ?? 'NULL') . "\n";
                echo "   - Updated_at: " . ($updatedTag['updated_at'] ?? 'NULL') . "\n";
            }
        } else {
            echo "   ❌ Eroare la actualizarea tag-ului\n";
        }
        echo "\n";
    }
    
    // Test 5: Listăm toate tag-urile cu noile câmpuri
    echo "5. Listăm toate tag-urile existente:\n";
    $allTags = $db->fetchAll("SELECT * FROM tags ORDER BY created_at DESC LIMIT 10");
    
    if (!empty($allTags)) {
        echo "   Total tag-uri găsite: " . count($allTags) . "\n";
        foreach ($allTags as $tag) {
            echo "   - ID: {$tag['id']} | Nume: {$tag['name']} | Descriere: " . 
                 ($tag['description'] ?? 'fără descriere') . 
                 " | Creat: " . ($tag['created_at'] ?? 'necunoscut') . "\n";
        }
    } else {
        echo "   Nu există tag-uri în baza de date.\n";
    }
    echo "\n";
    
    // Test 6: Testăm query-ul din API pentru statistici
    echo "6. Testăm query-ul pentru statistici:\n";
    
    $mostUsed = $db->fetchAll("
        SELECT t.name, COUNT(at.article_id) as usage_count
        FROM tags t 
        LEFT JOIN article_tags at ON t.id = at.tag_id 
        LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'approved'
        GROUP BY t.id, t.name
        HAVING usage_count > 0
        ORDER BY usage_count DESC 
        LIMIT 5
    ");
    
    echo "   Tag-uri cele mai folosite:\n";
    if (!empty($mostUsed)) {
        foreach ($mostUsed as $tag) {
            echo "   - {$tag['name']}: {$tag['usage_count']} utilizări\n";
        }
    } else {
        echo "   Nu există tag-uri folosite în articole.\n";
    }
    
    echo "\n";
    
    // Cleanup - șterge tag-ul de test
    if ($newTagId) {
        $db->query("DELETE FROM tags WHERE id = ?", [$newTagId]);
        echo "   🧹 Tag de test șters.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Eroare în timpul testării: " . $e->getMessage() . "\n";
    echo "Urmă stivă:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Sfârșitul testelor ===\n";

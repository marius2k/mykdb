<?php
// scripts/add_version_column.php
// Adaugă câmpul version în tabela articles și actualizează datele existente

require_once '../config/bootstrap.php';

$db = new Database();

try {
    // Adaugă câmpul version în tabela articles
    $db->query("ALTER TABLE articles ADD COLUMN version INT DEFAULT 1 AFTER status");
    echo "Câmpul 'version' a fost adăugat în tabela articles.<br>";

    // Actualizează articolele existente cu versiunea corectă din article_versions
    $articles = $db->fetchAll("SELECT id FROM articles");
    
    foreach ($articles as $article) {
        $articleId = $article['id'];
        // Găsește ultima versiune aprobată pentru acest articol
        $lastApprovedVersion = $db->fetchSingle("
            SELECT version_number 
            FROM article_versions 
            WHERE article_id = ? AND status = 'approved' 
            ORDER BY version_number DESC 
            LIMIT 1", [$articleId]);
        
        $version = $lastApprovedVersion ? $lastApprovedVersion['version_number'] : 1;
        
        // Actualizează câmpul version în articles
        $db->query("UPDATE articles SET version = ? WHERE id = ?", [$version, $articleId]);
        echo "Articolul $articleId actualizat cu versiunea $version<br>";
    }
    
    echo "Migrare finalizată cu succes!";
    
} catch (Exception $e) {
    echo "Eroare la migrare: " . $e->getMessage();
}

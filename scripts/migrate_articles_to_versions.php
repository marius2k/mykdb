<?php
// scripts/migrate_articles_to_versions.php
// Migrare articole -> article_versions (versiunea initiala v1)


require_once '../config/bootstrap.php';

$db = new Database();
$count = 0;

// Selectăm toate articolele
$articles = $db->fetchAll("SELECT * FROM articles");

foreach ($articles as $row) {
    $article_id = $row['id'];
    $title = $row['title'];
    $content = clean_html($row['content']);
    $author_id = $row['user_id'];
    $created_at = $row['created_at'];
    $updated_at = $row['updated_at'];
    $status = 'approved'; // sau 'published', după caz

    $sql_insert = "INSERT INTO article_versions (
        article_id, version_number, title, content, author_id, status, created_at, updated_at, change_note
    ) VALUES (
        :article_id, 1, :title, :content, :author_id, :status, :created_at, :updated_at, :change_note
    )";

    $params = [
        ':article_id' => $article_id,
        ':title' => $title,
        ':content' => $content,
        ':author_id' => $author_id,
        ':status' => $status,
        ':created_at' => $created_at,
        ':updated_at' => $updated_at,
        ':change_note' => null
    ];

    try {
        $db->query($sql_insert, $params);
        $count++;
    } catch (Exception $e) {
        echo "<br><br>Eroare la articolul $article_id: " . $e->getMessage() . "<br>";
    }
}

echo "Migrare finalizată. $count articole migrate în article_versions.";

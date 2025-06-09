<?php

// Conectare la baza de date
try {
    $db = new PDO('mysql:host=localhost;dbname=knowledge_db;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Eroare la conectarea la baza de date: " . $e->getMessage() . "\n";
    exit(1);
}

// Obține toți utilizatorii existenți
$stmt = $db->query("SELECT id FROM users");
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);


// Populează tabela user_configuration
$stmt = $db->prepare("
    INSERT INTO user_configuration (user_id, last_processed_activity_log_id)
    VALUES (:user_id, NULL)
");

foreach ($users as $userId) {
    try {
        $stmt->execute([':user_id' => $userId]);
        echo "Înregistrare creată pentru user_id: " . $userId . "\n";
    } catch (PDOException $e) {
        echo "Eroare la crearea înregistrării pentru user_id: " . $userId . ": " . $e->getMessage() . "\n";
    }
}

echo "Migrare finalizată.\n";
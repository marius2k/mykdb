<?php
//$host = 'localhost';
//$db   = 'knowledge_db';
//$user = 'root';
//$pass = '';
//$charset = 'utf8mb4';


$dsn = "mysql:host=". DB_HOST . ";dbname=" . DB_NAME . ";charset=". DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

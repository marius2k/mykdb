<?php


// Setari aplicatie
define('APP_NAME','My KDB');
//define('APP_ROOT','/opt/lampp/htdocs/mykdb/');
define('APP_URL','http://devbox11:8080/');
define('APP_VERSION','Version 2.4');

// Setări constante pentru conexiunea la bază de date
//define('DB_HOST', 'localhost');
//define('DB_PORT', '3306');
//define('DB_NAME', 'knowledge_db');
//define('DB_USER', 'root');
//define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');




// Settings for running in container (docker-compose)

$host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('MYSQL_DATABASE') ?: 'knowledge_db';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';


define('DB_HOST', $host);
define('DB_PORT', '3306');
define('DB_NAME', $db_name);
define('DB_USER', $user);
define('DB_PASS', $pass);


//$conn = new mysqli($host, $user, $pass, $db_name);
/*
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
*/
?>

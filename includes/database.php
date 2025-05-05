<?php
//require_once __DIR__ . '/config.php';

class Database {
    private $pdo;
    private $error;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database connection error: " . $this->error);
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    /**
     * Pregătește o declarație SQL pentru executare
     * @param string $sql Interogarea SQL cu parametri
     * @return PDOStatement|false Obiect PDOStatement pregătit
     */
    public function prepare($sql) {
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . implode(" ", $this->pdo->errorInfo()));
            }
            return $stmt;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Prepare statement error: " . $this->error);
            throw new Exception("Database query preparation failed.");
        }
    }

    /**
     * Execută o interogare SQL cu parametri
     * @param string $sql Interogarea SQL
     * @param array $params Parametrii pentru interogare
     * @return PDOStatement Obiect PDOStatement executat
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage() . "\nSQL: " . $sql);
            throw new Exception("Database query failed.");
        }
    }

    /**
     * Obține toate rândurile rezultate
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Obține un singur rând
     */
    public function fetchSingle($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Inserează date și returnează ID-ul inserat
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        try {
            $this->query($sql, $data);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizează date
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = :$column";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $where";
        $params = array_merge($data, $whereParams);
        
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returnează ultima eroare
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Începe o tranzacție
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Confirmă o tranzacție
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Anulează o tranzacție
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    public function getPdo() {
        return $this->pdo;
    }

    public function lastInsertedId(){

        return (int) $this->pdo->lastInsertId();
    }
    
    
}


// Funcție helper pentru conexiune globală
function getDatabaseConnection() {
    static $db = null;
    
    if ($db === null) {
        $db = new Database();
    }
    
    return $db->getPdo(); // Returnează direct instanța PDO pentru compatibilitate
}


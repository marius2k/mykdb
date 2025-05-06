<?php

class UserSettings {
    private $db;
    private $userId;

    public function __construct(Database $db, int $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }

    public function get(string $key): ?string {
        $row = $this->db->fetchSingle(
            "SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = ?",
            [$this->userId, $key]
        );
        return $row['setting_value'] ?? null;
    }

    public function set(string $key, string $value): void {
        $this->db->query(
            "INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$this->userId, $key, $value]
        );
    }

    public function all(): array {
        return $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?",
            [$this->userId]
        );
    }
}
?>
<?php

class UserSettings {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function get(string $key, int $userId): ?string {
        $sql = "SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = ?";
        return $this->db->fetchSingle($sql, [$userId, $key]);
    }

    public function set(string $key, string $value, int $userId): bool {
        // Verifică dacă deja există setarea
        $stmt = $this->db->prepare("SELECT id FROM user_settings WHERE user_id = ? AND setting_key = ?");
        $stmt->execute([$userId, $key]);
    
        if ($stmt->fetch()) {
            // Update
            $stmt = $this->db->prepare("UPDATE user_settings SET setting_value = ? WHERE user_id = ? AND setting_key = ?");
            return $stmt->execute([$value, $userId, $key]);
        } else {
            // Insert
            $stmt = $this->db->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)");
            return $stmt->execute([$userId, $key, $value]);
        }
    }
    

    public function getAll(int $userId): array {
        $sql = "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?";
        $rows = $this->db->fetchAll($sql, [$userId]);
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    
}
?>
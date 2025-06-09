<?php

class NotificationService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    private function createNotification(int $userId, string $type, string $message, array $data = [], int $activityLogId = null, string $channel = 'in_app'): int {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, message, data, activity_log_id, channel)
            VALUES (:user_id, :type, :message, :data, :activity_log_id, :channel)
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':message' => $message,
            ':data' => json_encode($data),
            ':activity_log_id' => $activityLogId,
            ':channel' => $channel
        ]);

        return $this->db->lastInsertId();
    }

    public function getNotificationsForUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");

        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $notificationId): void {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET read_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([':id' => $notificationId]);
    }


    

    // Noua metodă pentru a crea notificări din activity_log
    public function createNotificationFromActivityLog(int $activityLogId): void {
        $activityLog = $this->getActivityLog($activityLogId);

        if (!$activityLog) {
            return; // Sau aruncă o excepție
        }

        $userId = $activityLog['user_id'];
        $actionType = $activityLog['action_type'];
        $details = json_decode($activityLog['details'], true);

        // Mapare action_type la tipuri de notificări
        $notificationType = $this->mapActionTypeToNotificationType($actionType);

        if (!$notificationType) {
            return; // Nu este un tip de notificare relevant
        }

        // Creare mesaj de notificare
        $message = $this->createNotificationMessage($notificationType, $details);

        // Creare notificare
        $this->createNotification($userId, $notificationType, $message, $details, $activityLogId);
    }

    

    private function getActivityLog(int $activityLogId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM activity_log
            WHERE id = :id AND archived = 0 -- Adăugăm condiția archived = 0
        ");

        $stmt->execute([':id' => $activityLogId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    private function mapActionTypeToNotificationType(string $actionType): ?string {
        $mapping = [
            'register_user' => 'creare_user',
            'article_created' => 'creare_articol',
            'article_edited' => 'editare_articol',
            'category_created' => 'creare_categorie',
            'category_deleted' => 'stergere_categorie'
            // Adaugă mai multe mapări aici
        ];

        return $mapping[$actionType] ?? null;
    }

    private function createNotificationMessage(string $notificationType, array $details): string {
        switch ($notificationType) {
            case 'creare_user':
                return "Un utilizator nou a fost creat: " . $details['username'];
            case 'editare_articol':
                return "Articolul " . $details['article_title'] . " a fost editat.";
            // Adaugă mai multe cazuri aici
            default:
                return "O acțiune a avut loc: " . $notificationType;
        }
    }
}
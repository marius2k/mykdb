<?php

class Gamification {
    private $db;
    
    public function __construct($database = null) {
        $this->db = $database ?: new Database();
    }
    
    /**
     * Adaugă puncte pentru un utilizator
     */
    public function addPoints($userId, $points, $action, $description = '', $relatedId = null, $relatedType = 'system') {
        try {
            // Adaugă în istoric
            $this->db->query(
                "INSERT INTO points_history (user_id, action, points, description, related_id, related_type) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$userId, $action, $points, $description, $relatedId, $relatedType]
            );
            
            // Actualizează totalul utilizatorului
            $this->updateUserTotalPoints($userId);
            
            // Verifică badge-urile
            $this->checkAndAwardBadges($userId);
            
            return true;
        } catch (Exception $e) {
            error_log("Error adding points: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizează totalul punctelor pentru utilizator
     */
    private function updateUserTotalPoints($userId) {
        // Calculează totalul punctelor
        $totalPoints = $this->db->fetchSingle(
            "SELECT COALESCE(SUM(points), 0) as total FROM points_history WHERE user_id = ?",
            [$userId]
        )['total'] ?? 0;
        
        // Determină nivelul
        $level = $this->calculateLevel($totalPoints);
        
        // Actualizează în user_points
        $this->db->query(
            "INSERT INTO user_points (user_id, total_points, level, last_activity_date) 
             VALUES (?, ?, ?, CURDATE()) 
             ON DUPLICATE KEY UPDATE 
             total_points = ?, level = ?, last_activity_date = CURDATE()",
            [$userId, $totalPoints, $level, $totalPoints, $level]
        );
        
        // Actualizează și în tabelul users pentru acces rapid
        $this->db->query(
            "UPDATE users SET total_points = ?, user_level = ? WHERE id = ?",
            [$totalPoints, $level, $userId]
        );
    }
    
    /**
     * Calculează nivelul pe baza punctelor
     */
    private function calculateLevel($points) {
        if ($points >= 3000) return 'Legend';
        if ($points >= 1500) return 'Master';
        if ($points >= 700) return 'Expert';
        if ($points >= 300) return 'Contributor';
        if ($points >= 100) return 'Explorer';
        return 'Rookie';
    }
    
    /**
     * Verifică și acordă badge-uri
     */
    private function checkAndAwardBadges($userId) {
        // Obține badge-urile pe care utilizatorul nu le are încă
        $availableBadges = $this->db->fetchAll(
            "SELECT b.* FROM badges b 
             WHERE b.is_active = 1 
             AND b.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)",
            [$userId]
        );
        
        foreach ($availableBadges as $badge) {
            if ($this->checkBadgeConditions($userId, $badge)) {
                $this->awardBadge($userId, $badge['id']);
            }
        }
    }
    
    /**
     * Verifică condițiile pentru un badge
     */
    private function checkBadgeConditions($userId, $badge) {
        $conditions = json_decode($badge['conditions'], true);
        
        if (!$conditions) return false;
        
        foreach ($conditions as $condition => $requiredValue) {
            $currentValue = $this->getUserStatistic($userId, $condition);
            if ($currentValue < $requiredValue) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obține o statistică specifică pentru utilizator
     */
    private function getUserStatistic($userId, $statistic) {
        switch ($statistic) {
            case 'articles_published':
                return $this->db->fetchSingle(
                    "SELECT COUNT(*) as count FROM articles WHERE author_id = ? AND status = 'published'",
                    [$userId]
                )['count'] ?? 0;
                
            case 'comments_made':
                return $this->db->fetchSingle(
                    "SELECT COUNT(*) as count FROM comments WHERE user_id = ?",
                    [$userId]
                )['count'] ?? 0;
                
            case 'articles_read':
                // Aceasta ar trebui implementată cu un sistem de tracking
                // Pentru moment returnăm 0
                return 0;
                
            default:
                return 0;
        }
    }
    
    /**
     * Acordă un badge utilizatorului
     */
    private function awardBadge($userId, $badgeId) {
        try {
            $this->db->query(
                "INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)",
                [$userId, $badgeId]
            );
            
            // Adaugă punctele bonus pentru badge
            $badge = $this->db->fetchSingle("SELECT * FROM badges WHERE id = ?", [$badgeId]);
            if ($badge && $badge['points_reward'] > 0) {
                $this->addPoints(
                    $userId, 
                    $badge['points_reward'], 
                    'badge_earned', 
                    "Badge earned: " . $badge['name']
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error awarding badge: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obține punctele utilizatorului
     */
    public function getUserPoints($userId) {
        return $this->db->fetchSingle(
            "SELECT * FROM user_points WHERE user_id = ?",
            [$userId]
        ) ?: ['total_points' => 0, 'level' => 'Rookie', 'streak_days' => 0];
    }
    
    /**
     * Obține badge-urile utilizatorului
     */
    public function getUserBadges($userId) {
        return $this->db->fetchAll(
            "SELECT b.*, ub.earned_at 
             FROM user_badges ub 
             JOIN badges b ON ub.badge_id = b.id 
             WHERE ub.user_id = ? 
             ORDER BY ub.earned_at DESC",
            [$userId]
        );
    }
}
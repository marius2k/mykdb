<?php
/**
 * Analytics Tracker Class
 * 
 * Provides methods to track user and admin activities in real-time
 * for the User Statistics dashboard.
 */

class AnalyticsTracker {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Track admin activity (edit, approve, publish)
     * 
     * @param int $userId User performing the action
     * @param int $articleId Article being acted upon
     * @param string $actionType Type: 'edit', 'approve', 'publish'
     * @param string $ipAddress User's IP address
     * @param string $sessionId Session identifier
     * @return bool Success status
     */
    public function trackAdminAction($userId, $articleId, $actionType, $ipAddress = null, $sessionId = null) {
        try {
            // Validate action type
            $validActions = ['edit', 'approve', 'publish'];
            if (!in_array($actionType, $validActions)) {
                return false;
            }
            
            // Get IP and session if not provided
            if (!$ipAddress) {
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            }
            if (!$sessionId) {
                $sessionId = session_id() ?: 'nosession';
            }
            
            // Insert admin activity
            $this->db->insert('admin_activity_analytics', [
                'user_id' => $userId,
                'article_id' => $articleId,
                'action_type' => $actionType,
                'action_date' => date('Y-m-d H:i:s'),
                'ip_address' => $ipAddress,
                'session_id' => $sessionId
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Analytics tracking error (admin): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track user activity (view, bookmark, rating, etc.)
     * 
     * @param int $userId User performing the action
     * @param int $articleId Article being acted upon
     * @param string $actionType Type: 'view', 'bookmark', 'rating', 'usefulness_rating', etc.
     * @param float|null $value Optional value (e.g., rating stars)
     * @param string $ipAddress User's IP address
     * @param string $sessionId Session identifier
     * @return bool Success status
     */
    public function trackUserAction($userId, $articleId, $actionType, $value = null, $ipAddress = null, $sessionId = null) {
        try {
            // Validate action type
            $validActions = ['view', 'bookmark', 'comment', 'rating', 'vote', 'edit', 'publish', 'approve', 'save_pdf', 'usefulness_rating', 'search', 'search_click'];
            if (!in_array($actionType, $validActions)) {
                return false;
            }
            
            // Get IP and session if not provided
            if (!$ipAddress) {
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            }
            if (!$sessionId) {
                $sessionId = session_id() ?: 'nosession';
            }
            
            // Prepare data
            $data = [
                'user_id' => $userId,
                'article_id' => $articleId,
                'action_type' => $actionType,
                'action_date' => date('Y-m-d H:i:s'),
                'session_id' => $sessionId,
                'ip_address' => $ipAddress
            ];
            
            // Add value if provided
            if ($value !== null) {
                $data['value'] = $value;
            }
            
            // Insert user activity
            $this->db->insert('user_activity_analytics', $data);
            
            return true;
        } catch (Exception $e) {
            error_log("Analytics tracking error (user): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track article view
     * 
     * @param int $userId User viewing the article
     * @param int $articleId Article being viewed
     * @return bool Success status
     */
    public function trackView($userId, $articleId) {
        return $this->trackUserAction($userId, $articleId, 'view');
    }
    
    /**
     * Track bookmark action
     * 
     * @param int $userId User adding/removing bookmark
     * @param int $articleId Article being bookmarked
     * @return bool Success status
     */
    public function trackBookmark($userId, $articleId) {
        return $this->trackUserAction($userId, $articleId, 'bookmark');
    }
    
    /**
     * Track article rating
     * 
     * @param int $userId User rating the article
     * @param int $articleId Article being rated
     * @param float $stars Star rating value
     * @return bool Success status
     */
    public function trackRating($userId, $articleId, $stars) {
        return $this->trackUserAction($userId, $articleId, 'rating', $stars);
    }
    
    /**
     * Track usefulness rating
     * 
     * @param int $userId User rating usefulness
     * @param int $articleId Article being rated
     * @param int $wasHelpful 1 for helpful, 0 for not helpful
     * @return bool Success status
     */
    public function trackUsefulnessRating($userId, $articleId, $wasHelpful) {
        return $this->trackUserAction($userId, $articleId, 'usefulness_rating', $wasHelpful);
    }
    
    /**
     * Track article edit
     * 
     * @param int $userId User editing the article
     * @param int $articleId Article being edited
     * @return bool Success status
     */
    public function trackEdit($userId, $articleId) {
        return $this->trackAdminAction($userId, $articleId, 'edit');
    }
    
    /**
     * Track article approval
     * 
     * @param int $userId User approving the article
     * @param int $articleId Article being approved
     * @return bool Success status
     */
    public function trackApprove($userId, $articleId) {
        return $this->trackAdminAction($userId, $articleId, 'approve');
    }
    
    /**
     * Track article publish
     * 
     * @param int $userId User publishing the article
     * @param int $articleId Article being published
     * @return bool Success status
     */
    public function trackPublish($userId, $articleId) {
        return $this->trackAdminAction($userId, $articleId, 'publish');
    }
}

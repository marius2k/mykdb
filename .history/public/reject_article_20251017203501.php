<?php

// Include fișierele necesare pentru conexiunea la baza de date
require_once '../config/bootstrap.php'; 

/**
 * Track admin activity for analytics
 */
function trackAdminActivity($articleId, $actionType, $userId = null) {
    if ($userId === null && isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
    }
    
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $db = new Database();
        $db->insert('admin_activity_analytics', [
            'user_id' => $userId,
            'article_id' => $articleId,
            'session_id' => $sessionId,
            'action_type' => $actionType,
            'ip_address' => $ipAddress,
            'action_date' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Error tracking admin activity: " . $e->getMessage());
    }
}

// Inițializează sesiunea
session_start();

// Verifică dacă ID-ul articolului a fost trimis
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $articleId = intval($_GET['id']);

    // Creează o instanță a clasei Database
    $db = new Database();

    try {
        // Pregătește interogarea SQL pentru a actualiza statusul articolului
        $sql = "UPDATE articles SET status = 'draft' WHERE id = :id";
        $stmt = $db->getPdo()->prepare($sql);

        // Leagă parametrul și execută interogarea
        $stmt->bindParam(':id', $articleId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Respingerea a avut succes
            $_SESSION['message'] = 'Articolul a fost respins și trimis la editare.';
            
            // Track admin activity for analytics
            if (isset($_SESSION['user']['id'])) {
                trackAdminActivity($articleId, 'reject', $_SESSION['user']['id']);
            }
        } else {
            // A apărut o eroare la respingerea articolului
            $_SESSION['message'] = 'Eroare la respingerea articolului. Te rugăm să încerci din nou.';
        }
    } catch (PDOException $e) {
        // Capturarea erorilor de bază de date
        $_SESSION['message'] = 'Eroare de bază de date: ' . htmlspecialchars($e->getMessage());
    }
} else {
    // ID-ul articolului nu este valid
    $_SESSION['message'] = 'ID-ul articolului nu este valid.';
}

// Redirecționează utilizatorul înapoi la pagina anterioară
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>
<?php

require_once '../../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userok = auth_user($username, $password);

    if ($userok && $userok['status'] == 'active') {
        $userId = $userok['id'];
        $db = new Database();
        $sql = "SELECT u.*, r.name AS role_name, r.label AS role_label, r.id AS role_id
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
        $user = $db->fetchSingle($sql, [$userId]);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'role_id' => $user['role_id'],
            'role_label' => $user['role_label'],
            'profile_picture' => $user['profile_picture'],
            'status' => $user['status']
        ];
        $userSettings = new UserSettings($db);
        $_SESSION['settings'] = $userSettings->getAll($_SESSION['user']['id']);
        
        // Set user as online
        $db->query("UPDATE users SET online = 1 WHERE id = ?", [$userId]);
        
        // Log the successful login
        logActivity($user['id'], 'login_success', 'User logged in ' . $username);

        // add points for login
        $todayLogin = $db->fetchSingle("SELECT id FROM points_history WHERE user_id = ? AND action = 'daily_login' AND DATE(created_at) = CURDATE()",[$userId]);   
        if (!$todayLogin) {
            awardDailyLogin($userId);
        }



        echo json_encode(['success' => true]);
        exit;
    } elseif ($userok && ($userok['status'] == 'disabled' || $userok['status'] == 'pending')) {
        logActivity($userok['id'], 'login_failed', 'Login attempt for a disabled or inactive user: ' . $username);
        echo json_encode(['success' => false, 'error' => 'Contul tău este dezactivat sau în așteptare de aprobare.']);
        exit;
    } else {
        logActivity(null, 'login_failed', 'Failed login attempt for username: ' . $username);
        echo json_encode(['success' => false, 'error' => 'Date incorecte.']);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'Cerere invalidă.']);
?>
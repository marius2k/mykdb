<?php
require_once '../../config/bootstrap.php';
require_once '../../includes/gamification_helpers.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$ops = ['modify_own_data'];

if (!hasPermission($_SESSION['user']['id'], $ops)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

$db = new Database();
$userId = $_SESSION['user']['id'];

// Get current user data
$user = $db->fetchSingle("
    SELECT u.*, r.name AS role_name, r.label AS role_label
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?", [$userId]
);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch ($action) {
    case 'get_profile':
        // Return current profile data
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role_name' => $user['role_name'],
                'role_label' => $user['role_label'],
                'profile_picture' => $user['profile_picture'],
                'status' => $user['status']
            ]
        ]);
        break;

    case 'update_profile_picture':
        // Handle profile picture upload
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
            exit;
        }

        $file = $_FILES['profile_picture'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tip fișier invalid. Acceptăm doar jpg, png, gif.']);
            exit;
        }

        $filename = 'user_' . $userId . '_' . uniqid() . '.' . $ext;
        $dest = __DIR__ . '/../../uploads/profile_pics/' . $filename;

        if (!is_dir(__DIR__ . '/../../uploads/profile_pics')) {
            mkdir(__DIR__ . '/../../uploads/profile_pics', 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
            exit;
        }

        // Get form data
        $firstName = trim($_POST['first_name'] ?? $user['first_name']);
        $lastName = trim($_POST['last_name'] ?? $user['last_name']);
        $email = trim($_POST['email'] ?? $user['email']);

        // Update database
        $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_picture = ? WHERE id = ?", [
            $firstName, $lastName, $email, $filename, $userId
        ]);

        // Check and award profile completion
        checkAndAwardProfileCompletion($userId);

        // Update session
        $_SESSION['user'] = [
            'id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $user['username'],
            'email' => $email,
            'role' => $user['role_name'],
            'role_label' => $user['role_label'],
            'profile_picture' => $filename,
            'status' => $user['status']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Poza actualizată cu succes!',
            'profile_picture' => $filename
        ]);
        break;

    case 'update_profile':
        // Handle profile info update (without picture)
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        $picture = $user['profile_picture'];

        $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?", [
            $firstName, $lastName, $email, $userId
        ]);

        // Check and award profile completion
        checkAndAwardProfileCompletion($userId);

        // Update session
        $_SESSION['user'] = [
            'id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $user['username'],
            'email' => $email,
            'role' => $user['role_name'],
            'role_label' => $user['role_label'],
            'profile_picture' => $picture,
            'status' => $user['status']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Profil actualizat cu succes.'
        ]);
        break;

    case 'change_password':
        // Handle password change
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All password fields are required']);
            exit;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parola actuală este incorectă.']);
            exit;
        }

        // Check if passwords match
        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Noua parolă și confirmarea nu se potrivesc.']);
            exit;
        }

        // Check password length
        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parola trebuie să aibă cel puțin 6 caractere.']);
            exit;
        }

        // Update password
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Parola a fost actualizată cu succes.'
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

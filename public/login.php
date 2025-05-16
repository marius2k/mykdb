<?php

//loadConfig();

require_once '../config/bootstrap.php';
//require_once APP_ROOT.'config/config.php';
//require_once APP_ROOT.'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$error = '';


//echo "App ROOT" . APP_ROOT;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    //echo "user authenti/cation started...<br>";
    $userok = authUser($username, $password);

    //echo "user authentication ended...";
    
    //$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    //$stmt->execute([$username]);
    //$user = $stmt->fetch();

    if (($userok) && $userok['status'] == 'active'){
        
        //Authentificare reușită
        $userId = $userok['id'];
        //echo "UID: ".$userId;

        $db = new Database();

        $sql = "SELECT u.*, r.name AS role_name, r.label AS role_label, r.id AS role_id
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
        
        //echo "SQL: ". $sql;

        $user = $db->fetchSingle($sql, [$userId]);
        
        
        
        $_SESSION['user'] = [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role_name'],     // ex: 'admin'
            'role_id' => $user['role_id'],
            'role_label' => $user['role_label'], // ex: 'Administrator'
            'profile_picture' => $user['profile_picture'],
            'status' => $user['status']
        ];

        //echo "User ID: ". $_SESSION['user']['id'];
        header('Location: index.php');
        logActivity($user['id'], 'login_success', 'User logged in'.$username);

        $db = new Database();
        
        // Load user settings
        $userSettings = new UserSettings($db);
        $_SESSION['settings'] = $userSettings->getAll($_SESSION['user']['id']);

        //echo "User Settings: ". $_SESSION['settings']['language'];

        exit;

    }elseif($user['status'] == 'disabled' || $user['status'] == 'pending'){
            $error = 'Contul tău este dezactivat sau în așteptare de aprobare.';
            logActivity($user['id'], 'login_failed','Login attempt for a disabled or inactive user: ' . $username);
            //exit;
            //$_SESSION['user']['role']=$user['role'];            
        
    } else {
        $error = 'Date incorecte.';
        logActivity($_SESSION['user']['id'] ?? null, 'login_failed', 'Failed login attempt for username: ' . $username);
        //exit;
    }
}





?>

<?php include APP_ROOT . 'includes/header.php'; ?>


<!-- search results -->
<div id="searchResults" style="margin-top:10px;"></div>

<div id="defaultContent">


    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
                            </div>
                                
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                                
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>   
                </div> 
            </div>
        </div>           

    </div>
</div>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>



<?php include APP_ROOT . 'includes/footer.php'; ?>


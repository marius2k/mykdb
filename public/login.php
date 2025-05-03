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
    $user = authUser($username, $password);

    //echo "user authentication ended...";
    
    //$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    //$stmt->execute([$username]);
    //$user = $stmt->fetch();

    if (($user) && $user['status'] == 'active'){
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }elseif($user['status'] == 'pending'){
            $error = 'Contul tău este în așteptare de aprobare.';
            //exit;
            //$_SESSION['user']['role']=$user['role'];            
        
    } else {
        $error = 'Date incorecte.';
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


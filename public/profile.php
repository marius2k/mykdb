<?php
require_once '../config/bootstrap.php'; 

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$ops=['modify_own_user'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}


//echo "USER ID: " . $_SESSION['user']['id'] . "<br>";

$db = new Database();
$userId = $_SESSION['user']['id'];
$user = $db->fetchSingle("
        SELECT u.*, r.name AS role_name, r.label AS role_label
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?", [$userId]
    );

$role = $user['role_name'];
$picture = $user['profile_picture'] ?? null;

//echo "Role: " . $role . "<br>";
echo "Picture name: " . $_FILES['profile_picture']['name'] ?? "no picture selected <br>";


// schimbare poza de profil

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']['name'])) {

    $file = $_FILES['profile_picture'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext =  strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            $filename = 'user_' . $userId . '_' . uniqid() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/profile_pics/' . $filename;

            if (!is_dir(__DIR__ . '/../uploads/profile_pics')) {
                mkdir(__DIR__ . '/../uploads/profile_pics', 0777, true);
            }
            //echo "Destinatia: " . $dest . "<br>";
            //echo "Numele fișierului: " . $filename . "<br>";


            move_uploaded_file($file['tmp_name'], $dest);

            // update user table
            $firstName = trim($_POST['first_name']);
            $lastName  = trim($_POST['last_name']);
            $email     = trim($_POST['email']);
            //$role     = trim($_POST['role'] ?? 'user');
            //$role=$_SESSION['user']['role'];
            //$picture   = trim($_POST['profile_picture'] ?? null);

            $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_picture = ? WHERE id = ?", [
            $firstName, $lastName, $email, $filename, $userId]);

            $_SESSION['flash'] = "Poza actualizata cu succes!!!";
            

            $_SESSION['user'] = [
                'id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $user['username'],
                'email' => $email,
                'role' => $user['role_name'],     // ex: 'admin'
                'role_label' => $user['role_label'], // ex: 'Administrator'
                'profile_picture' => $filename,
                'status' => $user['status']
            ];
            
            //$_SESSION['user']['first_name'] = $firstName;
            //$_SESSION['user']['last_name'] = $lastName;
            //$_SESSION['user']['email'] = $email;
            //$_SESSION['user']['role'] = $role;
            //$_SESSION['user']['profile_picture'] = $filename;

            header('Location: profile.php');
            exit;
        } else {
            $_SESSION['flash'] = "Tip fișier invalid. Acceptăm doar jpg, png, gif.";
            header('Location: profile.php');
            exit;
        }
    }
}




// schimbare date profil, fara poza
if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $email     = trim($_POST['email']);
    //$role     = trim($_POST['role'] ?? 'user');
    //$profilePicture   = trim($_POST['profile_picture'] ?? null);
    //$profilePicture = $picture;

    $db->query("UPDATE users SET first_name = ?, last_name = ?, profile_picture = ?, email = ? WHERE id = ?", [
        $firstName, $lastName, $picture, $email, $userId
    ]);

    $_SESSION['flash'] = "Profil actualizat cu succes.";
    //$_SESSION['user']['first_name'] = $firstName;
    //$_SESSION['user']['last_name'] = $lastName;
    //$_SESSION['user']['email'] = $email;
    //$_SESSION['user']['role'] = $role;
    //$_SESSION['user']['profile_picture'] = $picture;

    $_SESSION['user'] = [
        'id' => $userId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'username' => $user['username'],
        'email' => $email,
        'role' => $user['role_name'],     // ex: 'admin'
        'role_label' => $user['role_label'], // ex: 'Administrator'
        'profile_picture' => $picture,
        'status' => $user['status']
    ];


    header('Location: profile.php');
    exit;
}

// Procesare schimbare parolă
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Preia parola actuală
    $row = $db->fetchSingle("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!password_verify($currentPassword, $row['password'])) {
        $_SESSION['flash'] = "Parola actuală este incorectă.";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['flash'] = "Noua parolă și confirmarea nu se potrivesc.";
    } elseif (strlen($newPassword) < 6) {
        $_SESSION['flash'] = "Parola trebuie să aibă cel puțin 6 caractere.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId]);
        $_SESSION['flash'] = "Parola a fost actualizată cu succes.";
    }

    //$_SESSION['user']['first_name'] = $firstName;
    //$_SESSION['user']['last_name'] = $lastName;
    //$_SESSION['user']['email'] = $email;
    $_SESSION['user']['role'] = $role;

    header("Location: profile.php");
    exit;
}

//$_SESSION['user']['first_name'] = $firstName;
//$_SESSION['user']['last_name'] = $lastName;
//$_SESSION['user']['email'] = $email;
//$_SESSION['user']['role'] = $role;





?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="container mt-4" style="max-width: 600px;">
        <div class="form-styled"> 
            <div class="form-styled-title"><?=lang_prof_msg_top_info?></div>
            
            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="alert alert-success"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
            <?php endif; ?>
        
            <div class="form-styled-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><?=lang_prof_fname?></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label><?=lang_prof_lname?></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label><?=lang_prof_email?></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                    </div>
                    <div class="form-group-1">
                        
                        <div><?=lang_prof_photo?>
                        <?php
                                       if (isset($_SESSION['user']['profile_picture']) && $_SESSION['user']['profile_picture'] != '') {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/' . $_SESSION['user']['profile_picture'];
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        } else {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/default-profile.png';
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        }
                        ?>
                        </div>
                        <div><?=lang_prof_photo_change?>
                            <input type="file" name="profile_picture" accept="image/*" class="form-control mb-2">
                        </div>
                    </div>
                    <div align="right">
                        <button type="submit" name="update_profile" class="btn btn-primary"><?=lang_prof_btn_save?></button>
                    </div>
                </form>
            </div>  
        </div>


<!--

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Poza de profil</label><br>
                <input type="file" name="profile_picture" accept="image/*">
            </div>
            <button type="submit" name="upload_picture" class="btn btn-secondary">Încarcă imagine</button>
        </form>
-->

    <hr>


    <div class="form-styled">

        <div class="form-styled-title"><?=lang_prof_msg_top_pass?></div>
        <div class="form-styled-body">

                <form method="post">
                    <div class="form-group password-toggle-group">
                        <label for="current_password"><?=lang_prof_pass_crt?></label>
                        <div class="password-wrapper">    
                            <input type="password" name="current_password" id="current_password" required>
                            <button type="button" id="btn-current-password" class="toggle-password" onclick="togglePasswordVisibility('current_password','btn-current-password')">👁️</button>
                        </div>
                    </div>
                    <div class="form-group password-toggle-group">
                        <label for="new_password"><?=lang_prof_pass_new?></label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" id="new_password" required>
                            <button type="button" id="btn-new-password" class="toggle-password" onclick="togglePasswordVisibility('new_password','btn-new-password')">👁️</button>
                        </div>  
                    </div>
                    <div class="form-group password-toggle-group">
                        <div><label for="confirm_password"><?=lang_prof_pass_confirm?></label></div>
                        <div class="password-wrapper">    
                            <input type="password" name="confirm_password" class="confirm_password" id="confirm_password" required>
                            <button type="button" id="btn-confirm-password" class="toggle-password" onclick="togglePasswordVisibility('confirm_password','btn-confirm-password')">👁️</button>
                            <small id="password-match-msg" style="color: red; display: none;"><?=lang_prof_pass_nomatch?></small>
                        </div>
                    </div>

                    <div align="right">
                        <button type="submit" id="change_password" name="change_password" class="btn btn-primary"><?=lang_prof_btn_pass?></button>
                    </div>
                </form>
        </div>
    </div>
<script>


const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');
const submitBtn = document.querySelector('button[id="change_password"]');

function validatePasswords() {
    if (confirmPassword.value.length === 0) {
        message.style.display = "none";
        submitBtn.disabled = false;
        return;
    }

    if (newPassword.value !== confirmPassword.value) {
        message.style.display = "block";
        message.textContent = "<?=lang_reg_pass_nok?>";
        message.style.color = "red";
        submitBtn.disabled = true;
    } else {
        message.style.display = "block";
        message.textContent = "<?=lang_reg_pass_ok?> ✔️";
        message.style.color = "green";
        submitBtn.disabled = false;
    }
}

newPassword.addEventListener('input', validatePasswords);
confirmPassword.addEventListener('input', validatePasswords);
</script>


<?php include APP_ROOT . 'includes/footer.php'; ?>
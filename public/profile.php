<?php
require_once '../config/bootstrap.php'; 
require_once '../includes/gamification_helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$ops=['modify_own_data'];

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
//echo "Picture name: " . $_FILES['profile_picture']['name'] ?? "no picture selected <br>";


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

            // Verifică și acordă puncte pentru profilul complet
            checkAndAwardProfileCompletion($userId);

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

    // Verifică și acordă puncte pentru profilul complet
    checkAndAwardProfileCompletion($userId);

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

// ===== GAMIFICATION DATA =====
// Get current user's gamification stats
$gamification = new Gamification($db);
$userPointsData = $gamification->getUserPoints($userId);
$currentLevel = $userPointsData['level'] ?? 'Rookie';
$currentPoints = $userPointsData['total_points'] ?? 0;

// Get user's earned badges using Gamification class - CORRECTED
$userBadgesData = $gamification->getUserBadges($userId);
$userEarnedBadges = [];

// Extract only badge names from the database results
foreach ($userBadgesData as $badgeData) {
    if (is_array($badgeData) && isset($badgeData['name'])) {
        $userEarnedBadges[] = $badgeData['name'];
    }
}

// Get all available badges with details
$allBadges = $db->query("SELECT * FROM badges ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get user activity stats for progress calculation
$stats = $db->query("
    SELECT 
        COUNT(CASE WHEN action = 'article_published' THEN 1 END) as articles_created,
        COUNT(CASE WHEN action = 'comment_added' THEN 1 END) as comments_created,
        COUNT(CASE WHEN action = 'article_read' THEN 1 END) as articles_read,
        COUNT(CASE WHEN action = 'daily_login' THEN 1 END) as daily_logins
    FROM points_history 
    WHERE user_id = ?
", [$userId])->fetch(PDO::FETCH_ASSOC);


// ===== END GAMIFICATION DATA =====
?>

<?php include APP_ROOT . 'includes/header.php'; ?>

<div class="container mt-4 profile-container" >
    
    <!-- Flash Messages (shown at top for both columns) -->
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Two Column Layout -->
    <div class="row">
        
        <!-- Left Column: Gaming Progress (60% width) -->
        <div class="col-lg-7 mb-4">
            <div class="gaming-column">
                <div class="custom-box-1">
                    <div class="corner-label-1">🏆 My Gaming Progress</div>
                    <div class="box-content-1" style="padding: 30px;">
                        <!-- User Stats Summary -->
                        <div class="row mb-3">
                            <div class="col-md-4 text-center">
                                <div class="stat-card">
                                    <h3 class="text-primary"><?= $currentLevel ?></h3>
                                    <p class="mb-0">Current Level</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="stat-card">
                                    <h3 class="text-success"><?= number_format($currentPoints) ?></h3>
                                    <p class="mb-0">Total Points</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="stat-card">
                                    <h3 class="text-warning"><?= count($userEarnedBadges) ?></h3>
                                    <p class="mb-0">Badges Earned</p>
                                </div>
                            </div>
                        </div>

                        <!-- Earned Badges Section -->
                        <div class="mb-3">
                            <h5 class="mb-2">🎖️ Earned Badges</h5>
                            <div class="row">
                                <?php $foundMatches = 0; ?>
                                <?php foreach ($allBadges as $badge): ?>
                                    <?php 
                                    $isEarned = in_array($badge['name'], $userEarnedBadges);
                                    
                                    if ($isEarned) {
                                        $foundMatches++;
                                    }
                                    ?>
                                    <?php if ($isEarned): ?>
                                        <!-- Earned Badge card with rectangular design - 2 per row -->
                                        <div class="col-md-6 mb-2">
                                            <div class="progress-badge-card earned-badge">
                                                <div class="d-flex align-items-center mb-1">
                                                    <div class="badge-icon-medium earned">
                                                        <i class="fas fa-medal text-warning"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-2">
                                                        <h6 class="badge-title earned"><?= htmlspecialchars($badge['name']) ?></h6>
                                                        <small class="badge-description"><?= htmlspecialchars($badge['description']) ?></small>
                                                    </div>
                                                    <div class="earned-indicator-small">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    </div>
                                                </div>
                                                
                                                <div class="progress mb-1">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: 100%;" 
                                                         aria-valuenow="100" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-success">
                                                        <strong><i class="fas fa-trophy"></i> Completed!</strong>
                                                    </small>
                                                    <small class="text-success">
                                                        <strong>100%</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($foundMatches === 0): ?>
                                    <div class="col-12 text-center">
                                        <p class="text-muted" style="font-size: 0.85rem;">No badges earned yet. Keep participating to earn your first badge!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Progress Tracking for Unearned Badges -->
                        <div class="mb-3">
                            <h5 class="mb-2">📈 Badge Progress</h5>
                            <div class="row">
                                <?php foreach ($allBadges as $badge): ?>
                                    <?php if (!in_array($badge['name'], $userEarnedBadges)): ?>
                                        <?php 
                                        $requirements = json_decode($badge['conditions'], true);
                                        $progress = 0;
                                        $current = 0;
                                        $requirement = 1;
                                        
                                        if (isset($requirements['articles_published'])) {
                                            $requirement = $requirements['articles_published'];
                                            $current = $stats['articles_created'];
                                        } elseif (isset($requirements['comments_made'])) {
                                            $requirement = $requirements['comments_made'];
                                            $current = $stats['comments_created'];
                                        } elseif (isset($requirements['articles_read'])) {
                                            $requirement = $requirements['articles_read'];
                                            $current = $stats['articles_read'];
                                        }
                                        
                                        $progress = min(100, ($current / $requirement) * 100);
                                        $progressClass = $progress >= 75 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-info');
                                        ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="progress-badge-card">
                                                <div class="d-flex align-items-center mb-1">
                                                    <div class="badge-icon-medium">
                                                        <i class="fas fa-medal text-muted"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-2">
                                                        <h6 class="badge-title"><?= htmlspecialchars($badge['name']) ?></h6>
                                                        <small class="badge-description"><?= htmlspecialchars($badge['description']) ?></small>
                                                    </div>
                                                </div>
                                                
                                                <div class="progress mb-1">
                                                    <div class="progress-bar <?= $progressClass ?>" 
                                                         role="progressbar" 
                                                         style="width: <?= $progress ?>%;" 
                                                         aria-valuenow="<?= $progress ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <strong><?= $current ?></strong> / <?= $requirement ?>
                                                    </small>
                                                    <small class="text-primary">
                                                        <?= number_format($progress, 1) ?>%
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Profile Forms (40% width) -->
        <div class="col-lg-5">
            <div class="forms-column">
                
                <!-- Profile Information Form -->
                <div class="custom-box-1 mb-4"> 
                    <div class="corner-label-1">👤 <?= lang('lang_prof_msg_top_info') ?></div>
                    
                    <div class="box-content-1" style="padding: 30px;">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label><?= lang('lang_prof_fname') ?></label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>">
                            </div>

                            <div class="form-group">
                                <label><?= lang('lang_prof_lname') ?></label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label><?= lang('lang_prof_email') ?></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                            </div>
                            <div class="form-group-1">
                                
                                <div><?= lang('lang_prof_photo') ?>
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
                                <div><?= lang('lang_prof_photo_change') ?>
                                    <input type="file" name="profile_picture" accept="image/*" class="form-control mb-2">
                                </div>
                            </div>
                            <div align="right">
                                <button type="submit" name="update_profile" class="btn btn-primary"><?= lang('lang_prof_btn_save') ?></button>
                            </div>
                        </form>
                    </div>  
                </div>

                <!-- Change Password Form -->
                <div class="custom-box-1">
                    <div class="corner-label-1">🔒 <?= lang('lang_prof_msg_top_pass') ?></div>
                    <div class="box-content-1" style="padding: 30px;">
                        <form method="post">
                            <div class="form-group password-toggle-group">
                                <label for="current_password"><?= lang('lang_prof_pass_crt') ?></label>
                                <div class="password-wrapper">    
                                    <input type="password" name="current_password" id="current_password" required>
                                    <button type="button" id="btn-current-password" class="toggle-password" onclick="togglePasswordVisibility('current_password','btn-current-password')">👁️</button>
                                </div>
                            </div>
                            <div class="form-group password-toggle-group">
                                <label for="new_password"><?= lang('lang_prof_pass_new') ?></label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="new_password" required>
                                    <button type="button" id="btn-new-password" class="toggle-password" onclick="togglePasswordVisibility('new_password','btn-new-password')">👁️</button>
                                </div>  
                            </div>
                            <div class="form-group password-toggle-group">
                                <div><label for="confirm_password"><?= lang('lang_prof_pass_confirm') ?></label></div>
                                <div class="password-wrapper">    
                                    <input type="password" name="confirm_password" class="confirm_password" id="confirm_password" required>
                                    <button type="button" id="btn-confirm-password" class="toggle-password" onclick="togglePasswordVisibility('confirm_password','btn-confirm-password')">👁️</button>
                                    <small id="password-match-msg" style="color: red; display: none;"><?= lang('lang_prof_pass_nomatch') ?></small>
                                </div>
                            </div>

                            <div align="right">
                                <button type="submit" id="change_password" name="change_password" class="btn btn-primary"><?= lang('lang_prof_btn_pass') ?></button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

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
        message.textContent = "<?= lang('lang_reg_pass_nok') ?>";
        message.style.color = "red";
        submitBtn.disabled = true;
    } else {
        message.style.display = "block";
        message.textContent = "<?= lang('lang_reg_pass_ok') ?> ✔️";
        message.style.color = "green";
        submitBtn.disabled = false;
    }
}

newPassword.addEventListener('input', validatePasswords);
confirmPassword.addEventListener('input', validatePasswords);

document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//$currentPage = ""
//loadConfig();

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


// start treatment of g            <div class="settings-bar" style="padding-right: 10px; padding-top: 5px;">est user


if (!isset($_SESSION['user'])) {

    initGuestSession();
    
}


// end treatment of guest user


$db = new Database();

// Load gamification data for logged-in users
$userGamificationData = null;
if (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? 'guest') !== 'guest') {
    require_once APP_ROOT . 'classes/gamification.php';
    require_once APP_ROOT . 'includes/gamification_helpers.php';
    
    $gamification = new Gamification($db);
    $userId = $_SESSION['user']['id'];
    
    // Get user points and level
    $userPointsData = $gamification->getUserPoints($userId);
    $userPoints = $userPointsData['total_points'] ?? 0;
    $userLevel = $userPointsData['level'] ?? 'Rookie';
    
    // Get user badges
    $userBadges = $gamification->getUserBadges($userId);
    
    // Calculate progress to next level (simplified for now)
    $levelThresholds = [
        'Rookie' => 0,
        'Explorer' => 100,
        'Contributor' => 300,
        'Expert' => 700,
        'Master' => 1500,
        'Legend' => 3000
    ];
    
    $currentLevelThreshold = $levelThresholds[$userLevel] ?? 0;
    $nextLevelKey = array_search($userLevel, array_keys($levelThresholds)) + 1;
    $nextLevelExists = $nextLevelKey < count($levelThresholds);
    $pointsToNextLevel = 0;
    
    if ($nextLevelExists) {
        $nextLevelName = array_keys($levelThresholds)[$nextLevelKey];
        $nextLevelThreshold = $levelThresholds[$nextLevelName];
        $pointsToNextLevel = $nextLevelThreshold - $userPoints;
    }
    
    $userGamificationData = [
        'points' => $userPoints,
        'level' => $userLevel,
        'level_name' => $userLevel,
        'badges' => $userBadges,
        'points_to_next_level' => $pointsToNextLevel,
        'next_level_exists' => $nextLevelExists
    ];
}

// Load user settings

if (isset($_SESSION['user']) && !isset($_SESSION['settings'])) {
    

    //$_SESSION['user']['id']=2;
    
    if (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? 'guest') == 'guest') {
       

        // Set default settings for guest user
        if (!isset($_POST['theme'])){
            $_SESSION['user']['theme'] = 'light';
        } else {
            $_SESSION['user']['theme'] = $_POST['theme'];
        }

        if (!isset($_POST['lang'])){
            $_SESSION['user']['lang'] = 'en';
        } else {
            $_SESSION['user']['lang'] = $_POST['lang'];
        }
        
        $_SESSION['settings']['theme'] = $_SESSION['user']['theme'];
        $_SESSION['settings']['language'] = $_SESSION['user']['lang'];
        

    } else {

        $userSettings = new UserSettings($db);
        $_SESSION['settings'] = $userSettings->getAll($_SESSION['user']['id']);
        
    }

}

$theme = $_SESSION['settings']['theme'] ?? 'light';
$lang = $_SESSION['settings']['language'] ?? 'en';


//echo "header.php: Used ID" . $_SESSION['user']['id'] . " Theme: " . $theme . " Lang: " . $lang;



//echo "<body class='theme-$theme'>";
//echo "Settings->Theme: ".$theme;
//echo " Settings->Lang: ".$lang;

$langFile = APP_ROOT . "assets/lang/{$lang}.php";
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include APP_ROOT . "assets/lang/en.php";
}

//$userId = $_SESSION['user']['id'];
//$notifications = $db->fetchAll("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5", [$userId]);

$userId = $_SESSION['user']['id'] ?? null;
$notifCount = 0;

if ($userId) {
  $notifCount = $db->fetchSingle(
    "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0", 
    [$userId]
)['cnt'];
}

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL?>assets/css/style-<?=$theme?>.css?v=<?= time() ?>">
    <!-- Force breadcrumb alignment fix after all other CSS -->
    <style>
    .breadcrumb, ol.breadcrumb {
        text-align: left !important;
        justify-content: flex-start !important;
    }
    
    /* Dropdown z-index fix - ensure dropdowns appear above navigation */
    .dropdown-menu {
        z-index: 10001 !important;
        position: absolute !important;
    }
    
    .dropdown:hover .dropdown-menu,
    .dropdown.show .dropdown-menu {
        z-index: 10001 !important;
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.min.js"></script>

    

    <!-- jQuery (necesar pentru Summernote) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Summernote CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote/dist/summernote-lite.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/xml/xml.min.js"></script>

    <!-- CSS Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- JS Select2 + jQuery -->
    <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>


    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
</head>
<body>


<div class="app-header" style="background-image: url('<?= APP_URL ?>assets/images/banner-top.png');background-size: cover; background-position: center; background-repeat: no-repeat;height: 130px;">
 
<div style="width: 100%; display: flex; align-items: center; justify-content: space-between;">    

<!--
    <div style="background-image: url('<?=APP_URL?>assets/images/banner-top.png; background-size: cover; background-position: left; background-repeat: no-repeat; background-color: #f0f0f0; width: 100%; height: 400px; width: 100%; display: flex; align-items: center; justify-content: space-between; ">
-->
    <div class="nav-app-name"><img src="<?=APP_URL?>assets/images/kdb-logo-1.png" style="width: auto; height: 50px;"></div>
        <div style="float: right; padding: 5px;">

            <!-- User Dropdown -->
            <?php if (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? 'guest') !== 'guest') { ?>
                            
                            <!-- Notifications --> 
                            
                                <div class="notif-bell">
                                    <a href="<?=APP_URL?>public/dashboard.php">
                                        <img src="<?=APP_URL?>assets/icons/icon-bell.svg" alt="Notificări" width="24" height="auto">
                                        <?php if ($notifCount > 0): ?>
                                        <span id="notif-badge" class="notif-badge"><?= $notifCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                           
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="me-2 d-none d-md-block text-end">
                                    <div style="color: white; font-size: 15px;"><?= htmlspecialchars(($_SESSION['user']['first_name'] ?? '') . " " . ($_SESSION['user']['last_name'] ?? '')) ?: 'Guest' ?></div>
                                    <div style="color: gainsboro; font-size: 12px;"><?= ucfirst($_SESSION['user']['role_label'] ?? $_SESSION['user']['role'] ?? 'Guest') ?></div>
                                    
                                    <?php if ($userGamificationData): ?>
                                    <!-- Gamification info -->
                                    <div style="color: #ffd700; font-size: 11px; margin-top: 2px;">
                                        <i class="bi bi-star-fill"></i> <?= lang('lang_gamification_level') ?> <?= htmlspecialchars($userGamificationData['level']) ?> 
                                        <span style="color: #87ceeb;">| <?= $userGamificationData['points'] ?> <?= lang('lang_gamification_xp') ?></span>
                                    </div>
                                    
                                    <!-- Badges -->
                                    <?php if (!empty($userGamificationData['badges'])): ?>
                                    <div style="margin-top: 3px;">
                                        <?php 
                                        $displayedBadges = 0;
                                        $badgeIcons = [
                                            'first_article' => 'bi-file-earmark-text',
                                            'prolific_writer' => 'bi-pen',
                                            'conversationalist' => 'bi-chat-dots',
                                            'daily_visitor' => 'bi-calendar-check',
                                            'profile_complete' => 'bi-person-check',
                                            'active_reader' => 'bi-book',
                                            'helpful_member' => 'bi-hand-thumbs-up'
                                        ];
                                        
                                        foreach ($userGamificationData['badges'] as $badge): 
                                            if ($displayedBadges >= 3) break; // Show max 3 badges in header
                                            $iconClass = $badgeIcons[$badge['name']] ?? 'bi-award';
                                        ?>
                                        <span class="badge-mini <?= $badge['category'] ?? 'gold' ?>" style="display: inline-block; width: 18px; height: 18px; background: linear-gradient(45deg, #ffd700, #ffed4e); border-radius: 50%; margin-right: 3px; font-size: 9px; text-align: center; line-height: 18px; color: #333;" title="<?= htmlspecialchars($badge['name']) ?>">
                                            <i class="<?= $iconClass ?>"></i>
                                        </span>
                                        <?php 
                                            $displayedBadges++;
                                        endforeach; 
                                        ?>
                                        <?php if (count($userGamificationData['badges']) > 3): ?>
                                        <span style="color: #87ceeb; font-size: 10px;">+<?= count($userGamificationData['badges']) - 3 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php
                                       if (isset($_SESSION['user']['profile_picture']) && $_SESSION['user']['profile_picture'] != '') {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/' . $_SESSION['user']['profile_picture'];
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        } else {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/default-profile.png';
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        }
                                ?>


                            </a>
                            <ul class="dropdown-menu dropdown-menu-end avatar-menu" style="z-index: 10001 !important;">
                                    <?php
                                        //echo "header.php: User Role;" . $_SESSION['user']['role'];
                                        $aMenu = generateAvatarMenu($_SESSION['user']['id'] ?? 0);
                                        echo $aMenu;
                                    ?>
                            </ul>
            <?php } else { ?>


                                

                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                        <div class="me-2 d-none d-md-block text-end">
                                            <div style="color: white; font-size: 15px;"><?= htmlspecialchars(($_SESSION['user']['first_name'] ?? '') . " " . ($_SESSION['user']['last_name'] ?? '') ?: 'Guest') ?></div>
                                            <div style="color: gainsboro; font-size: 12px;"><?= ucfirst($_SESSION['user']['role_label'] ?? 'Guest') ?></div>
                                        </div>
                                <?php
                                       if (isset($_SESSION['user']['profile_picture']) && $_SESSION['user']['profile_picture'] != '') {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/' . $_SESSION['user']['profile_picture'];
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        } else {
                                            $profilePicture = APP_URL . 'uploads/profile_pics/default-profile.png';
                                            echo '<img src="'. htmlspecialchars($profilePicture) . '" class="avatar" alt="Avatar" width="60" height="60">';
                                        }
                                ?>


                                </a>
                                <ul class="dropdown-menu dropdown-menu-end avatar-menu" style="z-index: 10001 !important;">
                                    <?php
                                        //echo "header.php: User Role;" . $_SESSION['user']['role'];
                                        $aMenu = generateAvatarMenu($_SESSION['user']['id'] ?? 0);
                                        echo $aMenu;
                                    ?>
                                </ul>

            <?php } ?> 
           
        </div>
        
    </div>
    
</div>

        <div class="topnav">
            <div class="main-nav-container" style="float: left; width: 100%; position: relative; z-index: 10000;">
            <?php

                $userID = $_SESSION['user']['id'] ?? null;
                $navbar = generateNavBar2($userID); 
                echo $navbar;
            ?>
            </div>
        </div>   
        
        <script>
            // Function to toggle submenu display
            function toggleSubmenu(event, submenuClass) {
                event.stopPropagation();
                
                // Close all other submenus first
                const allSubmenus = document.querySelectorAll('.dropdown-submenu .dropdown-menu');
                allSubmenus.forEach(menu => {
                    if (!menu.classList.contains(submenuClass)) {
                        menu.style.display = 'none';
                    }
                });
                
                // Toggle the current submenu
                const currentSubmenu = document.querySelector('.' + submenuClass);
                if (currentSubmenu) {
                    if (currentSubmenu.style.display === 'block') {
                        currentSubmenu.style.display = 'none';
                    } else {
                        currentSubmenu.style.display = 'block';
                    }
                }
            }
            
            // Add document click handler to close submenus when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideSubmenu = event.target.closest('.dropdown-submenu');
                if (!isClickInsideSubmenu) {
                    const allSubmenus = document.querySelectorAll('.dropdown-submenu .dropdown-menu');
                    allSubmenus.forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });
        </script>
        


<main style="padding: 0 20px 20px 20px;">


<?php if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? 'guest') === 'guest') : ?>
        <!-- Overlay pentru fundal -->
        <div id="modalOverlayRegister" style="display:none;"></div>

        <!-- Modalul de înregistrare  -->

        <div id="registerModal" class="modal-register" style="display:none;">
        <form id="registerForm" method="POST" autocomplete="off" >
            <div id="register-errors" style="display:none; color: red; margin-bottom: 10px;"></div>
            <div class="modal-header-register" >
                <span class="modal-title-register"><?= lang('lang_reg_msg_top') ?></span>
                <span class="modal-close-register" id="closeRegisterModal">&times;</span>
            </div>
            <div class="modal-content-register" >
            <div class="modal-body-register" >
                <div class="modal-row-register">
                <label class="modal-label-register" for="first_name"><?= lang('lang_reg_fname') ?></label>
                <input class="modal-input-register" type="text" name="first_name" id="first_name" required>
                </div>
                <div class="modal-row-register">
                <label class="modal-label-register" for="last_name"><?= lang('lang_reg_lname') ?></label>
                <input class="modal-input-register" type="text" name="last_name" id="last_name" required>
                </div>
                <div class="modal-row-register">
                <label class="modal-label-register" for="username"><?= lang('lang_reg_username') ?></label>
                <input class="modal-input-register" type="text" name="username" id="username" required>
                </div>
                <div class="modal-row-register">
                <label class="modal-label-register" for="password"><?= lang('lang_reg_pass') ?></label>
                <div class="password-wrapper">
                    <input class="modal-input-register" type="password" name="password" id="password" required>
                    <button type="button" id="btn-password" class="toggle-password" onclick="togglePasswordVisibility('password','btn-password')">👁️</button>
                </div>
                </div>
                <div class="modal-row-register" >
                
                    <label class="modal-label-register" for="confirm_password"><?= lang('lang_reg_pass_confirm') ?></label>
                    <input class="modal-input-register" type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <small id="password-match-msg" style="color: red; display: none; margin-left: 150px; margin-top: 4px;">
                    <?= lang('lang_reg_pass_nomatch') ?>
                </small>
                
            </div>
            <div class="modal-footer-register" >
                <button class="modal-btn-register cancel" type="button" id="cancelRegisterModal"><?= lang('lang_btn_cancel') ?></button>
                <button type="submit" class="modal-btn-register primary"><?= lang('lang_reg_btn_create') ?></button>
            </div>
            </div>
        </form>
        </div>

        <script>

        document.addEventListener('DOMContentLoaded', function() {
                // Modal logic
                const modal = document.getElementById('registerModal');
                
                const closeBtn = document.getElementById('closeRegisterModal');
                const cancelBtn = document.getElementById('cancelRegisterModal');


                const openBtns = document.querySelectorAll('.openRegisterModal');
                openBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = "block";
                    overlay.style.display = "block";
                    });
                });


                function resetRegisterForm() {
                    document.getElementById('registerForm').reset();
                    const message = document.getElementById('password-match-msg');
                    if (message) {
                        message.style.display = "none";
                        message.textContent = "";
                    }
                    const errorsDiv = document.getElementById('register-errors');
                    if (errorsDiv) {
                        errorsDiv.style.display = "none";
                        errorsDiv.innerHTML = "";
                    }
                }



                // Password validation
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                const overlay = document.getElementById('modalOverlayRegister');
                const message = document.getElementById('password-match-msg');
                const submitBtn = document.querySelector('#registerForm button[type="submit"]');

                function validatePasswords() {
                    if (confirmPassword.value.length === 0) {
                        message.style.display = "none";
                        submitBtn.disabled = false;
                        return;
                    }
                    if (password.value !== confirmPassword.value) {
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
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);





                // AJAX submit
                document.getElementById('registerForm').onsubmit = async function(e) {
                    e.preventDefault();
                    const form = e.target;
                    const data = new FormData(form);
                    const errorsDiv = document.getElementById('register-errors');
                    errorsDiv.style.display = "none";
                    errorsDiv.innerHTML = "";

                    const response = await fetch('<?=APP_URL?>public/api/bkd_register.php', {
                        method: 'POST',
                        body: data
                    });
                    const result = await response.json();
                    if (result.success) {
                        modal.style.display = "none";
                        alert("Account sent to admin for approval! You can log in once approved.");
                        window.location.href = "index.php";
                    } else {
                        errorsDiv.style.display = "block";
                        errorsDiv.innerHTML = result.errors.map(e => `<p>${e}</p>`).join('');
                    }
                };

                function closeRegisterModal() {
                    //resetRegisterForm();
                    modal.style.display = "none";
                    overlay.style.display = "none";
                    
                }




                window.onclick = (event) => { if (event.target == modal) modal.style.display = "none"; };

                // X-ul de sus
                if(closeBtn) closeBtn.onclick = closeRegisterModal;

                if(cancelBtn) cancelBtn.onclick = closeRegisterModal

                // Overlay click
                window.onclick = (event) => {
                    if (event.target == overlay) {
                        closeRegisterModal();
                    }
                };


                if(openBtns) openBtns.onclick = () => {
                    resetRegisterForm();
                    modal.style.display = "block";
                    overlay.style.display = "block";
                };

                if(closeBtn) closeBtn.onclick = () => {
                    resetRegisterForm
                    modal.style.display = "none";
                    overlay.style.display = "none";
                };


                // toggle notification visibility

                function toggleNotifications() {
                const dropdown = document.getElementById('notif-dropdown');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                }
            });
        </script>

<?php endif; //end if ?>
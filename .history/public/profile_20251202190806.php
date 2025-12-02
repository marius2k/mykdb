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

// ===== GAMIFICATION DATA =====
$gamification = new Gamification($db);
$userPointsData = $gamification->getUserPoints($userId);
$currentLevel = $userPointsData['level'] ?? 'Rookie';
$currentPoints = $userPointsData['total_points'] ?? 0;

// Get user's earned badges
$userBadgesData = $gamification->getUserBadges($userId);
$userEarnedBadges = [];

foreach ($userBadgesData as $badgeData) {
    if (is_array($badgeData) && isset($badgeData['name'])) {
        $userEarnedBadges[] = $badgeData['name'];
    }
}

// Get all available badges
$allBadges = $db->query("SELECT * FROM badges ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get user activity stats
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

<script>
// Make APP_URL available to JavaScript
window.APP_URL = '<?= APP_URL ?>';
</script>

<!-- Component CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/components/Button.css">

<!-- Custom styles to match Gaming Progress section font sizes -->
<style>
    /* Match labels and inputs to Gaming Progress section */
    #profile-form .col-form-label,
    #password-form .col-form-label {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    #profile-form .form-control,
    #password-form .form-control {
        font-size: 0.9rem;
    }
    
    #profile-form small,
    #password-form small {
        font-size: 0.75rem;
    }
</style>

<!-- React CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

<!-- Button Component (Compiled from JSX) -->
<script src="<?= APP_URL ?>assets/js/react-components-dist/Button.js"></script>

<div class="container mt-4 profile-container" >
    
    <!-- Flash Messages -->
    <div id="flash-message" style="display: none;">
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert" id="flash-alert"></div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="row">
        
        <!-- Left Column: Gaming Progress (60% width) -->
        <div class="col-lg-6 mb-4">
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
                                    if ($isEarned) $foundMatches++;
                                    ?>
                                    <?php if ($isEarned): ?>
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
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-success"><strong><i class="fas fa-trophy"></i> Completed!</strong></small>
                                                    <small class="text-success"><strong>100%</strong></small>
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
                                                    <div class="progress-bar <?= $progressClass ?>" role="progressbar" style="width: <?= $progress ?>%;"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted"><strong><?= $current ?></strong> / <?= $requirement ?></small>
                                                    <small class="text-primary"><?= number_format($progress, 1) ?>%</small>
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
                        <form id="profile-form" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <label class="col-sm-6 col-form-label"><?= lang('lang_prof_fname') ?></label>
                                <div class="col-sm-6">
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-6 col-form-label"><?= lang('lang_prof_lname') ?></label>
                                <div class="col-sm-6">
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <label class="col-sm-6 col-form-label"><?= lang('lang_prof_email') ?></label>
                                <div class="col-sm-6">
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <label class="col-sm-6 col-form-label"><?= lang('lang_prof_photo') ?></label>
                                <div class="col-sm-6">
                                    <img id="profile-pic-display" 
                                         src="<?= !empty($_SESSION['user']['profile_picture']) 
                                             ? APP_URL . 'uploads/profile_pics/' . htmlspecialchars($_SESSION['user']['profile_picture']) 
                                             : APP_URL . 'uploads/profile_pics/default-profile.png' ?>" 
                                         class="avatar" alt="Avatar" width="60" height="60" style="border-radius: 50%;">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <label class="col-sm-6 col-form-label"><?= lang('lang_prof_photo_change') ?></label>
                                <div class="col-sm-6">
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-12 text-end">
                                    <div id="update-profile-button"></div>
                                </div>
                            </div>
                        </form>
                    </div>  
                </div>

                <!-- Change Password Form -->
                <div class="custom-box-1">
                    <div class="corner-label-1">🔒 <?= lang('lang_prof_msg_top_pass') ?></div>
                    <div class="box-content-1" style="padding: 30px;">
                        <form id="password-form">
                            <div class="row mb-3 password-toggle-group">
                                <label for="current_password" class="col-sm-6 col-form-label"><?= lang('lang_prof_pass_crt') ?></label>
                                <div class="col-sm-6">
                                    <div class="password-wrapper">    
                                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                                        <button type="button" id="btn-current-password" class="toggle-password" onclick="togglePasswordVisibility('current_password','btn-current-password')">👁️</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3 password-toggle-group">
                                <label for="new_password" class="col-sm-6 col-form-label"><?= lang('lang_prof_pass_new') ?></label>
                                <div class="col-sm-6">
                                    <div class="password-wrapper">
                                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                                        <button type="button" id="btn-new-password" class="toggle-password" onclick="togglePasswordVisibility('new_password','btn-new-password')">👁️</button>
                                    </div>
                                </div>  
                            </div>
                            
                            <div class="row mb-3 password-toggle-group">
                                <label for="confirm_password" class="col-sm-6 col-form-label"><?= lang('lang_prof_pass_confirm') ?></label>
                                <div class="col-sm-6">
                                    <div class="password-wrapper">    
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                        <button type="button" id="btn-confirm-password" class="toggle-password" onclick="togglePasswordVisibility('confirm_password','btn-confirm-password')">👁️</button>
                                    </div>
                                    <small id="password-match-msg" style="display: none; margin-top: 5px; font-weight: 600;"><?= lang('lang_prof_pass_nomatch') ?></small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12 text-end">
                                    <div id="change-password-button"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
// Flash message helper
function showFlash(message, type = 'success') {
    const flashDiv = document.getElementById('flash-message');
    const alertDiv = document.getElementById('flash-alert');
    
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    flashDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        flashDiv.style.display = 'none';
    }, 5000);
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Handle profile form submission
document.getElementById('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const fileInput = document.getElementById('profile_picture');
    const hasFile = fileInput.files.length > 0 && fileInput.files[0].size > 0;
    
    formData.append('action', hasFile ? 'update_profile_picture' : 'update_profile');
    
    console.log('Submitting form:', hasFile ? 'with picture' : 'without picture');
    
    try {
        const response = await fetch(window.APP_URL + 'public/api/bkd_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', responseText);
            showFlash('Server returned invalid response. Check console for details.', 'danger');
            return;
        }
        
        console.log('Response:', data);
        
        if (data.success) {
            showFlash(data.message, 'success');
            
            // Update profile picture if changed
            if (data.profile_picture) {
                document.getElementById('profile-pic-display').src = 
                    window.APP_URL + 'uploads/profile_pics/' + data.profile_picture;
            }
            
            // Optionally reload to update session data in header
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showFlash(data.message, 'danger');
            if (data.debug || data.trace) {
                console.error('Error details:', data.debug || data.trace);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showFlash('Eroare la actualizarea profilului: ' + error.message, 'danger');
    }
});

// Handle password form submission
document.getElementById('password-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'change_password');
    
    try {
        const response = await fetch(window.APP_URL + 'public/api/bkd_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showFlash(data.message, 'success');
            e.target.reset(); // Clear form
        } else {
            showFlash(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showFlash('Eroare la schimbarea parolei', 'danger');
    }
});

// Password validation with debounce
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const message = document.getElementById('password-match-msg');

let validationTimeout;
let changePasswordButtonRoot = null;

function renderChangePasswordButton(disabled = false) {
    if (changePasswordButtonRoot && window.Button && window.React) {
        changePasswordButtonRoot.render(
            React.createElement(Button, {
                text: '<?= lang('lang_prof_btn_pass') ?>',
                onClick: (e) => {
                    e.preventDefault();
                    document.getElementById('password-form').dispatchEvent(new Event('submit'));
                },
                variant: 'primary',
                icon: 'icon-save.svg',
                size: 'medium',
                disabled: disabled,
                loading: disabled ? true : false
            })
        );
    }
}

function validatePasswords() {
    // Clear previous timeout
    clearTimeout(validationTimeout);
    
    // If confirm password is empty, hide message immediately
    if (confirmPassword.value.length === 0) {
        message.style.display = "none";
        renderChangePasswordButton(false);
        return;
    }
    
    // Add delay before showing validation message
    validationTimeout = setTimeout(() => {
        if (newPassword.value !== confirmPassword.value) {
            message.style.display = "inline-block";
            message.textContent = "<?= lang('lang_reg_pass_nok') ?>";
            message.style.color = "red";
            renderChangePasswordButton(true); // Disable button
        } else {
            message.style.display = "inline-block";
            message.textContent = "<?= lang('lang_reg_pass_ok') ?> ✔️";
            message.style.color = "green";
            renderChangePasswordButton(false); // Enable button
        }
    }, 500); // Wait 500ms after user stops typing
}

newPassword.addEventListener('input', validatePasswords);
confirmPassword.addEventListener('input', validatePasswords);

// Initialize custom boxes
document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
        initializeCustomBox1(box);
    });
});

// Initialize React Buttons
setTimeout(() => {
    if (window.Button && window.React && window.ReactDOM) {
        // Update Profile button
        const updateProfileRoot = ReactDOM.createRoot(document.getElementById('update-profile-button'));
        updateProfileRoot.render(
            React.createElement(Button, {
                text: '<?= lang('lang_prof_btn_save') ?>',
                onClick: (e) => {
                    e.preventDefault();
                    document.getElementById('profile-form').dispatchEvent(new Event('submit'));
                },
                variant: 'primary',
                icon: 'icon-save.svg',
                size: 'medium'
            })
        );
        
        // Change Password button - store root reference for validation updates
        changePasswordButtonRoot = ReactDOM.createRoot(document.getElementById('change-password-button'));
        renderChangePasswordButton(false); // Initial render with enabled state
    } else {
        console.error('React components not loaded:', { Button: window.Button, React: window.React, ReactDOM: window.ReactDOM });
    }
}, 100);
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

// Check permissions - only admin and superadmin can access
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('Location: ' . APP_URL . 'public/login.php');
    exit;
}

$lang = $_SESSION['settings']['language'] ?? 'en';
// Mapare rapidă dacă ai coduri locale
if ($lang === 'ro') $lang = 'ro';
if ($lang === 'en') $lang = 'en-GB';

// Check if all required user analytics tables exist
$db = new Database();
$tableExists = false;
try {
    $userActivityTable = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_analytics'");
    $adminActivityTable = $db->fetchSingle("SHOW TABLES LIKE 'admin_activity_analytics'");
    $userSummaryTable = $db->fetchSingle("SHOW TABLES LIKE 'user_activity_summary'");
    $userReadingTable = $db->fetchSingle("SHOW TABLES LIKE 'user_reading_time'");
    
    // All tables need to exist
    $tableExists = (!empty($userActivityTable) && !empty($adminActivityTable) && 
                    !empty($userSummaryTable) && !empty($userReadingTable));
    
    // Debug log
    error_log("Tables exist check: " . ($tableExists ? "TRUE" : "FALSE"));
} catch (Exception $e) {
    // Table doesn't exist or other error
    error_log("Error checking tables: " . $e->getMessage());
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<style>
.analytics-container {
    padding: 20px;
    max-width: 1300px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 10px solid #3498db;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-value {
    font-size: 2.5em;
    font-weight: bold;
    margin-left: 10px;
}

.stat-label {
    font-size: 1.2em;
    font-weight:normal;
    margin-top: 1px;
    margin-left: 10px;
    color: #7f8c8d;
}

.chart-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.table-container h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2c3e50;
    font-size: 1.2em;
}

.engagement-score-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 5px;
}

.engagement-high {
    background-color: #2ecc71;
}

.engagement-medium {
    background-color: #f1c40f;
}

.engagement-low {
    background-color: #e67e22;
}

.filter-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 0px;
    align-items: center;
    font-weight: 200;
    font-size: 1.1em;
}

.filter-section .form-group {
    margin-bottom: 0;
    display: flex;
    align-items: center;
}

.filter-section label {
    /*margin-right: 10px;*/
    font-weight: 200;
    font-size: 1.1em;
}

/* Tab styling */
.analytics-tabs {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
    font-size: 1.05em;
}

.analytics-tabs .tab {
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-weight: 200;
    font-size: 1.05em;
    color: #6c757d;
}

.analytics-tabs .tab.active {
    border-bottom-color: #1391a5;
    color: #1391a5;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Make layout responsive */
@media (max-width: 1024px) {
    .analytics-container > div:first-child {
        grid-template-columns: 1fr;
    }
    
    .filter-section {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="breadcrumb-container" style="width: 100%; margin-top: 20px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <br>
            <li class="breadcrumb-item"><?= lang('lang_analytics_admin_label') ?></li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_analytics') ?>
            </li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_users') ?>
            </li>
        </ol>
    </nav>
</div>

<?php if (!$tableExists): ?>
<div class="alert alert-warning" role="alert">
    <strong>Warning:</strong> User Analytics tables do not exist yet. Please run the 
    <a href="apply_user_analytics_migration.php" class="alert-link">database migration</a> to create the necessary tables.
    <p class="mt-2">Once the migration is complete, <a href="user_analytics.php" class="alert-link">return to this page</a> to view your user analytics dashboard.</p>
</div>
<?php endif; ?>

<div class="analytics-container" >
    
    <!-- Tabs and Filters on same line -->
    <div style="display: flex; align-items: flex-end; border-bottom: 1px solid #dee2e6; margin-bottom: 20px;">
        <!-- Tabs -->
        <div class="analytics-tabs" style="border-bottom: none; margin-bottom: 0; flex: 1;">
            <div class="tab active" data-tab="user-engagement"><?= lang('lang_analytics_user_engagement') ?></div>
            <div class="tab" data-tab="admin-activity"><?= lang('lang_analytics_admin_activity') ?></div>
            <div class="tab" data-tab="content-interaction"><?= lang('lang_analytics_content_interaction') ?></div>
            <div class="tab" data-tab="user-statistics"><?= lang('lang_analytics_user_statistics') ?></div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section" style="display: flex; gap: 0px !important; align-items: center; margin-bottom: 0 !important; margin-left: auto;">
            <div class="form-group" style="white-space: nowrap; margin: 0 !important; padding: 0 !important; display: flex; align-items: center; gap: 2px;">
                <label for="role-filter" style="margin: 0 !important; padding: 0 !important; font-size: 0.9em; text-align: right; color: #6c757d;"><?= lang('lang_analytics_role') ?>:</label>
                <select id="role-filter" class="form-control" style="width: 140px; height: 32px; font-size: 0.9em; padding: 4px 8px; margin: 0 !important;">
                    <option value="all"><?= lang('lang_analytics_all_roles') ?></option>
                    <option value="admin"><?= lang('lang_analytics_admin') ?></option>
                    <option value="editor"><?= lang('lang_analytics_editor') ?></option>
                    <option value="contributor"><?= lang('lang_analytics_contributor') ?></option>
                    <option value="user"><?= lang('lang_analytics_user') ?></option>
                </select>
            </div>
            <div class="form-group" style="white-space: nowrap; margin: 0 !important; padding: 0 !important; display: flex; align-items: center; gap: 2px;">
                <label for="time-period" style="margin: 0 !important; padding: 0 !important; font-size: 0.9em; text-align: right; color: #6c757d;"><?= lang('lang_analytics_time_period')?></label>
                <select id="time-period" class="form-control" style="width: 140px; height: 32px; font-size: 0.9em; padding: 4px 8px; margin: 0 !important;">
                    <option value="7" selected><?= lang('lang_analytics_last_7_days') ?></option>
                    <option value="30"><?= lang('lang_analytics_last_30_days') ?></option>
                    <option value="90"><?= lang('lang_analytics_last_90_days') ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- User Engagement Tab -->
    <div class="tab-content active" id="user-engagement-tab">
        <!-- User Engagement Overview -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color: #9b59b6;">
                <div class="stat-value" id="total-active-users">0</div>
                <div class="stat-label"><?= lang('lang_analytics_active_users') ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #3498db;">
                <div class="stat-value" id="returning-users">0</div>
                <div class="stat-label"><?= lang('lang_analytics_returning_users') ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #2ecc71;">
                <div class="stat-value" id="avg-interactions">0</div>
                <div class="stat-label"><?= lang('lang_analytics_avg_interactions') ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #f1c40f;">
                <div class="stat-value" id="power-users">0</div>
                <div class="stat-label"><?= lang('lang_analytics_power_users') ?></div>
            </div>
        </div>
        
        <!-- Engagement Over Time Chart -->
        <div class="chart-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-over-time.svg" width="40" >&nbsp;&nbsp;<span id="title-engagement-over-time"><?= lang('lang_analytics_engagement_over_time') ?></span></h4>
            <canvas id="engagementChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Most Engaged Users Table -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-most.svg" width="40">&nbsp;&nbsp;<span id="title-most-engaged-users"><?= lang('lang_analytics_most_engaged_users') ?></span></h4>
            <table id="engagedUsersTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th><?= lang('lang_analytics_username') ?></th>
                        <th><?= lang('lang_analytics_email') ?></th>
                        <th><?= lang('lang_analytics_role') ?></th>
                        <th><?= lang('lang_analytics_articles_viewed') ?></th>
                        <th><?= lang('lang_analytics_articles_bookmarked') ?></th>
                        <th><?= lang('lang_analytics_articles_commented') ?></th>
                        <th><?= lang('lang_analytics_articles_rated') ?></th>
                        <th><?= lang('lang_analytics_total_interactions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <!-- Engagement by Role -->
        <div class="chart-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-by-role.svg" width="40">&nbsp;&nbsp;<span id="title-engagement-by-role"><?= lang('lang_analytics_engagement_by_role') ?></span></h4>
            <canvas id="roleEngagementChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Admin Activity Tab -->
    <div class="tab-content" id="admin-activity-tab">
        <!-- Admin Activity Chart -->
        <div class="chart-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-admin-activity-over-time.svg" width="40">&nbsp;&nbsp;<span id="title-admin-activity-over-time"><?= lang('lang_analytics_admin_activity_over_time') ?></span></h4>
            <canvas id="adminActivityChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Activity by Admin Table -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-admin-activity.svg" width="40">&nbsp;&nbsp;<span id="title-activity-by-admin"><?= lang('lang_analytics_activity_by_admin') ?></span></h4>
            <table id="adminActivityTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th><?= lang('lang_analytics_username') ?></th>
                        <th><?= lang('lang_analytics_edits') ?></th>
                        <th><?= lang('lang_analytics_publishes') ?></th>
                        <th><?= lang('lang_analytics_approvals') ?></th>
                        <th><?= lang('lang_analytics_total_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <!-- Most Active Articles Table -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-most-active-articles.svg" width="40">&nbsp;&nbsp;<span id="title-most-active-articles"><?= lang('lang_analytics_most_active_articles') ?></span></h4>
            <table id="activeArticlesTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= lang('lang_analytics_title') ?></th>
                        <th><?= lang('lang_analytics_edits') ?></th>
                        <th><?= lang('lang_analytics_publishes') ?></th>
                        <th><?= lang('lang_analytics_approvals') ?></th>
                        <th><?= lang('lang_analytics_last_action') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Content Interaction Tab -->
    <div class="tab-content" id="content-interaction-tab">
        <!-- Content Interaction Overview -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color: #9b59b6;">
                <div class="stat-value" id="total-bookmarks">0</div>
                <div class="stat-label"><?= lang('lang_analytics_bookmarks') ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #3498db;">
                <div class="stat-value" id="avg-rating">0.0</div>
                <div class="stat-label"><?= lang('lang_analytics_avg_rating') ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #2ecc71;">
                <div class="stat-value" id="useful-ratio">0%</div>
                <div class="stat-label"><?= lang('lang_analytics_useful_ratio') ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #f1c40f;">
                <div class="stat-value" id="total-comments">0</div>
                <div class="stat-label"><?= lang('lang_analytics_comments') ?></div>
            </div>
        </div>
        
        <!-- User Actions Chart -->
        <div class="chart-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-actions-over-time.svg" width="40">&nbsp;&nbsp;<span id="title-user-actions-over-time"><?= lang('lang_analytics_user_actions_over_time') ?></span></h4>
            <div id="content-loading-indicator" class="text-center my-3" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden"><?= lang('lang_analytics_loading') ?></span>
                </div>
                <p><?= lang('lang_analytics_loading_data') ?></p>
            </div>
            <canvas id="userActionsChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Top Articles by User Interaction -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user-eng-interactions.svg" width="40">&nbsp;&nbsp;<span id="title-top-articles-by-interaction"><?= lang('lang_analytics_top_articles_by_interaction') ?></span></h4>
            <table id="articleInteractionTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th width="30%"><?= lang('lang_analytics_title') ?></th>
                        <th><?= lang('lang_analytics_bookmarks') ?></th>
                        <th><?= lang('lang_analytics_rating') ?></th>
                        <th><?= lang('lang_analytics_useful_yes') ?></th>
                        <th><?= lang('lang_analytics_useful_no') ?></th>
                        <th><?= lang('lang_analytics_comments') ?></th>
                        <th><?= lang('lang_analytics_pdf_saves') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
        
    </div>

    <!-- User Statistics Tab -->
    <div class="tab-content" id="user-statistics-tab">
        <!-- Most Active Contributors -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-user.svg" width="40">&nbsp;&nbsp;<?= lang('lang_analytics_most_active_contributors') ?></h4>
            <table id="activeContributorsTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th><?= lang('lang_analytics_rank') ?></th>
                        <th><?= lang('lang_analytics_username') ?></th>
                        <th><?= lang('lang_analytics_role') ?></th>
                        <th><?= lang('lang_analytics_articles_submitted') ?></th>
                        <th><?= lang('lang_analytics_articles_approved') ?></th>
                        <th><?= lang('lang_analytics_articles_published') ?></th>
                        <th><?= lang('lang_analytics_total_activity') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Top Commenters -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-comment.svg" width="40">&nbsp;&nbsp;<?= lang('lang_analytics_top_commenters') ?></h4>
            <table id="topCommentersTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th><?= lang('lang_analytics_rank') ?></th>
                        <th><?= lang('lang_analytics_username') ?></th>
                        <th><?= lang('lang_analytics_role') ?></th>
                        <th><?= lang('lang_analytics_total_comments') ?></th>
                        <th><?= lang('lang_analytics_approved_comments') ?></th>
                        <th><?= lang('lang_analytics_avg_comment_length') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Most Engaged Readers -->
        <div class="table-container">
            <h4 style="font-size: 1.5em;"><img src="<?=APP_URL?>assets/icons/icon-view.svg" width="40">&nbsp;&nbsp;<?= lang('lang_analytics_most_engaged_readers') ?></h4>
            <table id="engagedReadersTable" class="articles-table" style="font-size: 0.85em; width:100%">
                <thead>
                    <tr>
                        <th><?= lang('lang_analytics_rank') ?></th>
                        <th><?= lang('lang_analytics_username') ?></th>
                        <th><?= lang('lang_analytics_role') ?></th>
                        <th><?= lang('lang_analytics_articles_viewed') ?></th>
                        <th><?= lang('lang_analytics_bookmarks') ?></th>
                        <th><?= lang('lang_analytics_ratings_given') ?></th>
                        <th><?= lang('lang_analytics_engagement_score') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// App configuration
const APP_URL = '<?= APP_URL ?>';

// Translation variables for JavaScript
const USER_ANALYTICS_TRANSLATIONS = {
    bookmarks: '<?= lang('lang_analytics_bookmarks') ?>',
    bookmark: '<?= lang('lang_analytics_bookmarks') ?? 'Bookmarks' ?>',
    ratings: '<?= lang('lang_analytics_star_ratings') ?>',
    starrating: '<?= lang('lang_analytics_star_ratings') ?? 'Star Ratings' ?>',
    useful: '<?= lang('lang_analytics_useful_ratings') ?>',
    usefulnessrating: '<?= lang('lang_analytics_useful_ratings') ?? 'Usefulness Ratings' ?>',
    usefulness_rating: '<?= lang('lang_analytics_useful_ratings') ?? 'Usefulness Ratings' ?>',
    comments: '<?= lang('lang_analytics_comments') ?>',
    comment: '<?= lang('lang_analytics_comments') ?? 'Comments' ?>',
    pdf_saves: '<?= lang('lang_analytics_pdf_saves') ?>',
    savepdf: '<?= lang('lang_analytics_pdf_saves') ?? 'PDF Saves' ?>',
    view: '<?= lang('lang_analytics_views') ?? 'Views' ?>',
    edit: '<?= lang('lang_analytics_edits') ?? 'Edits' ?>',
    edits: '<?= lang('lang_analytics_edits') ?>',
    publish: '<?= lang('lang_analytics_publishes') ?? 'Publishes' ?>',
    publishes: '<?= lang('lang_analytics_publishes') ?>',
    approve: '<?= lang('lang_analytics_approvals') ?? 'Approvals' ?>',
    approvals: '<?= lang('lang_analytics_approvals') ?>',
    articles_viewed: '<?= lang('lang_analytics_articles_viewed') ?>',
    articles_bookmarked: '<?= lang('lang_analytics_articles_bookmarked') ?>',
    articles_commented: '<?= lang('lang_analytics_articles_commented') ?>',
    active_users: '<?= lang('lang_analytics_active_users') ?>',
    returning_users: '<?= lang('lang_analytics_returning_users') ?>',
    power_users: '<?= lang('lang_analytics_power_users') ?>',
    interactions: '<?= lang('lang_analytics_interactions') ?>',
    users: '<?= lang('lang_analytics_users') ?? 'Users' ?>',
    avg_interactions: '<?= lang('lang_analytics_avg_interactions') ?? 'Avg. Interactions' ?>',
    engagement_over_time: '<?= lang('lang_analytics_engagement_over_time') ?? 'Engagement Over Time' ?>',
    engagement_by_role: '<?= lang('lang_analytics_engagement_by_role') ?? 'Engagement by Role' ?>',
    admin_activity_over_time: '<?= lang('lang_analytics_admin_activity_over_time') ?? 'Admin Activity Over Time' ?>',
    error_loading_data: '<?= lang('lang_analytics_error_loading_data') ?>',
    no_data: '<?= lang('lang_no_data') ?? 'No data available' ?>',
    tables_not_exist: '<?= lang('lang_analytics_tables_not_exist') ?? 'The analytics tables do not exist. Please run the migration first.' ?>',
    loading_data: '<?= lang('lang_analytics_loading_data') ?? 'Loading data...' ?>',
    user_actions_over_time: '<?= lang('lang_analytics_user_actions_over_time') ?>',
    data_incomplete: '<?= lang('lang_analytics_data_incomplete') ?? 'Some data could not be loaded. The information displayed may be incomplete.' ?>',
    all_roles: '<?= lang('lang_analytics_all_roles') ?>',
    role_label: '<?= lang('lang_analytics_role') ?>',
    most_engaged_users: '<?= lang('lang_analytics_most_engaged_users') ?>',
    activity_by_admin: '<?= lang('lang_analytics_activity_by_admin') ?>',
    most_active_articles: '<?= lang('lang_analytics_most_active_articles') ?>',
    top_articles_by_interaction: '<?= lang('lang_analytics_top_articles_by_interaction') ?>',
};

// Store base titles for dynamic updates
const BASE_TITLES = {
    'title-engagement-over-time': USER_ANALYTICS_TRANSLATIONS.engagement_over_time,
    'title-most-engaged-users': USER_ANALYTICS_TRANSLATIONS.most_engaged_users,
    'title-engagement-by-role': USER_ANALYTICS_TRANSLATIONS.engagement_by_role,
    'title-admin-activity-over-time': USER_ANALYTICS_TRANSLATIONS.admin_activity_over_time,
    'title-activity-by-admin': USER_ANALYTICS_TRANSLATIONS.activity_by_admin,
    'title-most-active-articles': USER_ANALYTICS_TRANSLATIONS.most_active_articles,
    'title-user-actions-over-time': USER_ANALYTICS_TRANSLATIONS.user_actions_over_time,
    'title-top-articles-by-interaction': USER_ANALYTICS_TRANSLATIONS.top_articles_by_interaction,
};

// Chart objects
let engagementChart = null;
let roleEngagementChart = null;
let adminActivityChart = null;
let userActionsChart = null;

// Function to update all section titles with role filter info
function updateSectionTitles() {
    const roleSelect = document.getElementById('role-filter');
    const selectedRole = roleSelect.value;
    const roleText = roleSelect.options[roleSelect.selectedIndex].text;
    
    const timePeriodSelect = document.getElementById('time-period');
    const timePeriodText = timePeriodSelect.options[timePeriodSelect.selectedIndex].text;
    
    // Determine the suffix based on role selection
    let suffix = '';
    if (selectedRole === 'all') {
        suffix = ' - ' + USER_ANALYTICS_TRANSLATIONS.all_roles;
    } else {
        suffix = ' - ' + USER_ANALYTICS_TRANSLATIONS.role_label + ': ' + roleText;
    }
    
    // Add time period to suffix
    suffix += ' - ' + timePeriodText;
    
    // Update all title elements
    Object.keys(BASE_TITLES).forEach(titleId => {
        const element = document.getElementById(titleId);
        if (element) {
            element.textContent = BASE_TITLES[titleId] + suffix;
        }
    });
}

// Helper function to calculate date range based on time period
function getDateRange() {
    const timePeriod = parseInt(document.getElementById('time-period').value);
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - timePeriod);
    
    return {
        startDate: startDate.toISOString().split('T')[0],
        endDate: endDate.toISOString().split('T')[0]
    };
}

// Helper function to reload data for active tab
function reloadActiveTabData() {
    const activeTab = document.querySelector('.tab.active').dataset.tab;
    
    // Update section titles with current role filter
    updateSectionTitles();
    
    switch (activeTab) {
        case 'user-engagement':
            loadUserEngagementData();
            break;
        case 'admin-activity':
            loadAdminActivityData();
            break;
        case 'content-interaction':
            loadContentInteractionData();
            break;
        case 'user-statistics':
            loadUserStatisticsData();
            break;
    }
}

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    initTabs();
    
    // Update titles on initial load
    updateSectionTitles();
    
    // Load initial data
    loadUserEngagementData();
    
    // Add event listeners for automatic reload on filter change
    document.getElementById('time-period').addEventListener('change', function() {
        reloadActiveTabData();
    });
    
    document.getElementById('role-filter').addEventListener('change', function() {
        reloadActiveTabData();
    });
});

// Function to initialize tabs
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            try {
                console.log('Tab clicked:', this.dataset.tab);
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show selected tab content
                const tabContentElement = document.getElementById(`${this.dataset.tab}-tab`);
                if (tabContentElement) {
                    tabContentElement.classList.add('active');
                } else {
                    console.error(`Tab content element not found for tab: ${this.dataset.tab}`);
                }
                
                // Load tab data if needed
                switch (this.dataset.tab) {
                    case 'user-engagement':
                        loadUserEngagementData();
                        break;
                    case 'admin-activity':
                        loadAdminActivityData();
                        break;
                    case 'content-interaction':
                        loadContentInteractionData();
                        break;
                    case 'user-statistics':
                        loadUserStatisticsData();
                        break;
                    default:
                        console.error(`Unknown tab: ${this.dataset.tab}`);
                }
            } catch (error) {
                console.error('Error switching tabs:', error);
                alert(USER_ANALYTICS_TRANSLATIONS.error_loading_data || 'An error occurred while loading the tab data.');
            }
        });
    });
}

// Function to check if tables exist first
async function checkTablesExist() {
    try {
        console.log('Checking if analytics tables exist...');
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=check_tables`);
        
        if (!response.ok) {
            console.error(`HTTP Error: ${response.status} ${response.statusText}`);
            return false;
        }
        
        // Get the response text first
        const responseText = await response.text();
        console.log('Response text:', responseText.substring(0, 100) + '...');
        
        try {
            // Try to parse as JSON
            const data = JSON.parse(responseText);
            console.log('Table check result:', data);
            return data.success && data.tables_exist === true;
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was not valid JSON:', responseText.substring(0, 200));
            return false;
        }
    } catch (error) {
        console.error('Error checking tables:', error);
        return false;
    }
}

// Function to load user engagement data
async function loadUserEngagementData() {
    const dateRange = getDateRange();
    const startDate = dateRange.startDate;
    const endDate = dateRange.endDate;
    const role = document.getElementById('role-filter').value;
    
    try {
        console.log('Loading user engagement data...');
        // First check if tables exist
        const tablesExist = await checkTablesExist();
        console.log('Tables exist check result:', tablesExist);
        
        if (!tablesExist) {
            console.warn('Analytics tables do not exist');
            showError('User analytics tables do not exist yet. Please run the migration script first.');
            
            // Show the migration alert at the top of the page
            const migrationAlert = document.querySelector('.alert-warning');
            if (migrationAlert) {
                migrationAlert.style.display = 'block';
            }
            return;
        }
        
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_user_engagement&start_date=${startDate}&end_date=${endDate}${role !== 'all' ? `&role=${role}` : ''}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            displayUserEngagementOverview(data.retention);
            displayEngagementChart(data.engagement_over_time);
            displayEngagedUsersTable(data.engaged_users);
            displayRoleEngagementChart(data.engagement_by_role);
        } else {
            console.error('Error loading user engagement data:', data.error);
            showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ' ' + data.error);
        }
    } catch (error) {
        console.error('Error loading user engagement data:', error);
        showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ' ' + error.message);
    }
}

// Function to load admin activity data
async function loadAdminActivityData() {
    const dateRange = getDateRange();
    const startDate = dateRange.startDate;
    const endDate = dateRange.endDate;
    const role = document.getElementById('role-filter').value;
    
    try {
        // First check if tables exist
        const tablesExist = await checkTablesExist();
        
        if (!tablesExist) {
            showError('User analytics tables do not exist yet. Please run the migration script first.');
            
            // Show the migration alert at the top of the page
            const migrationAlert = document.querySelector('.alert-warning');
            if (migrationAlert) {
                migrationAlert.style.display = 'block';
            }
            return;
        }
        
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_admin_activity&start_date=${startDate}&end_date=${endDate}${role !== 'all' ? `&role=${role}` : ''}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            displayAdminActivityChart(data.daily_activity);
            displayAdminActivityTable(data.activity_summary);
            displayActiveArticlesTable(data.active_articles);
        } else {
            console.error('Error loading admin activity data:', data.error);
            showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ' ' + data.error);
        }
    } catch (error) {
        console.error('Error loading admin activity data:', error);
        showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ' ' + error.message);
    }
}

// Function to load content interaction data
async function loadContentInteractionData() {
    console.log('Loading content interaction data');
    
    // Display empty data first to clear any previous data
    displayContentInteractionOverview(null);
    displayUserActionsChart(null);
    displayArticleInteractionTable(null);
    
    // Show loading indicator
    try {
        const loadingIndicator = document.getElementById('content-loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
        }
    } catch (e) {
        console.error('Error with loading indicator:', e);
    }
    
    try {
        // First check if the analytics tables exist
        console.log('Checking if analytics tables exist...');
        const tablesExistResult = await checkTablesExist();
        console.log('Tables exist check result:', tablesExistResult);
        
        if (!tablesExistResult) {
            console.error('Analytics tables check failed');
            showError(USER_ANALYTICS_TRANSLATIONS.tables_not_exist || 'The analytics tables do not exist. Please run the migration first.');
            
            // Show the migration alert at the top of the page
            try {
                const migrationAlert = document.querySelector('.alert-warning');
                if (migrationAlert) {
                    migrationAlert.style.display = 'block';
                }
            } catch (e) {
                console.error('Error showing migration alert:', e);
            }
            return;
        }
        
        // Prepare the date range
        const dateRange = getDateRange();
        const startDate = dateRange.startDate;
        const endDate = dateRange.endDate;
        const role = document.getElementById('role-filter')?.value || 'all';
        
        console.log(`Fetching content interaction data for date range: ${startDate} to ${endDate}, role: ${role}...`);
        
        // Add a timeout to abort the request if it takes too long
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000); // 20-second timeout
        
        try {
            const response = await fetch(
                `<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_user_analytics&start_date=${startDate}&end_date=${endDate}${role !== 'all' ? `&role=${role}` : ''}`,
                { signal: controller.signal }
            );
            
            clearTimeout(timeoutId); // Clear the timeout if the request completes in time
            
            // Get the response text first to handle potential non-JSON responses
            const responseText = await response.text();
            console.log('Response first 100 chars:', responseText.substring(0, 100));
            
            if (!response.ok) {
                console.error(`HTTP Error: ${response.status} ${response.statusText}`);
                console.error('Response text:', responseText.substring(0, 500));
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Try to parse the response as JSON
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Content interaction data received:', data);
                console.log('Overall stats:', data.overall_stats);
                console.log('Daily activity:', data.daily_activity);
                console.log('Top articles:', data.top_articles);
                
                if (data.success) {
                    // Update the UI with the data - with individual error handling
                    try {
                        displayContentInteractionOverview(data.overall_stats || {});
                        console.log('Overview displayed successfully');
                    } catch (e) {
                        console.error('Error displaying overview:', e);
                    }
                    
                    try {
                        displayUserActionsChart(data.daily_activity || []);
                        console.log('Chart displayed successfully');
                    } catch (e) {
                        console.error('Error displaying chart:', e);
                    }
                    
                    try {
                        displayArticleInteractionTable(data.top_articles || []);
                        console.log('Table displayed successfully');
                    } catch (e) {
                        console.error('Error displaying table:', e);
                    }
                    
                    console.log('Content interaction data displayed successfully');
                } else {
                    console.error('API returned error:', data.error);
                    // Even if there's an error, try to display the UI with empty data if available
                    if (data.empty_data) {
                        console.log('Displaying empty data due to backend error');
                        try {
                            displayContentInteractionOverview(data.overall_stats || {});
                        } catch (e) {
                            console.error('Error displaying overview:', e);
                        }
                        try {
                            displayUserActionsChart(data.daily_activity || []);
                        } catch (e) {
                            console.error('Error displaying chart:', e);
                        }
                        try {
                            displayArticleInteractionTable(data.top_articles || []);
                        } catch (e) {
                            console.error('Error displaying table:', e);
                        }
                    }
                    
                    // Show a user-friendly error message but don't prevent displaying what we can
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-warning';
                    errorDiv.innerHTML = USER_ANALYTICS_TRANSLATIONS.data_incomplete || 'Some data could not be loaded. The information displayed may be incomplete.';
                    
                    const contentTab = document.getElementById('content-interaction-tab');
                    if (contentTab) {
                        contentTab.insertBefore(errorDiv, contentTab.firstChild);
                    }
                }
            } catch (parseError) {
                console.error('Error parsing JSON response:', parseError);
                console.error('Response was not valid JSON:', responseText.substring(0, 200));
                // Don't throw - just show error
                showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ': Invalid JSON response');
                return; // Exit function instead of throwing
            }
        } catch (fetchError) {
            if (fetchError.name === 'AbortError') {
                console.error('Request timed out after 20 seconds');
                showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ': Request timed out');
            } else {
                console.error('Fetch error:', fetchError);
                showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ': ' + fetchError.message);
            }
        }
    } catch (error) {
        console.error('Fatal error in loadContentInteractionData:', error);
        showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ': ' + error.message);
    } finally {
        // Always hide the loading indicator
        try {
            const loadingIndicator = document.getElementById('content-loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
        } catch (e) {
            console.error('Error hiding loading indicator:', e);
        }
    }
}

// Function to display user engagement overview
function displayUserEngagementOverview(retention) {
    document.getElementById('total-active-users').textContent = formatNumber(retention.total_users || 0);
    document.getElementById('returning-users').textContent = formatNumber(retention.returning_users || 0);
    document.getElementById('avg-interactions').textContent = calculateRetentionRate(retention.returning_users, retention.total_users) + '%';
    document.getElementById('power-users').textContent = formatNumber(retention.power_users || 0);
}

// Function to display content interaction overview
function displayContentInteractionOverview(stats) {
    console.log('Updating content interaction overview stats');
    
    try {
        // Safety check for undefined stats data
        if (!stats || typeof stats !== 'object') {
            console.log('No content interaction stats available, using defaults');
            stats = {
                bookmarks: 0,
                avg_star_rating: 0,
                useful_yes: 0,
                useful_no: 0,
                comments: 0
            };
        }
        
        // Safely update each element, with error handling
        try {
            const totalBookmarks = document.getElementById('total-bookmarks');
            if (totalBookmarks) {
                totalBookmarks.textContent = formatNumber(stats.bookmarks || 0);
            }
        } catch (e) {
            console.error('Error updating total-bookmarks:', e);
        }
        
        try {
            const avgRating = document.getElementById('avg-rating');
            if (avgRating) {
                const rating = parseFloat(stats.avg_star_rating) || 0;
                avgRating.textContent = rating > 0 ? rating.toFixed(1) + '/5.0' : '0.0/5.0';
            }
        } catch (e) {
            console.error('Error updating avg-rating:', e);
        }
        
        try {
            const usefulYes = parseInt(stats.useful_yes || 0);
            const usefulNo = parseInt(stats.useful_no || 0);
            const usefulRatio = usefulYes + usefulNo > 0 ? Math.round(usefulYes / (usefulYes + usefulNo) * 100) : 0;
            
            const usefulRatioEl = document.getElementById('useful-ratio');
            if (usefulRatioEl) {
                usefulRatioEl.textContent = usefulRatio + '%';
            }
        } catch (e) {
            console.error('Error updating useful-ratio:', e);
        }
        
        try {
            const totalComments = document.getElementById('total-comments');
            if (totalComments) {
                totalComments.textContent = formatNumber(stats.comments || 0);
            }
        } catch (e) {
            console.error('Error updating total-comments:', e);
        }
        
        console.log('Content interaction overview updated successfully');
    } catch (error) {
        console.error('Fatal error in displayContentInteractionOverview:', error);
    }
}

// Function to display engagement chart
function displayEngagementChart(engagementData) {
    const ctx = document.getElementById('engagementChart').getContext('2d');
    
    if (engagementChart) {
        engagementChart.destroy();
    }
    
    const dates = engagementData.map(item => item.date);
    const uniqueUsers = engagementData.map(item => item.unique_users);
    const totalInteractions = engagementData.map(item => item.total_interactions);
    
    engagementChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: USER_ANALYTICS_TRANSLATIONS.active_users,
                    data: uniqueUsers,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                },
                {
                    label: USER_ANALYTICS_TRANSLATIONS.interactions,
                    data: totalInteractions,
                    borderColor: 'rgba(155, 89, 182, 1)',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: false,
                    text: USER_ANALYTICS_TRANSLATIONS.engagement_over_time
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Function to display role engagement chart
function displayRoleEngagementChart(roleData) {
    const ctx = document.getElementById('roleEngagementChart').getContext('2d');
    
    if (roleEngagementChart) {
        roleEngagementChart.destroy();
    }
    
    const roles = roleData.map(item => item.role);
    const userCounts = roleData.map(item => parseInt(item.user_count));
    const avgInteractions = roleData.map(item => parseFloat(item.avg_interactions_per_user));
    
    roleEngagementChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: roles,
            datasets: [
                {
                    label: USER_ANALYTICS_TRANSLATIONS.users,
                    data: userCounts,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    yAxisID: 'y'
                },
                {
                    label: USER_ANALYTICS_TRANSLATIONS.avg_interactions,
                    data: avgInteractions,
                    backgroundColor: 'rgba(155, 89, 182, 0.7)',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: false,
                    text: USER_ANALYTICS_TRANSLATIONS.engagement_by_role
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: USER_ANALYTICS_TRANSLATIONS.users
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: USER_ANALYTICS_TRANSLATIONS.avg_interactions
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Function to display admin activity chart
function displayAdminActivityChart(activityData) {
    const ctx = document.getElementById('adminActivityChart').getContext('2d');
    
    if (adminActivityChart) {
        adminActivityChart.destroy();
    }
    
    // Safety check for undefined data
    if (!activityData || !Array.isArray(activityData) || activityData.length === 0) {
        console.log('No activity data available for admin activity chart');
        // Create empty chart with legend to avoid errors
        adminActivityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.edits || 'Edits',
                        data: [],
                        borderColor: 'rgba(52, 152, 219, 0.7)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.publishes || 'Publishes',
                        data: [],
                        borderColor: 'rgba(46, 204, 113, 0.7)',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.approvals || 'Approvals',
                        data: [],
                        borderColor: 'rgba(241, 196, 15, 0.7)',
                        backgroundColor: 'rgba(241, 196, 15, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: false,
                        text: USER_ANALYTICS_TRANSLATIONS.admin_activity_over_time
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        return;
    }
    
    // Process data for the chart
    const dates = [...new Set(activityData.map(item => item.date))].sort();
    
    const actionTypes = [...new Set(activityData.map(item => item.action_type))];
    const datasets = [];
    
    // Colors mapped to action types WITHOUT 'admin_' prefix (as stored in DB)
    const colors = {
        'edit': 'rgba(52, 152, 219, 0.7)',
        'publish': 'rgba(46, 204, 113, 0.7)',
        'approve': 'rgba(241, 196, 15, 0.7)'
    };
    
    actionTypes.forEach(actionType => {
        // Safety check for undefined actionType
        if (!actionType) {
            console.warn('Skipping undefined action type');
            return;
        }
        
        const data = dates.map(date => {
            const entry = activityData.find(item => item.date === date && item.action_type === actionType);
            return entry ? parseInt(entry.count) : 0;
        });
        
        // Action types are stored as 'edit', 'publish', 'approve' (no prefix)
        const displayName = actionType;
        
        datasets.push({
            label: USER_ANALYTICS_TRANSLATIONS[displayName] || displayName,
            data: data,
            borderColor: colors[actionType] || 'rgba(100, 100, 100, 0.7)',
            backgroundColor: (colors[actionType] || 'rgba(100, 100, 100, 0.7)').replace('0.7', '0.1'),
            tension: 0.4
        });
    });
    
    adminActivityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: false,
                    text: USER_ANALYTICS_TRANSLATIONS.admin_activity_over_time
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Function to display user actions chart
function displayUserActionsChart(activityData) {
    const ctx = document.getElementById('userActionsChart').getContext('2d');
    
    if (userActionsChart) {
        userActionsChart.destroy();
    }
    
    // Safety check for undefined data
    if (!activityData || !Array.isArray(activityData) || activityData.length === 0) {
        console.log('No activity data available for user actions chart');
        // Create empty chart with legend to avoid errors
        userActionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.bookmarks || 'Bookmarks',
                        data: [],
                        borderColor: 'rgba(155, 89, 182, 0.7)',
                        backgroundColor: 'rgba(155, 89, 182, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.starrating || 'Star Ratings',
                        data: [],
                        borderColor: 'rgba(52, 152, 219, 0.7)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.usefulnessrating || 'Usefulness Ratings',
                        data: [],
                        borderColor: 'rgba(46, 204, 113, 0.7)',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.pdf_saves || 'PDF Saves',
                        data: [],
                        borderColor: 'rgba(241, 196, 15, 0.7)',
                        backgroundColor: 'rgba(241, 196, 15, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.comments || 'Comments',
                        data: [],
                        borderColor: 'rgba(230, 126, 34, 0.7)',
                        backgroundColor: 'rgba(230, 126, 34, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: USER_ANALYTICS_TRANSLATIONS.view || 'Views',
                        data: [],
                        borderColor: 'rgba(52, 73, 94, 0.7)',
                        backgroundColor: 'rgba(52, 73, 94, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: false,
                        text: USER_ANALYTICS_TRANSLATIONS.user_actions_over_time
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        return;
    }
    
    // Process data for the chart
    const dates = [...new Set(activityData.map(item => item.date))].sort();
    
    const actionTypes = [...new Set(activityData.map(item => item.action_type))];
    const datasets = [];
    
    const colors = {
        'bookmark': 'rgba(155, 89, 182, 0.7)',
        'rating': 'rgba(52, 152, 219, 0.7)',
        'usefulness_rating': 'rgba(46, 204, 113, 0.7)',
        'save_pdf': 'rgba(241, 196, 15, 0.7)',
        'comment': 'rgba(230, 126, 34, 0.7)',
        'view': 'rgba(52, 73, 94, 0.7)'
    };
    
    actionTypes.forEach(actionType => {
        if (['bookmark', 'rating', 'usefulness_rating', 'save_pdf', 'comment', 'view'].includes(actionType)) {
            const data = dates.map(date => {
                const entry = activityData.find(item => item.date === date && item.action_type === actionType);
                return entry ? parseInt(entry.count) : 0;
            });
            
            // Map action type to translation key
            const translationKey = actionType === 'rating' ? 'starrating' : actionType.replace('_', '');
            datasets.push({
                label: USER_ANALYTICS_TRANSLATIONS[translationKey] || actionType,
                data: data,
                borderColor: colors[actionType],
                backgroundColor: colors[actionType].replace('0.7', '0.1'),
                tension: 0.4
            });
        }
    });
    
    userActionsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: false,
                    text: USER_ANALYTICS_TRANSLATIONS.user_actions_over_time
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Function to display engaged users table
function displayEngagedUsersTable(users) {
    // Destroy existing table if it exists
    if ($.fn.DataTable.isDataTable('#engagedUsersTable')) {
        $('#engagedUsersTable').DataTable().destroy();
    }
    
    const tbody = document.querySelector('#engagedUsersTable tbody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.role}</td>
            <td>${formatNumber(user.articles_viewed || 0)}</td>
            <td>${formatNumber(user.articles_bookmarked || 0)}</td>
            <td>${formatNumber(user.articles_commented || 0)}</td>
            <td>${formatNumber(user.articles_rated || 0)}</td>
            <td>${formatNumber(user.total_interactions || 0)}</td>
        `;
    });
    
    // Initialize DataTable
    $('#engagedUsersTable').DataTable({
        order: [[7, 'desc']], // Sort by total interactions by default
        pageLength: 10,
        responsive: true,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
}

// Function to display admin activity table
function displayAdminActivityTable(activitySummary) {
    // Destroy existing table if it exists
    if ($.fn.DataTable.isDataTable('#adminActivityTable')) {
        $('#adminActivityTable').DataTable().destroy();
    }
    
    const tbody = document.querySelector('#adminActivityTable tbody');
    tbody.innerHTML = '';
    
    activitySummary.forEach(admin => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${admin.username}</td>
            <td>${formatNumber(admin.edits || 0)}</td>
            <td>${formatNumber(admin.publishes || 0)}</td>
            <td>${formatNumber(admin.approvals || 0)}</td>
            <td>${formatNumber(admin.total_actions || 0)}</td>
        `;
    });
    
    // Initialize DataTable
    $('#adminActivityTable').DataTable({
        order: [[4, 'desc']], // Sort by total actions by default
        pageLength: 10,
        responsive: true,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
}

// Function to display active articles table
function displayActiveArticlesTable(activeArticles) {
    // Destroy existing table if it exists
    if ($.fn.DataTable.isDataTable('#activeArticlesTable')) {
        $('#activeArticlesTable').DataTable().destroy();
    }
    
    const tbody = document.querySelector('#activeArticlesTable tbody');
    tbody.innerHTML = '';
    
    activeArticles.forEach(article => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${article.article_id}</td>
            <td><a href="${APP_URL}public/view_article.php?id=${article.article_id}" target="_blank">${truncateText(article.title, 50)}</a></td>
            <td>${formatNumber(article.edits || 0)}</td>
            <td>${formatNumber(article.publishes || 0)}</td>
            <td>${formatNumber(article.approvals || 0)}</td>
            <td>${formatDate(article.last_action)}</td>
        `;
    });
    
    // Initialize DataTable
    $('#activeArticlesTable').DataTable({
        order: [[2, 'desc']], // Sort by edits by default
        pageLength: 10,
        responsive: true,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
}

// Function to display article interaction table
function displayArticleInteractionTable(articles) {
    console.log('Initializing article interaction table');
    
    try {
        // Instead of manipulating the DOM directly, prepare data for DataTables
        let tableData = [];
        
        // Safety check for undefined articles data
        if (articles && Array.isArray(articles) && articles.length > 0) {
            console.log(`Processing ${articles.length} articles for the table`);
            
            // Map the articles data to the table format
            tableData = articles
                .filter(article => article && article.article_id !== null && article.article_id > 0) // Filter out null/invalid articles
                .map(article => {
                    const articleId = article.article_id || 0;
                    const title = article.title || `Unknown Article #${articleId}`;
                    
                    return [
                        articleId,
                        `<a href="${APP_URL}public/view_article.php?id=${articleId}" target="_blank">${truncateText(title, 50)}</a>`,
                        formatNumber(article.bookmarks || 0),
                        article.avg_rating ? parseFloat(article.avg_rating).toFixed(1) + '/5.0' : '-',
                        formatNumber(article.useful_yes || 0),
                        formatNumber(article.useful_no || 0),
                        formatNumber(article.comments || 0),
                        formatNumber(article.pdf_saves || 0)
                    ];
                });
            
            console.log(`Filtered to ${tableData.length} valid articles`);
        } else {
            console.log('No article data available');
        }
        
        // Always completely recreate the table
        try {
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#articleInteractionTable')) {
                console.log('Destroying existing DataTable');
                $('#articleInteractionTable').DataTable().destroy();
            }
            
            // Clear the table content
            $('#articleInteractionTable tbody').empty();
            
            // Create a brand new DataTable
            console.log('Creating new DataTable with', tableData.length, 'rows');
            
            $('#articleInteractionTable').DataTable({
                data: tableData,
                responsive: true,
                order: [[2, 'desc']], // Sort by bookmarks by default
                pageLength: 10,
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json",
                    emptyTable: USER_ANALYTICS_TRANSLATIONS.no_data || 'No data available'
                },
                // Explicitly define columns to ensure proper structure
                columns: [
                    { title: "ID" },
                    { title: "<?= lang('lang_analytics_title') ?>" },
                    { title: "<?= lang('lang_analytics_bookmarks') ?>" },
                    { title: "<?= lang('lang_analytics_rating') ?>" },
                    { title: "<?= lang('lang_analytics_useful_yes') ?>" },
                    { title: "<?= lang('lang_analytics_useful_no') ?>" },
                    { title: "<?= lang('lang_analytics_comments') ?>" },
                    { title: "<?= lang('lang_analytics_pdf_saves') ?>" }
                ]
            });
            console.log('DataTable successfully initialized');
        } catch (dtError) {
            console.error('Error initializing DataTable:', dtError);
            
            // If there's an error with DataTables, fall back to basic HTML
            const tbody = document.querySelector('#articleInteractionTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                
                if (tableData.length > 0) {
                    // Add rows manually
                    tableData.forEach(rowData => {
                        const row = tbody.insertRow();
                        rowData.forEach(cellData => {
                            const cell = row.insertCell();
                            cell.innerHTML = cellData;
                        });
                    });
                } else {
                    // Show empty message
                    const row = tbody.insertRow();
                    const cell = row.insertCell();
                    cell.colSpan = 8;
                    cell.className = 'text-center';
                    cell.innerHTML = USER_ANALYTICS_TRANSLATIONS.no_data || 'No data available';
                }
            }
        }
    } catch (error) {
        console.error('Fatal error in displayArticleInteractionTable:', error);
        alert(USER_ANALYTICS_TRANSLATIONS.error_loading_data || 'Error loading data');
    }
}

// Helper function to show error messages
function showError(message) {
    alert(message);
}

// Helper function to format numbers
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Helper function to truncate text
function truncateText(text, maxLength) {
    return text.length > maxLength ? text.substr(0, maxLength) + '...' : text;
}

// Helper function to format dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Helper function to check if analytics tables exist is defined earlier in the code

// Helper function to calculate retention rate
function calculateRetentionRate(returning, total) {
    if (!total) return 0;
    return Math.round((returning / total) * 100);
}

// Function to load user statistics data
async function loadUserStatisticsData() {
    const dateRange = getDateRange();
    const startDate = dateRange.startDate;
    const endDate = dateRange.endDate;
    const role = document.getElementById('role-filter').value;
    
    console.log('Loading user statistics data for date range:', startDate, 'to', endDate, 'role:', role);
    
    try {
        // Load most active contributors
        const contributorsResponse = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_active_contributors&start_date=${startDate}&end_date=${endDate}&role=${role}`);
        const contributorsData = await contributorsResponse.json();
        
        console.log('Contributors data received:', contributorsData);
        
        if (contributorsData.success && contributorsData.data) {
            updateActiveContributorsTable(contributorsData.data);
        } else {
            console.error('Error loading contributors data:', contributorsData.error);
        }
        
        // Load top commenters
        const commentersResponse = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_top_commenters&start_date=${startDate}&end_date=${endDate}&role=${role}`);
        const commentersData = await commentersResponse.json();
        
        console.log('Commenters data received:', commentersData);
        
        if (commentersData.success && commentersData.data) {
            updateTopCommentersTable(commentersData.data);
        } else {
            console.error('Error loading commenters data:', commentersData.error);
        }
        
        // Load most engaged readers
        const readersResponse = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_engaged_readers&start_date=${startDate}&end_date=${endDate}&role=${role}`);
        const readersData = await readersResponse.json();
        
        console.log('Engaged readers data received:', readersData);
        
        if (readersData.success && readersData.data) {
            updateEngagedReadersTable(readersData.data);
        } else {
            console.error('Error loading readers data:', readersData.error);
        }
        
    } catch (error) {
        console.error('Error loading user statistics:', error);
        showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data || 'Error loading data');
    }
}

// Update Active Contributors Table
function updateActiveContributorsTable(data) {
    const tableData = data.map((user, index) => [
        index + 1, // Rank
        user.username,
        user.role,
        user.articles_submitted || 0,
        user.articles_approved || 0,
        user.articles_published || 0,
        user.total_activity || 0
    ]);
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#activeContributorsTable')) {
        $('#activeContributorsTable').DataTable().destroy();
    }
    
    $('#activeContributorsTable tbody').empty();
    
    $('#activeContributorsTable').DataTable({
        data: tableData,
        responsive: true,
        order: [[6, 'desc']], // Sort by total activity
        pageLength: 10,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json",
            emptyTable: USER_ANALYTICS_TRANSLATIONS.no_data || 'No data available'
        }
    });
}

// Update Top Commenters Table
function updateTopCommentersTable(data) {
    const tableData = data.map((user, index) => [
        index + 1, // Rank
        user.username,
        user.role,
        user.total_comments || 0,
        user.approved_comments || 0,
        user.avg_comment_length ? Math.round(user.avg_comment_length) : 0
    ]);
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#topCommentersTable')) {
        $('#topCommentersTable').DataTable().destroy();
    }
    
    $('#topCommentersTable tbody').empty();
    
    $('#topCommentersTable').DataTable({
        data: tableData,
        responsive: true,
        order: [[3, 'desc']], // Sort by total comments
        pageLength: 10,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json",
            emptyTable: USER_ANALYTICS_TRANSLATIONS.no_data || 'No data available'
        }
    });
}

// Update Engaged Readers Table
function updateEngagedReadersTable(data) {
    const tableData = data.map((user, index) => [
        index + 1, // Rank
        user.username,
        user.role,
        user.articles_viewed || 0,
        user.bookmarks || 0,
        user.ratings_given || 0,
        user.engagement_score || 0
    ]);
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#engagedReadersTable')) {
        $('#engagedReadersTable').DataTable().destroy();
    }
    
    $('#engagedReadersTable tbody').empty();
    
    $('#engagedReadersTable').DataTable({
        data: tableData,
        responsive: true,
        order: [[6, 'desc']], // Sort by engagement score
        pageLength: 10,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json",
            emptyTable: USER_ANALYTICS_TRANSLATIONS.no_data || 'No data available'
        }
    });
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
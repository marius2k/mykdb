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
    max-width: 1500px;
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
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 10px solid #3498db;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
}

.stat-label {
    margin-top: 10px;
    color: #7f8c8d;
}

.chart-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.filter-section .form-group {
    margin-bottom: 0;
    display: flex;
    align-items: center;
}

.filter-section label {
    margin-right: 10px;
    font-weight: 500;
}

.filter-section .btn {
    margin-left: auto;
}

/* Data Table styling */
table.dataTable thead th {
    background-color: #1391a5 !important;
    color: white !important;
    padding: 10px !important;
}

table.dataTable tbody td {
    padding: 10px !important;
}

/* Tab styling */
.analytics-tabs {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
}

.analytics-tabs .tab {
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-weight: 500;
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
    
    .filter-section .btn {
        margin-left: 0;
        width: 100%;
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
                <?= lang('lang_users') ?>
            </li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_user_analytics') ?>
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

<div class="analytics-container">
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="form-group">
            <label for="start-date"><?= lang('lang_analytics_start_date') ?>:</label>
            <input type="date" id="start-date" class="form-control" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
        </div>
        
        <div class="form-group">
            <label for="end-date"><?= lang('lang_analytics_end_date') ?>:</label>
            <input type="date" id="end-date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="form-group">
            <label for="role-filter"><?= lang('lang_analytics_role') ?>:</label>
            <select id="role-filter" class="form-control">
                <option value="all"><?= lang('lang_analytics_all_roles') ?></option>
                <option value="admin"><?= lang('lang_analytics_admin') ?></option>
                <option value="editor"><?= lang('lang_analytics_editor') ?></option>
                <option value="contributor"><?= lang('lang_analytics_contributor') ?></option>
                <option value="user"><?= lang('lang_analytics_user') ?></option>
            </select>
        </div>
        
        <button type="button" id="update-filters" class="btn btn-primary"><?= lang('lang_analytics_update') ?></button>
    </div>
    
    <!-- Tabs -->
    <div class="analytics-tabs">
        <div class="tab active" data-tab="user-engagement"><?= lang('lang_analytics_user_engagement') ?></div>
        <div class="tab" data-tab="admin-activity"><?= lang('lang_analytics_admin_activity') ?></div>
        <div class="tab" data-tab="content-interaction"><?= lang('lang_analytics_content_interaction') ?> <small>(Beta)</small></div>
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
            <h4><?= lang('lang_analytics_engagement_over_time') ?></h4>
            <canvas id="engagementChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Most Engaged Users Table -->
        <div class="table-container">
            <h4><?= lang('lang_analytics_most_engaged_users') ?></h4>
            <table id="engagedUsersTable" class="display" style="width:100%">
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
            <h4><?= lang('lang_analytics_engagement_by_role') ?></h4>
            <canvas id="roleEngagementChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Admin Activity Tab -->
    <div class="tab-content" id="admin-activity-tab">
        <!-- Admin Activity Chart -->
        <div class="chart-container">
            <h4><?= lang('lang_analytics_admin_activity_over_time') ?></h4>
            <canvas id="adminActivityChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Activity by Admin Table -->
        <div class="table-container">
            <h4><?= lang('lang_analytics_activity_by_admin') ?></h4>
            <table id="adminActivityTable" class="display" style="width:100%">
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
            <h4><?= lang('lang_analytics_most_active_articles') ?></h4>
            <table id="activeArticlesTable" class="display" style="width:100%">
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
            <h4><?= lang('lang_analytics_user_actions_over_time') ?></h4>
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
            <h4><?= lang('lang_analytics_top_articles_by_interaction') ?></h4>
            <table id="articleInteractionTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= lang('lang_analytics_title') ?></th>
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
</div>

<script>
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
    edits: '<?= lang('lang_analytics_edits') ?>',
    publishes: '<?= lang('lang_analytics_publishes') ?>',
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
};

// Chart objects
let engagementChart = null;
let roleEngagementChart = null;
let adminActivityChart = null;
let userActionsChart = null;

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    initTabs();
    
    // Load initial data
    loadUserEngagementData();
    
    // Add event listeners
    document.getElementById('update-filters').addEventListener('click', function() {
        const activeTab = document.querySelector('.tab.active').dataset.tab;
        
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
        }
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
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
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
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
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
        
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_admin_activity&start_date=${startDate}&end_date=${endDate}`);
        
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
        const startDate = document.getElementById('start-date')?.value || '<?= date('Y-m-d', strtotime('-30 days')) ?>';
        const endDate = document.getElementById('end-date')?.value || '<?= date('Y-m-d') ?>';
        
        console.log(`Fetching content interaction data for date range: ${startDate} to ${endDate}...`);
        
        // Add a timeout to abort the request if it takes too long
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000); // 20-second timeout
        
        try {
            const response = await fetch(
                `<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_user_analytics&start_date=${startDate}&end_date=${endDate}`,
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
                        displayContentInteractionOverview(data.overall_stats || {});
                        displayUserActionsChart(data.daily_activity || []);
                        displayArticleInteractionTable(data.top_articles || []);
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
                throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
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
                avgRating.textContent = stats.avg_star_rating ? stats.avg_star_rating.toFixed(1) + '/5.0' : '0.0/5.0';
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
                    display: true,
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
                    display: true,
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
    
    // Process data for the chart
    const dates = [...new Set(activityData.map(item => item.date))].sort();
    
    const actionTypes = [...new Set(activityData.map(item => item.action_type))];
    const datasets = [];
    
    const colors = {
        'admin_edit': 'rgba(52, 152, 219, 0.7)',
        'admin_publish': 'rgba(46, 204, 113, 0.7)',
        'admin_approve': 'rgba(241, 196, 15, 0.7)'
    };
    
    actionTypes.forEach(actionType => {
        const data = dates.map(date => {
            const entry = activityData.find(item => item.date === date && item.action_type === actionType);
            return entry ? parseInt(entry.count) : 0;
        });
        
        const displayName = actionType.replace('admin_', '');
        
        datasets.push({
            label: USER_ANALYTICS_TRANSLATIONS[displayName] || displayName,
            data: data,
            borderColor: colors[actionType],
            backgroundColor: colors[actionType].replace('0.7', '0.1'),
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
                    display: true,
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
        // Create empty chart to avoid errors
        userActionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: USER_ANALYTICS_TRANSLATIONS.user_actions_over_time
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
                    display: true,
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
            tableData = articles.map(article => {
                if (!article) return null;
                
                const articleId = article.article_id || 0;
                const title = article.title || `Unknown Article #${articleId}`;
                
                return [
                    articleId,
                    `<a href="${APP_URL}public/view_article.php?id=${articleId}" target="_blank">${truncateText(title, 50)}</a>`,
                    formatNumber(article.bookmarks || 0),
                    article.avg_rating ? article.avg_rating.toFixed(1) + '/5.0' : '-',
                    formatNumber(article.useful_yes || 0),
                    formatNumber(article.useful_no || 0),
                    formatNumber(article.comments || 0),
                    formatNumber(article.pdf_saves || 0)
                ];
            }).filter(row => row !== null); // Remove any null rows
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
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
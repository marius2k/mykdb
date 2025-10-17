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
        <div class="tab" data-tab="content-interaction"><?= lang('lang_analytics_content_interaction') ?></div>
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
    ratings: '<?= lang('lang_analytics_star_ratings') ?>',
    useful: '<?= lang('lang_analytics_useful_ratings') ?>',
    comments: '<?= lang('lang_analytics_comments') ?>',
    pdf_saves: '<?= lang('lang_analytics_pdf_saves') ?>',
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
    error_loading_data: '<?= lang('lang_analytics_error_loading_data') ?>'
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
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(`${this.dataset.tab}-tab`).classList.add('active');
            
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
            }
        });
    });
}

// Function to load user engagement data
async function loadUserEngagementData() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const role = document.getElementById('role-filter').value;
    
    try {
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
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=get_user_analytics&start_date=${startDate}&end_date=${endDate}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            displayContentInteractionOverview(data.overall_stats);
            displayUserActionsChart(data.daily_activity);
            displayArticleInteractionTable(data.top_articles);
        } else {
            console.error('Error loading content interaction data:', data.error);
            showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ' ' + data.error);
        }
    } catch (error) {
        console.error('Error loading content interaction data:', error);
        showError(USER_ANALYTICS_TRANSLATIONS.error_loading_data + ' ' + error.message);
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
    document.getElementById('total-bookmarks').textContent = formatNumber(stats.bookmarks || 0);
    document.getElementById('avg-rating').textContent = stats.avg_star_rating ? stats.avg_star_rating.toFixed(1) + '/5.0' : '0.0/5.0';
    
    const usefulYes = stats.useful_yes || 0;
    const usefulNo = stats.useful_no || 0;
    const usefulRatio = usefulYes + usefulNo > 0 ? Math.round(usefulYes / (usefulYes + usefulNo) * 100) : 0;
    
    document.getElementById('useful-ratio').textContent = usefulRatio + '%';
    document.getElementById('total-comments').textContent = formatNumber(stats.comments || 0);
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
    
    // Process data for the chart
    const dates = [...new Set(activityData.map(item => item.date))].sort();
    
    const actionTypes = [...new Set(activityData.map(item => item.action_type))];
    const datasets = [];
    
    const colors = {
        'bookmark': 'rgba(155, 89, 182, 0.7)',
        'star_rating': 'rgba(52, 152, 219, 0.7)',
        'usefulness_rating': 'rgba(46, 204, 113, 0.7)',
        'pdf_save': 'rgba(241, 196, 15, 0.7)',
        'comment': 'rgba(230, 126, 34, 0.7)'
    };
    
    actionTypes.forEach(actionType => {
        if (['bookmark', 'star_rating', 'usefulness_rating', 'pdf_save', 'comment'].includes(actionType)) {
            const data = dates.map(date => {
                const entry = activityData.find(item => item.date === date && item.action_type === actionType);
                return entry ? parseInt(entry.count) : 0;
            });
            
            datasets.push({
                label: USER_ANALYTICS_TRANSLATIONS[actionType.replace('_', '')] || actionType,
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
    // Destroy existing table if it exists
    if ($.fn.DataTable.isDataTable('#articleInteractionTable')) {
        $('#articleInteractionTable').DataTable().destroy();
    }
    
    const tbody = document.querySelector('#articleInteractionTable tbody');
    tbody.innerHTML = '';
    
    articles.forEach(article => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${article.article_id}</td>
            <td><a href="${APP_URL}public/view_article.php?id=${article.article_id}" target="_blank">${truncateText(article.title, 50)}</a></td>
            <td>${formatNumber(article.bookmarks || 0)}</td>
            <td>${article.avg_rating ? article.avg_rating.toFixed(1) + '/5.0' : '-'}</td>
            <td>${formatNumber(article.useful_yes || 0)}</td>
            <td>${formatNumber(article.useful_no || 0)}</td>
            <td>${formatNumber(article.comments || 0)}</td>
            <td>${formatNumber(article.pdf_saves || 0)}</td>
        `;
    });
    
    // Initialize DataTable
    $('#articleInteractionTable').DataTable({
        order: [[2, 'desc']], // Sort by bookmarks by default
        pageLength: 10,
        responsive: true,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
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

// Helper function to calculate retention rate
function calculateRetentionRate(returning, total) {
    if (!total) return 0;
    return Math.round((returning / total) * 100);
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
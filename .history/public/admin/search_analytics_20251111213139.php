<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Permission check
$requiredPermissions = ['view_analytics', 'edit_article'];
if (!hasPermission($_SESSION['user']['id'], $requiredPermissions)) {
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';
    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;
}

if ($_SESSION['user']['role'] === 'guest') {
    header("Location:" . APP_URL . "public/login.php");
    exit;
}

// Date range filtering
$period = isset($_GET['period']) && is_numeric($_GET['period']) ? (int)$_GET['period'] : 7;

?>

<script>
window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
window.USER_ROLE = "<?= $_SESSION['user']['role'] ?>";
</script>

<style>
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
    /*margin: 10px 0 10px 0;*/
}

.metric-card {
    background: white;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 10px solid #3498db;
    transition: transform 0.2s, box-shadow 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.metric-card.searches { border-left-color: rgba(251, 255, 8, 1); }
.metric-card.queries { border-left-color: #e63420ff; }
.metric-card.users { border-left-color: #01a54bff; }
.metric-card.results { border-left-color: #0a4c79ff; }

.metric-card h3 {
    font-size: 1.2em;
    font-weight: normal;
    margin: 1px 0 5px 10px;
    color: #7f8c8d;
}

.metric-value {
    font-size: 2.5em;
    font-weight: bold;
    margin-left: 10px;
    margin-bottom: 0;
    color: #2c3e50;
}

.metric-label {
    font-size: 1em;
    font-weight: normal;
    margin-top: 0;
    margin-left: 10px;
    color: #7f8c8d;
}

.analytics-tabs {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    margin: 20px 0;
    font-size: 1.2em;
}

.analytics-tabs .tab {
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-weight: 200;
    font-size: 0.9em;
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

.section-content {
    display: none;
}

.section-content.active {
    display: block;
}

.analytics-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    /*margin: 20px 0;*/
}

.widget {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.widget h3 {
    margin-top: 0;
    color: #2c3e50;
    padding-bottom: 10px;
}

.period-selector {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.period-selector a {
    padding: 8px 16px;
    background: #ecf0f1;
    border-radius: 4px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s;
}

.period-selector a:hover {
    background: #bdc3c7;
}

.period-selector a.active {
    background: #3498db;
    color: white;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-top: 20px;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
}
</style>

<div class="breadcrumb-container" style="width: 100%; margin-top: 20px;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><?= lang('lang_analytics_admin_label') ?></li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_analytics') ?>
            </li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                <?= lang('lang_search_analytics') ?>
            </li>
        </ol>
    </nav>
</div>
<br>

<div class="container" style="max-width: 1400px; margin: 0 auto;">
   
    <!-- Header with Tabs and Period Filter -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <!-- Section Tabs -->
        <div class="analytics-tabs" style="margin: 0; border-bottom: none; flex: 1;">
            <div class="tab section-tab active" data-section="overview"><?= lang('lang_search_analytics_overview') ?></div>
            <div class="tab section-tab" data-section="queries"><?= lang('lang_search_analytics_search_queries') ?></div>
            <div class="tab section-tab" data-section="performance"><?= lang('lang_search_analytics_performance') ?></div>
            <div class="tab section-tab" data-section="user-behavior"><?= lang('lang_search_analytics_user_behavior') ?></div>
        </div>
        
        <!-- Period Selector Dropdown -->
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="period-selector" style="font-weight: 500; color: #2c3e50; white-space: nowrap;"><?= lang('lang_search_analytics_time_period') ?>:</label>
            <select id="period-selector" class="form-control" style="width: auto; min-width: 150px;">
                <option value="7" selected><?= lang('lang_search_analytics_last_7_days') ?></option>
                <option value="30"><?= lang('lang_search_analytics_last_30_days') ?></option>
                <option value="90"><?= lang('lang_search_analytics_last_90_days') ?></option>
            </select>
        </div>
    </div>
    
    <!-- Overview Section -->
    <div class="section-content active" id="overview-section">
        
        <!-- Metrics Cards -->
        <div class="metrics-grid">
            <div class="metric-card searches">
                <h3><?= lang('lang_search_analytics_total_searches') ?></h3>
                <p class="metric-value" id="total-searches"><?= lang('lang_search_analytics_loading') ?></p>
                <p class="metric-label"><?= lang('lang_search_analytics_in_last_days') ?> <span id="period-label"><?= $period ?></span> <?= lang('lang_search_analytics_days') ?></p>
            </div>
            <div class="metric-card queries">
                <h3><?= lang('lang_search_analytics_unique_queries') ?></h3>
                <p class="metric-value" id="unique-queries"><?= lang('lang_search_analytics_loading') ?></p>
                <p class="metric-label"><?= lang('lang_search_analytics_different_terms') ?></p>
            </div>
            <div class="metric-card users">
                <h3><?= lang('lang_search_analytics_unique_users') ?></h3>
                <p class="metric-value" id="unique-users"><?= lang('lang_search_analytics_loading') ?></p>
                <p class="metric-label"><?= lang('lang_search_analytics_users_searching') ?></p>
            </div>
            <div class="metric-card results">
                <h3><?= lang('lang_search_analytics_avg_results') ?></h3>
                <p class="metric-value" id="avg-results"><?= lang('lang_search_analytics_loading') ?></p>
                <p class="metric-label"><?= lang('lang_search_analytics_results_per_search') ?></p>
            </div>
        </div>
        
        <!-- Search Trends Chart -->
        <div class="widget">
            <h4><img src="<?=APP_URL?>assets/icons/icon-search-over-time.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_search_trends') ?></h4>
            <div id="trends-container">
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- User Search Statistics -->
        <div class="widget">
            <h4><img src="<?=APP_URL?>assets/icons/icon-search-over-time.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_search_activity') ?></h4>
            <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_search_activity_desc') ?></p>
            <div id="user-search-stats-container"><?= lang('lang_search_analytics_loading') ?></div>
        </div>
    </div> <!-- Close overview-section -->
    
    <!-- Search Queries Section -->
    <div class="section-content" id="queries-section">
        <div class="analytics-grid">
            <!-- Top Searches -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-top-search.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_top_searches') ?></h4>
                <div id="top-searches-container"><?= lang('lang_search_analytics_loading') ?></div>
            </div>
            
            <!-- Zero Results Searches -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-no-results.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_no_results') ?></h4>
                <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_no_results_desc') ?></p>
                <div id="zero-results-container"><?= lang('lang_search_analytics_loading') ?></div>
            </div>
        </div>
    </div> <!-- Close queries-section -->
    
    <!-- Performance Section -->
    <div class="section-content" id="performance-section">
        <div class="analytics-grid">
            <!-- Click-Through Rates -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-click-rates.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_click_through_rates') ?></h4>
                <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_ctr_desc') ?></p>
                <div id="ctr-container"><?= lang('lang_search_analytics_loading') ?></div>
            </div>
            
            <!-- Most Clicked Articles -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-most-clicked.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_most_clicked') ?></h4>
                <div id="clicked-articles-container"><?= lang('lang_search_analytics_loading') ?></div>
            </div>
            
            <!-- Click Position Distribution -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-click-position.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_click_position') ?></h4>
                <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_click_position_desc') ?></p>
                <div id="position-container">
                    <div class="chart-container">
                        <canvas id="positionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Close performance-section -->
    
    <!-- User Behavior Section -->
    <div class="section-content" id="user-behavior-section">
        <!-- Search Behavior Metrics Cards -->
        <div class="metrics-grid" style="margin-bottom: 30px;">
            <div class="metric-card" style="border-left-color: #9b59b6;">
                <h3><?= lang('lang_search_analytics_total_searches') ?></h3>
                <p class="metric-value" id="behavior-total-searches">0</p>
                <p class="metric-label"><?= lang('lang_search_analytics_search_queries_metric') ?></p>
            </div>
            <div class="metric-card" style="border-left-color: #3498db;">
                <h3><?= lang('lang_search_analytics_unique_users') ?></h3>
                <p class="metric-value" id="behavior-unique-users">0</p>
                <p class="metric-label"><?= lang('lang_search_analytics_users_searching') ?></p>
            </div>
            <div class="metric-card" style="border-left-color: #2ecc71;">
                <h3><?= lang('lang_search_analytics_click_rate') ?></h3>
                <p class="metric-value" id="behavior-click-rate">0%</p>
                <p class="metric-label"><?= lang('lang_search_analytics_searches_with_clicks') ?></p>
            </div>
            <div class="metric-card" style="border-left-color: #f39c12;">
                <h3><?= lang('lang_search_analytics_avg_position') ?></h3>
                <p class="metric-value" id="behavior-avg-position">0</p>
                <p class="metric-label"><?= lang('lang_search_analytics_clicked_result_position') ?></p>
            </div>
        </div>
        
        <div class="analytics-grid">
            <!-- Top Search Queries by Users -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-popular.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_popular_queries') ?></h4>
                <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_popular_queries_desc') ?></p>
                <div id="user-top-queries-container"><?= lang('lang_search_analytics_loading') ?></div>
            </div>
            
            <!-- Most Clicked Articles by Users -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-most-clicked.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_articles_found') ?></h4>
                <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_articles_found_desc') ?></p>
                <div id="user-clicked-articles-container"><?= lang('lang_search_analytics_loading') ?></div>
            </div>
            
            <!-- Recent Search Activity -->
            <div class="widget">
                <h4><img src="<?=APP_URL?>assets/icons/icon-search-recent.svg" width="40">&nbsp;&nbsp;<?= lang('lang_search_analytics_recent_activity') ?></h4>
                <p style="color: #666; font-size: 0.9em;"><?= lang('lang_search_analytics_recent_activity_desc') ?></p>
                <div id="recent-search-activity-container" style="max-height: 500px; overflow-y: auto;">
                    <p style="color: #999; text-align: center; padding: 20px;"><?= lang('lang_search_analytics_loading') ?></p>
                </div>
            </div>
        </div>
    </div> <!-- Close user-behavior-section -->
    
</div> <!-- Close container -->

<script>
// Translation variables for JavaScript
const TRANSLATIONS = {
    query: '<?= lang('lang_search_analytics_query') ?>',
    count: '<?= lang('lang_search_analytics_count') ?>',
    clicks: '<?= lang('lang_search_analytics_clicks') ?>',
    ctr: '<?= lang('lang_search_analytics_ctr') ?>',
    article: '<?= lang('lang_search_analytics_article') ?>',
    position: '<?= lang('lang_search_analytics_position') ?>',
    searches: '<?= lang('lang_search_analytics_searches') ?>',
    avg_results: '<?= lang('lang_search_analytics_avg_results') ?>',
    unique_users: '<?= lang('lang_search_analytics_unique_users') ?>',
    last_search: '<?= lang('lang_search_analytics_last_search') ?>',
    role: '<?= lang('lang_search_analytics_role') ?>',
    total_searches: '<?= lang('lang_search_analytics_total_searches') ?>',
    unique_queries: '<?= lang('lang_search_analytics_unique_queries_count') ?>',
    articles_clicked: '<?= lang('lang_search_analytics_articles_clicked') ?>',
    avg_position: '<?= lang('lang_search_analytics_avg_position') ?>',
    no_data: '<?= lang('lang_search_analytics_no_data') ?>',
    all_results: '<?= lang('lang_search_analytics_all_results') ?>',
    no_click_data: '<?= lang('lang_search_analytics_no_click_data') ?>',
    no_position_data: '<?= lang('lang_search_analytics_no_position_data') ?>',
    no_queries: '<?= lang('lang_search_analytics_no_queries') ?>',
    no_articles: '<?= lang('lang_search_analytics_no_articles') ?>',
    no_recent: '<?= lang('lang_search_analytics_no_recent') ?>',
    loading: '<?= lang('lang_search_analytics_loading') ?>',
    guest: '<?= lang('lang_search_analytics_guest') ?>',
    
    // Chart labels
    chart_searches: '<?= lang('lang_search_analytics_chart_searches') ?>',
    chart_unique_users: '<?= lang('lang_search_analytics_chart_unique_users') ?>',
    chart_clicks: '<?= lang('lang_search_analytics_chart_clicks') ?>',
    chart_position: '<?= lang('lang_search_analytics_chart_position') ?>',
    
    // Table headers
    table_query: '<?= lang('lang_search_analytics_table_query') ?>',
    table_count: '<?= lang('lang_search_analytics_table_count') ?>',
    table_avg_results: '<?= lang('lang_search_analytics_table_avg_results') ?>',
    table_unique_users: '<?= lang('lang_search_analytics_table_unique_users') ?>',
    table_last_search: '<?= lang('lang_search_analytics_table_last_search') ?>',
    table_searches: '<?= lang('lang_search_analytics_table_searches') ?>',
    table_clicks: '<?= lang('lang_search_analytics_table_clicks') ?>',
    table_ctr: '<?= lang('lang_search_analytics_table_ctr') ?>',
    table_article: '<?= lang('lang_search_analytics_table_article') ?>',
    table_avg_position: '<?= lang('lang_search_analytics_table_avg_position') ?>',
    table_user: '<?= lang('lang_search_analytics_table_user') ?>',
    table_role: '<?= lang('lang_search_analytics_table_role') ?>',
    table_total_searches: '<?= lang('lang_search_analytics_table_total_searches') ?>',
    table_unique_queries: '<?= lang('lang_search_analytics_table_unique_queries') ?>',
    table_articles_clicked: '<?= lang('lang_search_analytics_table_articles_clicked') ?>',
    
    // Activity display
    clicked: '<?= lang('lang_search_analytics_clicked') ?>',
    at_position: '<?= lang('lang_search_analytics_at_position') ?>',
    no_click_recorded: '<?= lang('lang_search_analytics_no_click_recorded') ?>',
    results_shown: '<?= lang('lang_search_analytics_results_shown') ?>',
    
    // DataTables
    dt_search: '<?= lang('lang_datatable_search') ?>',
    dt_show_entries: '<?= lang('lang_datatable_show_entries') ?>',
    dt_showing: '<?= lang('lang_datatable_showing') ?>',
    dt_showing_filtered: '<?= lang('lang_datatable_showing_filtered') ?>',
    dt_empty: '<?= lang('lang_datatable_empty') ?>',
    dt_zero_records: '<?= lang('lang_datatable_zero_records') ?>',
    dt_first: '<?= lang('lang_datatable_first') ?>',
    dt_last: '<?= lang('lang_datatable_last') ?>',
    dt_next: '<?= lang('lang_datatable_next') ?>',
    dt_previous: '<?= lang('lang_datatable_previous') ?>'
};

// Global variables for charts
let trendsChart = null;
let positionChart = null;
let currentPeriod = '7';  // Default period
let currentSection = 'overview';  // Default section
let cachedDashboardData = null;  // Store dashboard data for re-rendering when switching sections

// Initialize tabs
function initTabs() {
    // Handle section tabs
    const sectionTabs = document.querySelectorAll('.section-tab');
    sectionTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all section tabs
            sectionTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all section content
            document.querySelectorAll('.section-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected section
            const sectionId = this.dataset.section + '-section';
            const sectionElement = document.getElementById(sectionId);
            if (sectionElement) {
                sectionElement.classList.add('active');
            }
            
            // Update current section
            currentSection = this.dataset.section;
            
            // If switching to overview and we have cached data, re-render trends chart
            if (currentSection === 'overview' && cachedDashboardData) {
                console.log('Switching to overview, re-rendering trends chart with cached data');
                // Small delay to ensure DOM is ready
                setTimeout(() => {
                    updateTrendsChart(cachedDashboardData.trends);
                }, 100);
            }
            
            // If switching to performance and we have cached data, re-render position chart
            if (currentSection === 'performance' && cachedDashboardData) {
                console.log('Switching to performance, re-rendering position chart with cached data');
                // Small delay to ensure DOM is ready
                setTimeout(() => {
                    updatePositionChart(cachedDashboardData.position_stats);
                }, 100);
            }
            
            // Load data for the new section
            if (currentSection === 'user-behavior') {
                loadUserBehaviorData(currentPeriod);
            } else if (currentSection === 'overview') {
                // Only reload if we don't have cached data
                if (!cachedDashboardData) {
                    loadDashboardData(currentPeriod);
                }
            } else {
                // For other sections, ensure we have dashboard data loaded
                if (!cachedDashboardData) {
                    loadDashboardData(currentPeriod);
                }
            }
        });
    });
    
    // Handle period selector dropdown
    const periodSelector = document.getElementById('period-selector');
    if (periodSelector) {
        periodSelector.addEventListener('change', function() {
            // Get the period from the dropdown
            currentPeriod = this.value;
            
            // Update the period label if it exists
            const periodLabel = document.getElementById('period-label');
            if (periodLabel) {
                periodLabel.textContent = currentPeriod;
            }
            
            // Reload data with new period
            if (currentSection === 'user-behavior') {
                loadUserBehaviorData(currentPeriod);
            } else {
                loadDashboardData(currentPeriod);
            }
        });
    }
}

// Load dashboard data
async function loadDashboardData(period = currentPeriod) {
    try {
        console.log('Fetching dashboard data for period:', period);
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_search_analytics.php?action=get_dashboard_data&period=${period}`, {
            credentials: 'same-origin'  // Include cookies in the request
        });
        console.log('Response status:', response.status);
        
        const result = await response.json();
        console.log('API Result:', result);
        
        if (!result.success) {
            console.error('API returned error:', result.error);
            throw new Error(result.error || 'Failed to load data');
        }
        
        const data = result.data;
        console.log('Dashboard data:', data);
        
        // Cache the dashboard data for later use
        cachedDashboardData = data;
        
        // Update metrics cards
        updateMetrics(data.total_metrics);
        
        // Update tables
        updateTopSearches(data.top_searches);
        updateZeroResults(data.zero_results);
        updateCTRData(data.ctr_data);
        updateClickedArticles(data.top_clicked_articles);
        
        // Update charts based on current section
        if (currentSection === 'overview') {
            updateTrendsChart(data.trends);
        } else if (currentSection === 'performance') {
            updatePositionChart(data.position_stats);
        }
        
        // Update user search statistics
        displayUserSearchStats(data.user_search_stats);
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showError('Failed to load analytics data. Please try again.');
    }
}

function updateMetrics(metrics) {
    document.getElementById('total-searches').textContent = formatNumber(metrics.total_searches || 0);
    document.getElementById('unique-queries').textContent = formatNumber(metrics.unique_queries || 0);
    document.getElementById('unique-users').textContent = formatNumber(metrics.unique_users || 0);
    document.getElementById('avg-results').textContent = parseFloat(metrics.avg_results || 0).toFixed(1);
}

function updateTopSearches(searches) {
    const container = document.getElementById('top-searches-container');
    
    if (!searches || searches.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_data}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#top-searches-table')) {
        $('#top-searches-table').DataTable().destroy();
    }
    
    let html = `
        <table id="top-searches-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>${TRANSLATIONS.table_query}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_count}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_avg_results}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_unique_users}</th>
                    <th>${TRANSLATIONS.table_last_search}</th>
                </tr>
            </thead>
            <tbody>`;
    
    searches.forEach(search => {
        html += `
                <tr>
                    <td><strong>${escapeHtml(search.query)}</strong></td>
                    <td style="text-align:center;"><span class="badge badge-info">${search.search_count}</span></td>
                    <td style="text-align:center;">${parseFloat(search.avg_results).toFixed(1)}</td>
                    <td style="text-align:center;">${search.unique_users}</td>
                    <td>${formatDateTime(search.last_search)}</td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable
    $('#top-searches-table').DataTable({
        order: [[1, 'desc']], // Sort by Count descending
        pageLength: 10,
        responsive: true,
        language: {
            search: TRANSLATIONS.dt_search,
            lengthMenu: TRANSLATIONS.dt_show_entries,
            info: TRANSLATIONS.dt_showing,
            infoFiltered: TRANSLATIONS.dt_showing_filtered,
            emptyTable: TRANSLATIONS.dt_empty,
            zeroRecords: TRANSLATIONS.dt_zero_records,
            paginate: {
                first: TRANSLATIONS.dt_first,
                last: TRANSLATIONS.dt_last,
                next: TRANSLATIONS.dt_next,
                previous: TRANSLATIONS.dt_previous
            }
        }
    });
}

function updateZeroResults(searches) {
    const container = document.getElementById('zero-results-container');
    
    if (!searches || searches.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.all_results}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#zero-results-table')) {
        $('#zero-results-table').DataTable().destroy();
    }
    
    let html = `
        <table id="zero-results-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>${TRANSLATIONS.table_query}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_count}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_unique_users}</th>
                    <th>${TRANSLATIONS.table_last_search}</th>
                </tr>
            </thead>
            <tbody>`;
    
    searches.forEach(search => {
        html += `
                <tr>
                    <td><strong>${escapeHtml(search.query)}</strong></td>
                    <td style="text-align:center;"><span class="badge badge-danger">${search.count}</span></td>
                    <td style="text-align:center;">${search.unique_users}</td>
                    <td>${formatDateTime(search.last_search)}</td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable
    $('#zero-results-table').DataTable({
        order: [[1, 'desc']], // Sort by Count descending
        pageLength: 10,
        responsive: true,
        language: {
            search: TRANSLATIONS.dt_search,
            lengthMenu: TRANSLATIONS.dt_show_entries,
            info: TRANSLATIONS.dt_showing,
            infoFiltered: TRANSLATIONS.dt_showing_filtered,
            emptyTable: TRANSLATIONS.dt_empty,
            zeroRecords: TRANSLATIONS.dt_zero_records,
            paginate: {
                first: TRANSLATIONS.dt_first,
                last: TRANSLATIONS.dt_last,
                next: TRANSLATIONS.dt_next,
                previous: TRANSLATIONS.dt_previous
            }
        }
    });
}

function updateCTRData(ctrData) {
    const container = document.getElementById('ctr-container');
    
    if (!ctrData || ctrData.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_click_data}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#ctr-table')) {
        $('#ctr-table').DataTable().destroy();
    }
    
    let html = `
        <table id="ctr-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>${TRANSLATIONS.table_query}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_searches}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_clicks}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_ctr}</th>
                </tr>
            </thead>
            <tbody>`;
    
    ctrData.forEach(ctr => {
        const ctrValue = parseFloat(ctr.ctr);
        const badgeClass = ctrValue >= 50 ? 'badge-success' : (ctrValue >= 20 ? 'badge-warning' : 'badge-danger');
        
        html += `
                <tr>
                    <td><strong>${escapeHtml(ctr.query)}</strong></td>
                    <td style="text-align:center;">${ctr.total_searches}</td>
                    <td style="text-align:center;">${ctr.total_clicks}</td>
                    <td style="text-align:center;">
                        <span class="badge ${badgeClass}">${ctrValue.toFixed(2)}%</span>
                    </td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable
    $('#ctr-table').DataTable({
        order: [[3, 'desc']], // Sort by CTR descending
        pageLength: 10,
        responsive: true,
        language: {
            search: TRANSLATIONS.dt_search,
            lengthMenu: TRANSLATIONS.dt_show_entries,
            info: TRANSLATIONS.dt_showing,
            infoFiltered: TRANSLATIONS.dt_showing_filtered,
            emptyTable: TRANSLATIONS.dt_empty,
            zeroRecords: TRANSLATIONS.dt_zero_records,
            paginate: {
                first: TRANSLATIONS.dt_first,
                last: TRANSLATIONS.dt_last,
                next: TRANSLATIONS.dt_next,
                previous: TRANSLATIONS.dt_previous
            }
        }
    });
}

function updateClickedArticles(articles) {
    const container = document.getElementById('clicked-articles-container');
    
    if (!articles || articles.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_click_data}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#clicked-articles-table')) {
        $('#clicked-articles-table').DataTable().destroy();
    }
    
    let html = `
        <table id="clicked-articles-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>${TRANSLATIONS.table_article}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_clicks}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_avg_position}</th>
                    <th style="text-align:center;">${TRANSLATIONS.table_unique_users}</th>
                </tr>
            </thead>
            <tbody>`;
    
    articles.forEach(article => {
        const title = article.title || `Article #${article.article_id}`;
        html += `
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>public/view_article.php?id=${article.article_id}" target="_blank">
                            ${escapeHtml(title)}
                        </a>
                    </td>
                    <td style="text-align:center;"><span class="badge badge-success">${article.click_count}</span></td>
                    <td style="text-align:center;">${parseFloat(article.avg_position).toFixed(1)}</td>
                    <td style="text-align:center;">${article.unique_users}</td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable
    $('#clicked-articles-table').DataTable({
        order: [[1, 'desc']], // Sort by Clicks descending
        pageLength: 10,
        responsive: true,
        language: {
            search: TRANSLATIONS.dt_search,
            lengthMenu: TRANSLATIONS.dt_show_entries,
            info: TRANSLATIONS.dt_showing,
            infoFiltered: TRANSLATIONS.dt_showing_filtered,
            emptyTable: TRANSLATIONS.dt_empty,
            zeroRecords: TRANSLATIONS.dt_zero_records,
            paginate: {
                first: TRANSLATIONS.dt_first,
                last: TRANSLATIONS.dt_last,
                next: TRANSLATIONS.dt_next,
                previous: TRANSLATIONS.dt_previous
            }
        }
    });
}

function updateTrendsChart(trends) {
    console.log('updateTrendsChart called with trends:', trends);
    
    // First, let's check the container
    const container = document.getElementById('trends-container');
    console.log('trends-container found:', container);
    
    if (!trends || trends.length === 0) {
        console.log('No trends data available');
        if (container) {
            // Replace with no-data message
            container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_data}</div>`;
        }
        return;
    }
    
    // Ensure we have the chart container with canvas
    if (container && !document.getElementById('trendsChart')) {
        console.log('Recreating canvas element...');
        container.innerHTML = '<div class="chart-container"><canvas id="trendsChart"></canvas></div>';
    }
    
    console.log('Processing trends data, count:', trends.length);
    const labels = trends.map(t => t.date);
    const searches = trends.map(t => parseInt(t.search_count));
    const users = trends.map(t => parseInt(t.unique_users));
    
    console.log('Chart data prepared - labels:', labels, 'searches:', searches, 'users:', users);
    
    if (trendsChart) {
        trendsChart.destroy();
    }
    
    console.log('Looking for canvas element with id "trendsChart"...');
    const canvas = document.getElementById('trendsChart');
    console.log('Canvas element:', canvas);
    console.log('Canvas parent:', canvas ? canvas.parentElement : 'N/A');
    console.log('Canvas visibility:', canvas ? window.getComputedStyle(canvas).display : 'N/A');
    
    if (!canvas) {
        console.error('ERROR: Trends chart canvas not found in DOM!');
        console.log('All canvas elements:', document.querySelectorAll('canvas'));
        return;
    }
    
    console.log('Canvas found, creating chart...');
    const ctx = canvas.getContext('2d');
    trendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: TRANSLATIONS.chart_searches,
                    data: searches,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: TRANSLATIONS.chart_unique_users,
                    data: users,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function updatePositionChart(positionStats) {
    const container = document.getElementById('position-container');
    
    if (!positionStats || positionStats.length === 0) {
        if (container) {
            container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_position_data}</div>`;
        }
        return;
    }
    
    // Ensure we have the chart container with canvas
    if (container && !document.getElementById('positionChart')) {
        console.log('Recreating position canvas element...');
        container.innerHTML = '<div class="chart-container"><canvas id="positionChart"></canvas></div>';
    }
    
    const labels = positionStats.map(p => `Position ${p.position}`);
    const clicks = positionStats.map(p => parseInt(p.clicks));
    
    if (positionChart) {
        positionChart.destroy();
    }
    
    const canvas = document.getElementById('positionChart');
    if (!canvas) {
        console.warn('Position chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    positionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Clicks',
                data: clicks,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}


// Display user search statistics
function displayUserSearchStats(userStats) {
    const container = document.getElementById('user-search-stats-container');
    
    if (!userStats || userStats.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_data}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#user-search-stats-table')) {
        $('#user-search-stats-table').DataTable().destroy();
    }
    
    let html = `
        <table id="user-search-stats-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th style="text-align:center;">Total Searches</th>
                    <th style="text-align:center;">Unique Queries</th>
                    <th style="text-align:center;">Avg Results</th>
                    <th style="text-align:center;">Clicks</th>
                    <th style="text-align:center;">Articles Clicked</th>
                    <th style="text-align:center;">CTR</th>
                </tr>
            </thead>
            <tbody>`;
    
    userStats.forEach(user => {
        const ctr = parseFloat(user.click_through_rate || 0);
        const ctrBadgeClass = ctr >= 50 ? 'badge-success' : (ctr >= 20 ? 'badge-warning' : 'badge-info');
        const roleBadge = user.role ? `<span class="badge badge-info">${escapeHtml(user.role)}</span>` : '<span class="badge" style="background: #95a5a6; color: white;">Guest</span>';
        
        html += `
                <tr>
                    <td><strong>${escapeHtml(user.username)}</strong></td>
                    <td>${roleBadge}</td>
                    <td style="text-align:center;"><span class="badge badge-info">${user.total_searches}</span></td>
                    <td style="text-align:center;">${user.unique_queries}</td>
                    <td style="text-align:center;">${parseFloat(user.avg_results || 0).toFixed(1)}</td>
                    <td style="text-align:center;">${user.total_clicks || 0}</td>
                    <td style="text-align:center;">${user.unique_articles_clicked || 0}</td>
                    <td style="text-align:center;"><span class="badge ${ctrBadgeClass}">${ctr.toFixed(1)}%</span></td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable with same styling as user_analytics.php
    $('#user-search-stats-table').DataTable({
        order: [[2, 'desc']], // Sort by Total Searches column descending
        pageLength: 10,
        responsive: true
    });
}
// Helper functions
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showError(message) {
    alert(message);
}

// Load user behavior data from the backend API
async function loadUserBehaviorData(period = currentPeriod) {
    try {
        console.log('Loading user behavior data for period:', period);
        
        // Calculate date range based on period
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - parseInt(period));
        
        const formatDate = (date) => date.toISOString().split('T')[0];
        
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_user_analytics.php?action=search_behavior&start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}&role=all`, {
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('User behavior response:', result);
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load user behavior data');
        }
        
        const data = result.data || [];
        console.log(`Loaded ${data.length} user behavior records`);
        
        // Process the data
        const processed = processUserBehaviorData(data);
        
        // Update metrics
        displayUserBehaviorMetrics(processed.metrics);
        
        // Display top queries
        displayUserTopQueries(processed.topQueries);
        
        // Display clicked articles
        displayUserClickedArticles(processed.clickedArticles);
        
        // Display recent activity
        displayRecentActivity(processed.recentActivity);
        
        console.log('User behavior data displayed successfully');
        
    } catch (error) {
        console.error('Error loading user behavior data:', error);
        showError('Error loading user behavior data: ' + error.message);
    }
}

// Process user behavior data
function processUserBehaviorData(data) {
    // Calculate metrics
    const totalSearches = data.filter(d => d.query && d.query !== 'N/A').length;
    const uniqueUsers = new Set(data.map(d => d.username)).size;
    const searchesWithClicks = data.filter(d => d.article_id > 0).length;
    const clickRate = totalSearches > 0 ? ((searchesWithClicks / totalSearches) * 100).toFixed(1) : 0;
    
    const positions = data.filter(d => d.position > 0).map(d => d.position);
    const avgPosition = positions.length > 0 ? (positions.reduce((a, b) => a + b, 0) / positions.length).toFixed(1) : 0;
    
    // Group by query
    const queryMap = {};
    data.forEach(d => {
        if (d.query && d.query !== 'N/A') {
            if (!queryMap[d.query]) {
                queryMap[d.query] = {
                    query: d.query,
                    count: 0,
                    clicks: 0,
                    totalResults: 0
                };
            }
            queryMap[d.query].count++;
            queryMap[d.query].totalResults += d.result_count || 0;
            if (d.article_id > 0) queryMap[d.query].clicks++;
        }
    });
    
    const topQueries = Object.values(queryMap)
        .sort((a, b) => b.count - a.count)
        .slice(0, 10)
        .map(q => ({
            ...q,
            avgResults: q.count > 0 ? (q.totalResults / q.count).toFixed(1) : 0,
            ctr: q.count > 0 ? ((q.clicks / q.count) * 100).toFixed(1) : 0
        }));
    
    // Group by article
    const articleMap = {};
    data.forEach((d) => {
        if (d.article_id > 0) {
            if (!articleMap[d.article_id]) {
                articleMap[d.article_id] = {
                    article_id: d.article_id,
                    article_title: d.article_title,
                    clicks: 0,
                    queries: new Set(),
                    positions: []
                };
            }
            articleMap[d.article_id].clicks++;
            if (d.query && d.query !== 'N/A') {
                articleMap[d.article_id].queries.add(d.query);
            }
            if (d.position > 0) {
                articleMap[d.article_id].positions.push(d.position);
            }
        }
    });
    
    const clickedArticles = Object.values(articleMap)
        .sort((a, b) => b.clicks - a.clicks)
        .slice(0, 10)
        .map(a => ({
            ...a,
            uniqueQueries: a.queries.size,
            avgPosition: a.positions.length > 0 ? (a.positions.reduce((x, y) => x + y, 0) / a.positions.length).toFixed(1) : 0
        }));
    
    // Recent activity (last 20 records)
    const recentActivity = data
        .filter(d => d.query && d.query !== 'N/A')
        .slice(0, 20);
    
    return {
        metrics: {
            totalSearches,
            uniqueUsers,
            clickRate,
            avgPosition
        },
        topQueries,
        clickedArticles,
        recentActivity
    };
}

// Display user behavior metrics
function displayUserBehaviorMetrics(metrics) {
    document.getElementById('behavior-total-searches').textContent = formatNumber(metrics.totalSearches);
    document.getElementById('behavior-unique-users').textContent = formatNumber(metrics.uniqueUsers);
    document.getElementById('behavior-click-rate').textContent = metrics.clickRate + '%';
    document.getElementById('behavior-avg-position').textContent = metrics.avgPosition;
}

// Display top queries by users
function displayUserTopQueries(queries) {
    const container = document.getElementById('user-top-queries-container');
    
    if (!queries || queries.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_queries}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#user-top-queries-table')) {
        $('#user-top-queries-table').DataTable().destroy();
    }
    
    let html = `
        <table id="user-top-queries-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>Query</th>
                    <th style="text-align:center;">Searches</th>
                    <th style="text-align:center;">Clicks</th>
                    <th style="text-align:center;">CTR</th>
                    <th style="text-align:center;">Avg Results</th>
                </tr>
            </thead>
            <tbody>`;
    
    queries.forEach(q => {
        const ctrClass = parseFloat(q.ctr) >= 50 ? 'badge-success' : (parseFloat(q.ctr) >= 20 ? 'badge-warning' : 'badge-info');
        html += `
                <tr>
                    <td><strong>${escapeHtml(q.query)}</strong></td>
                    <td style="text-align:center;"><span class="badge badge-info">${q.count}</span></td>
                    <td style="text-align:center;">${q.clicks}</td>
                    <td style="text-align:center;"><span class="badge ${ctrClass}">${q.ctr}%</span></td>
                    <td style="text-align:center;">${q.avgResults}</td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable
    $('#user-top-queries-table').DataTable({
        order: [[1, 'desc']], // Sort by Searches descending
        pageLength: 10,
        responsive: true
    });
}

// Display clicked articles by users
function displayUserClickedArticles(articles) {
    const container = document.getElementById('user-clicked-articles-container');
    
    if (!articles || articles.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_articles}</div>`;
        return;
    }
    
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#user-clicked-articles-table')) {
        $('#user-clicked-articles-table').DataTable().destroy();
    }
    
    let html = `
        <table id="user-clicked-articles-table" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th>Article</th>
                    <th style="text-align:center;">Clicks</th>
                    <th style="text-align:center;">Unique Queries</th>
                    <th style="text-align:center;">Avg Position</th>
                </tr>
            </thead>
            <tbody>`;
    
    articles.forEach(article => {
        html += `
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>public/view_article.php?id=${article.article_id}" target="_blank">
                            ${escapeHtml(article.article_title || 'Article #' + article.article_id)}
                        </a>
                    </td>
                    <td style="text-align:center;"><span class="badge badge-success">${article.clicks}</span></td>
                    <td style="text-align:center;">${article.uniqueQueries}</td>
                    <td style="text-align:center;">${article.avgPosition}</td>
                </tr>`;
    });
    
    html += `
            </tbody>
        </table>`;
    
    container.innerHTML = html;
    
    // Initialize DataTable
    $('#user-clicked-articles-table').DataTable({
        order: [[1, 'desc']], // Sort by Clicks descending
        pageLength: 10,
        responsive: true
    });
}

// Display recent search activity
function displayRecentActivity(activities) {
    const container = document.getElementById('recent-search-activity-container');
    
    if (!activities || activities.length === 0) {
        container.innerHTML = `<div class="no-data">${TRANSLATIONS.no_recent}</div>`;
        return;
    }
    
    let html = '<div style="font-size: 0.9em;">';
    
    activities.forEach(activity => {
        const hasClick = activity.article_id > 0;
        const iconColor = hasClick ? '#2ecc71' : '#95a5a6';
        const icon = hasClick ? '✓' : '○';
        
        html += `
            <div style="padding: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                <div style="color: ${iconColor}; font-size: 1.5em; font-weight: bold; min-width: 20px;">${icon}</div>
                <div style="flex: 1;">
                    <div style="margin-bottom: 5px;">
                        <strong style="color: #2c3e50;">${escapeHtml(activity.query)}</strong>
                        <span style="color: #7f8c8d; font-size: 0.9em; margin-left: 10px;">
                            ${formatDateTime(activity.action_date)}
                        </span>
                    </div>`;
        
        if (hasClick && activity.article_title) {
            html += `
                    <div style="color: #7f8c8d; font-size: 0.9em;">
                        → Clicked: <a href="<?= APP_URL ?>public/view_article.php?id=${activity.article_id}" target="_blank">${escapeHtml(activity.article_title)}</a>
                        ${activity.position > 0 ? `at position #${activity.position}` : ''}
                    </div>`;
        } else {
            html += `
                    <div style="color: #95a5a6; font-size: 0.9em;">
                        No click recorded (${activity.result_count || 0} results shown)
                    </div>`;
        }
        
        html += `
                </div>
            </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial section based on active tab
    const activeTab = document.querySelector('.section-tab.active');
    if (activeTab) {
        currentSection = activeTab.dataset.section;
    }
    console.log('Initial section:', currentSection);
    
    initTabs();
    loadDashboardData();
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

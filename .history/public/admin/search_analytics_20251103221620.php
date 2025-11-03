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
    margin-bottom: 10px;
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
    margin: 20px 0;
}

.widget {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9em;
}

.data-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #dee2e6;
}

.data-table tr:hover {
    background: #f8f9fa;
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
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                Analytics
            </li>
            <li class="breadcrumb-item breadcrumb-separator">
                <img src="<?=APP_URL?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                Search Analytics
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
            <div class="tab section-tab active" data-section="overview">Overview</div>
            <div class="tab section-tab" data-section="queries">Search Queries</div>
            <div class="tab section-tab" data-section="performance">Performance</div>
            <div class="tab section-tab" data-section="user-behavior">User Behavior</div>
        </div>
        
        <!-- Period Selector Dropdown -->
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="period-selector" style="font-weight: 500; color: #2c3e50; white-space: nowrap;">Time Period:</label>
            <select id="period-selector" class="form-control" style="width: auto; min-width: 150px;">
                <option value="7" selected>Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
        </div>
    </div>
    
    <!-- Overview Section -->
    <div class="section-content active" id="overview-section">
        
        <!-- Metrics Cards -->
        <div class="metrics-grid">
            <div class="metric-card searches">
                <h3>Total Searches</h3>
                <p class="metric-value" id="total-searches">Loading...</p>
                <p class="metric-label">in last <span id="period-label"><?= $period ?></span> days</p>
            </div>
            <div class="metric-card queries">
                <h3>Unique Queries</h3>
                <p class="metric-value" id="unique-queries">Loading...</p>
                <p class="metric-label">different search terms</p>
            </div>
            <div class="metric-card users">
                <h3>Unique Users</h3>
                <p class="metric-value" id="unique-users">Loading...</p>
                <p class="metric-label">users searching</p>
            </div>
            <div class="metric-card results">
                <h3>Avg Results</h3>
                <p class="metric-value" id="avg-results">Loading...</p>
                <p class="metric-label">results per search</p>
            </div>
        </div>
        
        <!-- Search Trends Chart -->
        <div class="widget">
            <h3>📈 Search Trends Over Time</h3>
            <div id="trends-container">
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- User Search Statistics -->
        <div class="widget">
            <h3>👥 Search Activity by User</h3>
            <p style="color: #666; font-size: 0.9em;">Detailed search statistics for each user</p>
            <div id="user-search-stats-container">Loading...</div>
        </div>
    </div> <!-- Close overview-section -->
    
    <!-- Search Queries Section -->
    <div class="section-content" id="queries-section">
        <div class="analytics-grid">
            <!-- Top Searches -->
            <div class="widget">
                <h3>🔥 Top Searches</h3>
                <div id="top-searches-container">Loading...</div>
            </div>
            
            <!-- Zero Results Searches -->
            <div class="widget">
                <h3>⚠️ Searches with No Results</h3>
                <p style="color: #666; font-size: 0.9em;">These searches returned zero results - opportunity to improve content</p>
                <div id="zero-results-container">Loading...</div>
            </div>
        </div>
    </div> <!-- Close queries-section -->
    
    <!-- Performance Section -->
    <div class="section-content" id="performance-section">
        <div class="analytics-grid">
            <!-- Click-Through Rates -->
            <div class="widget">
                <h3>📊 Click-Through Rates</h3>
                <p style="color: #666; font-size: 0.9em;">Percentage of searches that resulted in clicks (minimum 3 searches)</p>
                <div id="ctr-container">Loading...</div>
            </div>
            
            <!-- Most Clicked Articles -->
            <div class="widget">
                <h3>🎯 Most Clicked Articles from Search</h3>
                <div id="clicked-articles-container">Loading...</div>
            </div>
            
            <!-- Click Position Distribution -->
            <div class="widget">
                <h3>📍 Click Position Distribution</h3>
                <p style="color: #666; font-size: 0.9em;">Which positions in search results get clicked most</p>
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
                <h3>Total Searches</h3>
                <p class="metric-value" id="behavior-total-searches">0</p>
                <p class="metric-label">search queries</p>
            </div>
            <div class="metric-card" style="border-left-color: #3498db;">
                <h3>Unique Users</h3>
                <p class="metric-value" id="behavior-unique-users">0</p>
                <p class="metric-label">users searching</p>
            </div>
            <div class="metric-card" style="border-left-color: #2ecc71;">
                <h3>Click Rate</h3>
                <p class="metric-value" id="behavior-click-rate">0%</p>
                <p class="metric-label">searches with clicks</p>
            </div>
            <div class="metric-card" style="border-left-color: #f39c12;">
                <h3>Avg Position</h3>
                <p class="metric-value" id="behavior-avg-position">0</p>
                <p class="metric-label">clicked result position</p>
            </div>
        </div>
        
        <div class="analytics-grid">
            <!-- Top Search Queries by Users -->
            <div class="widget">
                <h3>🔍 Popular Search Queries</h3>
                <p style="color: #666; font-size: 0.9em;">Most frequently searched terms by users</p>
                <div id="user-top-queries-container">Loading...</div>
            </div>
            
            <!-- Most Clicked Articles by Users -->
            <div class="widget">
                <h3>🎯 Articles Found Through Search</h3>
                <p style="color: #666; font-size: 0.9em;">Articles that users click on from search results</p>
                <div id="user-clicked-articles-container">Loading...</div>
            </div>
            
            <!-- Recent Search Activity -->
            <div class="widget">
                <h3>📊 Recent Search Activity</h3>
                <p style="color: #666; font-size: 0.9em;">Latest search queries and user interactions</p>
                <div id="recent-search-activity-container" style="max-height: 500px; overflow-y: auto;">
                    <p style="color: #999; text-align: center; padding: 20px;">Loading...</p>
                </div>
            </div>
        </div>
    </div> <!-- Close user-behavior-section -->
    
</div> <!-- Close container -->

<script>
// Global variables for charts
let trendsChart = null;
let positionChart = null;
let currentPeriod = '7';  // Default period
let currentSection = 'overview';  // Default section

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
            
            // Load data for the new section
            if (currentSection === 'user-behavior') {
                loadUserBehaviorData(currentPeriod);
            } else {
                loadDashboardData(currentPeriod);
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
        
        // Update metrics cards
        updateMetrics(data.total_metrics);
        
        // Update tables
        updateTopSearches(data.top_searches);
        updateZeroResults(data.zero_results);
        updateCTRData(data.ctr_data);
        updateClickedArticles(data.top_clicked_articles);
        
        // Update charts
        updateTrendsChart(data.trends);
        updatePositionChart(data.position_stats);
        
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
        container.innerHTML = '<div class="no-data">No search data available</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Query</th>
                    <th style="text-align:center;">Count</th>
                    <th style="text-align:center;">Avg Results</th>
                    <th style="text-align:center;">Unique Users</th>
                    <th>Last Search</th>
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
}

function updateZeroResults(searches) {
    const container = document.getElementById('zero-results-container');
    
    if (!searches || searches.length === 0) {
        container.innerHTML = '<div class="no-data">All searches returned results! 🎉</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Query</th>
                    <th style="text-align:center;">Count</th>
                    <th style="text-align:center;">Unique Users</th>
                    <th>Last Search</th>
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
}

function updateCTRData(ctrData) {
    const container = document.getElementById('ctr-container');
    
    if (!ctrData || ctrData.length === 0) {
        container.innerHTML = '<div class="no-data">No click data available</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Query</th>
                    <th style="text-align:center;">Searches</th>
                    <th style="text-align:center;">Clicks</th>
                    <th style="text-align:center;">CTR</th>
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
}

function updateClickedArticles(articles) {
    const container = document.getElementById('clicked-articles-container');
    
    if (!articles || articles.length === 0) {
        container.innerHTML = '<div class="no-data">No click data available</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th style="text-align:center;">Clicks</th>
                    <th style="text-align:center;">Avg Position</th>
                    <th style="text-align:center;">Unique Users</th>
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
}

function updateTrendsChart(trends) {
    if (!trends || trends.length === 0) {
        document.getElementById('trends-container').innerHTML = '<div class="no-data">No search data available for this period</div>';
        return;
    }
    
    const labels = trends.map(t => t.date);
    const searches = trends.map(t => parseInt(t.search_count));
    const users = trends.map(t => parseInt(t.unique_users));
    
    if (trendsChart) {
        trendsChart.destroy();
    }
    
    const ctx = document.getElementById('trendsChart').getContext('2d');
    trendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Searches',
                    data: searches,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Unique Users',
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
    if (!positionStats || positionStats.length === 0) {
        document.getElementById('position-container').innerHTML = '<div class="no-data">No position data available</div>';
        return;
    }
    
    const labels = positionStats.map(p => `Position ${p.position}`);
    const clicks = positionStats.map(p => parseInt(p.clicks));
    
    if (positionChart) {
        positionChart.destroy();
    }
    
    const ctx = document.getElementById('positionChart').getContext('2d');
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
        container.innerHTML = '<div class="no-data">No user search data available</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
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
    data.forEach(d => {
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
        container.innerHTML = '<div class="no-data">No search queries available</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
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
}

// Display clicked articles by users
function displayUserClickedArticles(articles) {
    const container = document.getElementById('user-clicked-articles-container');
    
    if (!articles || articles.length === 0) {
        container.innerHTML = '<div class="no-data">No clicked articles available</div>';
        return;
    }
    
    let html = `
        <table class="data-table">
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
}

// Display recent search activity
function displayRecentActivity(activities) {
    const container = document.getElementById('recent-search-activity-container');
    
    if (!activities || activities.length === 0) {
        container.innerHTML = '<div class="no-data">No recent activity available</div>';
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
    initTabs();
    loadDashboardData();
});
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

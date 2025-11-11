<?php
require_once '../../config/bootstrap.php';

// Verifică permisiuni ÎNAINTE de a include header.php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'moderator', 'editor', 'superadmin'])) {
    header('Location: ' . APP_URL . 'public/login.php');
    exit;
}

include APP_ROOT . 'includes/header.php';

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

.stat-card.views { border-left-color: rgba(251, 255, 8, 1); }
.stat-card.likes { border-left-color: #e63420ff; }
.stat-card.reading { border-left-color: #01a54bff; }
.stat-card.engagement { border-left-color: #0a4c79ff; }

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
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-x: auto;
}

#article-details {
    display: none;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #7f8c8d;
}

.error {
    background: #e74c3c;
    color: white;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}

.success {
    background: #2ecc71;
    color: white;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}

/* Styling for clickable article titles */
.clickable-article-title {
    color: #3498db !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: color 0.3s ease;
}

.clickable-article-title:hover {
    color: #2980b9 !important;
    text-decoration: underline !important;
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
                <?= lang('lang_articles') ?>
            </li>
        </ol>
    </nav>
</div>
<div class="analytics-container">
    
    <!-- Period Selector -->
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; justify-content: flex-end;">
        <label for="period-selector" style="font-weight: 500; color: #2c3e50; white-space: nowrap;"><?= lang('lang_analytics_time_period') ?></label>
        <select id="period-selector" class="form-control" style="width: auto; min-width: 150px;">
            <option value="7" selected><?= lang('lang_analytics_last_7_days') ?></option>
            <option value="30"><?= lang('lang_analytics_last_30_days') ?></option>
            <option value="90"><?= lang('lang_analytics_last_90_days') ?></option>
        </select>
    </div>
    <br>
    <!-- Statistici generale -->
    <div class="stats-grid" id="weekly-stats">
        <!-- Se populează dinamic -->
    </div>
    
    
    
    <!-- Grafic performanță -->
    <div class="chart-container">
        <h3><img src="<?=APP_URL?>assets/icons/icon-performance.svg" width="40"> <?= lang('lang_analytics_top_articles_performance') ?></h3>
        <br>
        <canvas id="performanceChart" width="400" height="200"></canvas>
    </div>
    
    <!-- Tabel articole -->
    <div class="table-container">
        <h3><img src="<?=APP_URL?>assets/icons/icon-analytics-detailed.svg" width="40"> <?= lang('lang_analytics_top_articles_detailed') ?></h3>
        <br>
        <table id="articlesTable" class="articles-table" style="font-size: 0.85em; width:100%">
            <thead>
                <tr>
                    <th><?= lang('lang_analytics_id') ?></th>
                    <th><?= lang('lang_analytics_title') ?></th>
                    <th><?= lang('lang_analytics_author') ?></th>
                    <th><?= lang('lang_analytics_public_views') ?></th>
                    <th><?= lang('lang_analytics_admin_views') ?></th>
                    <th><?= lang('lang_analytics_likes') ?></th>
                    <th><?= lang('lang_analytics_avg_public_reading_time') ?></th>
                    <th><?= lang('lang_analytics_scroll_avg') ?></th>
                    <th><?= lang('lang_analytics_unique_readers') ?></th>
                    <th><?= lang('lang_analytics_engagement') ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Se populează dinamic -->
            </tbody>
        </table>
    </div>
    <br>
    <!-- Detalii articol specific -->
    <div id="article-details" class="chart-container">
        <h3><img src="<?=APP_URL?>assets/icons/icon-article-details.svg" width="40"> <?= lang('lang_analytics_article_details') ?></h3>
        <br>
        <div id="article-stats"></div>
        <br>
        <canvas id="articleChart" width="400" height="200"></canvas>
    </div>

</div>



<script>
// Translation variables for JavaScript
const TRANSLATIONS = {
    views_this_week: '<?= lang('lang_analytics_views_this_week') ?>',
    likes_this_week: '<?= lang('lang_analytics_likes_this_week') ?>',
    avg_reading_time: '<?= lang('lang_analytics_avg_reading_time') ?>',
    reading_sessions: '<?= lang('lang_analytics_reading_sessions') ?>',
    views: '<?= lang('lang_analytics_views') ?>',
    likes: '<?= lang('lang_analytics_likes') ?>',
    reading_time_seconds: '<?= lang('lang_analytics_reading_time_seconds') ?>',
    top_10_comparison: '<?= lang('lang_analytics_top_10_comparison') ?>',
    total_views: '<?= lang('lang_analytics_total_views') ?>',
    total_likes: '<?= lang('lang_analytics_total_likes') ?>',
    avg_reading_time_label: '<?= lang('lang_analytics_avg_reading_time_label') ?>',
    avg_scroll: '<?= lang('lang_analytics_avg_scroll') ?>',
    daily_views: '<?= lang('lang_analytics_daily_views') ?>',
    reading_sessions_daily: '<?= lang('lang_analytics_reading_sessions_daily') ?>',
    activity_7_days: '<?= lang('lang_analytics_activity_7_days') ?>',
    error_loading_dashboard: '<?= lang('lang_analytics_error_loading_dashboard') ?>',
    error_loading_articles: '<?= lang('lang_analytics_error_loading_articles') ?>',
    connection_error: '<?= lang('lang_analytics_connection_error') ?>',
    enter_article_id_alert: '<?= lang('lang_analytics_enter_article_id_alert') ?>',
    no_data_found: '<?= lang('lang_analytics_no_data_found') ?>',
    article_not_found: '<?= lang('lang_analytics_article_not_found') ?>',
    error_loading_data: '<?= lang('lang_analytics_error_loading_data') ?>',
    
    // Public/Admin view tracking translations
    public_views_this_week: '<?= lang('lang_analytics_public_views_this_week') ?>',
    avg_public_reading_time: '<?= lang('lang_analytics_avg_public_reading_time') ?>',
    public_reading_sessions: '<?= lang('lang_analytics_public_reading_sessions') ?>',
    public_views: '<?= lang('lang_analytics_public_views') ?>',
    admin_views: '<?= lang('lang_analytics_admin_views') ?>',
    public_reading_time_seconds: '<?= lang('lang_analytics_public_reading_time_seconds') ?>',
    admin_label: '<?= lang('lang_analytics_admin_label') ?>'
};

// Variabile globale
let performanceChart = null;
let articleChart = null;
let currentPeriod = '7'; // Default period

// Încarcă datele când pagina este gata
document.addEventListener('DOMContentLoaded', function() {
    initPeriodSelector();
    loadDashboardData(currentPeriod);
    loadTopArticles(currentPeriod);
});

// Initialize period selector
function initPeriodSelector() {
    const periodSelector = document.getElementById('period-selector');
    periodSelector.addEventListener('change', function() {
        currentPeriod = this.value;
        loadDashboardData(currentPeriod);
        loadTopArticles(currentPeriod);
    });
}

// Funcție pentru încărcarea dashboard-ului principal
async function loadDashboardData(period = '7') {
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_article_reading_analytics.php?action=get_analytics_dashboard&period=${period}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            displayWeeklyStats(data.weekly_stats, period);
            displayPerformanceChart(data.top_articles);
        } else {
            console.error('Eroare la încărcarea dashboard-ului:', data.error);
            showError(TRANSLATIONS.error_loading_dashboard + ' ' + data.error);
        }
    } catch (error) {
        console.error('Eroare la încărcarea dashboard-ului:', error);
        showError(TRANSLATIONS.connection_error + ' ' + error.message);
    }
}

// Funcție pentru încărcarea top articolelor
async function loadTopArticles(period = '7') {
    // Use default values instead of reading from removed controls
    const sortBy = 'engagement'; // Default sort by engagement
    const limit = 25; // Default limit
    
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_article_reading_analytics.php?action=get_combined_stats&order_by=${sortBy}&limit=${limit}&period=${period}`);
        const data = await response.json();
        
        if (data.success) {
            displayTopArticlesTable(data.articles);
        } else {
            console.error('Eroare la încărcarea top articole:', data.error);
            showError(TRANSLATIONS.error_loading_articles + ' ' + data.error);
        }
    } catch (error) {
        console.error('Eroare la încărcarea top articole:', error);
        showError(TRANSLATIONS.connection_error);
    }
}

// Funcție pentru analiza unui articol specific prin click
async function loadArticleDetailsByClick(articleId) {
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_article_reading_analytics.php?action=get_article_analytics&article_id=${articleId}&period=${currentPeriod}`);
        const data = await response.json();
        
        if (data.success) {
            displayArticleDetails(data.stats, data.daily_views, data.daily_reading);
            document.getElementById('article-details').style.display = 'block';
            
            // Scroll to the article details section
            document.getElementById('article-details').scrollIntoView({ 
                behavior: 'smooth' 
            });
        } else {
            alert(TRANSLATIONS.no_data_found + ' ' + (data.error || TRANSLATIONS.article_not_found));
        }
    } catch (error) {
        console.error('Eroare la încărcarea detaliilor articolului:', error);
        alert(TRANSLATIONS.error_loading_data);
    }
}

// Funcție pentru afișarea statisticilor săptămânale
function displayWeeklyStats(stats, period = '7') {
    //const periodLabel = period === '7' ? 'this week' : `last ${period} days`;
    const container = document.getElementById('weekly-stats');
    container.innerHTML = `
        <div class="stat-card views">
            <div class="stat-value">${formatNumber(stats.weekly_public_views || 0)}</div>
            <div class="stat-label">${TRANSLATIONS.public_views}</div>
            <div class="stat-sublabel" style="font-size: 1em; color: #95a5a6; margin-top: 2px; margin-left: 10px;">
                ${TRANSLATIONS.admin_label}: ${formatNumber(stats.weekly_admin_views || 0)}
            </div>
        </div>
        <div class="stat-card likes">
            <div class="stat-value">${formatNumber(stats.weekly_likes || 0)}</div>
            <div class="stat-label">${TRANSLATIONS.likes}</div>
        </div>
        <div class="stat-card reading">
            <div class="stat-value">${formatTime(stats.avg_weekly_public_reading_time || 0)}</div>
            <div class="stat-label">${TRANSLATIONS.avg_public_reading_time}</div>
            <div class="stat-sublabel" style="font-size: 1em; color: #95a5a6; margin-top: 2px; margin-left: 10px;">
                ${TRANSLATIONS.admin_label}: ${formatTime(stats.avg_weekly_admin_reading_time || 0)}
            </div>
        </div>
        <div class="stat-card engagement">
            <div class="stat-value">${formatNumber(stats.weekly_public_reading_sessions || 0)}</div>
            <div class="stat-label">${TRANSLATIONS.reading_sessions}</div>
            <div class="stat-sublabel" style="font-size: 1em; color: #95a5a6; margin-top: 2px; margin-left: 10px;">
                ${TRANSLATIONS.admin_label}: ${formatNumber(stats.weekly_admin_reading_sessions || 0)}
            </div>
        </div>
    `;
}

// Funcție pentru afișarea graficului de performanță
function displayPerformanceChart(articles) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    if (performanceChart) {
        performanceChart.destroy();
    }
    
    const labels = articles.slice(0, 10).map(a => truncateText(a.title, 30));
    const publicViewsData = articles.slice(0, 10).map(a => a.public_views || 0);
    const adminViewsData = articles.slice(0, 10).map(a => a.admin_views || 0);
    const likesData = articles.slice(0, 10).map(a => a.total_likes || 0);
    const publicReadingData = articles.slice(0, 10).map(a => Math.round((a.avg_public_reading_time || 0) / 1000));
    
    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: TRANSLATIONS.public_views,
                    data: publicViewsData,
                    backgroundColor: 'rgba(1, 165, 75, 0.7)',
                    borderColor: 'rgba(1, 165, 75, 1)',
                    borderWidth: 1
                },
                {
                    label: TRANSLATIONS.admin_views,
                    data: adminViewsData,
                    backgroundColor: 'rgba(1, 165, 75, 0.4)',
                    borderColor: 'rgba(1, 165, 75, 0.8)',
                    borderWidth: 1
                },
                {
                    label: TRANSLATIONS.likes,
                    data: likesData,
                    backgroundColor: 'rgba(230, 52, 32, 0.7)',
                    borderColor: 'rgba(230, 52, 32, 1)',
                    borderWidth: 1
                },
                {
                    label: TRANSLATIONS.public_reading_time_seconds,
                    data: publicReadingData,
                    backgroundColor: 'rgba(1, 79, 130, 0.7)',
                    borderColor: 'rgba(4, 79, 130, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: TRANSLATIONS.top_10_comparison
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
}

// Funcție pentru afișarea tabelului cu articole
function displayTopArticlesTable(articles) {
    // Distruge tabelul existent dacă există
    if ($.fn.DataTable.isDataTable('#articlesTable')) {
        $('#articlesTable').DataTable().destroy();
    }
    
    const tbody = document.querySelector('#articlesTable tbody');
    tbody.innerHTML = '';
    
    articles.forEach(article => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${article.id}</td>
            <td><a href="#" onclick="loadArticleDetailsByClick(${article.id}); return false;" class="clickable-article-title" title="Click to view analytics for this article">${truncateText(article.title, 50)}</a></td>
            <td>${article.author || 'N/A'}</td>
            <td>${formatNumber(article.public_views || 0)}</td>
            <td>${formatNumber(article.admin_views || 0)}</td>
            <td>${formatNumber(article.total_likes || 0)}</td>
            <td>${formatTime(article.avg_public_reading_time || 0)}</td>
            <td>${formatPercentage(article.avg_scroll_percentage || 0)}</td>
            <td>${formatNumber(article.unique_readers || 0)}</td>
            <td>${formatNumber(article.engagement_score || 0)}</td>
        `;
    });
    
    // Reinițializează DataTable
    $('#articlesTable').DataTable({
        order: [[9, 'desc']], // Sortează după engagement score (updated column index)
        pageLength: 10,
        responsive: true,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/<?= $lang ?>.json"
        }
    });
}

// Funcție pentru afișarea detaliilor unui articol
function displayArticleDetails(stats, dailyViews, dailyReading) {
    const container = document.getElementById('article-stats');
    container.innerHTML = `
        <h4>${stats.title}</h4>
        <br>
        <div class="stats-grid">
            <div class="stat-card views">
                <div class="stat-value">${formatNumber(stats.total_views || 0)}</div>
                <div class="stat-label">${TRANSLATIONS.total_views}</div>
            </div>
            <div class="stat-card likes">
                <div class="stat-value">${formatNumber(stats.total_likes || 0)}</div>
                <div class="stat-label">${TRANSLATIONS.total_likes}</div>
            </div>
            <div class="stat-card reading">
                <div class="stat-value">${formatTime(stats.avg_reading_time || 0)}</div>
                <div class="stat-label">${TRANSLATIONS.avg_reading_time_label}</div>
            </div>
            <div class="stat-card engagement">
                <div class="stat-value">${formatPercentage(stats.avg_scroll_percentage || 0)}</div>
                <div class="stat-label">${TRANSLATIONS.avg_scroll}</div>
            </div>
        </div>
    `;
    
    // Creează graficul pentru ultimele 7 zile
    displayArticleChart(dailyViews, dailyReading);
}

// Funcție pentru graficul unui articol specific
function displayArticleChart(dailyViews, dailyReading) {
    const ctx = document.getElementById('articleChart').getContext('2d');
    
    if (articleChart) {
        articleChart.destroy();
    }
    
    // Pregătește datele pentru ultimele 7 zile
    const last7Days = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        last7Days.push(date.toISOString().split('T')[0]);
    }
    
    const viewsData = last7Days.map(date => {
        const dayData = dailyViews.find(d => d.date === date);
        return dayData ? parseInt(dayData.daily_views) : 0;
    });
    
    const readingData = last7Days.map(date => {
        const dayData = dailyReading.find(d => d.date === date);
        return dayData ? parseInt(dayData.daily_reading_sessions) : 0;
    });
    
    const labels = last7Days.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('ro-RO', { weekday: 'short', day: 'numeric' });
    });
    
    articleChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: TRANSLATIONS.daily_views,
                    data: viewsData,
                    borderColor: 'rgba(46, 204, 113, 1)',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4
                },
                {
                    label: TRANSLATIONS.reading_sessions_daily,
                    data: readingData,
                    borderColor: 'rgba(243, 156, 18, 1)',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: TRANSLATIONS.activity_7_days
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

// Funcții helper pentru formatare
function formatNumber(num) {
    if (num == null) return '0';
    return new Intl.NumberFormat('ro-RO').format(num);
}

function formatTime(milliseconds) {
    if (!milliseconds || milliseconds === 0) return '0s';
    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    
    if (minutes > 0) {
        return `${minutes}m ${remainingSeconds}s`;
    }
    return `${remainingSeconds}s`;
}

function formatPercentage(num) {
    if (num == null) return '0%';
    return Math.round(num) + '%';
}

function truncateText(text, maxLength) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error';
    errorDiv.textContent = message;
    document.querySelector('.analytics-container').insertBefore(errorDiv, document.querySelector('.analytics-container').firstChild);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success';
    successDiv.textContent = message;
    document.querySelector('.analytics-container').insertBefore(successDiv, document.querySelector('.analytics-container').firstChild);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

// Verifică permisiuni
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'moderator', 'superadmin'])) {
    header('Location: ' . APP_URL . 'public/login.php');
    exit;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<style>
.analytics-container {
    padding: 20px;
    max-width: 1200px;
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
    border-left: 4px solid #3498db;
}

.stat-card.views { border-left-color: #2ecc71; }
.stat-card.likes { border-left-color: #e74c3c; }
.stat-card.reading { border-left-color: #f39c12; }
.stat-card.engagement { border-left-color: #9b59b6; }

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9em;
    margin-top: 5px;
}

.chart-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.controls {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.controls .form-group {
    display: inline-block;
    margin-right: 20px;
}

.controls label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.controls select, .controls input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.controls button {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.controls button:hover {
    background: #2980b9;
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
</style>

<div class="analytics-container">
    <h1>📊 <?= lang('lang_analytics_dashboard') ?></h1>
    
    <!-- Statistici generale -->
    <div class="stats-grid" id="weekly-stats">
        <!-- Se populează dinamic -->
    </div>
    
    <!-- Controale -->
    <div class="controls">
        <div class="form-group">
            <label for="sortBy"><?= lang('lang_analytics_sort_by') ?></label>
            <select id="sortBy" onchange="loadTopArticles()">
                <option value="views"><?= lang('lang_analytics_views') ?></option>
                <option value="likes"><?= lang('lang_analytics_likes') ?></option>
                <option value="reading_time"><?= lang('lang_analytics_reading_time') ?></option>
                <option value="engagement"><?= lang('lang_analytics_total_engagement') ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="limitSelect"><?= lang('lang_analytics_number_articles') ?></label>
            <select id="limitSelect" onchange="loadTopArticles()">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="articleIdInput"><?= lang('lang_analytics_specific_article_id') ?></label>
            <input type="number" id="articleIdInput" placeholder="<?= lang('lang_analytics_enter_article_id') ?>">
            <button onclick="loadArticleDetails()"><?= lang('lang_analytics_analyze') ?></button>
        </div>
    </div>
    
    <!-- Grafic performanță -->
    <div class="chart-container">
        <h3>📈 <?= lang('lang_analytics_top_articles_performance') ?></h3>
        <canvas id="performanceChart" width="400" height="200"></canvas>
    </div>
    
    <!-- Tabel articole -->
    <div class="table-container">
        <h3>📋 <?= lang('lang_analytics_top_articles_detailed') ?></h3>
        <table id="articlesTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th><?= lang('lang_analytics_id') ?></th>
                    <th><?= lang('lang_analytics_title') ?></th>
                    <th><?= lang('lang_analytics_author') ?></th>
                    <th><?= lang('lang_analytics_views') ?></th>
                    <th><?= lang('lang_analytics_likes') ?></th>
                    <th><?= lang('lang_analytics_avg_reading') ?></th>
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
    
    <!-- Detalii articol specific -->
    <div id="article-details" class="chart-container">
        <h3>📝 <?= lang('lang_analytics_article_details') ?></h3>
        <div id="article-stats"></div>
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
    error_loading_data: '<?= lang('lang_analytics_error_loading_data') ?>'
};

// Variabile globale
let performanceChart = null;
let articleChart = null;

// Încarcă datele când pagina este gata
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadTopArticles();
});

// Funcție pentru încărcarea dashboard-ului principal
async function loadDashboardData() {
    try {
        const response = await fetch('<?= APP_URL ?>public/api/bkd_article_reading_analytics.php?action=get_analytics_dashboard');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            displayWeeklyStats(data.weekly_stats);
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
async function loadTopArticles() {
    const sortBy = document.getElementById('sortBy').value;
    const limit = document.getElementById('limitSelect').value;
    
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_article_reading_analytics.php?action=get_combined_stats&order_by=${sortBy}&limit=${limit}`);
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

// Funcție pentru analiza unui articol specific
async function loadArticleDetails() {
    const articleId = document.getElementById('articleIdInput').value;
    
    if (!articleId) {
        alert(TRANSLATIONS.enter_article_id_alert);
        return;
    }
    
    try {
        const response = await fetch(`<?= APP_URL ?>public/api/bkd_article_reading_analytics.php?action=get_article_analytics&article_id=${articleId}`);
        const data = await response.json();
        
        if (data.success) {
            displayArticleDetails(data.stats, data.daily_views, data.daily_reading);
            document.getElementById('article-details').style.display = 'block';
        } else {
            alert('Nu s-au găsit date pentru acest articol: ' + (data.error || 'Articol inexistent'));
        }
    } catch (error) {
        console.error('Eroare la încărcarea detaliilor articolului:', error);
        alert('Eroare la încărcarea datelor');
    }
}

// Funcție pentru afișarea statisticilor săptămânale
function displayWeeklyStats(stats) {
    const container = document.getElementById('weekly-stats');
    container.innerHTML = `
        <div class="stat-card views">
            <div class="stat-value">${formatNumber(stats.weekly_views || 0)}</div>
            <div class="stat-label">👁️ Views săptămâna aceasta</div>
        </div>
        <div class="stat-card likes">
            <div class="stat-value">${formatNumber(stats.weekly_likes || 0)}</div>
            <div class="stat-label">❤️ Likes săptămâna aceasta</div>
        </div>
        <div class="stat-card reading">
            <div class="stat-value">${formatTime(stats.avg_weekly_reading_time || 0)}</div>
            <div class="stat-label">📚 Timp mediu de citire</div>
        </div>
        <div class="stat-card engagement">
            <div class="stat-value">${formatNumber(stats.weekly_reading_sessions || 0)}</div>
            <div class="stat-label">🔥 Sesiuni de citire</div>
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
    const viewsData = articles.slice(0, 10).map(a => a.total_views || 0);
    const likesData = articles.slice(0, 10).map(a => a.total_likes || 0);
    const readingData = articles.slice(0, 10).map(a => Math.round((a.avg_reading_time || 0) / 1000));
    
    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Views',
                    data: viewsData,
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Likes',
                    data: likesData,
                    backgroundColor: 'rgba(231, 76, 60, 0.7)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Timp citire (secunde)',
                    data: readingData,
                    backgroundColor: 'rgba(243, 156, 18, 0.7)',
                    borderColor: 'rgba(243, 156, 18, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Top 10 Articole - Comparație Metrici'
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
            <td><a href="<?= APP_URL ?>public/view_article.php?id=${article.id}" target="_blank">${truncateText(article.title, 50)}</a></td>
            <td>${article.author || 'N/A'}</td>
            <td>${formatNumber(article.total_views || 0)}</td>
            <td>${formatNumber(article.total_likes || 0)}</td>
            <td>${formatTime(article.avg_reading_time || 0)}</td>
            <td>${formatPercentage(article.avg_scroll_percentage || 0)}</td>
            <td>${formatNumber(article.unique_readers || 0)}</td>
            <td>${formatNumber(article.engagement_score || 0)}</td>
        `;
    });
    
    // Reinițializează DataTable
    $('#articlesTable').DataTable({
        order: [[8, 'desc']], // Sortează după engagement score
        pageLength: 25,
        responsive: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ro.json'
        }
    });
}

// Funcție pentru afișarea detaliilor unui articol
function displayArticleDetails(stats, dailyViews, dailyReading) {
    const container = document.getElementById('article-stats');
    container.innerHTML = `
        <h4>${stats.title}</h4>
        <div class="stats-grid">
            <div class="stat-card views">
                <div class="stat-value">${formatNumber(stats.total_views || 0)}</div>
                <div class="stat-label">👁️ Total Views</div>
            </div>
            <div class="stat-card likes">
                <div class="stat-value">${formatNumber(stats.total_likes || 0)}</div>
                <div class="stat-label">❤️ Total Likes</div>
            </div>
            <div class="stat-card reading">
                <div class="stat-value">${formatTime(stats.avg_reading_time || 0)}</div>
                <div class="stat-label">📚 Timp mediu citire</div>
            </div>
            <div class="stat-card engagement">
                <div class="stat-value">${formatPercentage(stats.avg_scroll_percentage || 0)}</div>
                <div class="stat-label">📜 Scroll mediu</div>
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
                    label: 'Views zilnice',
                    data: viewsData,
                    borderColor: 'rgba(46, 204, 113, 1)',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Sesiuni citire',
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
                    text: 'Activitate ultimele 7 zile'
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
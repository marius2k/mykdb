<?php
require_once '../../config/bootstrap.php';
include APP_ROOT . 'includes/header.php';

// Verifică permisiuni
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'moderator', 'superadmin'])) {
    $_SESSION['flash'] = "⚠️ Access Denied";
    header("Location: " . APP_URL . "public/index.php");
    exit;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<style>
.analytics-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #3498db;
}

.stat-card.views { border-left-color: #2ecc71; }
.stat-card.likes { border-left-color: #e74c3c; }
.stat-card.reading { border-left-color: #f39c12; }
.stat-card.engagement { border-left-color: #9b59b6; }

.stat-value {
    font-size: 2.2em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.chart-container {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.analytics-table {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tab-navigation {
    display: flex;
    border-bottom: 2px solid #ecf0f1;
    margin-bottom: 30px;
}

.tab-button {
    background: none;
    border: none;
    padding: 15px 25px;
    cursor: pointer;
    font-size: 1em;
    color: #7f8c8d;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-button.active {
    color: #2c3e50;
    border-bottom-color: #3498db;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.metric-icon {
    font-size: 1.5em;
    margin-bottom: 10px;
}
</style>

<div class="breadcrumb-container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item">
                <img src="<?= APP_URL ?>assets/icons/icon-play-arrow.svg" class="breadcrumb-arrow" alt="→">
                Analytics Complete
            </li>
        </ol>
    </nav>
</div>

<div class="analytics-container">
    <h1>📊 Analytics Complete - Views, Likes & Reading Time</h1>
    
    <!-- Navigare tab-uri -->
    <div class="tab-navigation">
        <button class="tab-button active" onclick="showTab('overview')">📈 Overview</button>
        <button class="tab-button" onclick="showTab('articles')">📚 Top Articole</button>
        <button class="tab-button" onclick="showTab('detailed')">🔍 Statistici Detaliate</button>
    </div>
    
    <!-- Tab Overview -->
    <div id="overview-tab" class="tab-content active">
        <div class="stats-grid" id="weekly-stats">
            <!-- Se populează cu JavaScript -->
        </div>
        
        <div class="chart-container">
            <h3>📊 Performanța Articolelor - Top 15</h3>
            <canvas id="performanceChart" width="400" height="200"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>📈 Engagement vs Reading Time</h3>
            <canvas id="engagementChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Tab Top Articole -->
    <div id="articles-tab" class="tab-content">
        <div class="analytics-table">
            <h3>🏆 Top Articole - Toate Metricile</h3>
            <div style="margin-bottom: 20px;">
                <label for="sortBy">Sortează după:</label>
                <select id="sortBy" onchange="loadTopArticles()" style="margin-right: 20px;">
                    <option value="views">👁️ Views</option>
                    <option value="likes">❤️ Likes</option>
                    <option value="reading_time">⏱️ Timp de Citire</option>
                    <option value="engagement">🚀 Engagement Score</option>
                </select>
                
                <label for="limitSelect">Afișează:</label>
                <select id="limitSelect" onchange="loadTopArticles()">
                    <option value="10">10 articole</option>
                    <option value="20" selected>20 articole</option>
                    <option value="50">50 articole</option>
                </select>
            </div>
            <table id="topArticlesTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>📝 Titlu</th>
                        <th>👤 Author</th>
                        <th>👁️ Views</th>
                        <th>❤️ Likes</th>
                        <th>📚 Sesiuni Citire</th>
                        <th>⏱️ Timp Mediu</th>
                        <th>📊 Scroll %</th>
                        <th>👥 Cititori Unici</th>
                        <th>🚀 Score</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    
    <!-- Tab Statistici Detaliate -->
    <div id="detailed-tab" class="tab-content">
        <div class="analytics-table">
            <h3>🔍 Statistici Detaliate pe Articol</h3>
            <p>Introduceți ID-ul articolului pentru a vedea statistici complete:</p>
            
            <div style="margin-bottom: 20px;">
                <input type="number" id="articleIdInput" placeholder="ID Articol" style="margin-right: 10px; padding: 8px;">
                <button onclick="loadArticleDetails()" style="padding: 8px 15px;">📊 Încarcă Statistici</button>
            </div>
            
            <div id="article-details" style="display: none;">
                <div class="stats-grid" id="article-stats-grid">
                    <!-- Se populează cu JavaScript -->
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="chart-container">
                        <h4>👁️ Views Zilnice (Ultima Săptămână)</h4>
                        <canvas id="dailyViewsChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>📚 Reading Sessions Zilnice</h4>
                        <canvas id="dailyReadingChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let performanceChart = null;
let engagementChart = null;
let dailyViewsChart = null;
let dailyReadingChart = null;

$(document).ready(function() {
    loadDashboardData();
    loadTopArticles();
});

function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

async function loadDashboardData() {
    try {
        const response = await fetch('/mykdb/public/api/bkd_article_reading_analytics.php?action=get_analytics_dashboard');
        const data = await response.json();
        
        if (data.success) {
            displayWeeklyStats(data.weekly_stats);
            displayPerformanceChart(data.top_articles);
        }
    } catch (error) {
        console.error('Eroare la încărcarea dashboard-ului:', error);
    }
}

function displayWeeklyStats(stats) {
    const container = document.getElementById('weekly-stats');
    
    const avgReadingMinutes = Math.round((stats.avg_weekly_reading_time || 0) / 60000 * 10) / 10;
    
    container.innerHTML = `
        <div class="stat-card views">
            <div class="metric-icon">👁️</div>
            <div class="stat-value">${stats.weekly_views || 0}</div>
            <div class="stat-label">Views Săptămânale</div>
        </div>
        <div class="stat-card likes">
            <div class="metric-icon">❤️</div>
            <div class="stat-value">${stats.weekly_likes || 0}</div>
            <div class="stat-label">Likes Săptămânale</div>
        </div>
        <div class="stat-card reading">
            <div class="metric-icon">📚</div>
            <div class="stat-value">${stats.weekly_reading_sessions || 0}</div>
            <div class="stat-label">Sesiuni de Citire</div>
        </div>
        <div class="stat-card reading">
            <div class="metric-icon">⏱️</div>
            <div class="stat-value">${avgReadingMinutes} min</div>
            <div class="stat-label">Timp Mediu Citire</div>
        </div>
        <div class="stat-card engagement">
            <div class="metric-icon">📊</div>
            <div class="stat-value">${Math.round(stats.avg_weekly_scroll || 0)}%</div>
            <div class="stat-label">Scroll Mediu</div>
        </div>
        <div class="stat-card engagement">
            <div class="metric-icon">👥</div>
            <div class="stat-value">${stats.unique_readers || 0}</div>
            <div class="stat-label">Cititori Unici</div>
        </div>
    `;
}

function displayPerformanceChart(articles) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    if (performanceChart) {
        performanceChart.destroy();
    }
    
    const top15 = articles.slice(0, 15);
    
    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top15.map(a => a.title.length > 30 ? a.title.substring(0, 30) + '...' : a.title),
            datasets: [
                {
                    label: 'Views',
                    data: top15.map(a => a.total_views),
                    backgroundColor: 'rgba(46, 204, 113, 0.8)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Likes',
                    data: top15.map(a => a.total_likes),
                    backgroundColor: 'rgba(231, 76, 60, 0.8)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Reading Sessions',
                    data: top15.map(a => a.reading_sessions),
                    backgroundColor: 'rgba(243, 156, 18, 0.8)',
                    borderColor: 'rgba(243, 156, 18, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Numărul de Interacțiuni'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Articole'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
    
    // Grafic pentru engagement vs reading time
    displayEngagementChart(articles);
}

function displayEngagementChart(articles) {
    const ctx = document.getElementById('engagementChart').getContext('2d');
    
    if (engagementChart) {
        engagementChart.destroy();
    }
    
    engagementChart = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Articole',
                data: articles.map(article => ({
                    x: article.engagement_score,
                    y: Math.round((article.avg_reading_time || 0) / 60000 * 10) / 10
                })),
                backgroundColor: 'rgba(155, 89, 182, 0.6)',
                borderColor: 'rgba(155, 89, 182, 1)',
                borderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Engagement Score (Views + Likes + Reading Sessions)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Timp Mediu de Citire (minute)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            return articles[index].title;
                        },
                        label: function(context) {
                            const index = context.dataIndex;
                            const article = articles[index];
                            return [
                                `Engagement: ${context.parsed.x}`,
                                `Reading Time: ${context.parsed.y} min`,
                                `Views: ${article.total_views}`,
                                `Likes: ${article.total_likes}`,
                                `Reading Sessions: ${article.reading_sessions}`
                            ];
                        }
                    }
                }
            }
        }
    });
}

async function loadTopArticles() {
    const sortBy = document.getElementById('sortBy').value;
    const limit = document.getElementById('limitSelect').value;
    
    try {
        const response = await fetch(`/mykdb/public/api/bkd_article_reading_analytics.php?action=get_combined_stats&order_by=${sortBy}&limit=${limit}`);
        const data = await response.json();
        
        if (data.success) {
            displayTopArticlesTable(data.articles);
        }
    } catch (error) {
        console.error('Eroare la încărcarea top articole:', error);
    }
}

function displayTopArticlesTable(articles) {
    if ($.fn.DataTable.isDataTable('#topArticlesTable')) {
        $('#topArticlesTable').DataTable().destroy();
    }
    
    $('#topArticlesTable').DataTable({
        data: articles,
        columns: [
            { 
                data: 'title',
                render: function(data, type, row) {
                    const truncated = data.length > 40 ? data.substring(0, 40) + '...' : data;
                    return `<span title="${data}">${truncated}</span>`;
                }
            },
            { data: 'author' },
            { 
                data: 'total_views',
                render: function(data) {
                    return `<span style="color: #27ae60; font-weight: bold;">${data}</span>`;
                }
            },
            { 
                data: 'total_likes',
                render: function(data) {
                    return `<span style="color: #e74c3c; font-weight: bold;">${data}</span>`;
                }
            },
            { 
                data: 'reading_sessions',
                render: function(data) {
                    return `<span style="color: #f39c12; font-weight: bold;">${data}</span>`;
                }
            },
            { 
                data: 'avg_reading_time',
                render: function(data) {
                    const minutes = Math.round((data || 0) / 60000 * 10) / 10;
                    return `<span style="color: #8e44ad;">${minutes} min</span>`;
                }
            },
            { 
                data: 'avg_scroll_percentage',
                render: function(data) {
                    return Math.round(data || 0) + '%';
                }
            },
            { data: 'unique_readers' },
            { 
                data: 'engagement_score',
                render: function(data) {
                    return `<span style="color: #9b59b6; font-weight: bold;">${data}</span>`;
                }
            }
        ],
        order: [[8, 'desc']], // Sortează după engagement score implicit
        pageLength: 20,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/ro.json"
        }
    });
}

async function loadArticleDetails() {
    const articleId = document.getElementById('articleIdInput').value;
    
    if (!articleId) {
        alert('Te rog să introduci un ID de articol');
        return;
    }
    
    try {
        const response = await fetch(`/mykdb/public/api/bkd_article_reading_analytics.php?action=get_article_analytics&article_id=${articleId}`);
        const data = await response.json();
        
        if (data.success) {
            displayArticleDetails(data.stats, data.daily_views, data.daily_reading);
            document.getElementById('article-details').style.display = 'block';
        } else {
            alert('Nu s-au găsit date pentru acest articol');
        }
    } catch (error) {
        console.error('Eroare la încărcarea detaliilor articolului:', error);
        alert('Eroare la încărcarea datelor');
    }
}

function displayArticleDetails(stats, dailyViews, dailyReading) {
    const container = document.getElementById('article-stats-grid');
    const avgReadingMinutes = Math.round((stats.avg_reading_time || 0) / 60000 * 10) / 10;
    
    container.innerHTML = `
        <div class="stat-card views">
            <div class="metric-icon">👁️</div>
            <div class="stat-value">${stats.total_views}</div>
            <div class="stat-label">Total Views</div>
        </div>
        <div class="stat-card likes">
            <div class="metric-icon">❤️</div>
            <div class="stat-value">${stats.total_likes}</div>
            <div class="stat-label">Total Likes</div>
        </div>
        <div class="stat-card reading">
            <div class="metric-icon">📚</div>
            <div class="stat-value">${stats.reading_sessions}</div>
            <div class="stat-label">Reading Sessions</div>
        </div>
        <div class="stat-card reading">
            <div class="metric-icon">⏱️</div>
            <div class="stat-value">${avgReadingMinutes} min</div>
            <div class="stat-label">Timp Mediu Citire</div>
        </div>
        <div class="stat-card engagement">
            <div class="metric-icon">📊</div>
            <div class="stat-value">${Math.round(stats.avg_scroll_percentage || 0)}%</div>
            <div class="stat-label">Scroll Mediu</div>
        </div>
        <div class="stat-card engagement">
            <div class="metric-icon">👥</div>
            <div class="stat-value">${stats.unique_readers}</div>
            <div class="stat-label">Cititori Unici</div>
        </div>
    `;
    
    // Charts pentru activitatea zilnică
    displayDailyCharts(dailyViews, dailyReading);
}

function displayDailyCharts(dailyViews, dailyReading) {
    // Views Chart
    const viewsCtx = document.getElementById('dailyViewsChart').getContext('2d');
    
    if (dailyViewsChart) {
        dailyViewsChart.destroy();
    }
    
    const viewsLabels = dailyViews.map(day => day.date);
    const viewsData = dailyViews.map(day => day.daily_views);
    
    dailyViewsChart = new Chart(viewsCtx, {
        type: 'line',
        data: {
            labels: viewsLabels,
            datasets: [{
                label: 'Views Zilnice',
                data: viewsData,
                borderColor: 'rgba(46, 204, 113, 1)',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Numărul de Views'
                    }
                }
            }
        }
    });
    
    // Reading Sessions Chart
    const readingCtx = document.getElementById('dailyReadingChart').getContext('2d');
    
    if (dailyReadingChart) {
        dailyReadingChart.destroy();
    }
    
    const readingLabels = dailyReading.map(day => day.date);
    const sessionsData = dailyReading.map(day => day.daily_reading_sessions);
    const readingTimeData = dailyReading.map(day => Math.round((day.avg_daily_reading_time || 0) / 60000 * 10) / 10);
    
    dailyReadingChart = new Chart(readingCtx, {
        type: 'line',
        data: {
            labels: readingLabels,
            datasets: [
                {
                    label: 'Reading Sessions',
                    data: sessionsData,
                    borderColor: 'rgba(243, 156, 18, 1)',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Timp Mediu (min)',
                    data: readingTimeData,
                    borderColor: 'rgba(155, 89, 182, 1)',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Reading Sessions'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Timp Mediu (min)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>
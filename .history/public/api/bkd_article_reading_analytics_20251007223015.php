<?php
require_once '../../config/bootstrap.php';
//require_once '../../classes/database.php';
require_once APP_ROOT . 'includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = new Database();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'save_reading_time':
            saveReadingTime($db);
            break;
        case 'get_article_analytics':
            getArticleAnalytics($db);
            break;
        case 'get_analytics_dashboard':
            getAnalyticsDashboard($db);
            break;
        case 'get_combined_stats':
            getCombinedStats($db);
            break;
        default:
            throw new Exception('Acțiune necunoscută');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function saveReadingTime($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metodă nepermisă');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $articleId = (int)($data['article_id'] ?? 0);
    $readingTime = (int)($data['reading_time'] ?? 0);
    $scrollPercentage = (float)($data['scroll_percentage'] ?? 0);
    $pageVisibilityTime = (int)($data['page_visibility_time'] ?? 0);
    $totalTimeOnPage = (int)($data['total_time_on_page'] ?? 0);
    $viewSource = $data['view_source'] ?? 'public';
    
    // Validate view source
    $validSources = ['public', 'admin_preview', 'admin_edit', 'admin_analytics'];
    if (!in_array($viewSource, $validSources)) {
        $viewSource = 'public';
    }
    
    if ($articleId <= 0) {
        throw new Exception('ID articol invalid');
    }
    
    // Validare minimă (cel puțin 3 secunde de citire efectivă)
    if ($readingTime < 3000) {
        echo json_encode(['success' => false, 'message' => 'Timp de citire prea scurt']);
        return;
    }
    
    // Verifică dacă articolul există folosind metoda clasei Database
    $article = $db->fetchSingle("SELECT id FROM articles WHERE id = ?", [$articleId]);
    if (!$article) {
        throw new Exception('Articolul nu există');
    }
    
    // Session is already started by bootstrap.php
    $sessionId = session_id();
    $userId = $_SESSION['user']['id'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Verifică dacă tabelul reading time există
    try {
        $tableExists = $db->fetchSingle("SHOW TABLES LIKE 'article_reading_time'");
        if (!$tableExists) {
            // Tabelul nu există, creează-l
            createReadingTimeTable($db);
        }
    } catch (Exception $e) {
        // Dacă nu poate crea tabelul, returnează success pentru a nu bloca frontend-ul
        echo json_encode([
            'success' => true, 
            'message' => 'Reading time feature not yet configured',
            'debug' => $e->getMessage()
        ]);
        return;
    }
    
    // Salvează reading time folosind metoda insert a clasei Database
    try {
        $readingData = [
            'article_id' => $articleId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'reading_time' => $readingTime,
            'scroll_percentage' => $scrollPercentage,
            'page_visibility_time' => $pageVisibilityTime,
            'total_time_on_page' => $totalTimeOnPage,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'view_source' => $viewSource
        ];
        
        $readingId = $db->insert('article_reading_time', $readingData);
        
        if ($readingId) {
            echo json_encode([
                'success' => true, 
                'message' => 'Reading time salvat cu succes',
                'reading_id' => $readingId
            ]);
        } else {
            throw new Exception('Eroare la salvarea reading time');
        }
    } catch (Exception $e) {
        // Dacă inserarea eșuează, returnează success pentru a nu bloca frontend-ul
        echo json_encode([
            'success' => true, 
            'message' => 'Reading time feature not yet configured',
            'debug' => $e->getMessage()
        ]);
    }
}

function getArticleAnalytics($db) {
    $articleId = (int)($_GET['article_id'] ?? 0);
    
    if ($articleId <= 0) {
        throw new Exception('ID articol invalid');
    }
    
    // Verifică dacă tabelul de reading time există
    $hasReadingTime = false;
    try {
        $tableCheck = $db->fetchSingle("SHOW TABLES LIKE 'article_reading_time'");
        $hasReadingTime = (bool)$tableCheck;
    } catch (Exception $e) {
        $hasReadingTime = false;
    }
    
    // Obține statistici de bază: views + likes
    $stats = $db->fetchSingle("
        SELECT 
            a.id,
            a.title,
            -- Views din tabelul existent
            (SELECT COUNT(*) FROM article_views WHERE article_id = a.id) as total_views,
            -- Likes din tabelul existent  
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes
        FROM articles a
        WHERE a.id = ?
    ", [$articleId]);
    
    if (!$stats) {
        throw new Exception('Articolul nu a fost găsit');
    }
    
    // Adaugă statistici de reading time dacă tabelul există
    if ($hasReadingTime) {
        try {
            $readingStats = $db->fetchSingle("
                SELECT 
                    COUNT(*) as reading_sessions,
                    AVG(reading_time) as avg_reading_time,
                    AVG(scroll_percentage) as avg_scroll_percentage,
                    COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_readers
                FROM article_reading_time 
                WHERE article_id = ?
            ", [$articleId]);
            
            if ($readingStats) {
                $stats = array_merge($stats, $readingStats);
            }
        } catch (Exception $e) {
            // Dacă query-ul eșuează, setează valori default
            $stats['reading_sessions'] = 0;
            $stats['avg_reading_time'] = 0;
            $stats['avg_scroll_percentage'] = 0;
            $stats['unique_readers'] = 0;
        }
    } else {
        // Setează valori default pentru reading time
        $stats['reading_sessions'] = 0;
        $stats['avg_reading_time'] = 0;
        $stats['avg_scroll_percentage'] = 0;
        $stats['unique_readers'] = 0;
    }
    
    // Statistici detaliate din ultima săptămână pentru views
    $dailyViews = $db->fetchAll("
        SELECT 
            DATE(viewed_at) as date,
            COUNT(*) as daily_views
        FROM article_views
        WHERE article_id = ? 
        AND viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(viewed_at)
        ORDER BY date DESC
    ", [$articleId]);
    
    // Statistici detaliate pentru reading time (dacă există tabelul)
    $dailyReading = [];
    if ($hasReadingTime) {
        try {
            $dailyReading = $db->fetchAll("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as daily_reading_sessions,
                    AVG(reading_time) as avg_daily_reading_time,
                    AVG(scroll_percentage) as avg_daily_scroll
                FROM article_reading_time 
                WHERE article_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ", [$articleId]);
        } catch (Exception $e) {
            $dailyReading = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'daily_reading' => $dailyReading,
        'daily_views' => $dailyViews,
        'has_reading_time' => $hasReadingTime
    ]);
}

function getAnalyticsDashboard($db) {
    // Session is already started by bootstrap.php
    
    // Verifică permisiuni
    if (!isset($_SESSION['user'])) {
        throw new Exception('Utilizator neautentificat');
    }
    
    if (!in_array($_SESSION['user']['role'], ['admin', 'moderator', 'superadmin'])) {
        throw new Exception('Acces neautorizat - rol insuficient');
    }
    
    // Verifică dacă tabelul de reading time există
    $hasReadingTime = false;
    try {
        $tableCheck = $db->fetchSingle("SHOW TABLES LIKE 'article_reading_time'");
        $hasReadingTime = (bool)$tableCheck;
    } catch (Exception $e) {
        $hasReadingTime = false;
    }
    
    // Top articole cu statistici de bază - separate public/admin views
    $topArticles = $db->fetchAll("
        SELECT 
            a.id,
            a.title,
            u.username as author,
            -- Total views din tabelul existent
            (SELECT COUNT(*) FROM article_views WHERE article_id = a.id) as total_views,
            -- Public views only
            (SELECT COUNT(*) FROM article_views WHERE article_id = a.id AND (view_source = 'public' OR view_source IS NULL)) as public_views,
            -- Admin views only
            (SELECT COUNT(*) FROM article_views WHERE article_id = a.id AND view_source IN ('admin_preview', 'admin_edit', 'admin_analytics')) as admin_views,
            -- Likes din tabelul existent
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.status = 'published'
        ORDER BY public_views DESC
        LIMIT 20
    ");
    
    // Adaugă statistici de reading time pentru fiecare articol (dacă există tabelul)
    if ($hasReadingTime) {
        try {
            foreach ($topArticles as &$article) {
                $readingStats = $db->fetchSingle("
                    SELECT 
                        AVG(reading_time) as avg_reading_time,
                        AVG(scroll_percentage) as avg_scroll_percentage,
                        COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_readers,
                        COUNT(*) as reading_sessions,
                        -- Separate public/admin reading stats
                        AVG(CASE WHEN (view_source = 'public' OR view_source IS NULL) THEN reading_time END) as avg_public_reading_time,
                        AVG(CASE WHEN view_source IN ('admin_preview', 'admin_edit', 'admin_analytics') THEN reading_time END) as avg_admin_reading_time,
                        COUNT(CASE WHEN (view_source = 'public' OR view_source IS NULL) THEN 1 END) as public_reading_sessions,
                        COUNT(CASE WHEN view_source IN ('admin_preview', 'admin_edit', 'admin_analytics') THEN 1 END) as admin_reading_sessions
                    FROM article_reading_time 
                    WHERE article_id = ?
                ", [$article['id']]);
                
                if ($readingStats) {
                    $article['avg_reading_time'] = $readingStats['avg_reading_time'] ?: 0;
                    $article['avg_scroll_percentage'] = $readingStats['avg_scroll_percentage'] ?: 0;
                    $article['unique_readers'] = $readingStats['unique_readers'] ?: 0;
                    $article['reading_sessions'] = $readingStats['reading_sessions'] ?: 0;
                } else {
                    $article['avg_reading_time'] = 0;
                    $article['avg_scroll_percentage'] = 0;
                    $article['unique_readers'] = 0;
                    $article['reading_sessions'] = 0;
                }
                
                $article['engagement_score'] = $article['total_views'] + $article['total_likes'] + $article['reading_sessions'];
            }
        } catch (Exception $e) {
            // Dacă eșuează, setează valori default pentru reading time
            foreach ($topArticles as &$article) {
                $article['avg_reading_time'] = 0;
                $article['avg_scroll_percentage'] = 0;
                $article['unique_readers'] = 0;
                $article['reading_sessions'] = 0;
                $article['engagement_score'] = $article['total_views'] + $article['total_likes'];
            }
        }
    } else {
        // Setează valori default pentru reading time
        foreach ($topArticles as &$article) {
            $article['avg_reading_time'] = 0;
            $article['avg_scroll_percentage'] = 0;
            $article['unique_readers'] = 0;
            $article['reading_sessions'] = 0;
            $article['engagement_score'] = $article['total_views'] + $article['total_likes'];
        }
    }
    
    // Statistici generale
    $weeklyStats = $db->fetchSingle("
        SELECT 
            -- Views statistics (using viewed_at column)
            (SELECT COUNT(*) FROM article_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_views,
            (SELECT COUNT(DISTINCT article_id) FROM article_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as articles_viewed,
            -- Likes statistics (using created_at column)
            (SELECT COUNT(*) FROM article_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_likes
    ");
    
    // Adaugă statistici de reading time (dacă există tabelul)
    if ($hasReadingTime) {
        try {
            $readingWeeklyStats = $db->fetchSingle("
                SELECT 
                    COUNT(*) as weekly_reading_sessions,
                    AVG(reading_time) as avg_weekly_reading_time,
                    AVG(scroll_percentage) as avg_weekly_scroll,
                    COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_readers
                FROM article_reading_time 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            if ($readingWeeklyStats) {
                $weeklyStats = array_merge($weeklyStats, $readingWeeklyStats);
            }
        } catch (Exception $e) {
            // Dacă eșuează, setează valori default
            $weeklyStats['weekly_reading_sessions'] = 0;
            $weeklyStats['avg_weekly_reading_time'] = 0;
            $weeklyStats['avg_weekly_scroll'] = 0;
            $weeklyStats['unique_readers'] = 0;
        }
    } else {
        // Setează valori default pentru reading time
        $weeklyStats['weekly_reading_sessions'] = 0;
        $weeklyStats['avg_weekly_reading_time'] = 0;
        $weeklyStats['avg_weekly_scroll'] = 0;
        $weeklyStats['unique_readers'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'top_articles' => $topArticles,
        'weekly_stats' => $weeklyStats,
        'has_reading_time' => $hasReadingTime
    ]);
}

function getCombinedStats($db) {
    $limit = (int)($_GET['limit'] ?? 10);
    $orderBy = $_GET['order_by'] ?? 'views';
    
    $validOrderBy = ['views', 'likes', 'reading_time', 'engagement'];
    if (!in_array($orderBy, $validOrderBy)) {
        $orderBy = 'views';
    }
    
    // Verifică dacă tabelul de reading time există
    $hasReadingTime = false;
    try {
        $tableCheck = $db->fetchSingle("SHOW TABLES LIKE 'article_reading_time'");
        $hasReadingTime = (bool)$tableCheck;
    } catch (Exception $e) {
        $hasReadingTime = false;
    }
    
    // Obține articolele cu views și likes
    $articles = $db->fetchAll("
        SELECT 
            a.id,
            a.title,
            u.username as author,
            -- Views și likes din tabelele existente
            (SELECT COUNT(*) FROM article_views WHERE article_id = a.id) as total_views,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.status = 'published'
        ORDER BY total_views DESC
        LIMIT ?
    ", [$limit * 2]); // Luăm mai multe pentru a putea sorta după
    
    // Adaugă statistici de reading time pentru fiecare articol (dacă există tabelul)
    if ($hasReadingTime) {
        try {
            foreach ($articles as &$article) {
                $readingStats = $db->fetchSingle("
                    SELECT 
                        AVG(reading_time) as avg_reading_time,
                        AVG(scroll_percentage) as avg_scroll_percentage,
                        COUNT(DISTINCT COALESCE(user_id, session_id)) as unique_readers,
                        COUNT(*) as reading_sessions
                    FROM article_reading_time 
                    WHERE article_id = ?
                ", [$article['id']]);
                
                if ($readingStats) {
                    $article['avg_reading_time'] = $readingStats['avg_reading_time'] ?: 0;
                    $article['avg_scroll_percentage'] = $readingStats['avg_scroll_percentage'] ?: 0;
                    $article['unique_readers'] = $readingStats['unique_readers'] ?: 0;
                    $article['reading_sessions'] = $readingStats['reading_sessions'] ?: 0;
                } else {
                    $article['avg_reading_time'] = 0;
                    $article['avg_scroll_percentage'] = 0;
                    $article['unique_readers'] = 0;
                    $article['reading_sessions'] = 0;
                }
                
                $article['engagement_score'] = $article['total_views'] + $article['total_likes'] + $article['reading_sessions'];
            }
        } catch (Exception $e) {
            // Dacă eșuează, setează valori default
            foreach ($articles as &$article) {
                $article['avg_reading_time'] = 0;
                $article['avg_scroll_percentage'] = 0;
                $article['unique_readers'] = 0;
                $article['reading_sessions'] = 0;
                $article['engagement_score'] = $article['total_views'] + $article['total_likes'];
            }
        }
    } else {
        // Setează valori default pentru reading time
        foreach ($articles as &$article) {
            $article['avg_reading_time'] = 0;
            $article['avg_scroll_percentage'] = 0;
            $article['unique_readers'] = 0;
            $article['reading_sessions'] = 0;
            $article['engagement_score'] = $article['total_views'] + $article['total_likes'];
        }
    }
    
    // Sortează după criteriul specificat
    usort($articles, function($a, $b) use ($orderBy) {
        switch ($orderBy) {
            case 'views':
                return $b['total_views'] - $a['total_views'];
            case 'likes':
                return $b['total_likes'] - $a['total_likes'];
            case 'reading_time':
                return $b['avg_reading_time'] - $a['avg_reading_time'];
            case 'engagement':
                return $b['engagement_score'] - $a['engagement_score'];
            default:
                return $b['total_views'] - $a['total_views'];
        }
    });
    
    // Limitează la numărul cerut
    $articles = array_slice($articles, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'articles' => $articles,
        'order_by' => $orderBy,
        'has_reading_time' => $hasReadingTime
    ]);
}

function createReadingTimeTable($db) {
    // Folosește metoda getPdo() pentru comenzi DDL
    $pdo = $db->getPdo();
    $sql = "
        CREATE TABLE article_reading_time (
            id INT PRIMARY KEY AUTO_INCREMENT,
            article_id INT NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(255) NOT NULL,
            reading_time INT NOT NULL,
            scroll_percentage DECIMAL(5,2) DEFAULT 0,
            page_visibility_time INT DEFAULT 0,
            total_time_on_page INT DEFAULT 0,
            user_agent TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            
            INDEX idx_article_reading (article_id, created_at),
            INDEX idx_user_reading (user_id, created_at),
            INDEX idx_session_reading (session_id, created_at)
        )
    ";
    
    $pdo->exec($sql);
}
?>
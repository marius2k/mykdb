<?php
require_once '../../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $pdo = new Database();
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
    
    if ($articleId <= 0) {
        throw new Exception('ID articol invalid');
    }
    
    // Validare minimă (cel puțin 3 secunde de citire efectivă)
    if ($readingTime < 3000) {
        echo json_encode(['success' => false, 'message' => 'Timp de citire prea scurt']);
        return;
    }
    
    // Verifică dacă articolul există
    $stmt = $db->prepare("SELECT id FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    if (!$stmt->fetch()) {
        throw new Exception('Articolul nu există');
    }
    
    session_start();
    $sessionId = session_id();
    $userId = $_SESSION['user']['id'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Salvează reading time
    $stmt = $db->prepare(
        "INSERT INTO article_reading_time 
         (article_id, user_id, session_id, reading_time, scroll_percentage, 
          page_visibility_time, total_time_on_page, user_agent, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->execute([
        $articleId, 
        $userId, 
        $sessionId, 
        $readingTime, 
        $scrollPercentage,
        $pageVisibilityTime,
        $totalTimeOnPage,
        $userAgent,
        $ipAddress
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reading time salvat cu succes',
        'reading_id' => $db->lastInsertId()
    ]);
}

function getArticleAnalytics($db) {
    $articleId = (int)($_GET['article_id'] ?? 0);
    
    if ($articleId <= 0) {
        throw new Exception('ID articol invalid');
    }
    
    // Obține statistici combinate: views + likes + reading time
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            -- Views din tabelul existent
            (SELECT COUNT(*) FROM article_view WHERE article_id = a.id) as total_views,
            -- Likes din tabelul existent  
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes,
            -- Reading time din tabelul nou
            COALESCE(ars.total_reading_sessions, 0) as reading_sessions,
            COALESCE(ars.avg_reading_time, 0) as avg_reading_time,
            COALESCE(ars.avg_scroll_percentage, 0) as avg_scroll_percentage,
            COALESCE(ars.unique_readers, 0) as unique_readers,
            ars.last_reading_update
        FROM articles a
        LEFT JOIN article_analytics_summary ars ON a.id = ars.article_id
        WHERE a.id = ?
    ");
    $stmt->execute([$articleId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        throw new Exception('Articolul nu a fost găsit');
    }
    
    // Statistici detaliate din ultima săptămână pentru reading time
    $stmt = $db->prepare("
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
    ");
    $stmt->execute([$articleId]);
    $dailyReading = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistici detaliate pentru views din ultima săptămână
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as daily_views
        FROM article_view 
        WHERE article_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$articleId]);
    $dailyViews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'daily_reading' => $dailyReading,
        'daily_views' => $dailyViews
    ]);
}

function getAnalyticsDashboard($db) {
    session_start();
    
    // Verifică permisiuni
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'moderator', 'superadmin'])) {
        throw new Exception('Acces neautorizat');
    }
    
    // Top articole cu statistici combinate
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            u.username as author,
            -- Views din tabelul existent
            (SELECT COUNT(*) FROM article_view WHERE article_id = a.id) as total_views,
            -- Likes din tabelul existent
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes,
            -- Reading stats din tabelul nou
            COALESCE(ars.avg_reading_time, 0) as avg_reading_time,
            COALESCE(ars.avg_scroll_percentage, 0) as avg_scroll_percentage,
            COALESCE(ars.unique_readers, 0) as unique_readers,
            COALESCE(ars.total_reading_sessions, 0) as reading_sessions
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN article_analytics_summary ars ON a.id = ars.article_id
        WHERE a.status = 'published'
        ORDER BY total_views DESC
        LIMIT 20
    ");
    $stmt->execute();
    $topArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistici generale
    $stmt = $db->prepare("
        SELECT 
            -- Views statistics
            (SELECT COUNT(*) FROM article_view WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_views,
            (SELECT COUNT(DISTINCT article_id) FROM article_view WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as articles_viewed,
            -- Likes statistics  
            (SELECT COUNT(*) FROM article_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_likes,
            -- Reading time statistics
            (SELECT COUNT(*) FROM article_reading_time WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_reading_sessions,
            (SELECT AVG(reading_time) FROM article_reading_time WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as avg_weekly_reading_time,
            (SELECT AVG(scroll_percentage) FROM article_reading_time WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as avg_weekly_scroll,
            (SELECT COUNT(DISTINCT COALESCE(user_id, session_id)) FROM article_reading_time WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as unique_readers
    ");
    $stmt->execute();
    $weeklyStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'top_articles' => $topArticles,
        'weekly_stats' => $weeklyStats
    ]);
}

function getCombinedStats($db) {
    $limit = (int)($_GET['limit'] ?? 10);
    $orderBy = $_GET['order_by'] ?? 'views'; // views, likes, reading_time, engagement
    
    $validOrderBy = ['views', 'likes', 'reading_time', 'engagement'];
    if (!in_array($orderBy, $validOrderBy)) {
        $orderBy = 'views';
    }
    
    $orderColumn = match($orderBy) {
        'views' => 'total_views',
        'likes' => 'total_likes', 
        'reading_time' => 'avg_reading_time',
        'engagement' => '(total_views + total_likes + COALESCE(ars.total_reading_sessions, 0))'
    };
    
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            u.username as author,
            -- Views și likes din tabelele existente
            (SELECT COUNT(*) FROM article_view WHERE article_id = a.id) as total_views,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as total_likes,
            -- Reading time din tabelul nou
            COALESCE(ars.avg_reading_time, 0) as avg_reading_time,
            COALESCE(ars.avg_scroll_percentage, 0) as avg_scroll_percentage,
            COALESCE(ars.unique_readers, 0) as unique_readers,
            COALESCE(ars.total_reading_sessions, 0) as reading_sessions,
            -- Calculează engagement score
            (SELECT COUNT(*) FROM article_view WHERE article_id = a.id) + 
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) + 
            COALESCE(ars.total_reading_sessions, 0) as engagement_score
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN article_analytics_summary ars ON a.id = ars.article_id
        WHERE a.status = 'published'
        ORDER BY $orderColumn DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'articles' => $articles,
        'order_by' => $orderBy
    ]);
}
?>
<?php
require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the requested action
$action = $_GET['action'] ?? $_POST['action'] ?? '';
error_log("API Request: action=$action, method=" . $_SERVER['REQUEST_METHOD'] . ", ip=" . $_SERVER['REMOTE_ADDR']);

try {
    // For debugging, always return success with mock data
    if ($action == 'get_user_analytics') {
        // Return mock data for development
        echo json_encode([
            'success' => true,
            'debug_mode' => true,
            'overall_stats' => [
                'bookmarks' => rand(10, 100),
                'usefulness_ratings' => rand(50, 200),
                'avg_star_rating' => rand(30, 50) / 10,
                'pdf_saves' => rand(5, 30),
                'comments' => rand(20, 150),
                'useful_yes' => rand(40, 120),
                'useful_no' => rand(5, 30)
            ],
            'daily_activity' => [
                [
                    'date' => date('Y-m-d', strtotime('-1 day')),
                    'action_type' => 'bookmark',
                    'count' => rand(1, 10)
                ],
                [
                    'date' => date('Y-m-d', strtotime('-1 day')),
                    'action_type' => 'comment',
                    'count' => rand(1, 15)
                ],
                [
                    'date' => date('Y-m-d', strtotime('-2 days')),
                    'action_type' => 'bookmark',
                    'count' => rand(1, 10)
                ],
                [
                    'date' => date('Y-m-d', strtotime('-2 days')),
                    'action_type' => 'comment',
                    'count' => rand(1, 15)
                ]
            ],
            'top_articles' => [
                [
                    'article_id' => 1,
                    'title' => 'Example Article One',
                    'bookmarks' => rand(10, 50),
                    'pdf_saves' => rand(5, 20),
                    'comments' => rand(10, 30),
                    'avg_rating' => 4.5,
                    'useful_yes' => rand(20, 40),
                    'useful_no' => rand(1, 10)
                ],
                [
                    'article_id' => 2,
                    'title' => 'Example Article Two',
                    'bookmarks' => rand(5, 30),
                    'pdf_saves' => rand(1, 15),
                    'comments' => rand(5, 25),
                    'avg_rating' => 3.8,
                    'useful_yes' => rand(15, 35),
                    'useful_no' => rand(2, 12)
                ]
            ]
        ]);
    } else if ($action == 'check_tables' || $action == 'check_tables_exist') {
        // Always report that tables exist
        echo json_encode([
            'success' => true,
            'tables_exist' => true
        ]);
    } else {
        // For unknown actions
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action: ' . htmlspecialchars($action)
        ]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
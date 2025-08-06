<?php
require_once '../../config/bootstrap.php';
require_once APP_ROOT . 'includes/functions.php';


$lang = $_SESSION['settings']['language'] ?? 'ro';

//require_once APP_ROOT . 'assets/lang/.$lang.php'; // sau en.php

$langFile = APP_ROOT . "assets/lang/{$lang}.php";
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include APP_ROOT . "assets/lang/en.php";
}


header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Neautentificat']);
    exit;
}
$daysTopLiked = 30; // Zile pentru top articole
$daysTopCommented = 30; // Zile pentru top comentarii
$daysArticlesStats = 60; // Zile pentru statistici articole
$daysCommentsStats = 60; // Zile pentru statistici comentarii
$daysTopViewed = 30; // Zile pentru articolele cele mai vizualizate

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Preia datele necesare pentru dashboard
$data = [
    'drafts'           => getDraftsArticles($userId),
    'notifications'    => getUnreadNotifications($userId),
    'pendingArticles'  => getPendingArticles(),
    'pendingComments'  => getPendingComments(),
    'topViewed'        => getTop5ViewedArticles($daysTopViewed),
    'topLiked'         => getTop5LikedArticles($daysTopLiked),
    'topCommented'     => getTop5CommentedArticles($daysTopCommented),
    'articlesStats'    => getArticlesByLastDays($daysArticlesStats),
    'commentsStats'    => getCommentsByLastDays($daysCommentsStats),
];

//error_log('Pending comments: ' . print_r(getPendingComments(), true));

//error_log('Pending articles: ' . print_r(getPendingArticles(), true));


// Configurație modulară pe roluri
$dashboardConfig = [
    'superadmin' => [
        'left'  => ['drafts', 'notifications'],
        'right' => ['topViewed', 'topLiked', 'topCommented', 'articlesChart', 'commentsChart', 'operations', 'logs', 'exports']
    ],
    'admin' => [
        'left'  => ['drafts', 'notifications'],
        'right' => ['topViewed', 'topLiked', 'topCommented', 'articlesChart', 'commentsChart']
    ],
    'moderator' => [
        'left'  => ['drafts','pendingArticles', 'pendingComments', 'notifications'],
        'right' => ['topViewed', 'topLiked', 'topCommented', 'articlesChart', 'commentsChart']
    ],
    'editor' => [
        'left'  => ['drafts', 'notifications'],
        'right' => ['topViewed', 'topLiked', 'topCommented', 'articlesChart', 'commentsChart']
    ],
    'contributor' => [
        'left'  => ['drafts', 'notifications'],
        'right' => ['topViewed', 'topLiked', 'topCommented']
    ]
    // Adaugă și alte roluri dacă e nevoie
];

// Alege layout-ul dashboard-ului în funcție de rol
$config = $dashboardConfig[$userRole] ?? ['left' => [], 'right' => []];

// Generează HTML-ul modular
$html = '<div class="dashboard-wrapper">';
$html .= '<div class="dashboard-left">';
foreach ($config['left'] as $box) {
    $html .= renderDashboardBox($box, $data);
}
$html .= '</div><div class="dashboard-right">';
foreach ($config['right'] as $box) {
    $html .= renderDashboardBox($box, $data);
}
$html .= '</div></div>';

// Pregătește datele pentru grafice (dacă există)
$days=30;
$articlesChart = null;
$commentsChart = null;
if (in_array('articlesChart', $config['right'])) {
    $labels = array_keys($data['articlesStats']);
    $values = array_values($data['articlesStats']);
    $articlesChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $labels,
            'datasets' => [[
                'label' => lang('lang_db_articles_published'),
                'data' => $values,
                'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1,
                'borderRadius' => 2
            ]]
        ],
        'options' => [
            'responsive' => true,
            'plugins' => [
                'legend' => ['display' => false],
                'title' => [
                    'display' => true,
                    'text' => lang('lang_db_art_published_last_days').$daysArticlesStats.lang('lang_db_art_days').' ('.array_sum($values).')'
                ]
            ],
            'scales' => [
                'x' => ['ticks' => ['display' => false]],
                'y' => [
                    'beginAtZero' => true,
                    'precision' => 0,
                    'max' => max($values) + 1,
                    'ticks' => ['stepSize' => 1, 'font' => ['size' => 10]]
                ]
            ]
        ]
    ];
}
if (in_array('commentsChart', $config['right'])) {
    $labels = array_keys($data['commentsStats']);
    $values = array_values($data['commentsStats']);
    $commentsChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $labels,
            'datasets' => [[
                'label' => lang('lang_db_comments_received'),
                'data' => $values,
                'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1,
                'borderRadius' => 2
            ]]
        ],
        'options' => [
            'responsive' => true,
            'plugins' => [
                'legend' => ['display' => false],
                'title' => [
                    'display' => true,
                    'text' => lang('lang_db_comments_last_days').$daysCommentsStats.lang('lang_db_art_days'). ' ('.array_sum($values).')'
                ]
            ],
            'scales' => [
                'x' => ['ticks' => ['display' => false]],
                'y' => [
                    'beginAtZero' => true,
                    'precision' => 0,
                    'max' => max($values) + 1,
                    'ticks' => ['stepSize' => 1, 'font' => ['size' => 10]]
                ]
            ]
        ]
    ];
}


// prepare titles for dashboard boxes

$titleTopViewed = lang('lang_db_top5_views').$daysTopViewed . ' '. lang('lang_db_days');
$titleTopLiked = lang('lang_db_top5_likes') . $daysTopLiked . ' ' . lang('lang_db_days');
$titleTopCommented = lang('lang_db_comments_last_days') . $daysTopCommented . ' ' . lang('lang_db_days');


echo json_encode([
    'html' => $html,
    'articlesChart' => $articlesChart,
    'commentsChart' => $commentsChart,
    'titleTopViewed' => $titleTopViewed,
    'titleTopLiked' => $titleTopLiked,
    'titleTopCommented' => $titleTopCommented
]);

?>
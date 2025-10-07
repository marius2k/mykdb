<?php
// Simple translation test without database
define('APP_ROOT', __DIR__ . '/');
require_once 'config/config.php';

// Include lang function manually
function lang($key) {
    global $lang_data;
    
    $language = $_SESSION['settings']['language'] ?? 'en';
    
    if (!isset($lang_data[$language])) {
        $lang_file = APP_ROOT . "assets/lang/{$language}.php";
        if (file_exists($lang_file)) {
            $lang_data[$language] = require $lang_file;
        } else {
            // Fallback to English
            $lang_data[$language] = require APP_ROOT . "assets/lang/en.php";
        }
    }
    
    return $lang_data[$language][$key] ?? $key;
}

global $lang_data;
$lang_data = [];

echo "Testing Analytics Translations:\n\n";

// Test Romanian translations
$_SESSION['settings']['language'] = 'ro';
echo "Romanian (RO) translations:\n";
echo "Dashboard: " . lang('lang_analytics_dashboard') . "\n";
echo "Views: " . lang('lang_analytics_views') . "\n";
echo "Likes: " . lang('lang_analytics_likes') . "\n";
echo "Reading Time: " . lang('lang_analytics_reading_time') . "\n";
echo "Engagement: " . lang('lang_analytics_engagement') . "\n";
echo "Sort by: " . lang('lang_analytics_sort_by') . "\n";
echo "Number of articles: " . lang('lang_analytics_number_articles') . "\n";
echo "Analyze: " . lang('lang_analytics_analyze') . "\n";

echo "\n";

// Test English translations
$_SESSION['settings']['language'] = 'en';
echo "English (EN) translations:\n";
echo "Dashboard: " . lang('lang_analytics_dashboard') . "\n";
echo "Views: " . lang('lang_analytics_views') . "\n";
echo "Likes: " . lang('lang_analytics_likes') . "\n";
echo "Reading Time: " . lang('lang_analytics_reading_time') . "\n";
echo "Engagement: " . lang('lang_analytics_engagement') . "\n";
echo "Sort by: " . lang('lang_analytics_sort_by') . "\n";
echo "Number of articles: " . lang('lang_analytics_number_articles') . "\n";
echo "Analyze: " . lang('lang_analytics_analyze') . "\n";

echo "\nTranslation test completed successfully!\n";
?>
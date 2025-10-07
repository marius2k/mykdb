<?php
require_once 'config/bootstrap.php';

// Test translation function
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
<?php

/**
 * Escape HTML special characters
 */
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a MySQL timestamp to readable date
 */
function formatDate($datetime) {
    return date("d.m.Y H:i", strtotime($datetime));
}

/**
 * Shorten long text (used in previews)
 */
function shortenText($text, $max = 200) {
    return strlen($text) > $max 
        ? substr($text, 0, $max) . '...' 
        : $text;
}

/**
 * Get all categories as array (id => name)
 */
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM categories");
    $cats = [];
    foreach ($stmt as $cat) {
        $cats[$cat['id']] = $cat['name'];
    }
    return $cats;
}

/**
 * Get username from user ID
 */
function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

function loadConfig(){

    if (!defined('APP_ROOT')) {
        define('APP_ROOT', '/opt/lampp/htdocs/mykdb/');
    }
    if (!defined('APP_URL')) {
        define('APP_URL', 'http://localhost/mykdb/');
    }
    exit;

}

function SystemStatus(){

    //echo '<div class="backgroubd: linear-gradient(to right,rgb(25, 108, 141),rgb(67, 190, 238)); width: 100%; display: flex; align-items: center; justify-content: space-between;">';
            
        echo '<div class="width: 100%; display: flex; align-items: center; justify-content: space-between; small">';
                $db = new Database();
                $stats = $db->fetchSingle("
                    SELECT 
                        (SELECT COUNT(*) FROM users) as users,
                        (SELECT COUNT(*) FROM articles WHERE status='approved') as aaproved,
                        (SELECT COUNT(*) FROM articles WHERE status='pending') as apending                        
                ");
                
                echo '  System contains: '.$stats['users'].' users | '.$stats['aaproved'].' articles approved | '.$stats['apending'].' articles in pending';
        echo '</div>';
    //echo '</div>';

}
function clean_html($html) {
    $allowed_tags = '<b><i><u><strong><em><ul><ol><li><p><br><a><h1><h2><h3><h4><img><figure>';
    return strip_tags($html, $allowed_tags);
}


function truncateHtmlWithImages($html, $limit = 500) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // suprimă warning-uri HTML
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    $body = $dom->getElementsByTagName('body')->item(0);
    $output = '';
    $length = 0;

    foreach ($body->childNodes as $node) {
        if ($length >= $limit) break;

        $frag = $dom->saveHTML($node);
        $textLength = mb_strlen(strip_tags($frag));

        if (($length + $textLength) > $limit) {
            $cut = $limit - $length;
            $textOnly = strip_tags($frag);
            $frag = mb_substr($textOnly, 0, $cut) . '...';
        }

        $output .= $frag;
        $length += $textLength;
    }

    return $output;
}

function removeImageCaptionText($html) {
    return preg_replace('/(<a[^>]+>\s*<img[^>]+>)([^<]+)?(<\/a>)/i', '$1$3', $html);
}

function full_name($user) {
    return trim($user['first_name'] . ' ' . $user['last_name']);
}

<?php
/**
 * Empty State API
 * Returns HTML for empty search results with suggestions
 */

require_once '../../config/bootstrap.php';
require_once '../../includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');

$searchQuery = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';

echo getEmptyStateHTML($searchQuery, $category, $author);

# Search Analytics Refactoring Guide

## Overview
The `public/admin/search_analytics.php` file needs to be refactored to follow the same architecture as `articles_analytics.php` and `user_analytics.php`, where the frontend fetches data from a backend API (`bkd_search_analytics.php`) using JavaScript instead of directly querying the database in PHP.

## Current State vs. Target State

### Current Implementation (INCORRECT)
- `search_analytics.php` directly queries MySQL database using PHP
- Data is embedded in the HTML at page load time
- Charts are rendered with PHP-generated data
- No separation between presentation and data layers

### Target Implementation (CORRECT)
- `search_analytics.php` only contains HTML structure and JavaScript
- `bkd_search_analytics.php` handles all database queries and returns JSON
- JavaScript fetches data from API and dynamically populates the page
- Clean separation of concerns

## Changes Made

### 1. Backend API (`bkd_search_analytics.php`) ✅ COMPLETED

Added GET endpoint handling:
```php
// Handle GET requests for analytics data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetRequest($db);
    exit;
}
```

Added functions:
- `handleGetRequest($db)` - Routes GET requests to appropriate handlers
- `getDashboardData($db)` - Returns all analytics data as JSON

**Endpoint**: `GET /public/api/bkd_search_analytics.php?action=get_dashboard_data&period=7`

**Response Structure**:
```json
{
  "success": true,
  "data": {
    "total_metrics": {
      "total_searches": 150,
      "unique_users": 45,
      "unique_queries": 80,
      "total_results": 2500,
      "avg_results": 16.7
    },
    "top_searches": [...],
    "zero_results": [...],
    "ctr_data": [...],
    "trends": [...],
    "top_clicked_articles": [...],
    "position_stats": [...]
  }
}
```

### 2. Frontend (`search_analytics.php`) ⚠️ IN PROGRESS

**Current Issue**: File contains mixed PHP database queries and HTML rendering. Needs complete rewrite to:

1. Remove all direct database queries
2. Replace PHP-rendered content with JavaScript-populated containers
3. Add async functions to fetch data from API
4. Update charts to use dynamically loaded data

## Required Frontend Structure

###Human: continue
# Search Analytics Implementation Guide

## Overview
The search analytics system tracks user search behavior including:
- Search queries performed
- Number of results returned
- Clicks on search results
- Position of clicked results
- Time from search to click

## Components

### 1. Frontend JavaScript (`search-analytics.js`)
- **Location**: `/assets/js/search-analytics.js`
- **Automatic initialization**: Loads on pages with search functionality
- **Functionality**:
  - Tracks search queries with debouncing (prevents tracking every keystroke)
  - Tracks clicks on search results
  - Correlates searches with result clicks using unique search IDs
  - Sends data to backend API

### 2. Backend API (`bkd_search_analytics.php`)
- **Location**: `/public/api/bkd_search_analytics.php`
- **Endpoints**:
  - `action_type: 'search_query'` - Records search queries
  - `action_type: 'search_result_click'` - Records clicks on search results
- **Data Storage**: Saves to `search_queries` and `search_result_clicks` tables

### 3. Database Tables

#### `search_queries`
Stores information about each search performed:
- `id` - Auto-increment primary key
- `user_id` - User who performed the search (NULL for guests)
- `query` - The search term
- `result_count` - Number of results returned
- `search_id` - Unique identifier for correlating with clicks
- `session_id` - Session identifier
- `ip_address` - User's IP address
- `created_at` - Timestamp

#### `search_result_clicks`
Stores information about clicks on search results:
- `id` - Auto-increment primary key
- `user_id` - User who clicked (NULL for guests)
- `search_id` - Correlates with search_queries
- `query` - The search query that led to this click
- `result_url` - URL of the clicked result
- `result_title` - Title of the clicked result
- `article_id` - ID of the article clicked
- `position` - Position in search results (1-based)
- `time_to_click` - Milliseconds from search to click
- `session_id` - Session identifier
- `ip_address` - User's IP address
- `created_at` - Timestamp

## Implementation in index.php

### Search Results Structure
Search results must include:
```html
<div class="search-result kb-search-result">
  <a href="..." class="search-result-link" data-result-id="ARTICLE_ID" data-position="1">
    Article Title
  </a>
</div>
```

### Key Requirements:
1. **CSS Classes**: Results must have `search-result` or `kb-search-result` class
2. **Links**: Must have `search-result-link` class
3. **Data Attributes**:
   - `data-result-id`: The article ID
   - `data-position`: Position in results (1-based)

### Analytics Tracking
The JavaScript automatically:
1. **Tracks Searches**: When `SearchAnalytics.trackSearch(query, resultCount)` is called
2. **Tracks Clicks**: When users click on search result links

## Usage

### Manual Tracking
If you need to manually track a search:

```javascript
// Track a search with 5 results
SearchAnalytics.trackSearch('nodejs tutorial', 5);

// Track a click on a search result
SearchAnalytics.trackSearchResultClick(
    'view_article.php?id=123',  // URL
    'Node.js Tutorial',          // Title
    1,                           // Position
    123                          // Article ID
);
```

### Automatic Tracking
The system automatically tracks:
- Searches performed in the search box on `index.php`
- Clicks on search results

## Analytics Reports

To view search analytics:
1. Go to **Admin -> Users -> User Analytics**
2. Select the **Search** tab
3. View metrics:
   - Most searched terms
   - Search queries with no results (abandoned searches)
   - Most clicked articles from search
   - Click-through rate by position
   - Average time to click

## Troubleshooting

### Searches Not Being Tracked
1. Check browser console for JavaScript errors
2. Verify `search-analytics.js` is loaded on the page
3. Check that `APP_URL` is defined in JavaScript
4. Verify database tables exist and are accessible

### Clicks Not Being Tracked
1. Ensure search result links have proper CSS classes
2. Check data attributes are present (`data-result-id`, `data-position`)
3. Verify a search was performed before clicking (search ID must exist)

### Database Errors
1. Run migration script: `./scripts/docker_run_analytics_migrations.sh`
2. Check table structure matches expected schema
3. Verify database user has INSERT permissions

## Performance Considerations

- **Debouncing**: Search tracking is debounced (500ms) to avoid tracking every keystroke
- **Async Operations**: All analytics calls are asynchronous and won't block UI
- **Error Handling**: Analytics failures are logged but don't affect user experience

## Privacy

- IP addresses are stored for analytics purposes
- Guest user searches are tracked anonymously
- All data complies with GDPR requirements (anonymization support available)

# Search Analytics - Quick Reference

## Production Files (DO NOT DELETE)

### Core Files
```
/public/api/bkd_search_analytics.php          - Backend API endpoint
/assets/js/search-analytics.js                 - Frontend tracking library
/public/index.php                              - Search interface (modified)
/includes/footer.php                           - Loads analytics script
```

### Database
```
search_queries                                 - Stores search queries
search_result_clicks                          - Stores result clicks
user_activity_analytics                        - Unified analytics (also receives data)
```

### Documentation
```
/docs/search_analytics_implementation.md       - Full implementation guide
/docs/SEARCH_ANALYTICS_DEPLOYMENT.md          - Deployment summary
```

## Test/Debug Files (Can Remove After Testing)

```
/public/test_search_analytics.html            - Browser test interface
/scripts/test_search_analytics.php            - CLI test script
/scripts/check_search_tables.sh               - Table verification script
```

## Key Code Locations

### Track Search (index.php ~line 901)
```javascript
if (typeof SearchAnalytics !== 'undefined') {
    SearchAnalytics.trackSearch(query, data.length);
}
```

### Search Results Structure (index.php ~line 915)
```html
<div class="search-result kb-search-result">
    <a href="..." class="search-result-link" 
       data-result-id="ARTICLE_ID" 
       data-position="POSITION">
        Title
    </a>
</div>
```

### APP_URL Declaration (index.php ~line 197)
```javascript
var APP_URL = '<?= APP_URL ?>';
```

## Monitoring

### Check if working:
1. Open browser console on search page
2. Perform search - should see: "Search analytics (search_query) recorded successfully"
3. Click result - should see: "Search analytics (search_result_click) recorded successfully"

### Check database:
```sql
-- Recent searches
SELECT id, user_id, query, result_count, created_at 
FROM search_queries 
ORDER BY id DESC LIMIT 20;

-- Recent clicks
SELECT id, user_id, query, article_id, position, created_at 
FROM search_result_clicks 
ORDER BY id DESC LIMIT 20;
```

## Troubleshooting

### No data being saved:
1. Check browser console for errors
2. Verify `search-analytics.js` is loaded
3. Check `APP_URL` is defined in JavaScript
4. Verify tables exist in database

### 500 Error from API:
1. Check Docker logs: `docker logs mykdb-webapp --tail=50`
2. Verify bootstrap.php doesn't output anything
3. Check Database class is working

### Clicks not tracked:
1. Ensure search was performed first (need search_id)
2. Check link has `search-result-link` class
3. Verify `data-result-id` and `data-position` attributes exist

## Status: ✅ LIVE & COLLECTING DATA

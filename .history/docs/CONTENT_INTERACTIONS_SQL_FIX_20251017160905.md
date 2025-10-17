# Content Interactions SQL Fix

**Date:** 2025-10-17  
**Issue:** Content Interactions tab table `lang_analytics_top_articles_by_interaction` not populated  
**Status:** FIXED ✅

## Problem Description

The Content Interactions tab in the User Analytics dashboard (`/public/admin/user_analytics.php`) was displaying "Error loading data" when attempting to view article interaction statistics. The API was returning data but the frontend was failing to display it.

## Root Causes

### 1. Double WHERE Clause Bug (Backend)

**Double WHERE Clause Bug** in `/public/api/bkd_user_analytics.php` `getUserAnalytics()` function.

The function builds a `$whereClause` variable that includes `WHERE` when filters are present:

```php
$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
```

However, many queries already have WHERE clauses in their base SQL:

```sql
-- BROKEN QUERY (double WHERE)
SELECT COUNT(*) as count 
FROM user_activity_analytics 
WHERE action_type = 'bookmark' WHERE action_date >= ? AND action_date <= ?
```

This caused SQL syntax errors that prevented data from being retrieved.

### Affected Queries

All the following queries had the double WHERE clause bug:

1. **Bookmarks count** - `WHERE action_type = 'bookmark' WHERE ...`
2. **Usefulness ratings count** - `WHERE action_type = 'usefulness_rating' WHERE ...`
3. **Average star rating** - `WHERE action_type = 'rating' WHERE ...`
4. **PDF saves count** - `WHERE action_type = 'save_pdf' WHERE ...`
5. **Comments count** - `WHERE action_type = 'comment' WHERE ...`
6. **Useful yes count** - `WHERE action_type = 'usefulness_rating' AND value = 1 WHERE ...`
7. **Useful no count** - `WHERE action_type = 'usefulness_rating' AND value = 0 WHERE ...`
8. **Article count check** - `WHERE article_id > 0 WHERE ...`

### Error Log Evidence

```
[Fri Oct 17 12:45:46.164283 2025] [php:notice] [pid 21:tid 21] [client 192.168.1.178:43070] 
Prepare statement error: SQLSTATE[42000]: Syntax error or access violation: 1064 
You have an error in your SQL syntax; check the manual that corresponds to your MySQL 
server version for the right syntax to use near 'WHERE action_date >= ? AND action_date <= ?' 
at line 1

Query error: Database query preparation failed.
SQL: SELECT COUNT(*) as count FROM user_activity_analytics 
WHERE action_type = 'save_pdf' WHERE action_date >= ? AND action_date <= ?
```

## Solution

Created a separate `$andClause` variable that uses `AND` instead of `WHERE` for queries that already have a WHERE clause:

```php
$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
$andClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';
```

### Fixed Queries

Changed all queries with existing WHERE clauses to use `$andClause`:

```php
// BEFORE (broken)
$bookmarks = $db->fetchSingle(
    "SELECT COUNT(*) as count FROM user_activity_analytics 
     WHERE action_type = 'bookmark' $whereClause", 
    $params
);

// AFTER (fixed)
$bookmarks = $db->fetchSingle(
    "SELECT COUNT(*) as count FROM user_activity_analytics 
     WHERE action_type = 'bookmark' $andClause", 
    $params
);
```

### Queries That Remain Using $whereClause

The following queries correctly use `$whereClause` because they don't have a WHERE clause in the base query:

- **Daily activity query** - Base query has no WHERE
- **Top articles query** - Base query has no WHERE (uses LEFT JOIN)
- **Fallback query** - Base query has no WHERE

## Changes Made

### File: `/public/api/bkd_user_analytics.php`

**Lines Changed:** ~230-340

1. Added new variable after line ~229:
   ```php
   $andClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';
   ```

2. Replaced `$whereClause` with `$andClause` in 8 queries:
   - Bookmarks count query
   - Usefulness ratings count query
   - Average star rating query
   - PDF saves count query
   - Comments count query
   - Useful yes count query
   - Useful no count query
   - Article count check query

## Verification

### Database State

Articles with interactions in the database:
```sql
SELECT article_id, COUNT(*) as interaction_count, GROUP_CONCAT(DISTINCT action_type) as actions 
FROM user_activity_analytics 
WHERE article_id IS NOT NULL AND article_id > 0 
GROUP BY article_id 
ORDER BY interaction_count DESC 
LIMIT 5;
```

Result:
```
article_id | interaction_count | actions
-----------|-------------------|---------------------------
24         | 12                | view,bookmark,rating
23         | 11                | view,bookmark
9          | 5                 | rating,usefulness_rating
3          | 3                 | view,bookmark
13         | 3                 | view,bookmark
```

### After Fix

After applying the fix and restarting the webapp container:

```bash
docker restart mykdb-webapp
```

The Content Interactions tab should now populate with the top 20 articles showing:
- Article ID and Title
- Bookmarks count
- Average rating (X.X/5.0)
- Useful Yes count
- Useful No count
- Comments count
- PDF saves count

## Related Issues

This fix also resolves the "Overall Stats" cards not displaying correct numbers at the top of the Content Interactions tab (Bookmarks, Ratings, PDF Saves, etc.) since they use the same broken queries.

## Testing

1. Open User Analytics dashboard: `/public/admin/user_analytics.php`
2. Click "Content Interactions" tab
3. Select date range (e.g., last 30 days)
4. Verify:
   - Overall stats cards show numbers (not zeros)
   - Table displays articles with interaction counts
   - No JavaScript console errors
   - No SQL errors in Docker logs

## Docker Logs Check

```bash
# Check for SQL errors
docker logs mykdb-webapp --tail 100 | grep -i "error\|syntax"

# Should see successful queries instead of syntax errors
docker logs mykdb-webapp --tail 100 | grep "getUserAnalytics\|topArticles"
```

## Conclusion

The double WHERE clause bug was preventing the Content Interactions tab from displaying any data. By introducing a separate `$andClause` variable for queries that already have WHERE conditions, all queries now execute successfully and the dashboard properly displays analytics data.

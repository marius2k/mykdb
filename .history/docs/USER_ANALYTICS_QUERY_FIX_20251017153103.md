# User Analytics Query Fix - October 17, 2025

## Issue

The **Most Engaged Users** table and **Engagement by Role** chart in the User Analytics dashboard were showing no data.

## Root Cause

The SQL queries in `getUserEngagement()` function were using `u.role` column, but the database schema uses:
- `users.role_id` → Foreign key to roles table
- `roles.name` → Actual role name

### Database Schema
```sql
-- users table has role_id (FK)
users.role_id → roles.id

-- roles table has the name
roles.name (admin, moderator, contributor, etc.)
```

## Queries Fixed

### 1. Most Engaged Users Query

**Before (BROKEN):**
```sql
SELECT u.role, ...
FROM user_activity_analytics ua
JOIN users u ON ua.user_id = u.id
GROUP BY u.id, u.username, u.email, u.role
```

**After (FIXED):**
```sql
SELECT r.name as role, ...
FROM user_activity_analytics ua
JOIN users u ON ua.user_id = u.id
LEFT JOIN roles r ON u.role_id = r.id  -- Added roles join
GROUP BY u.id, u.username, u.email, r.name
```

### 2. Engagement by Role Query

**Before (BROKEN):**
```sql
SELECT u.role, ...
FROM user_activity_analytics ua
JOIN users u ON ua.user_id = u.id
GROUP BY u.role
```

**After (FIXED):**
```sql
SELECT r.name as role, ...
FROM user_activity_analytics ua
JOIN users u ON ua.user_id = u.id
LEFT JOIN roles r ON u.role_id = r.id  -- Added roles join
GROUP BY r.name
```

## Changes Made

**File:** `/public/api/bkd_user_analytics.php`

**Function:** `getUserEngagement()`

**Lines Modified:**
- Line ~576: Added `LEFT JOIN roles r ON u.role_id = r.id` to engaged users query
- Line ~649: Added `LEFT JOIN roles r ON u.role_id = r.id` to role engagement query
- Changed `u.role` to `r.name as role` in both SELECT and GROUP BY clauses

## Verification

### Database Check (Oct 15-17, 2025)
```bash
docker exec mykdb-db mysql -u root knowledge_db -e "
  SELECT r.name as role, 
         COUNT(DISTINCT u.id) as user_count,
         COUNT(*) as total_interactions
  FROM user_activity_analytics ua
  JOIN users u ON ua.user_id = u.id
  LEFT JOIN roles r ON u.role_id = r.id
  WHERE ua.action_date BETWEEN '2025-10-15 00:00:00' AND '2025-10-17 23:59:59'
  GROUP BY r.name;
"
```

**Result:**
| role        | user_count | total_interactions |
|-------------|------------|--------------------|
| admin       | 1          | 46                 |
| moderator   | 1          | 4                  |
| contributor | 1          | 3                  |

### Current Data Statistics
- **Total records:** 53 in `user_activity_analytics`
- **Date range:** Oct 15-17, 2025
- **Unique users:** 3
- **Action types:** bookmark (17), view (16), search (13), rating (5), usefulness_rating (1), search_click (1)

## Testing

1. **Access User Analytics Dashboard:**
   - Go to Admin → Users → User Analytics
   - Select date range: Oct 15-17, 2025
   - Click "Apply Filter"

2. **Expected Results:**
   - **Most Engaged Users Table:** Shows 3 users (admin, user2, mihai)
   - **Engagement by Role Chart:** Shows 3 roles with their interaction counts

## Related Files

- `/public/api/bkd_user_analytics.php` - API backend (FIXED)
- `/public/admin/user_analytics.php` - Frontend dashboard
- Database: `users` table (role_id FK)
- Database: `roles` table (role names)
- Database: `user_activity_analytics` table (tracking data)

## Additional Notes

The error was silently caught by the try-catch blocks, which returned empty arrays `[]` instead of throwing errors. This made debugging harder. The queries now properly join with the `roles` table to get role names.

## Status: ✅ FIXED

Both the "Most Engaged Users" table and "Engagement by Role" chart should now display data correctly.

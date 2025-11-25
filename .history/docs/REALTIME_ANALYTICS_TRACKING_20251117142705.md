# Real-Time Analytics Tracking Implementation

## Overview
Implemented automatic real-time tracking for user and admin activities to populate the User Statistics dashboard without needing to run manual scripts.

## What Was Implemented

### 1. AnalyticsTracker Class (`classes/analytics_tracker.php`)
A centralized class that handles all analytics tracking with methods for:
- **Admin Actions**: `trackEdit()`, `trackApprove()`, `trackPublish()`
- **User Actions**: `trackView()`, `trackBookmark()`, `trackRating()`, `trackUsefulnessRating()`
- Generic methods: `trackAdminAction()`, `trackUserAction()`

### 2. Tracking Points Added

#### Article Views
**File**: `public/view_article.php`
- Tracks when logged-in users view articles
- Silent failure to not break page if tracking fails

#### Article Ratings
**File**: `public/api/bkd_article_rating.php`
- Tracks star ratings (1-5 stars)
- Tracks usefulness ratings (helpful/not helpful)
- Updated from old tracking method to use new AnalyticsTracker class

#### Bookmarks
**File**: `public/api/bkd_toggle_bookmark.php`
- Tracks when users add bookmarks
- Does NOT track bookmark removal (only additions matter for engagement)
- Updated from old tracking method to use new AnalyticsTracker class

#### Admin Actions - Edit
**File**: `public/api/bkd_articles.php`
- Tracks article edits (both online and non-online versions)
- Captures when new article versions are created

#### Admin Actions - Approve
**File**: `public/api/bkd_articles.php`
- Tracks when moderators/admins approve article versions
- Records approval in `admin_activity_analytics` table

#### Admin Actions - Publish
**File**: `public/api/bkd_articles.php`
- Tracks when articles are published to make them public
- Records in `admin_activity_analytics` table

### 3. Database Tables Used

#### `admin_activity_analytics`
Stores admin actions:
- `user_id`: Who performed the action
- `article_id`: Which article was affected
- `action_type`: 'edit', 'approve', 'publish'
- `action_date`: When it happened
- `ip_address`, `session_id`: Session tracking

#### `user_activity_analytics`
Stores user actions:
- `user_id`: Who performed the action
- `article_id`: Which article was affected
- `action_type`: 'view', 'bookmark', 'rating', 'usefulness_rating', etc.
- `value`: Optional numeric value (e.g., star rating)
- `action_date`: When it happened
- `ip_address`, `session_id`: Session tracking

#### `article_comments`
Already exists - stores comments with:
- `status`: 'approved', 'pending', 'rejected'
- `content`: Comment text
- Used by Top Commenters query

## How It Works

1. **Automatic Tracking**: Every time a user or admin performs a tracked action, the system automatically inserts a record into the analytics tables

2. **Silent Failures**: All tracking is wrapped in try-catch blocks - if tracking fails, the main action still succeeds and only logs an error

3. **Real-Time Updates**: Statistics refresh immediately as users interact with the system

4. **No Manual Scripts Needed**: The `populate_user_statistics.php` script is only needed once to backfill historical data

## Testing

To test the implementation:

1. **View an article** → Check `user_activity_analytics` for 'view' action
2. **Rate an article** (stars or helpful) → Check for 'rating' or 'usefulness_rating' actions
3. **Bookmark an article** → Check for 'bookmark' action
4. **Edit an article** → Check `admin_activity_analytics` for 'edit' action
5. **Approve an article** → Check for 'approve' action
6. **Publish an article** → Check for 'publish' action

## Verification Query

```sql
-- Check recent tracking activity
SELECT 
    'Admin Actions' as type,
    COUNT(*) as count,
    MAX(action_date) as last_action
FROM admin_activity_analytics
WHERE action_date > NOW() - INTERVAL 1 HOUR

UNION ALL

SELECT 
    'User Actions' as type,
    COUNT(*) as count,
    MAX(action_date) as last_action
FROM user_activity_analytics
WHERE action_date > NOW() - INTERVAL 1 HOUR;
```

## Benefits

1. **No Manual Intervention**: Statistics update automatically
2. **Historical Data**: Backfill script (`populate_user_statistics.php`) captures past activity
3. **Real-Time Insights**: Dashboard shows current user engagement immediately
4. **Maintainable**: Centralized tracking logic in `AnalyticsTracker` class
5. **Fail-Safe**: Tracking errors don't break user experience

## Files Modified

1. `classes/analytics_tracker.php` - NEW
2. `includes/functions.php` - Added require for analytics tracker
3. `public/view_article.php` - Added view tracking
4. `public/api/bkd_article_rating.php` - Updated rating tracking
5. `public/api/bkd_toggle_bookmark.php` - Updated bookmark tracking
6. `public/api/bkd_articles.php` - Added edit, approve, publish tracking

## Future Enhancements

Potential additional tracking points:
- Comment submissions
- Search queries
- PDF downloads
- Vote actions (like/dislike)
- Article shares

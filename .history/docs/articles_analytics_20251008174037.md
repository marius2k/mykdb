# Article Analytics Implementation

## Overview
This implementation provides comprehensive article analytics that distinguishes between public user views and administrative views in the analytics system. This delivers more meaningful metrics by separating internal admin activity from actual user engagement, giving accurate insights into content performance and user behavior.

**Note:** The analytics system is now split into two separate modules:
1. **Article Analytics** (this document) - Focused on article performance metrics
2. **User Analytics** (see `user_analytics.md`) - Focused on user engagement and admin activities

## Key Features
- Separate tracking for public vs. admin views
- Detailed reading time metrics for different user types
- Scroll depth tracking and engagement scoring
- User session tracking and unique reader identification
- Optimized queries for performance dashboards

## Implementation Summary

### 1. Database Schema Changes
**File:** `sql/migrations/add_view_source_tracking.sql`
- Added `view_source` ENUM column to `article_views` table
- Added `view_source` ENUM column to `article_reading_time` table
- Created indexes on view_source columns for performance
- Values: 'public', 'admin_preview', 'admin_edit', 'admin_analytics'

**Note:** This migration needs to be run manually by the database administrator.

### 2. Core Tracking Logic Updates
**File:** `includes/functions.php`
- Updated `logArticleView()` function to accept `view_source` parameter
- Added validation for view_source values
- Modified INSERT statement to include view_source column

### 3. View Detection and Classification
**File:** `public/view_article.php`
- Added logic to detect view source based on URL parameters
- Detects admin_source GET parameter for admin views
- Defaults to 'public' for normal user views
- Passes detected source to both view logging and reading tracker

### 4. Reading Time Tracking Updates
**File:** `assets/js/reading-tracker.js`
- Updated constructor to accept viewSource parameter
- Modified saveReadingTime method to include view_source

**File:** `public/api/bkd_article_reading_analytics.php`
- Updated saveReadingTime function to handle view_source parameter
- Added validation and database insertion for view_source

### 5. Admin Navigation Updates
**Files:** 
- `public/admin/articles.php`
- `public/admin/comments.php`
- `public/admin/articles_old.php`

Updated all admin links to view_article.php to include appropriate admin_source parameters:
- Article preview links: `&admin_source=admin_preview`
- Comment management links: `&admin_source=admin_analytics`

### 6. Enhanced Analytics Dashboard
**File:** `public/api/bkd_article_reading_analytics.php`
- Modified analytics queries to separate public/admin metrics
- Added new fields: public_views, admin_views, avg_public_reading_time, etc.
- Updated engagement score calculation to use public metrics only

**File:** `public/admin/articles_analytics.php` (renamed from analytics.php)
- Updated weekly stats display to show public vs admin views
- Enhanced performance chart with separate public/admin datasets
- Modified data table to display public/admin columns separately
- Updated column headers and data population

## View Source Types

| Source Type | Description | Usage |
|-------------|-------------|-------|
| `public` | Normal user views from public site | Default for public article access |
| `admin_preview` | Admin viewing articles for preview/review | Admin article management, version previews |
| `admin_edit` | Admin viewing during editing process | Article editing workflows |
| `admin_analytics` | Admin viewing from analytics/reports | Analytics dashboard, comment management |

## Database Schema

### Article Views Table
```sql
CREATE TABLE article_views (
  id int NOT NULL AUTO_INCREMENT,
  article_id int NOT NULL,
  user_id int DEFAULT NULL,
  viewed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address varchar(45) DEFAULT NULL,
  user_agent varchar(255) DEFAULT NULL,
  view_source varchar(20) DEFAULT 'public',
  PRIMARY KEY (id),
  KEY article_id (article_id),
  KEY idx_article_views_source (article_id, view_source)
);
```

### Article Reading Time Table
```sql
CREATE TABLE article_reading_time (
  id int NOT NULL AUTO_INCREMENT,
  article_id int NOT NULL,
  user_id int DEFAULT NULL,
  session_id varchar(255) NOT NULL,
  reading_time int NOT NULL,
  scroll_percentage decimal(5,2) DEFAULT '0.00',
  page_visibility_time int DEFAULT '0',
  total_time_on_page int DEFAULT '0',
  user_agent text DEFAULT NULL,
  ip_address varchar(45) DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  view_source varchar(20) DEFAULT 'public',
  PRIMARY KEY (id),
  KEY article_id (article_id),
  KEY user_id (user_id),
  KEY session_id (session_id),
  KEY idx_article_reading_time_source (article_id, view_source)
);
```

## Key Analytics Queries

### Public vs Admin Views Separation
The system uses SQL subqueries to separate public and admin views:

```sql
-- Total views
(SELECT COUNT(*) FROM article_views WHERE article_id = a.id) as total_views,
-- Public views only
(SELECT COUNT(*) FROM article_views 
 WHERE article_id = a.id AND (view_source = 'public' OR view_source IS NULL)) as public_views,
-- Admin views only
(SELECT COUNT(*) FROM article_views 
 WHERE article_id = a.id AND view_source IN ('admin_preview', 'admin_edit', 'admin_analytics')) as admin_views
```

### Reading Time Metrics
Reading time is calculated separately for public and admin sessions:

```sql
-- Average reading time overall
AVG(reading_time) as avg_reading_time,
-- Public reading time only
(SELECT AVG(reading_time) FROM article_reading_time 
 WHERE article_id = ? AND (view_source = 'public' OR view_source IS NULL)) as avg_public_reading_time,
-- Admin reading time only
(SELECT AVG(reading_time) FROM article_reading_time 
 WHERE article_id = ? AND view_source IN ('admin_preview', 'admin_edit', 'admin_analytics')) as avg_admin_reading_time
```

## Key Benefits

1. **Meaningful Metrics**: Public engagement metrics are no longer skewed by admin activity
2. **Admin Insights**: Administrators can see their own usage patterns separately
3. **Better Decisions**: Content performance decisions based on actual user engagement
4. **Audit Trail**: Track different types of administrative access
5. **Performance**: Optimized queries with proper indexing

## Database Migration Required

Before using this implementation, run the SQL migration:

```sql
-- Run the migration file
source sql/migrations/add_view_source_tracking.sql;
```

## Testing

1. **Public Views**: Navigate to articles normally - should be tracked as 'public'
2. **Admin Preview**: Use admin article management links - should be tracked as 'admin_preview'
3. **Analytics Dashboard**: Verify separate public/admin metrics display correctly
4. **Reading Time**: Confirm reading time tracking includes source information

## Backward Compatibility

- Existing data without view_source will be treated as 'public' (NULL values)
- All analytics functions maintain backward compatibility
- Original metrics (total_views, total_reading_time) remain available

## Analytics Dashboard Components

The analytics dashboard is implemented in `public/admin/analytics.php` and consists of:

1. **Weekly Statistics Cards**
   - Shows overview metrics with separate public/admin stats
   - Includes views, reading time, engagement scores

2. **Performance Charts**
   - Visual representation of top article performance
   - Separates public views from admin views for clarity

3. **Top Articles Table**
   - Detailed metrics for all articles
   - Sortable by various engagement metrics
   - Includes:
     - Public/Admin view counts
     - Average public reading time
     - Scroll percentage
     - Unique readers
     - Engagement score

4. **Article Detail View**
   - In-depth analysis of specific article performance
   - Daily trend charts for views and reading time
   - Comprehensive engagement metrics

## Key Metrics Explained

1. **Public Views**: Count of article views by regular users
2. **Admin Views**: Count of article views by administrators
3. **Average Public Reading Time**: Mean time spent reading by public users
4. **Scroll Percentage**: Average scroll depth reached by users
5. **Unique Readers**: Count of distinct users/sessions
6. **Engagement Score**: Composite metric combining views, likes, and reading sessions

## Troubleshooting

### Common Issues and Solutions

1. **Zero Values in Public/Admin Views**
   - Check that the `view_source` column exists in database tables
   - Ensure SQL queries correctly filter by view_source
   - Verify that view_source is being properly set when logging views

2. **Missing Reading Time Data**
   - Check browser console for JavaScript errors
   - Verify AJAX calls to saveReadingTime endpoint
   - Ensure reading-tracker.js is properly initialized with view_source

3. **Incorrect Metrics**
   - Run SQL queries directly to verify data
   - Check that aggregation and filtering logic is correct
   - Ensure NULL values are handled correctly in view_source filters

## Future Enhancements

1. Add user role tracking to view_source for more granular insights
2. Implement time-based view source analysis
3. Add view source filtering to analytics dashboard
4. Create admin-specific analytics reports
5. Implement heat maps for content engagement
6. Add geographic analytics for view sources
7. Enable custom date range selections for all analytics
8. Implement exportable reports and data
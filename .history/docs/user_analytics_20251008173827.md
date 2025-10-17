# User Analytics System

This document describes the User Analytics system implementation in the Knowledge Base application.

## Overview

The User Analytics system is designed to track and analyze user engagement with the content, providing administrators with valuable insights into how users interact with articles and how admins manage the content. The system tracks various user actions such as article views, bookmarks, ratings, comments, and also monitors admin actions like edits, approvals, and publishing.

## Architecture

The User Analytics system is built on a separate architecture from the Article Analytics system:

1. **Frontend:** `public/admin/user_analytics.php` - Admin dashboard for user analytics
2. **API Backend:** `public/api/bkd_user_analytics.php` - Backend API for user analytics data
3. **Database:** 
   - `user_activity_analytics` - Raw activity tracking
   - `user_activity_summary` - Aggregated daily summaries
   - `user_reading_time` - User reading time tracking
   - `admin_activity_analytics` - Admin-specific activity tracking

## Key Features

### 1. User Engagement Tracking

The system tracks the following user engagement metrics:

- **Article views** - When users view articles
- **Bookmarks** - When users bookmark articles for later reading
- **Comments** - When users comment on articles
- **Ratings** - Both 5-star ratings and usefulness ratings (yes/no)
- **PDF Saves** - When users download articles as PDF
- **Reading Time** - How long users spend reading articles

### 2. Admin Activity Tracking

For administrators, editors, and moderators, the system tracks:

- **Edits** - When admins edit articles
- **Publishing** - When admins publish new or updated articles
- **Approvals** - When admins approve articles or comments
- **Rejections** - When admins reject articles or comments

### 3. Analytics Dashboard

The user analytics dashboard is divided into three main sections:

1. **User Engagement** - Overall user engagement metrics, engagement over time, most engaged users, and engagement by user role
2. **Admin Activity** - Admin activity over time, activity by admin, and most active articles
3. **Content Interaction** - How users interact with content through bookmarks, ratings, comments, etc.

## Database Schema

### user_activity_analytics

Tracks individual user actions:

```sql
CREATE TABLE IF NOT EXISTS `user_activity_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` enum('view', 'bookmark', 'comment', 'rating', 'vote', 'edit', 'publish', 'approve', 'save_pdf', 'usefulness_rating') NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `value` decimal(10,2) DEFAULT NULL COMMENT 'Used for ratings, votes, etc.',
  `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_action_date` (`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### user_activity_summary

Aggregates daily activity for faster reporting:

```sql
CREATE TABLE IF NOT EXISTS `user_activity_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_date` date NOT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `bookmarks_count` int(11) NOT NULL DEFAULT 0,
  `comments_count` int(11) NOT NULL DEFAULT 0,
  `ratings_count` int(11) NOT NULL DEFAULT 0,
  `votes_count` int(11) NOT NULL DEFAULT 0,
  `edits_count` int(11) NOT NULL DEFAULT 0,
  `publishes_count` int(11) NOT NULL DEFAULT 0,
  `approvals_count` int(11) NOT NULL DEFAULT 0,
  `pdf_saves_count` int(11) NOT NULL DEFAULT 0,
  `usefulness_ratings_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`, `activity_date`),
  KEY `idx_activity_date` (`activity_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### admin_activity_analytics

Tracks administrator actions for better governance:

```sql
CREATE TABLE IF NOT EXISTS `admin_activity_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` enum('edit', 'publish', 'approve', 'reject', 'delete', 'restore') NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_action_date` (`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Implementation

### Tracking User Activity

The system tracks user activity through the `bkd_user_analytics.php` API endpoint. Activities are tracked by making POST requests to this endpoint with relevant action data:

```javascript
// Example of tracking a bookmark action
fetch('/api/bkd_user_analytics.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    action: 'track_user_action',
    article_id: 123,
    action_type: 'bookmark'
  })
})
```

### Database Triggers

The system uses database triggers to automatically update the summary tables when new activity records are inserted:

```sql
CREATE TRIGGER update_user_activity_summary
AFTER INSERT ON user_activity_analytics
FOR EACH ROW
BEGIN
    -- Update summary table based on action type
    -- ...
END;
```

## Access Control

Access to the user analytics dashboard is restricted to specific user roles:

- **Article Analytics:** Visible to moderator, editor, admin, and superadmin roles
- **User Analytics:** Restricted to editor, admin, and superadmin roles only

## Future Enhancements

Potential future enhancements for the User Analytics system:

1. Real-time activity monitoring
2. User segmentation and cohort analysis
3. Personalized content recommendations based on user behavior
4. Advanced filtering and drill-down capabilities
5. Email reports and scheduled exports
6. Anomaly detection for suspicious activity
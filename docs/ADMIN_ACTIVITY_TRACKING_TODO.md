# Admin Activity Analytics - No Data Issue

**Date:** October 17, 2025  
**Status:** ⚠️ NOT A BUG - Admin actions are not yet being tracked

## Issue

The **Admin Activity** tab shows no data:
- Admin Activity Over Time chart is empty
- Activity by Admin table is empty
- Most Active Articles table is empty

## Root Cause

The `admin_activity_analytics` table is **empty** (0 records) because admin actions are **not being tracked yet**.

### Database Check
```bash
docker exec mykdb-db mysql -u root knowledge_db -e \
  "SELECT COUNT(*) FROM admin_activity_analytics;"
```
**Result:** 0 records

### What Should Be Tracked

According to the schema, these admin actions should be tracked:
- `edit` - Article edits
- `publish` - Article publications
- `approve` - Article/comment approvals
- `reject` - Article/comment rejections
- `delete` - Deletions
- `restore` - Restorations

## How Tracking Works

The `trackUserActionDirect()` function in `bkd_user_analytics.php` routes actions to the correct table:

```php
$isAdminAction = in_array($actionType, ['edit', 'publish', 'approve', 'reject', 'delete', 'restore']);
$tableName = $isAdminAction ? 'admin_activity_analytics' : 'user_activity_analytics';
```

**Admin actions** → `admin_activity_analytics` table  
**User actions** → `user_activity_analytics` table (bookmark, view, search, rating, etc.)

## Current State

### Tables Status
✅ `admin_activity_analytics` - Table exists (structure is correct)  
✅ `user_activity_analytics` - Has 53 records (working)  

### Tracking Status
✅ User actions ARE being tracked (views, bookmarks, searches, ratings)  
❌ Admin actions ARE NOT being tracked yet

## Solution: Implement Admin Action Tracking

To populate the Admin Activity dashboard, admin actions need to be tracked in the following files:

### 1. Article Edit Tracking
**File:** `/public/api/bkd_edit_article.php` or similar

Add after successful edit:
```php
require_once 'bkd_user_analytics.php';
trackUserActionDirect([
    'user_id' => $_SESSION['user']['id'],
    'action_type' => 'edit',
    'article_id' => $articleId
]);
```

### 2. Article Publish Tracking
**File:** `/public/api/bkd_submit_article.php` or article submission endpoint

Add after successful publish:
```php
require_once 'bkd_user_analytics.php';
trackUserActionDirect([
    'user_id' => $_SESSION['user']['id'],
    'action_type' => 'publish',
    'article_id' => $articleId
]);
```

### 3. Article Approval Tracking
**File:** `/public/api/bkd_approve_article.php` or similar

Add after successful approval:
```php
require_once 'bkd_user_analytics.php';
trackUserActionDirect([
    'user_id' => $_SESSION['user']['id'],
    'action_type' => 'approve',
    'article_id' => $articleId,
    'status' => 'approved'
]);
```

### 4. Comment Approval Tracking
**File:** `/public/api/bkd_approve_comment.php` or similar

Add after successful approval:
```php
require_once 'bkd_user_analytics.php';
trackUserActionDirect([
    'user_id' => $_SESSION['user']['id'],
    'action_type' => 'approve',
    'comment_id' => $commentId,
    'target_type' => 'comment'
]);
```

### 5. Rejection Tracking
**Files:** `/public/api/bkd_reject_article.php`, `/public/api/bkd_reject_comment.php`

Add after successful rejection:
```php
require_once 'bkd_user_analytics.php';
trackUserActionDirect([
    'user_id' => $_SESSION['user']['id'],
    'action_type' => 'reject',
    'article_id' => $articleId,  // or comment_id
    'status' => 'rejected'
]);
```

## Files That Need Tracking Integration

Search for these API endpoints in `/public/api/` or `/public/`:
- [ ] `bkd_edit_article.php` or article edit endpoint
- [ ] `bkd_submit_article.php` or article publish endpoint
- [ ] `bkd_approve_article.php` or article approval endpoint
- [ ] `bkd_reject_article.php` or article rejection endpoint
- [ ] `bkd_approve_comment.php` or comment approval endpoint
- [ ] `bkd_reject_comment.php` or comment rejection endpoint
- [ ] Any delete/restore endpoints

## Quick Test

To verify the system works, you can manually insert a test record:

```sql
INSERT INTO admin_activity_analytics 
(user_id, action_type, article_id, action_date, ip_address) 
VALUES 
(2, 'edit', 1, NOW(), '192.168.1.178');
```

Then refresh the Admin Activity tab to see if it displays.

## Summary

**The Admin Activity dashboard is functioning correctly** - it's just waiting for admin actions to be tracked. Once tracking calls are added to the admin action endpoints (edit, publish, approve, reject), the dashboard will populate with data.

## Next Steps

1. **Identify admin action endpoints** - Find files that handle edit, publish, approve, reject
2. **Add tracking calls** - Insert `trackUserActionDirect()` calls after successful actions
3. **Test** - Perform admin actions and verify data appears in dashboard

## Related Files

- `/public/api/bkd_user_analytics.php` - Contains `trackUserActionDirect()` function
- `/public/admin/user_analytics.php` - Admin Activity dashboard (frontend)
- Database: `admin_activity_analytics` table (backend storage)

## Status: ⏳ AWAITING IMPLEMENTATION

The tracking infrastructure is ready. Admin action tracking just needs to be integrated into the admin workflow endpoints.

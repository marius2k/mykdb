# Applying the Moderator Approve Permission Fix

## Issue
Moderators were unable to approve pending articles in the Admin→Articles interface because they lacked the `approve_article` permission in their role definition.

## What Was Changed
The `approve_article` permission has been added to the moderator role in `public/admin/seed_acl.php`:

```php
'moderator' => ['approve_article', 'disable_user', 'enable_user', 'approve_comment', 'modify_own_user'],
```

## How to Apply the Fix

### Option 1: Run the ACL Seed Script (Recommended)
This will update the database with the new permissions:

1. Access your application through a web browser
2. Navigate to: `http://your-domain/mykdb/public/admin/seed_acl.php`
3. You should see a success message: "🎉 ACL configurat cu succes!"
4. The moderator role now has the `approve_article` permission

**OR** run it from command line:
```bash
cd /path/to/mykdb
php public/admin/seed_acl.php
```

### Option 2: Manual Database Update
If you prefer to update the database directly:

```sql
-- Find the moderator role ID
SELECT id FROM roles WHERE name = 'moderator';

-- Find the approve_article operation ID
SELECT id FROM operations WHERE name = 'approve_article';

-- Add the permission (replace X and Y with the actual IDs from above)
INSERT INTO role_permissions (role_id, operation_id) 
VALUES (X, Y)
ON DUPLICATE KEY UPDATE role_id=role_id;
```

## Verification

After applying the fix, verify that moderators can now approve articles:

1. Log in with a moderator account
2. Navigate to Admin → Articles
3. Find an article with "pending" status
4. The approve icon (✓) should now be **active** (not grayed out)
5. Click the approve icon to approve the article

## Documentation
This fix aligns the implementation with the documented behavior in `docs/roles_and_permissions.md`, which states that moderators should be able to approve pending articles (line 120).

## Additional Notes
- This change only affects new permission checks; users who are already logged in may need to log out and log back in for the permissions to take effect
- The `seed_acl.php` script uses `REPLACE INTO`, so it's safe to run multiple times
- No other roles are affected by this change

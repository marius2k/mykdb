# Search Analytics Navigation Menu

## Overview
Added a dedicated **Analytics** submenu to the Admin dropdown in the main navigation bar, providing easy access to all analytics dashboards.

## Implementation Details

### Location
File: `includes/functions.php`
Function: `generateNavBar2($uid)`

### Menu Structure
```
Admin (dropdown)
  ├── Logs
  ├── Users
  │   ├── Manage Users
  │   └── User Analytics
  ├── Categories
  ├── Articles
  │   ├── View Articles
  │   └── Article Analytics
  ├── Tags
  ├── Comments
  ├── Analytics ← NEW SUBMENU
  │   ├── User Analytics
  │   ├── Article Analytics
  │   └── Search Analytics
  └── ACL
```

### Access Control
- **Visibility**: Only users with roles `editor`, `admin`, or `superadmin` can see the Analytics submenu
- **Implementation**: Uses role-based check instead of permission-based to simplify access control

```php
$userRole = $_SESSION['user']['role'] ?? '';
if (in_array($userRole, ['editor', 'admin', 'superadmin'])) {
    // Show Analytics submenu
}
```

### Menu Items

1. **User Analytics** (`user_analytics.php`)
   - Icon: `icon-user-analytics.svg`
   - Shows user engagement, admin activity, content interactions, and search behavior

2. **Article Analytics** (`articles_analytics.php`)
   - Icon: `icon-analytics.svg`
   - Displays article performance metrics and statistics

3. **Search Analytics** (`search_analytics.php`)
   - Icon: `icon-view.svg`
   - Provides search query analysis, CTR, trends, and zero-result tracking

### Active Page Detection
Updated the `$isAdminPageActive` array to include analytics pages for proper navigation highlighting:

```php
$isAdminPageActive = in_array($currentPage, [
    'logs.php', 
    'users.php', 
    'categories.php', 
    'articles.php', 
    'articles_analytics.php', 
    'user_analytics.php', 
    'search_analytics.php',  // Added
    'tags.php', 
    'comments.php', 
    'acl_edit.php'
]);
```

## Usage

### For Users
1. Log in as editor, admin, or superadmin
2. Click **Admin** in the navigation bar
3. Hover over or click **Analytics** submenu
4. Select desired analytics dashboard:
   - **User Analytics** - User behavior and engagement
   - **Article Analytics** - Article performance metrics
   - **Search Analytics** - Search query analysis

### Visual Indicators
- Active page highlighting works automatically
- Admin dropdown shows as active when on any admin page, including analytics pages
- Dropdown menus use hover and click interactions

## Benefits

1. **Centralized Access**: All analytics dashboards are now accessible from a single menu
2. **Consistent UX**: Follows the existing admin menu pattern with submenu structure
3. **Role-Based Security**: Only authorized users can see and access analytics
4. **Easy Navigation**: No need to remember URLs or navigate via breadcrumbs
5. **Future-Proof**: Easy to add more analytics dashboards to the submenu

## Testing Checklist

- [ ] Test menu visibility for different roles (guest, reader, contributor, moderator, editor, admin, superadmin)
- [ ] Verify all three analytics links work correctly
- [ ] Check active page highlighting on each analytics page
- [ ] Test dropdown behavior on desktop and mobile
- [ ] Verify icons display correctly
- [ ] Test keyboard navigation (accessibility)

## Related Files

- `includes/functions.php` - Navigation generation
- `includes/header.php` - Navigation rendering
- `public/admin/user_analytics.php` - User analytics dashboard
- `public/admin/articles_analytics.php` - Article analytics dashboard
- `public/admin/search_analytics.php` - Search analytics dashboard (new)

## Notes

- The Analytics submenu is separate from individual analytics links within Articles and Users submenus
- This provides both contextual access (within Articles/Users) and centralized access (Analytics submenu)
- Icons may need to be updated/customized based on design preferences
- Consider adding tooltips for better UX in future iterations

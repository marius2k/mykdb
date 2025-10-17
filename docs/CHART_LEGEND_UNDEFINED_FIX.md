# Chart Legend "undefined" Fix - October 17, 2025

## Issue

The **Engagement by Role** chart was showing "undefined" for both series in the legend instead of proper labels.

## Root Cause

The JavaScript code was using `USER_ANALYTICS_TRANSLATIONS.users` and `USER_ANALYTICS_TRANSLATIONS.avg_interactions` for the chart legend labels, but these keys were missing from the translations object.

### Code Using Missing Translations

**File:** `/public/admin/user_analytics.php`  
**Function:** `displayRoleEngagementChart()`

```javascript
datasets: [
    {
        label: USER_ANALYTICS_TRANSLATIONS.users,  // ❌ undefined
        data: userCounts,
        ...
    },
    {
        label: USER_ANALYTICS_TRANSLATIONS.avg_interactions,  // ❌ undefined
        data: avgInteractions,
        ...
    }
]
```

## Fix Applied

Added missing translation keys to the `USER_ANALYTICS_TRANSLATIONS` object:

```javascript
const USER_ANALYTICS_TRANSLATIONS = {
    // ... existing translations ...
    users: '<?= lang('lang_analytics_users') ?? 'Users' ?>',
    avg_interactions: '<?= lang('lang_analytics_avg_interactions') ?? 'Avg. Interactions' ?>',
    engagement_over_time: '<?= lang('lang_analytics_engagement_over_time') ?? 'Engagement Over Time' ?>',
    engagement_by_role: '<?= lang('lang_analytics_engagement_by_role') ?? 'Engagement by Role' ?>',
    admin_activity_over_time: '<?= lang('lang_analytics_admin_activity_over_time') ?? 'Admin Activity Over Time' ?>',
    data_incomplete: '<?= lang('lang_analytics_data_incomplete') ?? 'Some data could not be loaded...' ?>',
    // ... rest of translations ...
};
```

## Chart Series Explained

The **Engagement by Role** chart displays **TWO series** with dual Y-axes:

### Series 1: Users (Blue bars - Left Y-axis)
- **Label:** "Users"
- **Data:** Number of users per role
- **Color:** Light blue (`rgba(52, 152, 219, 0.7)`)
- **Y-Axis:** Left side (y)
- **Example:** admin: 1 user, moderator: 1 user, contributor: 1 user

### Series 2: Avg. Interactions (Purple bars - Right Y-axis)
- **Label:** "Avg. Interactions"
- **Data:** Average interactions per user for each role
- **Color:** Purple (`rgba(155, 89, 182, 0.7)`)
- **Y-Axis:** Right side (y1)
- **Example:** admin: 46.0 avg, moderator: 4.0 avg, contributor: 3.0 avg

## Visual Interpretation

The chart shows:
- **How many users** are active in each role (blue bars)
- **How engaged** users in each role are on average (purple bars)

For example, from your data:
- **Admin role:** 1 user with 46 average interactions (very engaged)
- **Moderator role:** 1 user with 4 average interactions
- **Contributor role:** 1 user with 3 average interactions

## Additional Translations Added

While fixing this issue, I also added other missing translations that were referenced elsewhere in the code:

- `engagement_over_time` - For chart titles
- `engagement_by_role` - For chart titles
- `admin_activity_over_time` - For chart titles
- `data_incomplete` - For error messages

## Testing

**Refresh the User Analytics page:**
1. Go to **Admin → Users → User Analytics**
2. The **Engagement by Role** chart should now show:
   - Legend: "Users" (blue) and "Avg. Interactions" (purple)
   - No more "undefined" labels

## Related Files

- `/public/admin/user_analytics.php` - Fixed translations (UPDATED)
- Chart uses Chart.js library with dual Y-axes configuration

## Fallback Values

If the language file doesn't have the translations defined, fallback English values are used:
- `lang_analytics_users` → fallback: "Users"
- `lang_analytics_avg_interactions` → fallback: "Avg. Interactions"

## Status: ✅ FIXED

The chart legend should now display proper labels instead of "undefined".

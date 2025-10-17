# Search Analytics Debounce Fix

**Date:** October 17, 2025  
**Issue:** Multiple partial search queries being saved as separate records

## Problem

When typing a search query like "stack", the system was saving multiple records:
- "s"
- "st"
- "sta"
- "stac"
- "stack"

This happened because the search function runs on every keystroke (debounced at 300ms for UI), and the analytics tracking was firing immediately for each search.

## Solution

Implemented a **3-second debounce timer** specifically for analytics tracking.

### Changes Made

**File:** `/assets/js/search-analytics.js`

1. **Increased debounce time:**
   - Changed from `debounceTime: 500` to `debounceTime: 3000` (3 seconds)

2. **Added separate tracking timer:**
   - Added `trackingTimer` to state variables
   - This timer is separate from the UI debounce timer

3. **Updated trackSearch() function:**
   - Now clears the previous tracking timer on each call
   - Updates search state immediately (for result click correlation)
   - Waits 3 seconds before actually saving to database
   - Only the final query after user stops typing gets saved

### How It Works

```javascript
// User types: "s" → "st" → "sta" → "stac" → "stack"

// Keystroke: "s" → Start 3s timer
// Keystroke: "st" → Clear timer, start new 3s timer
// Keystroke: "sta" → Clear timer, start new 3s timer  
// Keystroke: "stac" → Clear timer, start new 3s timer
// Keystroke: "stack" → Clear timer, start new 3s timer
// [User stops typing]
// [3 seconds pass]
// → Save only "stack" to database ✅
```

### Benefits

✅ Reduces database noise - only final queries saved  
✅ More accurate search analytics  
✅ Better performance - fewer API calls  
✅ User clicks still work during the debounce period (state updated immediately)  

### Configuration

To adjust the delay, modify in `/assets/js/search-analytics.js`:

```javascript
config: {
    debounceTime: 3000, // Change this value (in milliseconds)
}
```

## Testing

1. Go to the search page
2. Type a query slowly (e.g., "stack")
3. Open browser console to see: `Tracking search (after debounce): {query: "stack", ...}`
4. Check database - should only see final query saved:

```sql
SELECT id, query, result_count, created_at 
FROM search_queries 
ORDER BY id DESC LIMIT 10;
```

## Related Files

- `/assets/js/search-analytics.js` - Main tracking logic
- `/public/index.php` - Search interface (calls trackSearch)
- `/public/api/bkd_search_analytics.php` - Backend API

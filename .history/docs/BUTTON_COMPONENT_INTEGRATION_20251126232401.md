# React Button Component Integration

## Overview

This document explains how the reusable React Button component was created and integrated into `logs.php` to replace traditional HTML buttons with React components.

## Files Created/Modified

### 1. Button Component (`assets/js/react-components/Button.jsx`)

**Purpose:** Reusable button component with multiple variants, sizes, and states.

**Props:**
- `text` (required): Button text to display
- `onClick` (required): Function to call when button is clicked
- `variant` (optional, default: 'primary'): Button style variant
  - `'primary'` - Blue button
  - `'danger'` - Red button
  - `'warning'` - Orange button
  - `'success'` - Green button
- `disabled` (optional, default: false): Disable button clicks
- `icon` (optional): Emoji or icon to display before text
- `size` (optional, default: 'medium'): Button size
  - `'small'` - Smaller padding and font
  - `'medium'` - Standard size
  - `'large'` - Larger padding and font
- `loading` (optional, default: false): Show loading spinner
- `className` (optional): Additional CSS classes to apply

**Example Usage:**
```jsx
<Button 
  text="Delete Selected" 
  onClick={handleDelete}
  variant="danger"
  icon="🗑️"
  size="medium"
/>
```

### 2. Button Styles (`assets/css/react-components.css`)

**Added Styles:**
- `.react-button` - Base button styling
- `.react-button--primary/danger/warning/success` - Variant styles
- `.react-button--small/medium/large` - Size variations
- `.react-button--disabled` - Disabled state (50% opacity)
- `.react-button--loading` - Loading state with spinner
- Hover effects with `translateY(-2px)` and shadow
- Spin animation for loading spinner

### 3. Integration in logs.php

**Before (Traditional HTML):**
```php
<button type="button" class="btn btn-outline-grey" onclick="submitBulkLogs('archive')">
    <?= lang('lang_log_archive_selected') ?>
</button>
<button type="button" class="btn btn-outline-grey" onclick="submitBulkLogs('delete')">
    <?= lang('lang_log_delete_selected') ?>
</button>
```

**After (React Components):**
```html
<!-- Container divs for React to render into -->
<div id="archive-button-root"></div>
<div id="delete-button-root"></div>

<!-- React rendering script -->
<script type="text/babel">
document.addEventListener('DOMContentLoaded', () => {
    const archiveRoot = ReactDOM.createRoot(document.getElementById('archive-button-root'));
    const deleteRoot = ReactDOM.createRoot(document.getElementById('delete-button-root'));
    
    archiveRoot.render(
        React.createElement(Button, {
            text: '<?= lang('lang_log_archive_selected') ?>',
            onClick: () => submitBulkLogs('archive'),
            variant: 'warning',
            icon: '📦',
            size: 'medium'
        })
    );
    
    deleteRoot.render(
        React.createElement(Button, {
            text: '<?= lang('lang_log_delete_selected') ?>',
            onClick: () => submitBulkLogs('delete'),
            variant: 'danger',
            icon: '🗑️',
            size: 'medium'
        })
    );
});
</script>
```

**CDN Scripts Added:**
```html
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/react-components.css">
<script type="text/babel" src="<?= APP_URL ?>assets/js/react-components/Button.jsx"></script>
```

## Key Concepts Demonstrated

### 1. Props as Function Parameters
```javascript
const Button = ({ text, onClick, variant='primary' }) => {
  // Props are destructured from the first parameter
  // Default values are provided inline
}
```

### 2. Dynamic CSS Classes
```javascript
const buttonClass = [
  'react-button',
  `react-button--${variant}`,
  `react-button--${size}`,
  disabled && 'react-button--disabled',
  loading && 'react-button--loading',
  className
].filter(Boolean).join(' ');
```

### 3. Conditional Rendering
```javascript
{loading ? (
  <span className="button-spinner">⟳</span>
) : (
  <>
    {icon && <span className="button-icon">{icon}</span>}
    {text}
  </>
)}
```

### 4. Event Handling with State Prevention
```javascript
const handleClick = (e) => {
  if (disabled || loading) return; // Prevent clicks when disabled
  onClick(e);
};
```

### 5. React.createElement vs JSX
In logs.php, we use `React.createElement` because the script tag isn't transformed by Babel:
```javascript
// Without JSX (in regular script tag)
React.createElement(Button, {
  text: 'Delete',
  onClick: handleDelete,
  variant: 'danger'
})

// With JSX (in type="text/babel" script)
<Button 
  text="Delete"
  onClick={handleDelete}
  variant="danger"
/>
```

## Component Reusability

The Button component can now be used across **any page** in the application. Examples:

**User Management:**
```javascript
<Button text="Ban User" onClick={banUser} variant="danger" icon="🚫" />
<Button text="Promote to Admin" onClick={promote} variant="success" icon="⬆️" />
```

**Article Editor:**
```javascript
<Button text="Save Draft" onClick={saveDraft} variant="warning" icon="💾" />
<Button text="Publish" onClick={publish} variant="success" icon="✅" />
<Button text="Delete" onClick={deleteArticle} variant="danger" icon="🗑️" />
```

**Forms:**
```javascript
<Button text="Submit" onClick={handleSubmit} variant="primary" loading={isSubmitting} />
<Button text="Cancel" onClick={handleCancel} variant="warning" disabled={isSubmitting} />
```

## Testing Checklist

- [ ] Archive button renders with warning (orange) color
- [ ] Delete button renders with danger (red) color
- [ ] Icons (📦 and 🗑️) appear before text
- [ ] Hover effect shows shadow and lift
- [ ] Clicking buttons calls `submitBulkLogs` function
- [ ] Multi-language text displays correctly
- [ ] Buttons work in light, dark, and gray themes
- [ ] Responsive layout maintains button spacing

## Next Steps

### More Component Ideas
1. **SearchBar Component** - Reusable search input with clear button
2. **Modal Component** - Confirmation dialogs for delete actions
3. **Dropdown Component** - Better filter selects
4. **Toast Component** - Success/error notifications
5. **Pagination Component** - Replace DataTables pagination

### Advanced Button Features
- Add `tooltip` prop for hover descriptions
- Add `badge` prop for notification counts
- Add `dropdown` prop for split buttons with menus
- Add `confirmText` prop for confirmation dialogs

### Performance Optimization
- Move from CDN to local React build
- Use React production builds for better performance
- Implement code splitting for larger apps

## Learning Resources

- [React Props Documentation](https://react.dev/learn/passing-props-to-a-component)
- [React Event Handling](https://react.dev/learn/responding-to-events)
- [Conditional Rendering](https://react.dev/learn/conditional-rendering)
- [Component Composition](https://react.dev/learn/passing-props-to-a-component#passing-jsx-as-children)

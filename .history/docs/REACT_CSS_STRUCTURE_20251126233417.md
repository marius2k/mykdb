# React Component CSS Structure

## Overview

The React component styles have been split into separate CSS files, one for each component. This follows best practices for:
- **Modularity**: Each component has its own styling
- **Maintainability**: Easy to find and edit component-specific styles
- **Performance**: Load only the CSS you need per page
- **Scalability**: Simple to add new component styles

## File Structure

```
assets/css/
├── Button.css              # Button component styles
├── DashboardStats.css      # Dashboard Statistics component styles
└── [Future components].css # Additional component styles
```

## Component CSS Files

### 1. Button.css
**Component:** `assets/js/react-components/Button.jsx`

**Classes:**
- `.react-button` - Base button styling
- `.react-button--primary` - Primary variant (blue)
- `.react-button--danger` - Danger variant (red)
- `.react-button--warning` - Warning variant (orange)
- `.react-button--success` - Success variant (green)
- `.react-button--small` - Small size
- `.react-button--medium` - Medium size (default)
- `.react-button--large` - Large size
- `.react-button--disabled` - Disabled state
- `.react-button--loading` - Loading state
- `.button-icon` - Icon styling
- `.button-spinner` - Loading spinner

**Animations:**
- `@keyframes spin` - Spinner rotation

**Usage in Pages:**
```html
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/Button.css">
```

### 2. DashboardStats.css
**Component:** `assets/js/react-components/DashboardStats.jsx`

**Classes:**
- `.dashboard-stats-container` - Main container
- `.stats-loading` - Loading state
- `.stats-grid` - Grid layout for stat cards
- `.stat-card` - Individual stat card
- `.stat-icon` - Icon in card
- `.stat-content` - Card content wrapper
- `.articles-card` - Articles card variant (blue border)
- `.total-card` - Total articles card variant (green border)
- `.users-card` - Users card variant (red border)
- `.stat-details` - Grid for stat items
- `.stat-item` - Individual stat row
- `.stat-label` - Stat label text
- `.stat-value` - Stat value text
- `.stat-number` - Large number display
- `.stat-subtitle` - Subtitle text
- `.online-status` - Online users indicator
- `.online-indicator` - Pulsing dot

**Animations:**
- `@keyframes pulse` - Pulsing effect for online indicator

**Responsive:**
- Mobile breakpoint at 768px
- Single column layout on mobile

**Usage in Pages:**
```html
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/DashboardStats.css">
```

## Usage Guidelines

### For New Components

When creating a new React component:

1. **Create the component file:**
   ```
   assets/js/react-components/MyComponent.jsx
   ```

2. **Create the CSS file:**
   ```
   assets/css/MyComponent.css
   ```

3. **Use BEM-like naming:**
   ```css
   /* Base component */
   .my-component { }
   
   /* Element */
   .my-component__element { }
   
   /* Modifier */
   .my-component--variant { }
   ```

4. **Link in PHP pages:**
   ```html
   <link rel="stylesheet" href="<?= APP_URL ?>assets/css/MyComponent.css">
   ```

### Loading Multiple Component Styles

If a page uses multiple React components, load all needed CSS files:

```html
<!-- Load component styles -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/Button.css">
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/DashboardStats.css">
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/SearchBar.css">
```

### Performance Tips

1. **Load only what you need**: Don't load all component CSS on every page
2. **Combine in production**: Consider combining frequently used component CSS into bundles
3. **Use CSS variables**: Define theme colors in a separate variables file
4. **Minify**: Minify CSS files for production

## Example: Creating a New Component

Let's say you want to create a `Modal` component:

**1. Component file** (`assets/js/react-components/Modal.jsx`):
```jsx
const Modal = ({ isOpen, onClose, title, children }) => {
  if (!isOpen) return null;
  
  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <h3>{title}</h3>
          <button className="modal-close" onClick={onClose}>×</button>
        </div>
        <div className="modal-body">
          {children}
        </div>
      </div>
    </div>
  );
};

window.Modal = Modal;
```

**2. CSS file** (`assets/css/Modal.css`):
```css
/* Modal Component Styles */

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
}
```

**3. Use in page:**
```html
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/Modal.css">
<script type="text/babel" src="<?= APP_URL ?>assets/js/react-components/Modal.jsx"></script>
```

## Migration from Old Structure

### Before (Monolithic)
```html
<!-- Everything in one file -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/react-components.css">
```

### After (Component-specific)
```html
<!-- Load only what you need -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/Button.css">
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/DashboardStats.css">
```

## Benefits

✅ **Better organization**: Easy to find component styles  
✅ **Faster development**: No scrolling through one huge CSS file  
✅ **Better performance**: Load only needed styles per page  
✅ **Easier maintenance**: Changes to one component don't affect others  
✅ **Clear dependencies**: Know which styles belong to which component  
✅ **Scalable**: Easy to add new components without growing one file  

## Future Enhancements

Consider these improvements for production:

1. **CSS Modules**: Use CSS modules for scoped styles
2. **CSS-in-JS**: Consider styled-components or emotion for React
3. **Build process**: Add PostCSS, autoprefixer, minification
4. **Theme system**: Create CSS variables for consistent theming
5. **Component library**: Build a design system with Storybook

# CardInfoBox Component

## Overview
`CardInfoBox` is a reusable React component for displaying information cards with an icon, title, and body content. It features a clean, modern design with support for light, dark, and gray themes.

## Component Structure

```
card-info-box
├── card-info-header
│   ├── card-info-icon (optional)
│   └── card-info-title
└── card-info-body (optional)
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `icon` | string \| React.Element | `null` | Icon to display (image URL, emoji, or React element) |
| `title` | string | `''` | Card title/heading |
| `body` | string \| React.Element | `''` | Card body content (HTML string or React elements) |
| `className` | string | `''` | Additional CSS classes |
| `onClick` | function | `null` | Click handler for the entire card |
| `style` | object | `{}` | Inline styles |
| `collapsible` | boolean | `true` | Whether the card can be collapsed/expanded |
| `defaultOpen` | boolean | `true` | Whether the card is open by default |

## Usage Examples

### Basic Example

```jsx
<CardInfoBox
    icon="👤"
    title="User Profile"
    body="<p>This is some information about the user.</p>"
/>
```

### With Image Icon

```jsx
<CardInfoBox
    icon="/assets/icons/analytics.svg"
    title="Analytics Report"
    body="<p><strong>Views:</strong> 1,250</p><p><strong>Likes:</strong> 45</p>"
/>
```

### With Click Handler

```jsx
<CardInfoBox
    icon="📊"
    title="Click for Details"
    body="<p>Click this card to see more information.</p>"
    onClick={() => alert('Card clicked!')}
    className="clickable"
/>
```

### With React Elements as Body

```jsx
<CardInfoBox
    icon="⚙️"
    title="Settings"
    body={
        <div>
            <p>Custom React content</p>
            <button>Action Button</button>
        </div>
    }
/>
```

## Integration in PHP Files

### Step 1: Load React and Component Files

```php
<!-- Component CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/components/CardInfoBox.css">

<!-- React CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

<!-- CardInfoBox Component -->
<script src="<?= APP_URL ?>assets/js/react-components-dist/CardInfoBox.js"></script>
```

### Step 2: Add Root Element

```html
<div id="card-info-root"></div>
```

### Step 3: Render Component with JavaScript

```javascript
const rootElement = document.getElementById('card-info-root');
const root = ReactDOM.createRoot(rootElement);

root.render(
    React.createElement(CardInfoBox, {
        icon: '📄',
        title: 'Article Information',
        body: '<p><strong>Author:</strong> John Doe</p>',
        className: 'shadow-lg'
    })
);
```

## CSS Classes and Styling

### Main Classes

- `.card-info-box` - Main container
- `.card-info-header` - Header section with icon and title
- `.card-info-icon` - Icon container
- `.card-info-title` - Title container
- `.card-info-body` - Body content area

### Utility Classes

- `.clickable` - Applied automatically when `onClick` is provided
- `.compact` - Reduced padding
- `.no-border` - Remove border
- `.shadow-sm` - Small shadow
- `.shadow-lg` - Large shadow

### Theme Support

The component automatically supports dark and gray themes:

```css
.dark-theme .card-info-box { /* Dark theme styles */ }
.gray-theme .card-info-box { /* Gray theme styles */ }
```

## Real-World Example: Articles Analytics

From `public/admin/articles_analytics.php`:

```javascript
function renderTopArticleCard(article) {
    const rootElement = document.getElementById('top-article-card-root');
    const icon = article.icon ? `${APP_URL}assets/icons/categories/${article.icon}` : '📄';
    
    const bodyContent = `
        <p><strong>${article.title}</strong></p>
        <p><strong>Author:</strong> ${article.author}</p>
        <p><strong>Views:</strong> ${formatNumber(article.public_views)}</p>
        <p><strong>Likes:</strong> ${formatNumber(article.total_likes)}</p>
    `;
    
    const root = ReactDOM.createRoot(rootElement);
    root.render(
        React.createElement(CardInfoBox, {
            icon: icon,
            title: 'Top Performing Article',
            body: bodyContent,
            className: 'shadow-lg'
        })
    );
}
```

## Responsive Design

The component is fully responsive with breakpoints at:
- **768px** - Tablet adjustments
- **480px** - Mobile adjustments

## Accessibility

- Semantic HTML structure
- Proper heading hierarchy
- Keyboard navigation support (when clickable)
- Screen reader friendly

## Browser Compatibility

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## Notes

- The component uses `dangerouslySetInnerHTML` when body is a string, so ensure content is properly sanitized
- Images in icons are automatically sized to 40px (32px on mobile)
- Click handlers make the entire card clickable
- Supports both controlled and uncontrolled rendering patterns

## Files

- **Component**: `assets/js/react-components/CardInfoBox.jsx`
- **Compiled**: `assets/js/react-components-dist/CardInfoBox.js`
- **Styles**: `assets/css/components/CardInfoBox.css`

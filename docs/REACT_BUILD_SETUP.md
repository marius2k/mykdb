# React Build Setup

## Overview

This project now uses a build step to compile JSX components to plain JavaScript. Components are written in JSX (`.jsx` files) and compiled to `.js` files before being loaded by the browser.

## How It Works

```
Source (JSX)                          Build                           Output (JS)
└── assets/js/react-components/   →  npm run build  →  assets/js/react-components-dist/
    ├── Button.jsx                                         ├── Button.js
    └── DashboardStats.jsx                                 └── DashboardStats.js
```

## Development Workflow

### 1. Edit Component (JSX)
Edit your component using JSX syntax:

```jsx
// assets/js/react-components/Button.jsx
const Button = ({ text, onClick }) => {
  return (
    <button onClick={onClick}>
      {text}
    </button>
  );
};
```

### 2. Build Component
Compile JSX to JavaScript:

```bash
# Inside Docker container
docker exec mykdb-webapp npm run build

# Or from host machine
npm run build
```

### 3. Component is Ready
The compiled component is now available in `assets/js/react-components-dist/Button.js` and can be loaded in PHP pages:

```html
<script src="<?= APP_URL ?>assets/js/react-components-dist/Button.js"></script>
```

## Available Commands

### Build Once
Compiles all JSX files to JavaScript:
```bash
docker exec mykdb-webapp npm run build
```

### Watch Mode (Development)
Automatically recompiles when you save changes:
```bash
docker exec mykdb-webapp npm run watch
```

Press `Ctrl+C` to stop watch mode.

## Adding New Components

1. **Create JSX file:**
   ```bash
   # assets/js/react-components/MyComponent.jsx
   const MyComponent = ({ title }) => {
     return <div className="my-component">{title}</div>;
   };
   window.MyComponent = MyComponent;
   ```

2. **Build:**
   ```bash
   docker exec mykdb-webapp npm run build
   ```

3. **Use in PHP:**
   ```html
   <link rel="stylesheet" href="<?= APP_URL ?>assets/css/MyComponent.css">
   <script src="<?= APP_URL ?>assets/js/react-components-dist/MyComponent.js"></script>
   
   <div id="my-component-root"></div>
   
   <script>
     const root = ReactDOM.createRoot(document.getElementById('my-component-root'));
     root.render(React.createElement(MyComponent, { title: 'Hello!' }));
   </script>
   ```

## Files Structure

```
mykdb/
├── package.json                           # npm configuration & scripts
├── .babelrc                               # Babel configuration
├── .gitignore                            # Ignores node_modules and dist
│
├── assets/
│   ├── css/
│   │   ├── Button.css                    # Component styles
│   │   └── DashboardStats.css
│   │
│   └── js/
│       ├── react-components/             # Source (JSX) - Edit these!
│       │   ├── Button.jsx
│       │   └── DashboardStats.jsx
│       │
│       └── react-components-dist/        # Compiled (JS) - Auto-generated
│           ├── Button.js
│           └── DashboardStats.js
│
└── public/
    ├── logs.php                          # Uses Button component
    └── react-demo.php                    # Uses DashboardStats component
```

## What Changed from CDN-Only Approach

### Before (CDN + Babel Standalone)
```html
<!-- Had to load Babel -->
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

<!-- Components couldn't be external JSX files -->
<script type="text/babel">
  const Button = () => <button>Click</button>;
</script>
```

**Problems:**
- ❌ Babel transforms on every page load (slow)
- ❌ Couldn't use JSX in external files
- ❌ Had to define components inline
- ❌ Browser console warnings

### After (Build Step)
```html
<!-- No Babel needed in browser -->
<script src="assets/js/react-components-dist/Button.js"></script>
```

**Benefits:**
- ✅ Components pre-compiled (fast loading)
- ✅ JSX in separate files
- ✅ Reusable across pages
- ✅ No browser warnings
- ✅ Production-ready
- ✅ Clean component organization

## Docker Setup

Node.js 18.x is installed in the Docker container via the Dockerfile:

```dockerfile
RUN apt-get update \
  && apt-get install -y curl \
  && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
  && apt-get install -y nodejs
```

Dependencies are installed when you run:
```bash
docker exec mykdb-webapp npm install
```

## Troubleshooting

### Components not showing up?
1. Make sure you built them:
   ```bash
   docker exec mykdb-webapp npm run build
   ```

2. Check the dist folder exists:
   ```bash
   docker exec mykdb-webapp ls assets/js/react-components-dist/
   ```

### Changed component but no effect?
You need to rebuild after editing JSX files:
```bash
docker exec mykdb-webapp npm run build
```

Or use watch mode:
```bash
docker exec mykdb-webapp npm run watch
```

### Build errors?
Make sure dependencies are installed:
```bash
docker exec mykdb-webapp npm install
```

### After rebuilding Docker container
You need to reinstall dependencies and rebuild:
```bash
docker exec mykdb-webapp npm install
docker exec mykdb-webapp npm run build
```

## Production Deployment

For production, make sure to:

1. **Build components:**
   ```bash
   npm run build
   ```

2. **Commit compiled files** (the dist folder):
   - Source files: `assets/js/react-components/*.jsx`
   - Compiled files: `assets/js/react-components-dist/*.js`
   - Both should be in version control

3. **Don't commit node_modules:**
   - Already in `.gitignore`

## Next Steps

Consider these improvements:

1. **Minification:** Add `--minified` flag to build script
2. **Source Maps:** Enable for easier debugging
3. **CSS Processing:** Add PostCSS for autoprefixing
4. **TypeScript:** Add type safety with TypeScript
5. **Hot Reload:** Set up webpack for instant updates
6. **Component Library:** Create Storybook for component showcase

## Learn More

- [Babel Documentation](https://babeljs.io/docs/)
- [React Without JSX](https://react.dev/reference/react/createElement)
- [npm Scripts](https://docs.npmjs.com/cli/v9/using-npm/scripts)

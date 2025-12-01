<?php
require_once '../config/bootstrap.php';
require_login();

include APP_ROOT . 'includes/header.php';

$theme = $_SESSION['settings']['theme'] ?? 'light';

?>
<!-- Component CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>assets/css/components/DashboardStats.css">

<!-- Load React and ReactDOM from CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>

<!-- DashboardStats Component (Compiled from JSX) -->
<script src="<?= APP_URL ?>assets/js/react-components-dist/DashboardStats.js"></script>

<div class="breadcrumb-filter-section" style="display: flex; justify-content: space-between; align-items: center; width: 100vw; padding: 6px 20px; margin-top: 0; margin-bottom: 0; margin-left: calc(-50vw + 50%);">
    <div style="margin-left: 20px; color: #666; font-size: 14px;">
        <strong>React Demo: Dashboard Statistics</strong>
    </div>
</div>
<br>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h2>🎯 Learning React: Dashboard Stats Component</h2>
    
    <div class="info-box" style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>What you're learning:</strong></p>
        <ul>
            <li>Creating a React functional component</li>
            <li>Using React Hooks (useState, useEffect)</li>
            <li>Fetching data from API</li>
            <li>Conditional rendering (loading state)</li>
            <li>Component styling with CSS</li>
        </ul>
    </div>

    <!-- React component will be rendered here -->
    <div id="react-dashboard-stats-root"></div>

    <div class="code-explanation" style="margin-top: 40px; padding: 20px; background: #f5f5f5; border-radius: 8px;">
        <h3>📝 How This Works:</h3>
        <ol>
            <li><strong>Component File:</strong> <code>assets/js/react-components/DashboardStats.jsx</code></li>
            <li><strong>API Endpoint:</strong> <code>public/api/bkd_dashboard_stats.php</code></li>
            <li><strong>Styles:</strong> <code>assets/css/DashboardStats.css</code></li>
        </ol>
        
        <h4>Next Steps to Learn:</h4>
        <ul>
            <li>✅ <strong>State Management:</strong> The component uses <code>useState</code> to manage data</li>
            <li>✅ <strong>Side Effects:</strong> <code>useEffect</code> fetches data when component mounts</li>
            <li>✅ <strong>API Integration:</strong> Fetch data from PHP backend</li>
            <li>⏭️ <strong>Props:</strong> Try passing data to child components</li>
            <li>⏭️ <strong>Events:</strong> Add click handlers for user interactions</li>
            <li>⏭️ <strong>Real-time Updates:</strong> Use intervals to refresh data</li>
        </ul>
    </div>
</div>

<!-- Render the component -->
<script>
  const root = ReactDOM.createRoot(document.getElementById('react-dashboard-stats-root'));
  root.render(React.createElement(DashboardStats));
</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

<?php
require_once '../config/bootstrap.php';

// Verifică permisiuni de admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Access denied. Admin privileges required.');
}

$action = $_GET['action'] ?? '';
$output = '';

if ($action) {
    ob_start();
    
    switch ($action) {
        case 'audit':
            include '../scripts/check_gamification_audit.php';
            break;
            
        case 'migrate_profile':
            include '../scripts/migrate_profile_completion_points.php';
            break;
            
        case 'migrate_all':
            include '../scripts/migrate_all_gamification_points.php';
            break;
            
        default:
            echo "Invalid action specified.";
    }
    
    $output = ob_get_clean();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gamification Migration Tools</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007acc;
            padding-bottom: 10px;
        }
        .tool-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fafafa;
        }
        .tool-section h3 {
            margin-top: 0;
            color: #007acc;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007acc;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .output {
            background-color: #1e1e1e;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
            margin-top: 20px;
            border: 1px solid #333;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .breadcrumb {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007acc;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <strong>Admin Panel</strong> → <strong>Gamification Migration Tools</strong>
        </div>
        
        <h1>🎮 Gamification Migration Tools</h1>
        
        <div class="info">
            <strong>ℹ️ Info:</strong> These tools help migrate retroactive points for users who completed actions before the gamification system was implemented.
        </div>

        <div class="tool-section">
            <h3>🔍 1. Audit & Check</h3>
            <p>Check which users need retroactive points. <strong>This is safe - it only reads data.</strong></p>
            <a href="?action=audit" class="btn btn-primary" onclick="showLoading()">Run Audit</a>
        </div>

        <div class="tool-section">
            <h3>📋 2. Migrate Profile Completion Points</h3>
            <p>Award +25 points to users who have completed their profiles.</p>
            <div class="warning">
                <strong>⚠️ Warning:</strong> This will modify the database. Run audit first to see how many users will be affected.
            </div>
            <a href="?action=migrate_profile" class="btn btn-warning" onclick="return confirm('Are you sure you want to migrate profile completion points?') && showLoading()">Migrate Profile Points</a>
        </div>

        <div class="tool-section">
            <h3>🚀 3. Full Migration</h3>
            <p>Complete migration of all retroactive points:</p>
            <ul>
                <li><strong>Profile completion:</strong> +25 points</li>
                <li><strong>Published articles:</strong> +50 points each</li>
                <li><strong>Approved comments:</strong> +5 points each</li>
            </ul>
            <div class="warning">
                <strong>⚠️ Warning:</strong> This will modify the database extensively. Run audit first to see the impact.
            </div>
            <a href="?action=migrate_all" class="btn btn-success" onclick="return confirm('Are you sure you want to run the full migration? This will award points for all past actions.') && showLoading()">Full Migration</a>
        </div>

        <?php if ($action): ?>
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Processing... Please wait.</p>
        </div>
        
        <div id="results">
            <h2>📊 Results for: <?= htmlspecialchars($action) ?></h2>
            <div class="output"><?= htmlspecialchars($output) ?></div>
            <a href="gamification_migration.php" class="btn btn-primary">← Back to Tools</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loading')?.style.setProperty('display', 'block');
            return true;
        }
        
        // Hide loading when page loads with results
        <?php if ($action): ?>
        document.getElementById('loading')?.style.setProperty('display', 'none');
        <?php endif; ?>
    </script>
</body>
</html>

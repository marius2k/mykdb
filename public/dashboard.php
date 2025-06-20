<?php
require_once '../config/bootstrap.php';
require_login();

$ops = ['view_own_logs', 'view_all_logs'];
if (!hasPermission($_SESSION['user']['id'], $ops)) {
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';
    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];



include APP_ROOT . 'includes/header.php';


//error_log("Translations:" $translations['lang_create_article_error'] ?? 'N/A');
?>

<div id="dashboard-root">
    <div class="loading">Se încarcă dashboard-ul...</div>
</div>


<script>
$(function() {
    $.getJSON('api/bkd_dashboard.php', function(data) {
        if (data.error) {
            $('#dashboard-root').html('<div class="error">' + data.error + '</div>');
            return;
        }
        $('#dashboard-root').html(data.html);
        $('#art_top_view').text(data.titleTopViewed);
        $('#art_top_like').text(data.titleTopLiked);
        $('#art_top_com').text(data.titleTopCommented);

        // Inițializează graficele dacă există date
        if (typeof data.articlesChart === 'object') {
            renderChart('articlesChart', data.articlesChart);
        }
        if (typeof data.commentsChart === 'object') {
            renderChart('commentsChart', data.commentsChart);
        }

        // Inițializează custom-box-1 după ce HTML-ul a fost inserat
        document.querySelectorAll('.custom-box-1').forEach(box => {
            initializeCustomBox1(box);
        });
    });
});

// Funcție pentru a desena graficele Chart.js
function renderChart(canvasId, chartData) {
    if (!document.getElementById(canvasId)) return;
    new Chart(document.getElementById(canvasId).getContext('2d'), chartData);
}


</script>

<?php include APP_ROOT . 'includes/footer.php'; ?>

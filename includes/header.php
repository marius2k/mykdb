<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//$currentPage = ""
//loadConfig();

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.min.js"></script>

</head>
<body>




<div style="background: linear-gradient(to right,rgb(25, 108, 141),rgb(67, 190, 238)); color:#fff; padding:10px;">
<!-- 
<div style="background-image: url('<?= APP_URL ?>/assets/images/kdb_top.jpeg');background-size: cover; background-position: center; background-repeat: no-repeat; padding:10px;">    
-->
    <div style="width: 100%; display: flex; align-items: center; justify-content: space-between;">
        <div style="float: left; margin:0; font-size: 40px ">🧠 <?= APP_NAME; ?></div>
        <div style="float: right; padding: 10px; border-radius: 5px; margin: 10px;">
            <em>Search: </em> 
            <input type="text" id="liveSearch" placeholder="Caută articole..." autocomplete="off">
        </div>
    </div>
    <nav>
        <a href="<?= APP_URL ?>public/index.php" style="color:white;">Acasă</a>
        <?php if (isset($_SESSION['user'])): ?>
            | <a href="<?= APP_URL ?>public/dashboard.php" style="color:white;">Dashboard</a>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                | <a href="<?= APP_URL ?>public/admin/users.php" style="color:white;">Users</a>
                | <a href="<?= APP_URL ?>public/admin/categories.php" style="color:white;">Categories</a>
                | <a href="<?= APP_URL ?>public/admin/articles.php" style="color:white;">Articles</a>
            <?php endif; ?>
            | <a href="<?= APP_URL ?>public/logout.php" style="color:white;">Logout (<?= escape($_SESSION['user']['username']) ?>)</a>
        <?php else: ?>
            | <a href="<?= APP_URL ?>public/login.php" style="color:white;">Login</a>
            | <a href="<?= APP_URL ?>public/register.php" style="color:white;">Înregistrare</a>
        <?php endif; ?>
    </nav>
</div>


<main style="padding:20px;">


<!-- Custom JS -->
<script src="<?= APP_URL ?>assets/js/scripts.js"></script>

<hr>



<footer style="text-align:center; padding:10px; small;">
    <?php SystemStatus(); ?>
    <div class="width: 100%; display: flex; align-items: center; justify-content: space-between; small">&copy; <?= date("Y"); echo " ".APP_NAME; ?>.Toate drepturile rezervate.</div>
    <div class="width: 100%; display: flex; align-items: center; justify-content: space-between; small">
        <?php if (isset($_SESSION['user'])): ?>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                  <a href="<?= APP_URL ?>public/index.php">Acasă</a>
                | <a href="<?= APP_URL ?>public/dashboard.php">Dashboard</a>
                | <a href="<?= APP_URL ?>public/logout.php">Logout (<?= $_SESSION['user']['username'] ?>)</a>
            <?php else: ?>
                  <a href="<?= APP_URL ?>public/index.php">Acasă</a>
                | <a href="<?= APP_URL ?>public/dashboard.php">Dashboard</a>
                | <a href="<?= APP_URL ?>public/logout.php">Logout (<?= escape($_SESSION['user']['username']) ?>)</a>
            <?php endif; ?>    
        <?php else: ?>
             <a href="<?= APP_URL ?>public/index.php">Acasă</a>
            | <a href="<?= APP_URL ?>public/login.php">Login</a>
            | <a href="<?= APP_URL ?>public/register.php">Register</a>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>
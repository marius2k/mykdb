
<!-- Custom JS -->
<script src="<?= APP_URL ?>assets/js/scripts.js"></script>

<hr>



<footer style="text-align:center; padding:10px; small;">
    <?php SystemStatus(); ?>
    <div class="width: 100%; display: flex; align-items: center; justify-content: space-between; small">&copy; <?= date("Y"); echo " ".APP_NAME; echo " ".APP_VERSION . ".   "; ?>Toate drepturile rezervate.</div>
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

<!-- Back to Top Button -->
<button type="button" class="btn btn-primary btn-floating btn-lg rounded-circle position-fixed bottom-0 end-0 m-4" 
        id="back-to-top" title="Go to top" style="display: none;">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Back to top button
    document.addEventListener('DOMContentLoaded', function() {
        const backToTopButton = document.getElementById('back-to-top');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Enable tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Enable popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
</script>

<!-- Reading Time Tracker -->
<script src="<?= APP_URL ?>assets/js/reading-tracker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($article_id)): ?>
    // Inițializează tracking-ul doar pentru timpul de citire
    window.readingTracker = new ReadingTimeTracker(<?= $article_id ?>);
    
    console.log('📚 Reading Time Tracker pornit pentru articolul #<?= $article_id ?>');
    
    // Optional: statistici live în consolă (poți elimina în producție)
    setInterval(() => {
        const stats = window.readingTracker.getStats();
        console.log('📊 Reading Stats:', {
            citire: window.readingTracker.formatTime(stats.totalReadingTime),
            scroll: stats.maxScrollPercentage + '%',
            citește: stats.isReading ? 'Da' : 'Nu'
        });
    }, 15000); // La fiecare 15 secunde
    <?php endif; ?>
});
</script>

</body>
</html>
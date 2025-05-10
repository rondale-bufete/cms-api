    </main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>3D Object CMS</h5>
                    <p>A platform for managing 3D object files.</p>
                </div>
                <div class="col-md-3">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-white">Home</a></li>
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard.php" class="text-white">Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/upload.php" class="text-white">Upload</a></li>
                        <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>/login.php" class="text-white">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/register.php" class="text-white">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>API</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>/api/objects.php" class="text-white">Objects API</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> 3D Object CMS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>

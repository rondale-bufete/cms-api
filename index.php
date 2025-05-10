<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize database connection
$db = new Database();
$auth = new Auth();

// Get recent objects for public display
$query = "SELECT o.*, u.username FROM objects o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 8";
$recentObjects = $db->executeQuery($query);

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<!-- Hero section -->
<div class="bg-dark text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold">3D Object CMS</h1>
                <p class="lead">Upload, manage, and share your 3D object files with ease.</p>
                <p>A simple content management system for 3D object files (.obj, .mtl, .glb) with RESTful API support.</p>
                <?php if (!$auth->isLoggedIn()): ?>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-lg me-2">Get Started</a>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-light btn-lg">Login</a>
                </div>
                <?php else: ?>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-primary btn-lg me-2">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>/upload.php" class="btn btn-outline-light btn-lg">Upload 3D Object</a>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 text-center">
                <div class="p-5 bg-secondary bg-opacity-25 rounded">
                    <i class="fas fa-cube fa-5x mb-3"></i>
                    <i class="fas fa-cubes fa-5x mb-3 ms-4"></i>
                    <div class="mt-3">
                        <span class="badge bg-primary me-2">.obj</span>
                        <span class="badge bg-success me-2">.mtl</span>
                        <span class="badge bg-info me-2">.glb</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Features</h2>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-upload fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">Easy Upload</h3>
                    <p class="card-text">Upload your 3D object files (.obj, .mtl, .glb) with a simple interface.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-database fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">Secure Storage</h3>
                    <p class="card-text">Your 3D objects are securely stored and organized in our database.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-code fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">API Access</h3>
                    <p class="card-text">Access your 3D objects programmatically with our RESTful API.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent objects section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Recent 3D Objects</h2>
    <?php if (count($recentObjects) > 0): ?>
    <div class="row">
        <?php foreach ($recentObjects as $object): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="object-thumbnail">
                    <?php if ($object['file_type'] === 'obj'): ?>
                    <i class="fas fa-cube fa-3x"></i>
                    <?php elseif ($object['file_type'] === 'mtl'): ?>
                    <i class="fas fa-palette fa-3x"></i>
                    <?php elseif ($object['file_type'] === 'glb'): ?>
                    <i class="fas fa-cubes fa-3x"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($object['title']); ?></h5>
                    <p class="card-text small text-muted">
                        By <?php echo htmlspecialchars($object['username']); ?> | 
                        <?php echo date('M d, Y', strtotime($object['created_at'])); ?>
                    </p>
                    <a href="<?php echo BASE_URL; ?>/view_object.php?id=<?php echo $object['id']; ?>" class="btn btn-primary btn-sm">View Object</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">
        No 3D objects have been uploaded yet. <a href="<?php echo BASE_URL; ?>/upload.php">Be the first to upload!</a>
    </div>
    <?php endif; ?>
</div>

<!-- How it works section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">How It Works</h2>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge bg-primary rounded-circle p-3">1</span>
                        </div>
                        <div>
                            <h4>Create an Account</h4>
                            <p>Sign up for a free account to get started with 3D Object CMS.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge bg-primary rounded-circle p-3">2</span>
                        </div>
                        <div>
                            <h4>Upload Your 3D Objects</h4>
                            <p>Upload your 3D object files (.obj, .mtl, .glb) using our simple upload form.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge bg-primary rounded-circle p-3">3</span>
                        </div>
                        <div>
                            <h4>Manage Your Objects</h4>
                            <p>View, update, and delete your 3D objects from your dashboard.</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="me-3">
                            <span class="badge bg-primary rounded-circle p-3">4</span>
                        </div>
                        <div>
                            <h4>Access via API</h4>
                            <p>Use our RESTful API to programmatically access your 3D objects.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA section -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container text-center">
        <h2 class="mb-3">Ready to Get Started?</h2>
        <p class="lead mb-4">Create your account now and start managing your 3D objects.</p>
        <?php if (!$auth->isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-light btn-lg me-2">Sign Up</a>
        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-light btn-lg me-2">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>/upload.php" class="btn btn-outline-light btn-lg">Upload 3D Object</a>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>

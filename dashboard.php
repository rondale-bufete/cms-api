<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize auth and database
$auth = new Auth();
$db = new Database();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current user
$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];

// Get search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get user's objects
if (empty($search)) {
    $query = "SELECT * FROM objects WHERE user_id = ? ORDER BY created_at DESC";
    $objects = $db->executeQuery($query, "i", [$userId]);
} else {
    $searchTerm = "%$search%";
    $query = "SELECT * FROM objects WHERE user_id = ? AND (title LIKE ? OR description LIKE ?) ORDER BY created_at DESC";
    $objects = $db->executeQuery($query, "iss", [$userId, $searchTerm, $searchTerm]);
}

// Get object count
$query = "SELECT COUNT(*) as count FROM objects WHERE user_id = ?";
$result = $db->executeQuery($query, "i", [$userId]);
$objectCount = $result[0]['count'];

// Get total disk usage
$query = "SELECT SUM(file_size) as total_size FROM objects WHERE user_id = ?";
$result = $db->executeQuery($query, "i", [$userId]);
$totalSize = $result[0]['total_size'] ?? 0;
$totalSizeMB = number_format($totalSize / (1024 * 1024), 2);

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard</h2>
        <a href="<?php echo BASE_URL; ?>/upload.php" class="btn btn-primary">
            <i class="fas fa-upload me-2"></i> Upload 3D Object
        </a>
    </div>
    
    <!-- Dashboard summary -->
    <div class="row dashboard-summary">
        <div class="col-md-4">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <h3><?php echo $objectCount; ?></h3>
                <p class="text-muted">Total Objects</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <h3><?php echo $totalSizeMB; ?> MB</h3>
                <p class="text-muted">Storage Used</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3><?php echo date('M d, Y', strtotime($currentUser['created_at'])); ?></h3>
                <p class="text-muted">Member Since</p>
            </div>
        </div>
    </div>
    
    <!-- Search form -->
    <div class="mb-4">
        <form id="search-form" class="d-flex">
            <input type="text" class="form-control me-2" id="search-input" name="search" placeholder="Search objects..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-outline-primary">Search</button>
            <?php if (!empty($search)): ?>
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-secondary ms-2">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Objects list -->
    <div class="row">
        <?php if (count($objects) > 0): ?>
            <?php foreach ($objects as $object): ?>
                <div class="col-md-4 mb-4">
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
                            <p class="card-text">
                                <?php 
                                $description = $object['description'] ?? '';
                                echo !empty($description) ? htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '') : '<em>No description</em>'; 
                                ?>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-file me-1"></i> <?php echo strtoupper($object['file_type']); ?> | 
                                    <i class="fas fa-hdd me-1"></i> <?php echo number_format($object['file_size'] / (1024 * 1024), 2); ?> MB
                                </small>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i> Uploaded: <?php echo date('M d, Y', strtotime($object['created_at'])); ?>
                                </small>
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="btn-group w-100" role="group">
                                <a href="<?php echo BASE_URL; ?>/view_object.php?id=<?php echo $object['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="#" class="btn btn-outline-danger api-delete-btn" data-id="<?php echo $object['id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <?php if (empty($search)): ?>
                    <div class="alert alert-info">
                        You haven't uploaded any 3D objects yet. <a href="<?php echo BASE_URL; ?>/upload.php">Upload your first 3D object</a>.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No objects found matching your search term "<?php echo htmlspecialchars($search); ?>".
                        <a href="<?php echo BASE_URL; ?>/dashboard.php">View all objects</a>.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>

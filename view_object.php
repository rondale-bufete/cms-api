<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize auth and database
$auth = new Auth();
$db = new Database();

// Get object ID from URL
$objectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($objectId <= 0) {
    $_SESSION['flash_message'] = 'Invalid object ID';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

// Get object data
$query = "SELECT o.*, u.username FROM objects o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
$result = $db->executeQuery($query, "i", [$objectId]);

if (count($result) === 0) {
    $_SESSION['flash_message'] = 'Object not found';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$object = $result[0];

// Check if user has permission to view this object (public objects can be viewed by anyone)
$isOwner = $auth->isLoggedIn() && $_SESSION['user_id'] === $object['user_id'];

// Decode related files JSON if it exists
$relatedFiles = [];
if (!empty($object['related_files'])) {
    $relatedFiles = json_decode($object['related_files'], true);
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <?php if ($auth->isLoggedIn()): ?>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($object['title']); ?></li>
            </ol>
        </nav>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="mb-3"><?php echo htmlspecialchars($object['title']); ?></h1>
                    
                    <div class="d-flex mb-3">
                        <div class="me-4">
                            <span class="badge bg-primary">
                                <i class="fas fa-file me-1"></i> <?php echo strtoupper($object['file_type']); ?>
                            </span>
                        </div>
                        <div class="me-4">
                            <i class="fas fa-hdd me-1"></i> <?php echo number_format($object['file_size'] / (1024 * 1024), 2); ?> MB
                        </div>
                        <div>
                            <i class="fas fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($object['created_at'])); ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p>
                            <?php echo !empty($object['description']) ? nl2br(htmlspecialchars($object['description'])) : '<em>No description provided</em>'; ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h5>3D Object Viewer</h5>
                        <div id="model-viewer" class="viewer-container" data-file-path="<?php echo BASE_URL . '/uploads/' . $object['file_path']; ?>" data-file-type="<?php echo $object['file_type']; ?>"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL . '/uploads/' . $object['file_path']; ?>" download class="btn btn-primary">
                            <i class="fas fa-download me-2"></i> Download <?php echo strtoupper($object['file_type']); ?> File
                        </a>
                        
                        <?php if ($isOwner): ?>
                        <button class="btn btn-danger api-delete-btn" data-id="<?php echo $object['id']; ?>">
                            <i class="fas fa-trash me-2"></i> Delete Object
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Object Information</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Uploaded by</span>
                            <span><?php echo htmlspecialchars($object['username']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Upload date</span>
                            <span><?php echo date('M d, Y', strtotime($object['created_at'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>File type</span>
                            <span><?php echo strtoupper($object['file_type']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>File size</span>
                            <span><?php echo number_format($object['file_size'] / (1024 * 1024), 2); ?> MB</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <?php if (!empty($relatedFiles)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Related Files</h5>
                    <div class="related-files">
                        <?php foreach ($relatedFiles as $index => $file): ?>
                        <div class="related-file">
                            <div>
                                <i class="fas fa-file me-2"></i>
                                <?php echo htmlspecialchars($file['original_name']); ?>
                            </div>
                            <a href="<?php echo BASE_URL . '/uploads/' . $file['file_path']; ?>" download class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">API Access</h5>
                    <p class="card-text">Access this object programmatically via our API:</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo BASE_URL; ?>/api/objects.php?id=<?php echo $object['id']; ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/api/objects.php?id=<?php echo $object['id']; ?>')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load model viewer script -->
<script src="<?php echo BASE_URL; ?>/assets/js/model-viewer.js"></script>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>

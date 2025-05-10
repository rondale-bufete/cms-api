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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fileType = $_POST['file_type'] ?? '';
    
    $errors = [];
    
    // Validate title
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    // Validate file type
    if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    // Validate file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrorMessages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errorMessage = 'File upload error: ' . ($uploadErrorMessages[$_FILES['file']['error']] ?? 'Unknown error');
        $errors[] = $errorMessage;
    } else {
        $file = $_FILES['file'];
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds the maximum allowed size (' . (MAX_FILE_SIZE / (1024 * 1024)) . ' MB)';
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            $errors[] = 'Invalid file extension. Allowed extensions: ' . implode(', ', ALLOWED_EXTENSIONS);
        }
        
        // Verify file extension matches selected type
        if ($fileExtension !== $fileType) {
            $errors[] = 'Selected file type does not match the file extension';
        }
    }
    
    // If no errors, process upload
    if (empty($errors)) {
        // Create a unique filename
        $uniqueId = time() . '_' . generateRandomString(8);
        $newFileName = $uniqueId . '.' . $fileExtension;
        $uploadPath = UPLOAD_DIR . $newFileName;
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Process related files if any
            $relatedFiles = [];
            if (isset($_FILES['related_files']) && is_array($_FILES['related_files']['name'])) {
                for ($i = 0; $i < count($_FILES['related_files']['name']); $i++) {
                    if ($_FILES['related_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $relatedFile = [
                            'name' => $_FILES['related_files']['name'][$i],
                            'type' => $_FILES['related_files']['type'][$i],
                            'tmp_name' => $_FILES['related_files']['tmp_name'][$i],
                            'error' => $_FILES['related_files']['error'][$i],
                            'size' => $_FILES['related_files']['size'][$i]
                        ];
                        
                        $relatedExtension = strtolower(pathinfo($relatedFile['name'], PATHINFO_EXTENSION));
                        
                        // Only allow related files with approved extensions
                        if (in_array($relatedExtension, ALLOWED_EXTENSIONS)) {
                            $relatedFileName = $uniqueId . '_related_' . count($relatedFiles) . '.' . $relatedExtension;
                            $relatedPath = UPLOAD_DIR . $relatedFileName;
                            
                            if (move_uploaded_file($relatedFile['tmp_name'], $relatedPath)) {
                                $relatedFiles[] = [
                                    'original_name' => $relatedFile['name'],
                                    'file_path' => $relatedFileName,
                                    'file_type' => $relatedExtension,
                                    'file_size' => $relatedFile['size']
                                ];
                            }
                        }
                    }
                }
            }
            
            // Store file information in database
            $query = "INSERT INTO objects (user_id, title, description, file_path, file_type, file_size, related_files) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $relatedFilesJson = !empty($relatedFiles) ? json_encode($relatedFiles) : null;
            
            $result = $db->executeQuery(
                $query,
                "issssis",
                [$userId, $title, $description, $newFileName, $fileType, $file['size'], $relatedFilesJson]
            );
            
            if ($result['affected_rows'] > 0) {
                $_SESSION['flash_message'] = 'File uploaded successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Failed to save file information to database';
                
                // Clean up the uploaded file if database insert fails
                unlink($uploadPath);
                
                // Clean up related files if any
                foreach ($relatedFiles as $relatedFile) {
                    unlink(UPLOAD_DIR . $relatedFile['file_path']);
                }
            }
        } else {
            $errors[] = 'Failed to move uploaded file';
        }
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="upload-form">
        <h2 class="mb-4">Upload 3D Object</h2>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="post" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="file_type" class="form-label">File Type <span class="text-danger">*</span></label>
                <select class="form-control" id="file_type" name="file_type" required>
                    <option value="">Select file type</option>
                    <?php foreach (ALLOWED_EXTENSIONS as $ext): ?>
                    <option value="<?php echo $ext; ?>" <?php echo (isset($_POST['file_type']) && $_POST['file_type'] === $ext) ? 'selected' : ''; ?>>
                        .<?php echo strtoupper($ext); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="file" class="form-label">File <span class="text-danger">*</span></label>
                <div class="file-upload-wrapper">
                    <input type="file" class="form-control" id="file" name="file" required>
                </div>
                <div id="file-name" class="form-text">No file selected</div>
                <div class="form-text">
                    Maximum file size: <?php echo MAX_FILE_SIZE / (1024 * 1024); ?> MB. Allowed file types: <?php echo implode(', ', array_map('strtoupper', ALLOWED_EXTENSIONS)); ?>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Related Files (Optional)</label>
                <p class="form-text">
                    Upload related files (e.g., MTL files for OBJ models, textures). Only allowed file types: <?php echo implode(', ', array_map('strtoupper', ALLOWED_EXTENSIONS)); ?>
                </p>
                
                <div id="related-files-container">
                    <!-- Related files will be added here -->
                </div>
                
                <button type="button" class="btn btn-outline-secondary" id="add-related-file">
                    <i class="fas fa-plus"></i> Add Related File
                </button>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>

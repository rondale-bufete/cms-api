<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize database and auth
$db = new Database();
$auth = new Auth();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get user if logged in
$currentUser = $auth->isLoggedIn() ? $auth->getCurrentUser() : null;
$userId = $currentUser ? $currentUser['id'] : null;

// API response function
function response($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Object ID from query string
$objectId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle API requests
switch ($method) {
    case 'GET':
        // Get objects
        if ($objectId) {
            // Get specific object
            $query = "SELECT o.*, u.username FROM objects o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
            $result = $db->executeQuery($query, "i", [$objectId]);
            
            if (count($result) === 0) {
                response('error', 'Object not found', null, 404);
            }
            
            $object = $result[0];
            
            // Check if it's already a full URL (Supabase) or needs to be constructed
            if (filter_var($object['file_path'], FILTER_VALIDATE_URL)) {
                $object['file_url'] = $object['file_path']; // It's already a full URL (Supabase)
            } else {
                $object['file_url'] = BASE_URL . '/uploads/' . $object['file_path']; // Local file
            }
            
            // Process related files if any
            if (!empty($object['related_files'])) {
                $relatedFiles = json_decode($object['related_files'], true);
                foreach ($relatedFiles as &$file) {
                    if (filter_var($file['file_path'], FILTER_VALIDATE_URL)) {
                        $file['file_url'] = $file['file_path']; // It's already a full URL (Supabase)
                    } else {
                        $file['file_url'] = BASE_URL . '/uploads/' . $file['file_path']; // Local file
                    }
                }
                $object['related_files'] = $relatedFiles;
            }
            
            response('success', 'Object retrieved successfully', $object);
        } else {
            // Get all objects (if logged in) or return error
            if (!$auth->isLoggedIn()) {
                response('error', 'Authentication required', null, 401);
            }
            
            $query = "SELECT o.*, u.username FROM objects o JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.created_at DESC";
            $objects = $db->executeQuery($query, "i", [$userId]);
            
            // Add full URLs to file paths
            foreach ($objects as &$object) {
                // Check if it's already a full URL (Supabase) or needs to be constructed
                if (filter_var($object['file_path'], FILTER_VALIDATE_URL)) {
                    $object['file_url'] = $object['file_path']; // It's already a full URL (Supabase)
                } else {
                    $object['file_url'] = BASE_URL . '/uploads/' . $object['file_path']; // Local file
                }
                
                // Process related files if any
                if (!empty($object['related_files'])) {
                    $relatedFiles = json_decode($object['related_files'], true);
                    foreach ($relatedFiles as &$file) {
                        if (filter_var($file['file_path'], FILTER_VALIDATE_URL)) {
                            $file['file_url'] = $file['file_path']; // It's already a full URL (Supabase)
                        } else {
                            $file['file_url'] = BASE_URL . '/uploads/' . $file['file_path']; // Local file
                        }
                    }
                    $object['related_files'] = $relatedFiles;
                }
            }
            
            response('success', 'Objects retrieved successfully', $objects);
        }
        break;
        
    case 'POST':
        // Create new object (upload)
        if (!$auth->isLoggedIn()) {
            response('error', 'Authentication required', null, 401);
        }
        
        // Verify content type is multipart/form-data
        if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
            response('error', 'Content-Type must be multipart/form-data', null, 400);
        }
        
        // Get JSON data from POST request
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $fileType = $_POST['file_type'] ?? '';
        
        // Validate required fields
        if (empty($title) || empty($fileType) || !in_array($fileType, ALLOWED_EXTENSIONS)) {
            response('error', 'Missing or invalid required fields', null, 400);
        }
        
        // Validate file
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            response('error', 'File upload error', null, 400);
        }
        
        $file = $_FILES['file'];
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            response('error', 'File size exceeds maximum allowed size', null, 400);
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            response('error', 'Invalid file extension', null, 400);
        }
        
        // Create a unique filename
        $uniqueId = time() . '_' . generateRandomString(8);
        $newFileName = $uniqueId . '.' . $fileExtension;
        $uploadPath = UPLOAD_DIR . $newFileName;
        
        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            response('error', 'Failed to save uploaded file', null, 500);
        }
        
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
            // Get the newly created object
            $newObjectId = $result['insert_id'];
            $query = "SELECT * FROM objects WHERE id = ?";
            $newObject = $db->executeQuery($query, "i", [$newObjectId])[0];
            
            // Add full URL to file path
            $newObject['file_url'] = BASE_URL . '/uploads/' . $newObject['file_path'];
            
            // Process related files if any
            if (!empty($newObject['related_files'])) {
                $relatedFiles = json_decode($newObject['related_files'], true);
                foreach ($relatedFiles as &$file) {
                    $file['file_url'] = BASE_URL . '/uploads/' . $file['file_path'];
                }
                $newObject['related_files'] = $relatedFiles;
            }
            
            response('success', 'Object created successfully', $newObject, 201);
        } else {
            // Clean up the uploaded file if database insert fails
            unlink($uploadPath);
            
            // Clean up related files if any
            foreach ($relatedFiles as $relatedFile) {
                unlink(UPLOAD_DIR . $relatedFile['file_path']);
            }
            
            response('error', 'Failed to save object information', null, 500);
        }
        break;
        
    case 'PUT':
        // Update object
        if (!$auth->isLoggedIn()) {
            response('error', 'Authentication required', null, 401);
        }
        
        if (!$objectId) {
            response('error', 'Object ID is required', null, 400);
        }
        
        // Check if object exists and belongs to the user
        $query = "SELECT * FROM objects WHERE id = ? AND user_id = ?";
        $result = $db->executeQuery($query, "ii", [$objectId, $userId]);
        
        if (count($result) === 0) {
            response('error', 'Object not found or access denied', null, 404);
        }
        
        // Get JSON data from PUT request
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            response('error', 'Invalid JSON data', null, 400);
        }
        
        // Extract update fields
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        
        // Build update query based on provided fields
        $updateFields = [];
        $types = "";
        $params = [];
        
        if ($title !== null) {
            $updateFields[] = "title = ?";
            $types .= "s";
            $params[] = $title;
        }
        
        if ($description !== null) {
            $updateFields[] = "description = ?";
            $types .= "s";
            $params[] = $description;
        }
        
        if (empty($updateFields)) {
            response('error', 'No fields to update', null, 400);
        }
        
        // Add object ID and user ID to params
        $types .= "ii";
        $params[] = $objectId;
        $params[] = $userId;
        
        // Update object in database
        $query = "UPDATE objects SET " . implode(", ", $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?";
        $result = $db->executeQuery($query, $types, $params);
        
        if ($result['affected_rows'] > 0) {
            // Get the updated object
            $query = "SELECT * FROM objects WHERE id = ?";
            $updatedObject = $db->executeQuery($query, "i", [$objectId])[0];
            
            // Add full URL to file path
            $updatedObject['file_url'] = BASE_URL . '/uploads/' . $updatedObject['file_path'];
            
            // Process related files if any
            if (!empty($updatedObject['related_files'])) {
                $relatedFiles = json_decode($updatedObject['related_files'], true);
                foreach ($relatedFiles as &$file) {
                    $file['file_url'] = BASE_URL . '/uploads/' . $file['file_path'];
                }
                $updatedObject['related_files'] = $relatedFiles;
            }
            
            response('success', 'Object updated successfully', $updatedObject);
        } else {
            response('error', 'Failed to update object or no changes made', null, 500);
        }
        break;
        
    case 'DELETE':
        // Delete object
        if (!$auth->isLoggedIn()) {
            response('error', 'Authentication required', null, 401);
        }
        
        if (!$objectId) {
            response('error', 'Object ID is required', null, 400);
        }
        
        // Check if object exists and belongs to the user
        $query = "SELECT * FROM objects WHERE id = ? AND user_id = ?";
        $result = $db->executeQuery($query, "ii", [$objectId, $userId]);
        
        if (count($result) === 0) {
            response('error', 'Object not found or access denied', null, 404);
        }
        
        $object = $result[0];
        
        // Include Supabase storage functionality if enabled
        require_once __DIR__ . '/../includes/supabase_storage.php';
        $useSupabase = is_supabase_enabled();
        
        // Handle file deletion - either from local disk or Supabase
        if (filter_var($object['file_path'], FILTER_VALIDATE_URL) && $useSupabase) {
            // Delete from Supabase
            $fileName = basename($object['file_path']);
            deleteFromSupabase($fileName);
        } else {
            // Delete from local disk
            $filePath = UPLOAD_DIR . $object['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete related files if any
        if (!empty($object['related_files'])) {
            $relatedFiles = json_decode($object['related_files'], true);
            foreach ($relatedFiles as $file) {
                if (filter_var($file['file_path'], FILTER_VALIDATE_URL) && $useSupabase) {
                    // Delete from Supabase
                    $fileName = basename($file['file_path']);
                    deleteFromSupabase($fileName);
                } else {
                    // Delete from local disk
                    $relatedPath = UPLOAD_DIR . $file['file_path'];
                    if (file_exists($relatedPath)) {
                        unlink($relatedPath);
                    }
                }
            }
        }
        
        // Delete object from database
        $query = "DELETE FROM objects WHERE id = ? AND user_id = ?";
        $result = $db->executeQuery($query, "ii", [$objectId, $userId]);
        
        if ($result['affected_rows'] > 0) {
            response('success', 'Object deleted successfully');
        } else {
            response('error', 'Failed to delete object', null, 500);
        }
        break;
        
    default:
        // Unsupported method
        response('error', 'Method not allowed', null, 405);
        break;
}
?>

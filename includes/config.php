<?php
// Application configuration
session_start();

// Database configuration
define('DB_HOST', getenv('PGHOST') ?: 'localhost');
define('DB_USER', getenv('PGUSER') ?: 'root');
define('DB_PASS', getenv('PGPASSWORD') ?: '');
define('DB_NAME', getenv('PGDATABASE') ?: '3d_cms');

// Application paths
define('BASE_URL', 'http://localhost/Content3DManager');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['obj', 'mtl', 'glb']);
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Include Supabase configuration if needed
require_once __DIR__ . '/supabase_config.php';

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('UTC');

// Function to sanitize input
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to generate random string
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>
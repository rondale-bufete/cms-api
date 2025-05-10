<?php
/**
 * Supabase Storage Helper
 * 
 * Helper functions for interacting with Supabase Storage
 */

require_once __DIR__ . '/supabase_config.php';

/**
 * Upload file to Supabase Storage
 *
 * @param string $filePath Local path to file
 * @param string $fileName Name to use in storage
 * @return string|false Public URL of uploaded file or false on failure
 */
function uploadToSupabase($filePath, $fileName) {
    if (!is_supabase_enabled()) {
        return false;
    }
    
    // Initialize cURL
    $ch = curl_init();
    
    // Prepare URL for the storage API
    $url = SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $fileName;
    
    // Set headers for authorization and content type
    $headers = [
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/octet-stream'
    ];
    
    // Get file contents
    $fileData = file_get_contents($filePath);
    if ($fileData === false) {
        error_log("Failed to read file: $filePath");
        return false;
    }
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute request
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if (curl_errno($ch)) {
        error_log('Supabase Upload Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Handle response
    if ($status === 200) {
        // Return the public URL for the file
        return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $fileName;
    } else {
        error_log("Supabase Upload Failed: Status $status, Response: $response");
        return false;
    }
}

/**
 * Delete file from Supabase Storage
 *
 * @param string $fileName Name of file to delete
 * @return bool True on success, false on failure
 */
function deleteFromSupabase($fileName) {
    if (!is_supabase_enabled()) {
        return false;
    }
    
    // Initialize cURL
    $ch = curl_init();
    
    // Prepare URL for the storage API
    $url = SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $fileName;
    
    // Set headers for authorization
    $headers = [
        'Authorization: Bearer ' . SUPABASE_KEY
    ];
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute request
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if (curl_errno($ch)) {
        error_log('Supabase Delete Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Return success based on status code
    return ($status === 200);
}

/**
 * Get public URL for a file in Supabase Storage
 *
 * @param string $fileName Name of file
 * @return string The public URL
 */
function getSupabasePublicUrl($fileName) {
    return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $fileName;
}

/**
 * Check if a file path is a Supabase URL
 *
 * @param string $path The file path or URL
 * @return bool True if it's a Supabase URL
 */
function isSupabaseUrl($path) {
    return is_supabase_enabled() && strpos($path, SUPABASE_URL) === 0;
}
?>
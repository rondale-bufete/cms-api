<?php
/**
 * Supabase Configuration
 * 
 * This file contains configuration settings for Supabase integration
 */

// Supabase configuration - replace with your project details
define('SUPABASE_URL', 'YOUR_SUPABASE_URL'); // e.g. https://abcdefghijklm.supabase.co
define('SUPABASE_KEY', 'YOUR_SUPABASE_ANON_KEY'); // your anon/public key
define('SUPABASE_BUCKET', '3d-objects'); // the name of your storage bucket for 3D files

// Function to check if Supabase integration is enabled
function is_supabase_enabled() {
    return defined('SUPABASE_URL') && SUPABASE_URL !== 'YOUR_SUPABASE_URL' && 
           defined('SUPABASE_KEY') && SUPABASE_KEY !== 'YOUR_SUPABASE_ANON_KEY';
}
?>
<?php
/**
 * Supabase Configuration
 * 
 * This file contains configuration settings for Supabase integration
 */

// Supabase configuration - replace with your project details
define('SUPABASE_URL', 'https://mqhzokrrbmzpsomcmbcb.supabase.co'); // e.g. https://abcdefghijklm.supabase.co
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1xaHpva3JyYm16cHNvbWNtYmNiIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDY4NTU0ODcsImV4cCI6MjA2MjQzMTQ4N30.HxKn62H0P5klBYwj21wdRLOfg4EXItNZsLuBT47RSKk'); // your anon/public key
define('SUPABASE_BUCKET', '3d-objects'); // the name of your storage bucket for 3D files

// Function to check if Supabase integration is enabled
function is_supabase_enabled()
{
    return defined('SUPABASE_URL') && SUPABASE_URL !== 'https://mqhzokrrbmzpsomcmbcb.supabase.co' &&
        defined('SUPABASE_KEY') && SUPABASE_KEY !== 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1xaHpva3JyYm16cHNvbWNtYmNiIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDY4NTU0ODcsImV4cCI6MjA2MjQzMTQ4N30.HxKn62H0P5klBYwj21wdRLOfg4EXItNZsLuBT47RSKk';
}
?>
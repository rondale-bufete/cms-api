<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize auth
$auth = new Auth();

// Logout user
$auth->logout();

// Redirect to home page
$_SESSION['flash_message'] = 'You have been logged out successfully.';
$_SESSION['flash_type'] = 'success';
header('Location: index.php');
exit;
?>

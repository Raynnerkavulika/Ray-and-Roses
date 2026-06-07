<?php
// config/check_auth.php
// This file ensures that users are logged in before accessing protected pages

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Store the requested URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Set error message
    $_SESSION['error_message'] = "Please login to access this page.";
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Optional: Check if session is expired (1 hour timeout)
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // Session expired after 1 hour
    session_destroy();
    $_SESSION['error_message'] = "Your session has expired. Please login again.";
    header("Location: login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Regenerate session ID periodically to prevent fixation
if(!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// User is authenticated, continue
?>
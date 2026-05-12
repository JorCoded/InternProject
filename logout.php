<?php
/**
 * logout.php
 * ==========
 * Logs the current user out.
 *
 * Steps:
 *  1. Auth::logout() records the clock-out time for today
 *  2. Auth::logout() destroys the PHP session (clears all stored login data)
 *  3. Redirect to the login page with a goodbye message
 */
require_once 'includes/auth.php';

Auth::logout();

// Redirect to login page with a success message shown in the URL
header("Location: index.php?msg=" . urlencode("You have been logged out."));
exit;

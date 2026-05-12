<?php
/**
 * config/bootstrap.php
 * ====================
 * This file is the FIRST thing every page includes.
 * It sets up the database connection and loads all classes.
 *
 * Every PHP page in this project starts with:
 *   require_once '../includes/auth.php';
 * which in turn requires this file.
 */

// ── Database credentials ──────────────────────────────────────
// Change these to match your server's MySQL settings.
// You can also run setup.php to configure these automatically.
define('DB_HOST', 'localhost');  // usually 'localhost'
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'hrms');       // the database name

// ── Load all class files ──────────────────────────────────────
// We load each class file manually so PHP knows about them.
// The order matters: Database must load first because other classes use it.
require_once __DIR__ . '/../classes/Database.php';    // must be first
require_once __DIR__ . '/../classes/User.php';        // user accounts
require_once __DIR__ . '/../classes/Attendance.php';  // clock in/out, breaks
require_once __DIR__ . '/../classes/Models.php';      // Leave, Task, Notification, Setting, Auth, Announcement

// ── Start the PHP session ─────────────────────────────────────
// Sessions let us remember who is logged in across page loads.
// We start it here so it's available to every page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

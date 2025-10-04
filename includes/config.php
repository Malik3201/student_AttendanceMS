<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_ms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

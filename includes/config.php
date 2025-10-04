<?php
/**
 * Configuration File
 * 
 * This file contains all the configuration settings for the Canberra Student
 * Attendance and Marks Management System. Update these values according to
 * your server environment and requirements.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2024
 */

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================
// Database connection settings
// Update these values to match your MySQL database configuration

define('DB_HOST', 'localhost');        // Database server hostname
define('DB_NAME', 'attendance_ms');    // Database name
define('DB_USER', 'root');             // Database username
define('DB_PASS', '');                 // Database password

// ============================================================================
// ERROR REPORTING CONFIGURATION
// ============================================================================
// Error reporting settings - Enable for development, disable for production

error_reporting(E_ALL);                // Report all PHP errors
ini_set('display_errors', 1);          // Display errors on screen (development)
ini_set('log_errors', 1);              // Log errors to error log file

// ============================================================================
// SYSTEM CONFIGURATION
// ============================================================================
// Basic system settings and preferences

date_default_timezone_set('UTC');      // Set default timezone to UTC

// ============================================================================
// SESSION MANAGEMENT
// ============================================================================
// Start session if not already started
// This is required for user authentication and state management

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
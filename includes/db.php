<?php
/**
 * Database Connection File
 * 
 * This file establishes a secure connection to the MySQL database using PDO.
 * It includes proper error handling and security configurations.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2024
 */

// Include configuration file
require_once 'config.php';

// ============================================================================
// DATABASE CONNECTION
// ============================================================================
// Create PDO connection with security and performance optimizations

try {
    // Create PDO instance with MySQL connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            // Enable exception throwing for error handling
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            
            // Return associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            // Disable prepared statement emulation for security
            PDO::ATTR_EMULATE_PREPARES => false,
            
            // Additional security and performance options
            PDO::ATTR_PERSISTENT => false,           // Disable persistent connections
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Log successful connection (development only)
    error_log("Database connection established successfully");
    
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly error message
    die("Database connection failed. Please check your configuration and try again.");
}
?>
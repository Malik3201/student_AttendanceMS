<?php
/**
 * Access Control Guard Functions
 * 
 * This file contains access control functions that protect pages and resources
 * based on user authentication status and roles. These functions redirect
 * unauthorized users to appropriate pages.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2024
 */

// Include authentication functions
require_once 'auth.php';

// ============================================================================
// ACCESS CONTROL FUNCTIONS
// ============================================================================

/**
 * Require user to be logged in
 * 
 * Redirects to login page if user is not authenticated.
 * This is the base requirement for all protected pages.
 */
function require_login() {
    if (!is_logged_in()) {
        // Redirect to login page
        header('Location: login.php');
        exit;
    }
}

/**
 * Require user to be a lecturer
 * 
 * First checks if user is logged in, then verifies lecturer role.
 * Redirects to dashboard with error if access is denied.
 */
function require_lecturer() {
    // First ensure user is logged in
    require_login();
    
    // Check if user has lecturer role
    if (!is_lecturer()) {
        // Redirect to dashboard with access denied error
        header('Location: dashboard.php?error=access_denied');
        exit;
    }
}

/**
 * Require user to be a student
 * 
 * First checks if user is logged in, then verifies student role.
 * Redirects to dashboard with error if access is denied.
 */
function require_student() {
    // First ensure user is logged in
    require_login();
    
    // Check if user has student role
    if (!is_student()) {
        // Redirect to dashboard with access denied error
        header('Location: dashboard.php?error=access_denied');
        exit;
    }
}
?>
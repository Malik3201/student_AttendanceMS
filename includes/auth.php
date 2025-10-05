<?php
/**
 * Authentication Functions
 * 
 * This file contains all authentication-related functions for the Canberra
 * Student Attendance and Marks Management System. It handles user login,
 * logout, session management, and role-based access control.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2025
 */

// ============================================================================
// USER AUTHENTICATION FUNCTIONS
// ============================================================================

/**
 * Authenticate user login
 * 
 * Verifies user credentials against the database and creates a secure session
 * if authentication is successful.
 * 
 * @param PDO $pdo Database connection object
 * @param string $email User's email address
 * @param string $password User's plain text password
 * @return bool True if login successful, false otherwise
 */
function login($pdo, $email, $password) {
    // Prepare SQL query to fetch user data
    $stmt = $pdo->prepare("SELECT user_id, name, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Verify password and create session if valid
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store user data in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        return true;
    }
    
    return false;
}

/**
 * Get current logged-in user information
 * 
 * Retrieves the current user's information from the session.
 * 
 * @return array|null User data array or null if not logged in
 */
function current_user() {
    // Check if all required session variables exist
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['name'])) {
        return [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'role' => $_SESSION['role']
        ];
    }
    
    return null;
}

/**
 * Logout current user
 * 
 * Destroys the current session and starts a new one for security.
 */
function logout() {
    // Destroy current session
    session_destroy();
    
    // Start new session
    session_start();
}

// ============================================================================
// SESSION STATUS FUNCTIONS
// ============================================================================

/**
 * Check if user is currently logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return current_user() !== null;
}

/**
 * Check if current user is a lecturer
 * 
 * @return bool True if user is a lecturer, false otherwise
 */
function is_lecturer() {
    $user = current_user();
    return $user && $user['role'] === 'lecturer';
}

/**
 * Check if current user is a student
 * 
 * @return bool True if user is a student, false otherwise
 */
function is_student() {
    $user = current_user();
    return $user && $user['role'] === 'student';
}
?>
<?php
/**
 * Login Page - Canberra Student Attendance and Marks Management System
 * 
 * This page handles user authentication for both lecturers and students.
 * It includes form validation, error handling, and redirects authenticated
 * users to the appropriate dashboard.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2024
 */

// Include header template
require_once 'includes/header.php';

// Redirect to dashboard if user is already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Initialize error message variable
$error = '';

// ============================================================================
// LOGIN FORM PROCESSING
// ============================================================================

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Attempt to authenticate user
        if (login($pdo, $email, $password)) {
            // Redirect to dashboard on successful login
            header('Location: dashboard.php');
            exit;
        } else {
            // Display error for invalid credentials
            $error = 'Invalid email or password.';
        }
    }
}
?>

<main class="auth-main">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your Canberra System account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <circle cx="12" cy="16" r="1"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large auth-submit">
                    <span>Sign In</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/>
                        <path d="M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="auth-link">Create one here</a></p>
                <p><a href="index.php" class="auth-link-secondary">← Back to Home</a></p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
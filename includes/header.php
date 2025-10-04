<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canberra Student Attendance and Marks Management System</title>
    <meta name="description" content="Professional attendance and marks management system for educational institutions. Role-based access for lecturers and students.">
    <meta property="og:title" content="Canberra Student Attendance and Marks Management System">
    <meta property="og:description" content="Professional attendance and marks management system for educational institutions.">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="index.php" class="logo">
                <span class="logo-text">Canberra System</span>
                <span class="logo-subtitle">Student Attendance & Marks Management</span>
            </a>
            <?php if ($user): ?>
                <!-- Logged in navigation -->
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if ($user['role'] === 'lecturer'): ?>
                        <li><a href="courses.php">My Courses</a></li>
                        <li><a href="students.php">Students</a></li>
                        <li><a href="reports.php">Reports</a></li>
                    <?php else: ?>
                        <li><a href="my-courses.php">My Courses</a></li>
                        <li><a href="my-attendance.php">My Attendance</a></li>
                        <li><a href="my-marks.php">My Marks</a></li>
                    <?php endif; ?>
                </ul>
                <div class="nav-actions">
                    <div class="user-menu desktop-only">
                        <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    </div>
                    <button class="mobile-nav-toggle" id="mobileNavToggle" aria-expanded="false" aria-controls="mobileNav" aria-label="Toggle navigation menu">
                        <span class="hamburger"></span>
                    </button>
                </div>
            <?php else: ?>
                <!-- Not logged in navigation -->
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#stats">Statistics</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <div class="nav-actions">
                    <div class="nav-buttons">
                        <a href="login.php" class="btn btn-secondary">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    </div>
                    <button class="mobile-nav-toggle" id="mobileNavToggle" aria-expanded="false" aria-controls="mobileNav" aria-label="Toggle navigation menu">
                        <span class="hamburger"></span>
                    </button>
                </div>
            <?php endif; ?>
        </nav>
        <div class="mobile-nav" id="mobileNav">
            <div class="mobile-nav-header">
                <span class="mobile-nav-title">Menu</span>
                <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close navigation menu">×</button>
            </div>
            <?php if ($user): ?>
                <!-- Logged in mobile navigation -->
                <ul class="mobile-nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if ($user['role'] === 'lecturer'): ?>
                        <li><a href="courses.php">My Courses</a></li>
                        <li><a href="students.php">Students</a></li>
                        <li><a href="reports.php">Reports</a></li>
                    <?php else: ?>
                        <li><a href="my-courses.php">My Courses</a></li>
                        <li><a href="my-attendance.php">My Attendance</a></li>
                        <li><a href="my-marks.php">My Marks</a></li>
                    <?php endif; ?>
                </ul>
                <div class="mobile-nav-actions">
                    <div class="mobile-user-info">
                        <span class="mobile-user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-secondary mobile-logout-btn">Logout</a>
                </div>
            <?php else: ?>
                <!-- Not logged in mobile navigation -->
                <ul class="mobile-nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#stats">Statistics</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <div class="mobile-nav-actions">
                    <a href="login.php" class="btn btn-secondary">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="main">
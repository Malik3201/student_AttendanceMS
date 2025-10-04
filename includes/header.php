<?php
// Include auth functions if not already included
if (!function_exists('current_user')) {
    require_once __DIR__ . '/auth.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance & Marks Management System</title>
</head>
<body>
    <header>
        <h1>Attendance & Marks Management System</h1>
        <nav>
            <?php
            $user = current_user();
            if ($user):
                if ($user['role'] === 'student'):
            ?>
                <a href="dashboard.php">Dashboard</a> |
                <a href="student-enroll.php">My Courses</a> |
                <a href="my-records.php">My Records</a> |
                <a href="logout.php">Logout</a>
            <?php
                elseif ($user['role'] === 'lecturer'):
            ?>
                <a href="dashboard.php">Dashboard</a> |
                <a href="courses.php">My Courses</a> |
                <a href="attendance.php">Attendance</a> |
                <a href="marks.php">Marks</a> |
                <a href="logout.php">Logout</a>
            <?php
                endif;
            else:
            ?>
                <a href="login.php">Login</a> |
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>

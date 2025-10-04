<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

$user = current_user();
$denied = isset($_GET['denied']) ? true : false;

if ($denied) {
    echo '<p style="color: red;">Access denied. You do not have permission to access this page.</p>';
}

if ($user['role'] === 'lecturer') {
    // Get courses taught by this lecturer
    $stmt = $pdo->prepare("SELECT id, code, title FROM courses WHERE lecturer_id = ? ORDER BY code");
    $stmt->execute([$user['id']]);
    $courses = $stmt->fetchAll();
    
    echo '<h2>My Courses</h2>';
    if (empty($courses)) {
        echo '<p>No courses created yet. <a href="courses.php">Create your first course</a></p>';
    } else {
        echo '<table border="1">';
        echo '<tr><th>Code</th><th>Title</th><th>Actions</th></tr>';
        foreach ($courses as $course) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($course['code']) . '</td>';
            echo '<td>' . htmlspecialchars($course['title']) . '</td>';
            echo '<td>';
            echo '<a href="attendance.php?course=' . $course['id'] . '">Take Attendance</a> | ';
            echo '<a href="marks.php?course=' . $course['id'] . '">Enter Marks</a> | ';
            echo '<a href="enrollments.php?course=' . $course['id'] . '">Manage Students</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p><a href="courses.php">Manage All Courses</a></p>';
    }
} else {
    // Student dashboard
    $stmt = $pdo->prepare("
        SELECT c.id, c.code, c.title 
        FROM enrollments e 
        JOIN courses c ON c.id = e.course_id 
        WHERE e.student_id = ? 
        ORDER BY c.code
    ");
    $stmt->execute([$user['id']]);
    $courses = $stmt->fetchAll();
    
    echo '<h2>My Enrolled Courses</h2>';
    if (empty($courses)) {
        echo '<p>No courses enrolled yet. Please contact your lecturer to be enrolled in courses.</p>';
    } else {
        echo '<table border="1">';
        echo '<tr><th>Code</th><th>Title</th><th>Attendance %</th><th>Marks</th></tr>';
        
        foreach ($courses as $course) {
            // Get attendance percentage
            $attStmt = $pdo->prepare("
                SELECT ROUND(
                    (COUNT(CASE WHEN status = 'Present' THEN 1 END) * 100.0 / COUNT(*)), 
                    2
                ) AS attendance_percentage
                FROM attendance 
                WHERE course_id = ? AND student_id = ?
            ");
            $attStmt->execute([$course['id'], $user['id']]);
            $attendance = $attStmt->fetch();
            $attPct = $attendance['attendance_percentage'] ?? 0;
            
            // Get marks totals
            $marksStmt = $pdo->prepare("
                SELECT SUM(obtained) AS total_obtained, SUM(total) AS total_possible
                FROM marks 
                WHERE course_id = ? AND student_id = ?
            ");
            $marksStmt->execute([$course['id'], $user['id']]);
            $marks = $marksStmt->fetch();
            $obtained = $marks['total_obtained'] ?? 0;
            $possible = $marks['total_possible'] ?? 0;
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($course['code']) . '</td>';
            echo '<td>' . htmlspecialchars($course['title']) . '</td>';
            echo '<td>' . $attPct . '%</td>';
            echo '<td>' . $obtained . '/' . $possible . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

require_once 'includes/footer.php';
?>

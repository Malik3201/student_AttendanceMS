<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

require_role(['student']);

$user = current_user();

// Get enrolled courses
$stmt = $pdo->prepare("
    SELECT c.id, c.code, c.title, u.full_name as lecturer_name
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    JOIN lecturers l ON l.id = c.lecturer_id
    JOIN users u ON u.id = l.id
    WHERE e.student_id = ?
    ORDER BY c.code
");
$stmt->execute([$user['id']]);
$enrolled_courses = $stmt->fetchAll();

echo '<h2>My Enrolled Courses</h2>';

if (empty($enrolled_courses)) {
    echo '<p>You are not enrolled in any courses yet. Please contact your lecturer to be enrolled in courses.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Code</th><th>Title</th><th>Lecturer</th></tr>';
    
    foreach ($enrolled_courses as $course) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($course['code']) . '</td>';
        echo '<td>' . htmlspecialchars($course['title']) . '</td>';
        echo '<td>' . htmlspecialchars($course['lecturer_name']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<p><em>Note: Course enrollment is managed by your lecturers. Contact them if you need to be enrolled in additional courses.</em></p>';

require_once 'includes/footer.php';
?>
<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

require_role(['lecturer']);

$user = current_user();
$course_id = $_GET['course'] ?? '';
$att_date = $_GET['att_date'] ?? date('Y-m-d');
$saved = isset($_GET['saved']) ? true : false;

if (!$course_id) {
    echo '<p>Course ID required.</p>';
    require_once 'includes/footer.php';
    exit;
}

// Verify course belongs to current lecturer
$stmt = $pdo->prepare("SELECT id, code, title FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$course_id, $user['id']]);
$course = $stmt->fetch();

if (!$course) {
    echo '<p>Course not found or access denied.</p>';
    require_once 'includes/footer.php';
    exit;
}

if ($saved) {
    echo '<p style="color: green;">Attendance saved successfully!</p>';
}

// Get enrolled students
$stmt = $pdo->prepare("
    SELECT s.id, s.reg_no, u.full_name
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN users u ON u.id = s.id
    WHERE e.course_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();

// Get existing attendance for this date
$stmt = $pdo->prepare("
    SELECT student_id, status 
    FROM attendance 
    WHERE course_id = ? AND att_date = ?
");
$stmt->execute([$course_id, $att_date]);
$existing = [];
while ($row = $stmt->fetch()) {
    $existing[$row['student_id']] = $row['status'];
}

echo '<h2>Take Attendance - ' . htmlspecialchars($course['code']) . ' - ' . htmlspecialchars($course['title']) . '</h2>';

echo '<form method="POST" action="controllers/attendance_save.php">';
echo '<input type="hidden" name="course_id" value="' . $course_id . '">';
echo '<input type="hidden" name="att_date" value="' . $att_date . '">';

echo '<p><strong>Date:</strong> ' . htmlspecialchars($att_date) . '</p>';

if (empty($students)) {
    echo '<p>No students enrolled in this course.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Reg No</th><th>Name</th><th>Present</th><th>Absent</th><th>Late</th></tr>';
    
    foreach ($students as $student) {
        $currentStatus = $existing[$student['id']] ?? 'Present';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['reg_no']) . '</td>';
        echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
        echo '<td><input type="radio" name="status[' . $student['id'] . ']" value="Present" ' . ($currentStatus === 'Present' ? 'checked' : '') . '></td>';
        echo '<td><input type="radio" name="status[' . $student['id'] . ']" value="Absent" ' . ($currentStatus === 'Absent' ? 'checked' : '') . '></td>';
        echo '<td><input type="radio" name="status[' . $student['id'] . ']" value="Late" ' . ($currentStatus === 'Late' ? 'checked' : '') . '></td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<br><button type="submit">Save Attendance</button>';
}

echo '</form>';

require_once 'includes/footer.php';
?>

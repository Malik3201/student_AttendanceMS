<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

require_role(['lecturer']);

$user = current_user();
$course_id = $_GET['course'] ?? '';
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
    echo '<p style="color: green;">Marks saved successfully!</p>';
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

echo '<h2>Enter Marks - ' . htmlspecialchars($course['code']) . ' - ' . htmlspecialchars($course['title']) . '</h2>';

echo '<form method="POST" action="controllers/mark_save.php">';
echo '<input type="hidden" name="course_id" value="' . $course_id . '">';

echo '<div>';
echo '<label for="assessment">Assessment Name:</label>';
echo '<input type="text" id="assessment" name="assessment" required>';
echo '</div>';

echo '<div>';
echo '<label for="total">Total Marks:</label>';
echo '<input type="number" id="total" name="total" min="1" required>';
echo '</div>';

if (empty($students)) {
    echo '<p>No students enrolled in this course.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Reg No</th><th>Name</th><th>Obtained Marks</th></tr>';
    
    foreach ($students as $student) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['reg_no']) . '</td>';
        echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
        echo '<td><input type="number" name="obtained[' . $student['id'] . ']" min="0" value="0"></td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<br><button type="submit">Save Marks</button>';
}

echo '</form>';

require_once 'includes/footer.php';
?>

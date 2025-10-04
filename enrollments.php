<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

require_role(['lecturer']);

$user = current_user();
$course_id = $_GET['course'] ?? '';
$error = '';
$success = '';

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

// Handle enrollment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    
    if ($action === 'enroll' && $student_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $course_id]);
            $success = 'Student enrolled successfully!';
        } catch (Exception $e) {
            if ($e->getCode() == 23000) { // Duplicate key error
                $error = 'Student is already enrolled in this course.';
            } else {
                $error = 'Error enrolling student: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'unenroll' && $student_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);
            $success = 'Student unenrolled successfully!';
        } catch (Exception $e) {
            $error = 'Error unenrolling student: ' . $e->getMessage();
        }
    }
}

// Get all students
$stmt = $pdo->prepare("
    SELECT s.id, s.reg_no, u.full_name, u.email
    FROM students s
    JOIN users u ON u.id = s.id
    ORDER BY u.full_name
");
$stmt->execute();
$all_students = $stmt->fetchAll();

// Get enrolled students for this course
$stmt = $pdo->prepare("
    SELECT s.id, s.reg_no, u.full_name, u.email
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN users u ON u.id = s.id
    WHERE e.course_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$course_id]);
$enrolled_students = $stmt->fetchAll();

echo '<h2>Manage Enrollments - ' . htmlspecialchars($course['code']) . ' - ' . htmlspecialchars($course['title']) . '</h2>';

if ($error) {
    echo '<p style="color: red;">' . htmlspecialchars($error) . '</p>';
}

if ($success) {
    echo '<p style="color: green;">' . htmlspecialchars($success) . '</p>';
}

// Enrolled students
echo '<h3>Enrolled Students (' . count($enrolled_students) . ')</h3>';
if (empty($enrolled_students)) {
    echo '<p>No students enrolled in this course.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Reg No</th><th>Name</th><th>Email</th><th>Action</th></tr>';
    
    foreach ($enrolled_students as $student) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['reg_no']) . '</td>';
        echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($student['email']) . '</td>';
        echo '<td>';
        echo '<form method="POST" style="display: inline;">';
        echo '<input type="hidden" name="action" value="unenroll">';
        echo '<input type="hidden" name="student_id" value="' . $student['id'] . '">';
        echo '<button type="submit" onclick="return confirm(\'Are you sure you want to unenroll this student?\')">Unenroll</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<hr>';

// Available students (not enrolled)
$enrolled_ids = array_column($enrolled_students, 'id');
$available_students = array_filter($all_students, function($student) use ($enrolled_ids) {
    return !in_array($student['id'], $enrolled_ids);
});

echo '<h3>Available Students (' . count($available_students) . ')</h3>';
if (empty($available_students)) {
    echo '<p>All students are already enrolled in this course.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Reg No</th><th>Name</th><th>Email</th><th>Action</th></tr>';
    
    foreach ($available_students as $student) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['reg_no']) . '</td>';
        echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($student['email']) . '</td>';
        echo '<td>';
        echo '<form method="POST" style="display: inline;">';
        echo '<input type="hidden" name="action" value="enroll">';
        echo '<input type="hidden" name="student_id" value="' . $student['id'] . '">';
        echo '<button type="submit">Enroll</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<p><a href="courses.php">← Back to Courses</a></p>';

require_once 'includes/footer.php';
?>

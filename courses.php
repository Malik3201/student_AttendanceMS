<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

require_role(['lecturer']);

$user = current_user();
$error = '';
$success = '';

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_course') {
    $code = trim($_POST['code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    
    if (empty($code) || empty($title)) {
        $error = 'Course code and title are required.';
    } else {
        try {
            // Check if course code already exists
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $error = 'Course code already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO courses (code, title, lecturer_id) VALUES (?, ?, ?)");
                $stmt->execute([$code, $title, $user['id']]);
                $success = 'Course created successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error creating course: ' . $e->getMessage();
        }
    }
}

// Get all courses taught by this lecturer
$stmt = $pdo->prepare("SELECT id, code, title, created_at FROM courses WHERE lecturer_id = ? ORDER BY code");
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll();

echo '<h2>My Courses</h2>';

if ($error) {
    echo '<p style="color: red;">' . htmlspecialchars($error) . '</p>';
}

if ($success) {
    echo '<p style="color: green;">' . htmlspecialchars($success) . '</p>';
}

// Course creation form
echo '<h3>Create New Course</h3>';
echo '<form method="POST">';
echo '<input type="hidden" name="action" value="create_course">';
echo '<div>';
echo '<label for="code">Course Code:</label>';
echo '<input type="text" id="code" name="code" required placeholder="e.g., CS101">';
echo '</div>';
echo '<div>';
echo '<label for="title">Course Title:</label>';
echo '<input type="text" id="title" name="title" required placeholder="e.g., Introduction to Programming">';
echo '</div>';
echo '<div>';
echo '<button type="submit">Create Course</button>';
echo '</div>';
echo '</form>';

echo '<hr>';

// Display existing courses
if (empty($courses)) {
    echo '<p>No courses created yet. Create your first course above.</p>';
} else {
    echo '<h3>Existing Courses</h3>';
    echo '<table border="1">';
    echo '<tr><th>Code</th><th>Title</th><th>Created</th><th>Actions</th></tr>';
    
    foreach ($courses as $course) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($course['code']) . '</td>';
        echo '<td>' . htmlspecialchars($course['title']) . '</td>';
        echo '<td>' . htmlspecialchars($course['created_at']) . '</td>';
        echo '<td>';
        echo '<a href="attendance.php?course=' . $course['id'] . '">Take Attendance</a> | ';
        echo '<a href="marks.php?course=' . $course['id'] . '">Enter Marks</a> | ';
        echo '<a href="enrollments.php?course=' . $course['id'] . '">Manage Enrollments</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

require_once 'includes/footer.php';
?>

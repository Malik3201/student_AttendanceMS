<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';

require_lecturer();

$user = current_user();
$course_id = $_GET['course_id'] ?? '';
$error = '';
$success = '';

// Verify course belongs to lecturer
if ($course_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ? AND lecturer_id = ?");
        $stmt->execute([$course_id, $user['user_id']]);
$course = $stmt->fetch();

if (!$course) {
            header('Location: courses.php');
            exit;
        }
    } catch (Exception $e) {
        header('Location: courses.php');
    exit;
}
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    $date = $_POST['date'] ?? '';
    $attendance_data = $_POST['attendance'] ?? [];
    
    if (empty($date)) {
        $error = 'Please select a date.';
    } elseif (empty($attendance_data)) {
        $error = 'No students to mark attendance for.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Delete existing attendance for this date and course
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE course_id = ? AND date = ?");
            $stmt->execute([$course_id, $date]);
            
            // Insert new attendance records
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, date, status) VALUES (?, ?, ?, ?)");
            
            foreach ($attendance_data as $student_id => $status) {
                if (in_array($status, ['Present', 'Absent'])) {
                    $stmt->execute([$student_id, $course_id, $date, $status]);
                }
            }
            
            $pdo->commit();
            $success = 'Attendance marked successfully for ' . date('M j, Y', strtotime($date)) . '!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error saving attendance. Please try again.';
        }
    }
}

// Get lecturer's courses for course selection
try {
    $stmt = $pdo->prepare("SELECT course_id, course_name, course_code FROM courses WHERE lecturer_id = ? ORDER BY course_code");
    $stmt->execute([$user['user_id']]);
    $lecturer_courses = $stmt->fetchAll();
} catch (Exception $e) {
    $lecturer_courses = [];
}

// Get enrolled students for selected course
$enrolled_students = [];
if ($course_id) {
    try {
$stmt = $pdo->prepare("
            SELECT u.user_id, u.name, u.email
            FROM users u
            JOIN enrollments e ON u.user_id = e.student_id
    WHERE e.course_id = ?
            ORDER BY u.name
");
$stmt->execute([$course_id]);
        $enrolled_students = $stmt->fetchAll();
    } catch (Exception $e) {
        $enrolled_students = [];
    }
}

// Get existing attendance for today (if any)
$today_attendance = [];
if ($course_id) {
    $today = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE course_id = ? AND date = ?");
        $stmt->execute([$course_id, $today]);
        $today_attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $today_attendance = [];
    }
}
?>

<h1>Mark Attendance</h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Course Selection -->
<div class="card">
    <h2>Select Course</h2>
    <form method="GET">
        <div class="form-group">
            <label for="course_id">Course:</label>
            <select id="course_id" name="course_id" onchange="this.form.submit()">
                <option value="">Select a course</option>
                <?php foreach ($lecturer_courses as $c): ?>
                    <option value="<?php echo $c['course_id']; ?>" 
                            <?php echo $course_id == $c['course_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($course_id && $course): ?>
    <div class="card">
        <h2>Course: <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h2>
        <p>Semester: <?php echo htmlspecialchars($course['semester']); ?></p>
    </div>

    <?php if (empty($enrolled_students)): ?>
        <div class="card">
            <p>No students enrolled in this course. <a href="students.php?course_id=<?php echo $course_id; ?>">Enroll students first</a>.</p>
        </div>
    <?php else: ?>
        <!-- Attendance Form -->
        <div class="card">
            <h3>Mark Attendance</h3>
            <form method="POST">
                <input type="hidden" name="action" value="mark_attendance">
                
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Present</th>
                            <th>Absent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolled_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td>
                                    <input type="radio" 
                                           name="attendance[<?php echo $student['user_id']; ?>]" 
                                           value="Present"
                                           <?php echo ($today_attendance[$student['user_id']] ?? '') === 'Present' ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <input type="radio" 
                                           name="attendance[<?php echo $student['user_id']; ?>]" 
                                           value="Absent"
                                           <?php echo ($today_attendance[$student['user_id']] ?? 'Absent') === 'Absent' ? 'checked' : ''; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" class="btn btn-success">Save Attendance</button>
            </form>
        </div>

        <!-- Recent Attendance -->
        <div class="card">
            <h3>Recent Attendance Records</h3>
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT a.date, u.name, a.status
                    FROM attendance a
                    JOIN users u ON u.user_id = a.student_id
                    WHERE a.course_id = ?
                    ORDER BY a.date DESC, u.name
                    LIMIT 50
                ");
                $stmt->execute([$course_id]);
                $recent_attendance = $stmt->fetchAll();
                
                if (empty($recent_attendance)): ?>
                    <p>No attendance records yet.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attendance as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['name']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $record['status'] === 'Present' ? 'green' : 'red'; ?>;">
                                            <?php echo $record['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif;
            } catch (Exception $e) {
                echo '<p>Error loading attendance records.</p>';
            }
            ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
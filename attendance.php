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

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Mark Attendance</h1>
            <p>Select a course, choose a date, and mark each student's status.</p>
        </div>
        <div class="dashboard-actions">
            <a href="courses.php" class="btn btn-secondary">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Courses
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Select Course</h2>
        </div>
        <div class="course-form-container">
            <form method="GET" class="course-form">
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id" onchange="this.form.submit()">
                        <option value="">Select a course</option>
                        <?php foreach ($lecturer_courses as $c): ?>
                            <option value="<?php echo $c['course_id']; ?>" <?php echo $course_id == $c['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($course_id && isset($course) && $course): ?>
        <div class="dashboard-section">
            <div class="course-card">
                <div class="course-header">
                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                    <div class="course-students">Semester: <?php echo htmlspecialchars($course['semester']); ?></div>
                </div>
                <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
            </div>
        </div>

        <?php if (empty($enrolled_students)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3>No Students Enrolled</h3>
                <p>No students enrolled in this course yet. Enroll students to start marking attendance.</p>
                <a href="students.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">Manage Enrollments</a>
            </div>
        <?php else: ?>
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Mark Attendance</h2>
                </div>
                <div class="course-form-container">
                    <form method="POST" class="course-form">
                        <input type="hidden" name="action" value="mark_attendance">

                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="table-responsive">
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
                                                <input type="radio" name="attendance[<?php echo $student['user_id']; ?>]" value="Present" <?php echo ($today_attendance[$student['user_id']] ?? '') === 'Present' ? 'checked' : ''; ?>>
                                            </td>
                                            <td>
                                                <input type="radio" name="attendance[<?php echo $student['user_id']; ?>]" value="Absent" <?php echo ($today_attendance[$student['user_id']] ?? 'Absent') === 'Absent' ? 'checked' : ''; ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Attendance</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Attendance Records</h2>
                </div>
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
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h3>No Attendance Records</h3>
                            <p>No attendance records found for this course.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
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
                                                <?php if ($record['status'] === 'Present'): ?>
                                                    <span class="status-badge status-good">Present</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-poor">Absent</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif;
                } catch (Exception $e) {
                    echo '<div class="alert alert-error">Error loading attendance records.</div>';
                }
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
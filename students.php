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

// Handle student enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'enroll' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        try {
            // Check if already enrolled
            $stmt = $pdo->prepare("SELECT enroll_id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);
            if ($stmt->fetch()) {
                $error = 'Student is already enrolled in this course.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $stmt->execute([$student_id, $course_id]);
                $success = 'Student enrolled successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error enrolling student.';
        }
    } elseif ($_POST['action'] === 'unenroll' && isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);
            $success = 'Student unenrolled successfully!';
        } catch (Exception $e) {
            $error = 'Error unenrolling student.';
        }
    }
}

// Get all students
try {
    $stmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE role = 'student' ORDER BY name");
    $stmt->execute();
    $all_students = $stmt->fetchAll();
} catch (Exception $e) {
    $all_students = [];
}

// Get enrolled students for this course with additional statistics
$enrolled_students = [];
if ($course_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.name, u.email, e.enrolled_at,
                   COUNT(DISTINCT a.attendance_id) as attendance_count,
                   COUNT(DISTINCT m.mark_id) as marks_count,
                   ROUND(AVG(CASE WHEN a.status = 'Present' THEN 100 ELSE 0 END), 1) as attendance_rate
            FROM users u
            JOIN enrollments e ON u.user_id = e.student_id
            LEFT JOIN attendance a ON u.user_id = a.student_id AND a.course_id = ?
            LEFT JOIN marks m ON u.user_id = m.student_id AND m.course_id = ?
            WHERE e.course_id = ?
            GROUP BY u.user_id, u.name, u.email, e.enrolled_at
            ORDER BY u.name
        ");
        $stmt->execute([$course_id, $course_id, $course_id]);
        $enrolled_students = $stmt->fetchAll();
    } catch (Exception $e) {
        $enrolled_students = [];
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

// Get overall statistics
$total_students = count($all_students);
$enrolled_count = count($enrolled_students);
$available_students = $total_students - $enrolled_count;
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Student Management</h1>
            <p>Enroll and manage students in your courses, track their progress and performance</p>
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

    <!-- Error/Success Messages -->
    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Course Selection -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Select Course</h2>
        </div>
        <div class="course-form-container">
            <form method="GET">
                <div class="form-group">
                    <label for="course_id">Choose Course</label>
                    <select id="course_id" name="course_id" onchange="this.form.submit()">
                        <option value="">Select a course to manage students</option>
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
    </div>

    <?php if ($course_id && $course): ?>
        <!-- Course Information -->
        <div class="dashboard-section">
            <div class="course-card">
                <div class="course-header">
                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                    <div class="course-students"><?php echo $enrolled_count; ?> enrolled</div>
                </div>
                <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                <div class="course-meta">
                    <div class="course-date">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        <?php echo htmlspecialchars($course['semester']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $enrolled_count; ?></div>
                    <div class="stat-label">Enrolled Students</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $available_students; ?></div>
                    <div class="stat-label">Available Students</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
        </div>

        <!-- Enroll New Student -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Enroll New Student</h2>
            </div>
            <div class="course-form-container">
                <form method="POST" class="course-form">
                    <input type="hidden" name="action" value="enroll">
                    <div class="form-group">
                        <label for="student_id">Select Student</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Choose a student to enroll</option>
                            <?php 
                            $enrolled_ids = array_column($enrolled_students, 'user_id');
                            foreach ($all_students as $student): 
                                if (!in_array($student['user_id'], $enrolled_ids)):
                            ?>
                                <option value="<?php echo $student['user_id']; ?>">
                                    <?php echo htmlspecialchars($student['name'] . ' (' . $student['email'] . ')'); ?>
                                </option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                        <small>Select from available students not yet enrolled in this course</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Enroll Student
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enrolled Students -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Enrolled Students (<?php echo $enrolled_count; ?>)</h2>
            </div>
            
            <?php if (empty($enrolled_students)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                        </svg>
                    </div>
                    <h3>No Students Enrolled</h3>
                    <p>Enroll students in this course to start tracking attendance and managing marks.</p>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($enrolled_students as $student): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-code">STUDENT</div>
                                <div class="course-students">
                                    <?php echo $student['attendance_rate'] ?? 0; ?>% attendance
                                </div>
                            </div>
                            <div class="course-title"><?php echo htmlspecialchars($student['name']); ?></div>
                            <div class="course-meta">
                                <div class="course-date">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                    <?php echo htmlspecialchars($student['email']); ?>
                                </div>
                            </div>
                            <div class="course-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Enrolled:</span>
                                    <span class="stat-value"><?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Attendance Records:</span>
                                    <span class="stat-value"><?php echo $student['attendance_count']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Marks Entered:</span>
                                    <span class="stat-value"><?php echo $student['marks_count']; ?></span>
                                </div>
                            </div>
                            <div class="course-actions">
                                <form method="POST" style="display: inline; width: 100%;" 
                                      onsubmit="return confirm('Are you sure you want to unenroll this student?')">
                                    <input type="hidden" name="action" value="unenroll">
                                    <input type="hidden" name="student_id" value="<?php echo $student['user_id']; ?>">
                                    <button type="submit" class="action-btn" style="background: #dc2626; color: white; border-color: #dc2626;">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Unenroll Student
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

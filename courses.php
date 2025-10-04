<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';

require_lecturer();

$user = current_user();
$error = '';
$success = '';

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $course_name = trim($_POST['course_name'] ?? '');
    $course_code = trim($_POST['course_code'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    
    if (empty($course_name) || empty($course_code) || empty($semester)) {
        $error = 'All fields are required.';
    } else {
        try {
            // Check if course code already exists
            $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ?");
            $stmt->execute([$course_code]);
            if ($stmt->fetch()) {
                $error = 'Course code already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code, semester, lecturer_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$course_name, $course_code, $semester, $user['user_id']]);
                $success = 'Course created successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error creating course. Please try again.';
        }
    }
}

// Get lecturer's courses with additional statistics
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(e.student_id) as enrolled_students,
               COUNT(DISTINCT a.attendance_id) as attendance_records,
               COUNT(DISTINCT m.mark_id) as marks_entered
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN attendance a ON c.course_id = a.course_id
        LEFT JOIN marks m ON c.course_id = m.course_id
        WHERE c.lecturer_id = ?
        GROUP BY c.course_id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user['user_id']]);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    $courses = [];
    $error = 'Error loading courses.';
}

// Get overall statistics
$total_courses = count($courses);
$total_students = array_sum(array_column($courses, 'enrolled_students'));
$total_attendance_records = array_sum(array_column($courses, 'attendance_records'));
$total_marks = array_sum(array_column($courses, 'marks_entered'));
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Course Management</h1>
            <p>Create and manage your courses, track student enrollment and performance</p>
        </div>
        <div class="dashboard-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Dashboard
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

    <!-- Statistics Overview -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_attendance_records; ?></div>
                <div class="stat-label">Attendance Records</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_marks; ?></div>
                <div class="stat-label">Marks Entered</div>
            </div>
        </div>
    </div>

    <!-- Create Course Form -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Create New Course</h2>
        </div>
        <div class="course-form-container">
            <form method="POST" class="course-form">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="course_name">Course Name</label>
                    <input type="text" id="course_name" name="course_name" required 
                           placeholder="Enter course name"
                           value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="course_code">Course Code</label>
                    <input type="text" id="course_code" name="course_code" required 
                           placeholder="e.g., CS101"
                           value="<?php echo htmlspecialchars($_POST['course_code'] ?? ''); ?>">
                    <small>Unique identifier for the course</small>
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <input type="text" id="semester" name="semester" required 
                           placeholder="e.g., Fall 2024"
                           value="<?php echo htmlspecialchars($_POST['semester'] ?? ''); ?>">
                    <small>Academic term for this course</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Courses -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>My Courses (<?php echo $total_courses; ?>)</h2>
        </div>
        
        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3>No Courses Yet</h3>
                <p>Create your first course to get started with managing students and tracking attendance.</p>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                            <div class="course-students"><?php echo $course['enrolled_students']; ?> students</div>
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
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Students:</span>
                                <span class="stat-value"><?php echo $course['enrolled_students']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Attendance Records:</span>
                                <span class="stat-value"><?php echo $course['attendance_records']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Marks Entered:</span>
                                <span class="stat-value"><?php echo $course['marks_entered']; ?></span>
                            </div>
                        </div>
                        <div class="course-actions">
                            <a href="students.php?course_id=<?php echo $course['course_id']; ?>" class="action-btn">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                Manage Students
                            </a>
                            <a href="attendance.php?course_id=<?php echo $course['course_id']; ?>" class="action-btn">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                Mark Attendance
                            </a>
                            <a href="marks.php?course_id=<?php echo $course['course_id']; ?>" class="action-btn">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Manage Marks
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
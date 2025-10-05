<?php
/**
 * Dashboard - Canberra Student Attendance and Marks Management System
 * 
 * This is the main dashboard page that displays different content and statistics
 * based on the user's role (lecturer or student). It provides an overview of
 * courses, attendance, marks, and quick access to key features.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2025
 */

// Include required files
require_once 'includes/header.php';
require_once 'includes/guard.php';

// Ensure user is logged in
require_login();

// Get current user information
$user = current_user();

// Get error message from URL parameters
$error = $_GET['error'] ?? '';

// ============================================================================
// STATISTICS COLLECTION
// ============================================================================

// Initialize statistics array
$stats = [];

// Get comprehensive statistics based on user role
if ($user['role'] === 'lecturer') {
    try {
        // Total courses
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE lecturer_id = ?");
        $stmt->execute([$user['user_id']]);
        $stats['total_courses'] = $stmt->fetchColumn();
        
        // Total students across all courses
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.student_id) 
            FROM enrollments e 
            JOIN courses c ON c.course_id = e.course_id 
            WHERE c.lecturer_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $stats['total_students'] = $stmt->fetchColumn();

        // Total attendance records
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM attendance a 
            JOIN courses c ON c.course_id = a.course_id 
            WHERE c.lecturer_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $stats['total_attendance_records'] = $stmt->fetchColumn();

        // Average attendance rate
        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(CASE WHEN a.status = 'Present' THEN 100 ELSE 0 END), 1) 
            FROM attendance a 
            JOIN courses c ON c.course_id = a.course_id 
            WHERE c.lecturer_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $stats['avg_attendance_rate'] = $stmt->fetchColumn() ?? 0;

        // Total marks entered
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM marks m 
            JOIN courses c ON c.course_id = m.course_id 
            WHERE c.lecturer_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $stats['total_marks'] = $stmt->fetchColumn();

        // Recent courses (last 5)
        $stmt = $pdo->prepare("
            SELECT course_id, course_name, course_code, semester, created_at
            FROM courses 
            WHERE lecturer_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user['user_id']]);
        $stats['recent_courses'] = $stmt->fetchAll();

        // Recent attendance (last 5 records)
        $stmt = $pdo->prepare("
            SELECT a.date, a.status, c.course_name, c.course_code, u.name as student_name
            FROM attendance a 
            JOIN courses c ON c.course_id = a.course_id 
            JOIN users u ON u.user_id = a.student_id
            WHERE c.lecturer_id = ? 
            ORDER BY a.date DESC, a.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user['user_id']]);
        $stats['recent_attendance'] = $stmt->fetchAll();

    } catch (Exception $e) {
        // Set default values if there's an error
        $stats = [
            'total_courses' => 0,
            'total_students' => 0,
            'total_attendance_records' => 0,
            'avg_attendance_rate' => 0,
            'total_marks' => 0,
            'recent_courses' => [],
            'recent_attendance' => []
        ];
    }
}

// Get comprehensive statistics for student
$student_stats = [];
if ($user['role'] === 'student') {
    try {
        // Enrolled courses
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
        $stmt->execute([$user['user_id']]);
        $student_stats['enrolled_courses'] = $stmt->fetchColumn();
        
        // Average attendance
        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(CASE WHEN status = 'Present' THEN 100 ELSE 0 END), 1) 
            FROM attendance 
            WHERE student_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $student_stats['avg_attendance'] = $stmt->fetchColumn() ?? 0;

        // Total attendance records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
        $stmt->execute([$user['user_id']]);
        $student_stats['total_attendance_records'] = $stmt->fetchColumn();

        // Total marks received
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM marks WHERE student_id = ?");
        $stmt->execute([$user['user_id']]);
        $student_stats['total_marks'] = $stmt->fetchColumn();

        // Average marks
        $stmt = $pdo->prepare("SELECT ROUND(AVG(score), 1) FROM marks WHERE student_id = ?");
        $stmt->execute([$user['user_id']]);
        $student_stats['avg_marks'] = $stmt->fetchColumn() ?? 0;

        // Recent courses
        $stmt = $pdo->prepare("
            SELECT c.course_name, c.course_code, c.semester, e.enrolled_at
            FROM enrollments e 
            JOIN courses c ON c.course_id = e.course_id 
            WHERE e.student_id = ? 
            ORDER BY e.enrolled_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user['user_id']]);
        $student_stats['recent_courses'] = $stmt->fetchAll();

    } catch (Exception $e) {
        $student_stats = [
            'enrolled_courses' => 0,
            'avg_attendance' => 0,
            'total_attendance_records' => 0,
            'total_marks' => 0,
            'avg_marks' => 0,
            'recent_courses' => []
        ];
    }
}
?>

<div class="dashboard-container">
    <!-- Error Alert -->
    <?php if ($error === 'access_denied'): ?>
        <div class="alert alert-error">
            <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            Access denied. You don't have permission to access that page.
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p><?php echo ucfirst($user['role']); ?> Dashboard - <?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="dashboard-actions">
            <?php if ($user['role'] === 'lecturer'): ?>
                <a href="courses.php" class="btn btn-primary">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Manage Courses
                </a>
                <a href="attendance.php" class="btn btn-secondary">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Mark Attendance
                </a>
            <?php else: ?>
                <a href="my-courses.php" class="btn btn-primary">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    My Courses
                </a>
                <a href="my-attendance.php" class="btn btn-secondary">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    My Attendance
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user['role'] === 'lecturer'): ?>
        <!-- Lecturer Dashboard -->
        
        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
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
                    <div class="stat-number"><?php echo $stats['total_students']; ?></div>
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
                    <div class="stat-number"><?php echo $stats['total_attendance_records']; ?></div>
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
                    <div class="stat-number"><?php echo $stats['avg_attendance_rate']; ?>%</div>
                    <div class="stat-label">Avg Attendance</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="courses-grid">
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">COURSES</div>
                    </div>
                    <div class="course-title">Course Management</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Total Courses:</span>
                            <span class="stat-value"><?php echo $stats['total_courses']; ?></span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="courses.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Manage Courses
                        </a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">STUDENTS</div>
                    </div>
                    <div class="course-title">Student Management</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Total Students:</span>
                            <span class="stat-value"><?php echo $stats['total_students']; ?></span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="students.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                            </svg>
                            Manage Students
                        </a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">ATTENDANCE</div>
                    </div>
                    <div class="course-title">Attendance Management</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Records:</span>
                            <span class="stat-value"><?php echo $stats['total_attendance_records']; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Avg Rate:</span>
                            <span class="stat-value"><?php echo $stats['avg_attendance_rate']; ?>%</span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="attendance.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Mark Attendance
                        </a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">MARKS</div>
                    </div>
                    <div class="course-title">Marks Management</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Total Marks:</span>
                            <span class="stat-value"><?php echo $stats['total_marks']; ?></span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="marks.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Manage Marks
                        </a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">REPORTS</div>
                    </div>
                    <div class="course-title">Reports & Analytics</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Available:</span>
                            <span class="stat-value">All Reports</span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="reports.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                            </svg>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <?php if (!empty($stats['recent_courses']) || !empty($stats['recent_attendance'])): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Activity</h2>
            </div>
            <div class="courses-grid">
                <?php if (!empty($stats['recent_courses'])): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">RECENT</div>
                    </div>
                    <div class="course-title">Recent Courses</div>
                    <div class="course-stats">
                        <?php foreach (array_slice($stats['recent_courses'], 0, 3) as $course): ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo htmlspecialchars($course['course_code']); ?>:</span>
                            <span class="stat-value"><?php echo htmlspecialchars($course['course_name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($stats['recent_attendance'])): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">ATTENDANCE</div>
                    </div>
                    <div class="course-title">Recent Attendance</div>
                    <div class="course-stats">
                        <?php foreach (array_slice($stats['recent_attendance'], 0, 3) as $attendance): ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo date('M j', strtotime($attendance['date'])); ?>:</span>
                            <span class="stat-value"><?php echo htmlspecialchars($attendance['student_name']); ?> - <?php echo $attendance['status']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Student Dashboard -->
        
        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $student_stats['enrolled_courses']; ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $student_stats['avg_attendance']; ?>%</div>
                    <div class="stat-label">Avg Attendance</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $student_stats['total_attendance_records']; ?></div>
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
                    <div class="stat-number"><?php echo $student_stats['avg_marks']; ?></div>
                    <div class="stat-label">Avg Marks</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="courses-grid">
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">COURSES</div>
                    </div>
                    <div class="course-title">My Courses</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Enrolled:</span>
                            <span class="stat-value"><?php echo $student_stats['enrolled_courses']; ?></span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="my-courses.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            View Courses
                        </a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">ATTENDANCE</div>
                    </div>
                    <div class="course-title">My Attendance</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Avg Rate:</span>
                            <span class="stat-value"><?php echo $student_stats['avg_attendance']; ?>%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Records:</span>
                            <span class="stat-value"><?php echo $student_stats['total_attendance_records']; ?></span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="my-attendance.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            View Attendance
                        </a>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code">MARKS</div>
                    </div>
                    <div class="course-title">My Marks</div>
                    <div class="course-stats">
                        <div class="stat-item">
                            <span class="stat-label">Avg Score:</span>
                            <span class="stat-value"><?php echo $student_stats['avg_marks']; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total Marks:</span>
                            <span class="stat-value"><?php echo $student_stats['total_marks']; ?></span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <a href="my-marks.php" class="action-btn">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            View Marks
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Courses -->
        <?php if (!empty($student_stats['recent_courses'])): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Courses</h2>
            </div>
            <div class="courses-grid">
                <?php foreach ($student_stats['recent_courses'] as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                        <div class="course-students"><?php echo htmlspecialchars($course['semester']); ?></div>
                    </div>
                    <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                    <div class="course-meta">
                        <div class="course-date">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Enrolled: <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
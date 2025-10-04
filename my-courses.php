<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';

require_student();

$user = current_user();

// Get student's enrolled courses
try {
    $stmt = $pdo->prepare("
        SELECT c.course_id, c.course_name, c.course_code, c.semester, u.name as lecturer_name
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        JOIN users u ON c.lecturer_id = u.user_id
        WHERE e.student_id = ?
        ORDER BY c.course_code
    ");
    $stmt->execute([$user['user_id']]);
    $enrolled_courses = $stmt->fetchAll();
} catch (Exception $e) {
    $enrolled_courses = [];
    $error = 'Error loading courses.';
}

// Get attendance summary for each course
$attendance_summary = [];
foreach ($enrolled_courses as $course) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_classes,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count
            FROM attendance 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$user['user_id'], $course['course_id']]);
        $summary = $stmt->fetch();
        
        $attendance_summary[$course['course_id']] = [
            'total' => $summary['total_classes'],
            'present' => $summary['present_count'],
            'percentage' => $summary['total_classes'] > 0 ? round(($summary['present_count'] / $summary['total_classes']) * 100, 1) : 0
        ];
    } catch (Exception $e) {
        $attendance_summary[$course['course_id']] = ['total' => 0, 'present' => 0, 'percentage' => 0];
    }
}

// Get marks summary for each course
$marks_summary = [];
foreach ($enrolled_courses as $course) {
    try {
        $stmt = $pdo->prepare("
            SELECT exam_type, score 
            FROM marks 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$user['user_id'], $course['course_id']]);
        $marks = $stmt->fetchAll();
        
        $course_marks = [];
        $total_score = 0;
        $count = 0;
        
        foreach ($marks as $mark) {
            $course_marks[$mark['exam_type']] = $mark['score'];
            $total_score += $mark['score'];
            $count++;
        }
        
        $marks_summary[$course['course_id']] = [
            'marks' => $course_marks,
            'average' => $count > 0 ? round($total_score / $count, 2) : null
        ];
    } catch (Exception $e) {
        $marks_summary[$course['course_id']] = ['marks' => [], 'average' => null];
    }
}
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>My Courses</h1>
            <p>View your enrolled courses, track attendance, and monitor your academic progress</p>
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

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($enrolled_courses)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3>No Courses Enrolled</h3>
            <p>You are not enrolled in any courses yet. Please contact your lecturer to be enrolled.</p>
        </div>
    <?php else: ?>
        <!-- Statistics Overview -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($enrolled_courses); ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">
                        <?php 
                        $total_attendance = array_sum(array_column($attendance_summary, 'percentage'));
                        $avg_attendance = count($attendance_summary) > 0 ? round($total_attendance / count($attendance_summary), 1) : 0;
                        echo $avg_attendance . '%';
                        ?>
                    </div>
                    <div class="stat-label">Average Attendance</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">
                        <?php 
                        $averages = array_filter(array_column($marks_summary, 'average'));
                        $overall_avg = !empty($averages) ? round(array_sum($averages) / count($averages), 1) : 0;
                        echo $overall_avg > 0 ? number_format($overall_avg, 1) : 'N/A';
                        ?>
                    </div>
                    <div class="stat-label">Overall Average</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">
                        <?php 
                        $courses_with_marks = count(array_filter($marks_summary, function($m) { return $m['average'] !== null; }));
                        echo $courses_with_marks;
                        ?>
                    </div>
                    <div class="stat-label">Courses with Marks</div>
                </div>
            </div>
        </div>

        <!-- Enrolled Courses -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Enrolled Courses (<?php echo count($enrolled_courses); ?>)</h2>
            </div>
            
            <div class="courses-grid">
                <?php foreach ($enrolled_courses as $course): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                            <div class="course-students">
                                <?php 
                                $att = $attendance_summary[$course['course_id']];
                                echo $att['percentage'] . '% attendance';
                                ?>
                            </div>
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
                        <div class="course-meta">
                            <div class="course-date">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                <?php echo htmlspecialchars($course['lecturer_name']); ?>
                            </div>
                        </div>
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Attendance:</span>
                                <span class="stat-value" style="color: <?php echo $att['percentage'] >= 75 ? '#16a34a' : ($att['percentage'] >= 50 ? '#d97706' : '#dc2626'); ?>;">
                                    <?php echo $att['present']; ?>/<?php echo $att['total']; ?> classes
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Average Marks:</span>
                                <?php $marks = $marks_summary[$course['course_id']]; ?>
                                <span class="stat-value" style="color: <?php echo $marks['average'] !== null ? ($marks['average'] >= 80 ? '#16a34a' : ($marks['average'] >= 60 ? '#d97706' : '#dc2626')) : '#6b7280'; ?>;">
                                    <?php echo $marks['average'] !== null ? number_format($marks['average'], 2) : 'No marks yet'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="course-actions">
                            <a href="my-attendance.php?course_id=<?php echo $course['course_id']; ?>" class="action-btn">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                View Attendance
                            </a>
                            <a href="my-marks.php?course_id=<?php echo $course['course_id']; ?>" class="action-btn">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                View Marks
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

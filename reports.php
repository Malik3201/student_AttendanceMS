<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';

require_lecturer();

$user = current_user();
$course_id = $_GET['course_id'] ?? '';
$report_type = $_GET['report_type'] ?? 'overall';
$month = $_GET['month'] ?? date('Y-m');
$export = $_GET['export'] ?? '';

// Handle PDF export
if ($export === 'pdf' && $course_id) {
    require_once 'includes/pdf_generator.php';
    generateReportPDF($course_id, $report_type, $month, $user, $pdo);
    exit;
}

// Get lecturer's courses for course selection
try {
    $stmt = $pdo->prepare("SELECT course_id, course_name, course_code FROM courses WHERE lecturer_id = ? ORDER BY course_code");
    $stmt->execute([$user['user_id']]);
    $lecturer_courses = $stmt->fetchAll();
} catch (Exception $e) {
    $lecturer_courses = [];
}

// Verify course belongs to lecturer
$selected_course = null;
if ($course_id) {
    foreach ($lecturer_courses as $course) {
        if ($course['course_id'] == $course_id) {
            $selected_course = $course;
            break;
        }
    }
    if (!$selected_course) {
        $course_id = '';
    }
}

// Generate reports for selected course
$attendance_report = [];
$marks_report = [];
$monthly_stats = [];
$overall_stats = [];

if ($course_id) {
    try {
        // Build date filter for monthly reports
        $date_filter = "";
        $date_params = [];
        if ($report_type === 'monthly') {
            $date_filter = "AND DATE_FORMAT(a.date, '%Y-%m') = ?";
            $date_params[] = $month;
        }
        
        // Attendance Report
        $stmt = $pdo->prepare("
            SELECT u.name, u.email,
                   COUNT(a.attendance_id) as total_classes,
                   SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                   ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(a.attendance_id), 0)), 2) as attendance_percentage
            FROM users u
            JOIN enrollments e ON u.user_id = e.student_id
            LEFT JOIN attendance a ON u.user_id = a.student_id AND a.course_id = e.course_id $date_filter
            WHERE e.course_id = ?
            GROUP BY u.user_id, u.name, u.email
            ORDER BY u.name
        ");
        $stmt->execute(array_merge($date_params, [$course_id]));
        $attendance_report = $stmt->fetchAll();
        
        // Marks Report
        $stmt = $pdo->prepare("
            SELECT u.name, u.email,
                   MAX(CASE WHEN m.exam_type = 'quiz' THEN m.score END) as quiz_score,
                   MAX(CASE WHEN m.exam_type = 'midterm' THEN m.score END) as midterm_score,
                   MAX(CASE WHEN m.exam_type = 'final' THEN m.score END) as final_score
            FROM users u
            JOIN enrollments e ON u.user_id = e.student_id
            LEFT JOIN marks m ON u.user_id = m.student_id AND m.course_id = e.course_id
            WHERE e.course_id = ?
            GROUP BY u.user_id, u.name, u.email
            ORDER BY u.name
        ");
        $stmt->execute([$course_id]);
        $marks_report = $stmt->fetchAll();
        
        // Calculate averages for marks report
        foreach ($marks_report as &$student) {
            $scores = array_filter([
                $student['quiz_score'],
                $student['midterm_score'],
                $student['final_score']
            ], function($v) { return $v !== null; });
            
            $student['average'] = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;
        }
        
        // Monthly Statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT a.date) as total_days,
                COUNT(a.attendance_id) as total_records,
                SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_records,
                ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(a.attendance_id), 0)), 2) as overall_attendance_rate
            FROM attendance a
            WHERE a.course_id = ? $date_filter
        ");
        $stmt->execute(array_merge($date_params, [$course_id]));
        $monthly_stats = $stmt->fetch();
        
        // Overall Statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT a.date) as total_days,
                COUNT(a.attendance_id) as total_records,
                SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_records,
                ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(a.attendance_id), 0)), 2) as overall_attendance_rate
            FROM attendance a
            WHERE a.course_id = ?
        ");
        $stmt->execute([$course_id]);
        $overall_stats = $stmt->fetch();
        
    } catch (Exception $e) {
        $attendance_report = [];
        $marks_report = [];
        $monthly_stats = [];
        $overall_stats = [];
    }
}
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Reports & Analytics</h1>
            <p>Generate comprehensive reports for attendance and marks, with monthly analysis and PDF export</p>
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

    <!-- Report Controls -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Report Settings</h2>
        </div>
        <div class="course-form-container">
            <form method="GET" class="course-form">
                <div class="form-group">
                    <label for="course_id">Select Course</label>
                    <select id="course_id" name="course_id" onchange="this.form.submit()">
                        <option value="">Choose a course to generate reports</option>
                        <?php foreach ($lecturer_courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($course_id): ?>
                <div class="form-group">
                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report_type" onchange="this.form.submit()">
                        <option value="overall" <?php echo $report_type === 'overall' ? 'selected' : ''; ?>>Overall Report</option>
                        <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                    </select>
                </div>
                
                <?php if ($report_type === 'monthly'): ?>
                <div class="form-group">
                    <label for="month">Select Month</label>
                    <input type="month" id="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <?php if ($course_id): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn btn-primary">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Export PDF
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($course_id && $selected_course): ?>
        <!-- Course Information -->
        <div class="dashboard-section">
            <div class="course-card">
                <div class="course-header">
                    <div class="course-code"><?php echo htmlspecialchars($selected_course['course_code']); ?></div>
                    <div class="course-students"><?php echo ucfirst($report_type); ?> Report</div>
                </div>
                <div class="course-title"><?php echo htmlspecialchars($selected_course['course_name']); ?></div>
                <div class="course-meta">
                    <div class="course-date">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        <?php if ($report_type === 'monthly'): ?>
                            <?php echo date('F Y', strtotime($month . '-01')); ?>
                        <?php else: ?>
                            All Time
                        <?php endif; ?>
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
                    <div class="stat-number"><?php echo count($attendance_report); ?></div>
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
                    <div class="stat-number"><?php echo $report_type === 'monthly' ? ($monthly_stats['total_days'] ?? 0) : ($overall_stats['total_days'] ?? 0); ?></div>
                    <div class="stat-label">Class Days</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $report_type === 'monthly' ? ($monthly_stats['overall_attendance_rate'] ?? 0) : ($overall_stats['overall_attendance_rate'] ?? 0); ?>%</div>
                    <div class="stat-label">Overall Attendance</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $report_type === 'monthly' ? ($monthly_stats['present_records'] ?? 0) : ($overall_stats['present_records'] ?? 0); ?></div>
                    <div class="stat-label">Present Records</div>
                </div>
            </div>
        </div>

        <!-- Attendance Report -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Attendance Report</h2>
            </div>
            
            <?php if (empty($attendance_report)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h3>No Attendance Data</h3>
                    <p>No attendance records found for the selected period.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Present/Total</th>
                                <th>Attendance %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_report as $student): ?>
                                <?php $percentage = $student['attendance_percentage'] ?? 0; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['present_count']; ?>/<?php echo $student['total_classes']; ?></td>
                                    <td>
                                        <span class="attendance-percentage" style="color: <?php echo $percentage >= 75 ? '#16a34a' : ($percentage >= 50 ? '#d97706' : '#dc2626'); ?>;">
                                            <strong><?php echo $percentage; ?>%</strong>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($percentage >= 75): ?>
                                            <span class="status-badge status-good">Good</span>
                                        <?php elseif ($percentage >= 50): ?>
                                            <span class="status-badge status-warning">Warning</span>
                                        <?php else: ?>
                                            <span class="status-badge status-poor">Poor</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Attendance Summary -->
                <?php
                $total_students = count($attendance_report);
                $good_attendance = 0;
                $warning_attendance = 0;
                $poor_attendance = 0;
                
                foreach ($attendance_report as $student) {
                    $percentage = $student['attendance_percentage'] ?? 0;
                    if ($percentage >= 75) $good_attendance++;
                    elseif ($percentage >= 50) $warning_attendance++;
                    else $poor_attendance++;
                }
                ?>
                <div class="courses-grid" style="margin-top: 24px;">
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code">GOOD</div>
                            <div class="course-students">≥75%</div>
                        </div>
                        <div class="course-title">Good Attendance</div>
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Students:</span>
                                <span class="stat-value" style="color: #16a34a;"><?php echo $good_attendance; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code">WARNING</div>
                            <div class="course-students">50-74%</div>
                        </div>
                        <div class="course-title">Warning Attendance</div>
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Students:</span>
                                <span class="stat-value" style="color: #d97706;"><?php echo $warning_attendance; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code">POOR</div>
                            <div class="course-students"><50%</div>
                        </div>
                        <div class="course-title">Poor Attendance</div>
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Students:</span>
                                <span class="stat-value" style="color: #dc2626;"><?php echo $poor_attendance; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Marks Report -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Marks Report</h2>
            </div>
            
            <?php if (empty($marks_report)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3>No Marks Data</h3>
                    <p>No marks records found for this course.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Quiz</th>
                                <th>Midterm</th>
                                <th>Final</th>
                                <th>Average</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marks_report as $student): ?>
                                <?php
                                $average = $student['average'];
                                $grade = null;
                                if ($average !== null) {
                                    if ($average >= 90) $grade = 'A+';
                                    elseif ($average >= 85) $grade = 'A';
                                    elseif ($average >= 80) $grade = 'A-';
                                    elseif ($average >= 75) $grade = 'B+';
                                    elseif ($average >= 70) $grade = 'B';
                                    elseif ($average >= 65) $grade = 'B-';
                                    elseif ($average >= 60) $grade = 'C+';
                                    elseif ($average >= 55) $grade = 'C';
                                    elseif ($average >= 50) $grade = 'C-';
                                    else $grade = 'F';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['quiz_score'] !== null ? number_format($student['quiz_score'], 2) : '-'; ?></td>
                                    <td><?php echo $student['midterm_score'] !== null ? number_format($student['midterm_score'], 2) : '-'; ?></td>
                                    <td><?php echo $student['final_score'] !== null ? number_format($student['final_score'], 2) : '-'; ?></td>
                                    <td>
                                        <?php if ($average !== null): ?>
                                            <strong><?php echo number_format($average, 2); ?></strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($grade !== null): ?>
                                            <span class="grade-badge" style="color: <?php echo $average >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                                <strong><?php echo $grade; ?></strong>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Marks Summary -->
                <?php
                $students_with_marks = array_filter($marks_report, function($s) { return $s['average'] !== null; });
                if (!empty($students_with_marks)) {
                    $class_average = array_sum(array_column($students_with_marks, 'average')) / count($students_with_marks);
                    $passing_students = count(array_filter($students_with_marks, function($s) { return $s['average'] >= 60; }));
                    $failing_students = count($students_with_marks) - $passing_students;
                ?>
                    <div class="courses-grid" style="margin-top: 24px;">
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-code">AVERAGE</div>
                                <div class="course-students">Class</div>
                            </div>
                            <div class="course-title">Class Average</div>
                            <div class="course-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Score:</span>
                                    <span class="stat-value"><?php echo number_format($class_average, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-code">PASSING</div>
                                <div class="course-students">≥60</div>
                            </div>
                            <div class="course-title">Passing Students</div>
                            <div class="course-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Students:</span>
                                    <span class="stat-value" style="color: #16a34a;"><?php echo $passing_students; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-code">FAILING</div>
                                <div class="course-students"><60</div>
                            </div>
                            <div class="course-title">Failing Students</div>
                            <div class="course-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Students:</span>
                                    <span class="stat-value" style="color: #dc2626;"><?php echo $failing_students; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

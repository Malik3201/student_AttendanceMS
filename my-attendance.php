<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';

require_student();

$user = current_user();
$course_id = $_GET['course_id'] ?? '';

// Get student's enrolled courses for course selection
try {
    $stmt = $pdo->prepare("
        SELECT c.course_id, c.course_name, c.course_code
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.student_id = ?
        ORDER BY c.course_code
    ");
    $stmt->execute([$user['user_id']]);
    $enrolled_courses = $stmt->fetchAll();
} catch (Exception $e) {
    $enrolled_courses = [];
}

// Verify student is enrolled in selected course
$selected_course = null;
if ($course_id) {
    foreach ($enrolled_courses as $course) {
        if ($course['course_id'] == $course_id) {
            $selected_course = $course;
            break;
        }
    }
    if (!$selected_course) {
        $course_id = '';
    }
}

// Get attendance records for selected course
$attendance_records = [];
$attendance_summary = [];
if ($course_id) {
    try {
        // Get all attendance records
        $stmt = $pdo->prepare("
            SELECT date, status
            FROM attendance 
            WHERE student_id = ? AND course_id = ?
            ORDER BY date DESC
        ");
        $stmt->execute([$user['user_id'], $course_id]);
        $attendance_records = $stmt->fetchAll();
        
        // Calculate summary
        $total = count($attendance_records);
        $present = 0;
        foreach ($attendance_records as $record) {
            if ($record['status'] === 'Present') {
                $present++;
            }
        }
        
        $attendance_summary = [
            'total' => $total,
            'present' => $present,
            'absent' => $total - $present,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
        ];
    } catch (Exception $e) {
        $attendance_records = [];
        $attendance_summary = ['total' => 0, 'present' => 0, 'absent' => 0, 'percentage' => 0];
    }
}
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>My Attendance</h1>
            <p>Track your attendance records, view detailed reports, and monitor your attendance percentage</p>
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

    <!-- Course Selection -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Select Course</h2>
        </div>
        <div class="course-form-container">
            <form method="GET" class="course-form">
                <div class="form-group">
                    <label for="course_id">Choose a course to view attendance</label>
                    <select id="course_id" name="course_id" onchange="this.form.submit()">
                        <option value="">Select a course</option>
                        <?php foreach ($enrolled_courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($course_id && $selected_course): ?>
        <!-- Course Information -->
        <div class="dashboard-section">
            <div class="course-card">
                <div class="course-header">
                    <div class="course-code"><?php echo htmlspecialchars($selected_course['course_code']); ?></div>
                    <div class="course-students"><?php echo $attendance_summary['percentage']; ?>% attendance</div>
                </div>
                <div class="course-title"><?php echo htmlspecialchars($selected_course['course_name']); ?></div>
                <div class="course-meta">
                    <div class="course-date">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                        <?php echo $attendance_summary['total']; ?> total classes
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $attendance_summary['total']; ?></div>
                    <div class="stat-label">Total Classes</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: #16a34a;"><?php echo $attendance_summary['present']; ?></div>
                    <div class="stat-label">Present</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: #dc2626;"><?php echo $attendance_summary['absent']; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number" style="color: <?php echo $attendance_summary['percentage'] >= 75 ? '#16a34a' : ($attendance_summary['percentage'] >= 50 ? '#d97706' : '#dc2626'); ?>;">
                        <?php echo $attendance_summary['percentage']; ?>%
                    </div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>
        </div>

        <!-- Attendance Warning -->
        <?php if ($attendance_summary['percentage'] < 75): ?>
            <div class="alert alert-warning">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <strong>Warning:</strong> Your attendance is below 75%. Please attend classes regularly to maintain good standing.
            </div>
        <?php endif; ?>

        <!-- Detailed Attendance Records -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Attendance Records</h2>
            </div>
            
            <?php if (empty($attendance_records)): ?>
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
                                <th>Day</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo date('l', strtotime($record['date'])); ?></td>
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
                
                <!-- Monthly Breakdown -->
                <?php
                $monthly_data = [];
                foreach ($attendance_records as $record) {
                    $month = date('Y-m', strtotime($record['date']));
                    if (!isset($monthly_data[$month])) {
                        $monthly_data[$month] = ['total' => 0, 'present' => 0];
                    }
                    $monthly_data[$month]['total']++;
                    if ($record['status'] === 'Present') {
                        $monthly_data[$month]['present']++;
                    }
                }
                
                if (!empty($monthly_data)):
                ?>
                    <div class="section-header" style="margin-top: 32px;">
                        <h2>Monthly Breakdown</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Present/Total</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $month => $data): ?>
                                    <?php $percentage = round(($data['present'] / $data['total']) * 100, 1); ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month . '-01')); ?></td>
                                        <td><?php echo $data['present']; ?>/<?php echo $data['total']; ?></td>
                                        <td>
                                            <span class="attendance-percentage" style="color: <?php echo $percentage >= 75 ? '#16a34a' : ($percentage >= 50 ? '#d97706' : '#dc2626'); ?>;">
                                                <strong><?php echo $percentage; ?>%</strong>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

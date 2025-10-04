<?php
/**
 * PDF Report Generator for Student Attendance Management System
 * Generates beautiful, professional PDF reports for attendance and marks
 */

function generateReportPDF($course_id, $report_type, $month, $user, $pdo) {
    // Get course information
    $stmt = $pdo->prepare("SELECT course_name, course_code, semester FROM courses WHERE course_id = ? AND lecturer_id = ?");
    $stmt->execute([$course_id, $user['user_id']]);
    $course = $stmt->fetch();
    
    if (!$course) {
        die('Course not found or access denied.');
    }
    
    // Get attendance and marks data
    $attendance_report = [];
    $marks_report = [];
    $monthly_stats = [];
    $overall_stats = [];
    
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
        die('Error generating report data: ' . $e->getMessage());
    }
    
    // Generate PDF using HTML to PDF conversion
    generateHTMLReport($course, $attendance_report, $marks_report, $monthly_stats, $overall_stats, $report_type, $month, $user);
}

function generateHTMLReport($course, $attendance_report, $marks_report, $monthly_stats, $overall_stats, $report_type, $month, $user) {
    $report_date = $report_type === 'monthly' ? date('F Y', strtotime($month . '-01')) : 'All Time';
    $current_date = date('F j, Y');
    
    // Calculate summary statistics
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
    
    $students_with_marks = array_filter($marks_report, function($s) { return $s['average'] !== null; });
    $class_average = 0;
    $passing_students = 0;
    $failing_students = 0;
    
    if (!empty($students_with_marks)) {
        $class_average = array_sum(array_column($students_with_marks, 'average')) / count($students_with_marks);
        $passing_students = count(array_filter($students_with_marks, function($s) { return $s['average'] >= 60; }));
        $failing_students = count($students_with_marks) - $passing_students;
    }
    
    $overall_attendance = $report_type === 'monthly' ? ($monthly_stats['overall_attendance_rate'] ?? 0) : ($overall_stats['overall_attendance_rate'] ?? 0);
    $total_days = $report_type === 'monthly' ? ($monthly_stats['total_days'] ?? 0) : ($overall_stats['total_days'] ?? 0);
    $present_records = $report_type === 'monthly' ? ($monthly_stats['present_records'] ?? 0) : ($overall_stats['present_records'] ?? 0);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $course['course_code'] . '_' . ucfirst($report_type) . '_Report_' . date('Y-m-d') . '.pdf"');
    
    // Generate HTML content for PDF
    $html = generatePDFHTML($course, $attendance_report, $marks_report, $report_date, $current_date, $user, $total_students, $overall_attendance, $total_days, $present_records, $good_attendance, $warning_attendance, $poor_attendance, $class_average, $passing_students, $failing_students);
    
    // For now, we'll output HTML that can be printed to PDF
    // In a production environment, you would use a library like TCPDF, FPDF, or wkhtmltopdf
    echo $html;
}

function generatePDFHTML($course, $attendance_report, $marks_report, $report_date, $current_date, $user, $total_students, $overall_attendance, $total_days, $present_records, $good_attendance, $warning_attendance, $poor_attendance, $class_average, $passing_students, $failing_students) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Course Report - <?php echo htmlspecialchars($course['course_code']); ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background: #fff;
                font-size: 12px;
            }
            
            .pdf-container {
                max-width: 210mm;
                margin: 0 auto;
                padding: 20mm;
                background: white;
            }
            
            .pdf-header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #16a34a;
                padding-bottom: 20px;
            }
            
            .pdf-title {
                font-size: 28px;
                font-weight: bold;
                color: #16a34a;
                margin-bottom: 10px;
            }
            
            .pdf-subtitle {
                font-size: 18px;
                color: #666;
                margin-bottom: 5px;
            }
            
            .pdf-course-info {
                font-size: 16px;
                color: #333;
                font-weight: 600;
            }
            
            .pdf-meta {
                display: flex;
                justify-content: space-between;
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #16a34a;
            }
            
            .pdf-meta-item {
                text-align: center;
            }
            
            .pdf-meta-label {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 5px;
            }
            
            .pdf-meta-value {
                font-size: 16px;
                font-weight: bold;
                color: #16a34a;
            }
            
            .pdf-section {
                margin: 25px 0;
                page-break-inside: avoid;
            }
            
            .pdf-section-title {
                font-size: 18px;
                font-weight: bold;
                color: #16a34a;
                margin-bottom: 15px;
                padding-bottom: 8px;
                border-bottom: 2px solid #e5e7eb;
            }
            
            .pdf-stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin: 20px 0;
            }
            
            .pdf-stat-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                border: 1px solid #e5e7eb;
            }
            
            .pdf-stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #16a34a;
                margin-bottom: 5px;
            }
            
            .pdf-stat-label {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .pdf-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                font-size: 11px;
            }
            
            .pdf-table th {
                background: #16a34a;
                color: white;
                padding: 12px 8px;
                text-align: left;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .pdf-table td {
                padding: 10px 8px;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .pdf-table tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            .pdf-table tr:hover {
                background: #e5f7ed;
            }
            
            .attendance-good {
                color: #16a34a;
                font-weight: bold;
            }
            
            .attendance-warning {
                color: #d97706;
                font-weight: bold;
            }
            
            .attendance-poor {
                color: #dc2626;
                font-weight: bold;
            }
            
            .grade-pass {
                color: #16a34a;
                font-weight: bold;
            }
            
            .grade-fail {
                color: #dc2626;
                font-weight: bold;
            }
            
            .pdf-summary-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin: 20px 0;
            }
            
            .pdf-summary-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                border: 1px solid #e5e7eb;
            }
            
            .pdf-summary-title {
                font-size: 12px;
                font-weight: bold;
                color: #333;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .pdf-summary-value {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .pdf-summary-subtitle {
                font-size: 10px;
                color: #666;
            }
            
            .pdf-footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #e5e7eb;
                text-align: center;
                color: #666;
                font-size: 10px;
            }
            
            .pdf-page-break {
                page-break-before: always;
            }
            
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                
                .pdf-container {
                    margin: 0;
                    padding: 15mm;
                }
            }
        </style>
    </head>
    <body>
        <div class="pdf-container">
            <!-- Header -->
            <div class="pdf-header">
                <div class="pdf-title">Course Report</div>
                <div class="pdf-subtitle">Student Attendance Management System</div>
                <div class="pdf-course-info">
                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                </div>
            </div>
            
            <!-- Report Meta Information -->
            <div class="pdf-meta">
                <div class="pdf-meta-item">
                    <div class="pdf-meta-label">Report Type</div>
                    <div class="pdf-meta-value"><?php echo ucfirst($report_type); ?> Report</div>
                </div>
                <div class="pdf-meta-item">
                    <div class="pdf-meta-label">Period</div>
                    <div class="pdf-meta-value"><?php echo $report_date; ?></div>
                </div>
                <div class="pdf-meta-item">
                    <div class="pdf-meta-label">Generated</div>
                    <div class="pdf-meta-value"><?php echo $current_date; ?></div>
                </div>
                <div class="pdf-meta-item">
                    <div class="pdf-meta-label">Lecturer</div>
                    <div class="pdf-meta-value"><?php echo htmlspecialchars($user['name']); ?></div>
                </div>
            </div>
            
            <!-- Statistics Overview -->
            <div class="pdf-section">
                <div class="pdf-section-title">Statistics Overview</div>
                <div class="pdf-stats-grid">
                    <div class="pdf-stat-card">
                        <div class="pdf-stat-number"><?php echo $total_students; ?></div>
                        <div class="pdf-stat-label">Total Students</div>
                    </div>
                    <div class="pdf-stat-card">
                        <div class="pdf-stat-number"><?php echo $total_days; ?></div>
                        <div class="pdf-stat-label">Class Days</div>
                    </div>
                    <div class="pdf-stat-card">
                        <div class="pdf-stat-number"><?php echo $overall_attendance; ?>%</div>
                        <div class="pdf-stat-label">Overall Attendance</div>
                    </div>
                    <div class="pdf-stat-card">
                        <div class="pdf-stat-number"><?php echo $present_records; ?></div>
                        <div class="pdf-stat-label">Present Records</div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Report -->
            <div class="pdf-section">
                <div class="pdf-section-title">Attendance Report</div>
                <?php if (!empty($attendance_report)): ?>
                    <table class="pdf-table">
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
                                <?php 
                                $percentage = $student['attendance_percentage'] ?? 0;
                                $status_class = $percentage >= 75 ? 'attendance-good' : ($percentage >= 50 ? 'attendance-warning' : 'attendance-poor');
                                $status_text = $percentage >= 75 ? 'Good' : ($percentage >= 50 ? 'Warning' : 'Poor');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['present_count']; ?>/<?php echo $student['total_classes']; ?></td>
                                    <td class="<?php echo $status_class; ?>"><?php echo $percentage; ?>%</td>
                                    <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Attendance Summary -->
                    <div class="pdf-summary-grid">
                        <div class="pdf-summary-card">
                            <div class="pdf-summary-title">Good Attendance</div>
                            <div class="pdf-summary-value attendance-good"><?php echo $good_attendance; ?></div>
                            <div class="pdf-summary-subtitle">≥75%</div>
                        </div>
                        <div class="pdf-summary-card">
                            <div class="pdf-summary-title">Warning Attendance</div>
                            <div class="pdf-summary-value attendance-warning"><?php echo $warning_attendance; ?></div>
                            <div class="pdf-summary-subtitle">50-74%</div>
                        </div>
                        <div class="pdf-summary-card">
                            <div class="pdf-summary-title">Poor Attendance</div>
                            <div class="pdf-summary-value attendance-poor"><?php echo $poor_attendance; ?></div>
                            <div class="pdf-summary-subtitle"><50%</div>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic; padding: 20px;">No attendance data available for the selected period.</p>
                <?php endif; ?>
            </div>
            
            <!-- Marks Report -->
            <div class="pdf-section pdf-page-break">
                <div class="pdf-section-title">Marks Report</div>
                <?php if (!empty($marks_report)): ?>
                    <table class="pdf-table">
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
                                $grade_class = ($average !== null && $average >= 60) ? 'grade-pass' : 'grade-fail';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['quiz_score'] !== null ? number_format($student['quiz_score'], 2) : '-'; ?></td>
                                    <td><?php echo $student['midterm_score'] !== null ? number_format($student['midterm_score'], 2) : '-'; ?></td>
                                    <td><?php echo $student['final_score'] !== null ? number_format($student['final_score'], 2) : '-'; ?></td>
                                    <td><?php echo $average !== null ? number_format($average, 2) : '-'; ?></td>
                                    <td class="<?php echo $grade_class; ?>"><?php echo $grade ?? '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Marks Summary -->
                    <?php if (!empty($students_with_marks)): ?>
                        <div class="pdf-summary-grid">
                            <div class="pdf-summary-card">
                                <div class="pdf-summary-title">Class Average</div>
                                <div class="pdf-summary-value"><?php echo number_format($class_average, 2); ?></div>
                                <div class="pdf-summary-subtitle">Overall Score</div>
                            </div>
                            <div class="pdf-summary-card">
                                <div class="pdf-summary-title">Passing Students</div>
                                <div class="pdf-summary-value grade-pass"><?php echo $passing_students; ?></div>
                                <div class="pdf-summary-subtitle">≥60%</div>
                            </div>
                            <div class="pdf-summary-card">
                                <div class="pdf-summary-title">Failing Students</div>
                                <div class="pdf-summary-value grade-fail"><?php echo $failing_students; ?></div>
                                <div class="pdf-summary-subtitle"><60%</div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic; padding: 20px;">No marks data available for this course.</p>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="pdf-footer">
                <p>This report was generated on <?php echo $current_date; ?> by the Student Attendance Management System</p>
                <p>Course: <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?> | Lecturer: <?php echo htmlspecialchars($user['name']); ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>

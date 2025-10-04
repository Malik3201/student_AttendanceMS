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

// Get marks for selected course
$marks_data = [];
if ($course_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT exam_type, score
            FROM marks 
            WHERE student_id = ? AND course_id = ?
            ORDER BY 
                CASE exam_type 
                    WHEN 'quiz' THEN 1 
                    WHEN 'midterm' THEN 2 
                    WHEN 'final' THEN 3 
                END
        ");
        $stmt->execute([$user['user_id'], $course_id]);
        $marks_records = $stmt->fetchAll();
        
        foreach ($marks_records as $record) {
            $marks_data[$record['exam_type']] = $record['score'];
        }
    } catch (Exception $e) {
        $marks_data = [];
    }
}

// Calculate average and grade
$average = null;
$grade = null;
if (!empty($marks_data)) {
    $total_score = array_sum($marks_data);
    $count = count($marks_data);
    $average = $total_score / $count;
    
    // Calculate grade based on average
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

// Get marks for all courses (for overall summary)
$all_marks = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, m.exam_type, m.score
        FROM marks m
        JOIN courses c ON c.course_id = m.course_id
        JOIN enrollments e ON e.course_id = c.course_id
        WHERE m.student_id = ? AND e.student_id = ?
        ORDER BY c.course_code, 
            CASE m.exam_type 
                WHEN 'quiz' THEN 1 
                WHEN 'midterm' THEN 2 
                WHEN 'final' THEN 3 
            END
    ");
    $stmt->execute([$user['user_id'], $user['user_id']]);
    $all_marks_records = $stmt->fetchAll();
    
    foreach ($all_marks_records as $record) {
        $all_marks[$record['course_code']][$record['exam_type']] = $record['score'];
        $all_marks[$record['course_code']]['course_name'] = $record['course_name'];
    }
} catch (Exception $e) {
    $all_marks = [];
}
?>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>My Marks</h1>
            <p>View your exam scores, track your academic performance, and monitor your overall progress</p>
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
                    <label for="course_id">Choose a course to view marks</label>
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
                    <div class="course-students">
                        <?php if ($average !== null): ?>
                            <?php echo number_format($average, 1); ?> average
                        <?php else: ?>
                            No marks yet
                        <?php endif; ?>
                    </div>
                </div>
                <div class="course-title"><?php echo htmlspecialchars($selected_course['course_name']); ?></div>
                <div class="course-meta">
                    <div class="course-date">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php echo count($marks_data); ?> exams completed
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Marks Detail -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Exam Scores</h2>
            </div>
            
            <?php if (empty($marks_data)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3>No Marks Recorded</h3>
                    <p>No marks have been recorded for this course yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Exam Type</th>
                                <th>Score</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (['quiz', 'midterm', 'final'] as $exam_type): ?>
                                <?php if (isset($marks_data[$exam_type])): ?>
                                    <?php 
                                    $score = $marks_data[$exam_type];
                                    $exam_grade = '';
                                    if ($score >= 90) $exam_grade = 'A+';
                                    elseif ($score >= 85) $exam_grade = 'A';
                                    elseif ($score >= 80) $exam_grade = 'A-';
                                    elseif ($score >= 75) $exam_grade = 'B+';
                                    elseif ($score >= 70) $exam_grade = 'B';
                                    elseif ($score >= 65) $exam_grade = 'B-';
                                    elseif ($score >= 60) $exam_grade = 'C+';
                                    elseif ($score >= 55) $exam_grade = 'C';
                                    elseif ($score >= 50) $exam_grade = 'C-';
                                    else $exam_grade = 'F';
                                    ?>
                                    <tr>
                                        <td><?php echo ucfirst($exam_type); ?></td>
                                        <td><strong><?php echo number_format($score, 2); ?></strong></td>
                                        <td>
                                            <span class="grade-badge" style="color: <?php echo $score >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                                <strong><?php echo $exam_grade; ?></strong>
                                            </span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td><?php echo ucfirst($exam_type); ?></td>
                                        <td>-</td>
                                        <td>-</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($average !== null): ?>
                    <div class="courses-grid" style="margin-top: 24px;">
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-code">AVERAGE</div>
                                <div class="course-students">Course</div>
                            </div>
                            <div class="course-title">Course Average</div>
                            <div class="course-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Score:</span>
                                    <span class="stat-value" style="color: <?php echo $average >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                        <?php echo number_format($average, 2); ?>
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Grade:</span>
                                    <span class="stat-value" style="color: <?php echo $average >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                        <?php echo $grade; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Overall Summary -->
    <?php if (!empty($all_marks)): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2>All Courses Summary</h2>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Quiz</th>
                            <th>Midterm</th>
                            <th>Final</th>
                            <th>Average</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_marks as $course_code => $course_marks): ?>
                            <?php
                            $quiz = $course_marks['quiz'] ?? null;
                            $midterm = $course_marks['midterm'] ?? null;
                            $final = $course_marks['final'] ?? null;
                            
                            $scores = array_filter([$quiz, $midterm, $final], function($v) { return $v !== null; });
                            $course_avg = !empty($scores) ? array_sum($scores) / count($scores) : null;
                            
                            $course_grade = null;
                            if ($course_avg !== null) {
                                if ($course_avg >= 90) $course_grade = 'A+';
                                elseif ($course_avg >= 85) $course_grade = 'A';
                                elseif ($course_avg >= 80) $course_grade = 'A-';
                                elseif ($course_avg >= 75) $course_grade = 'B+';
                                elseif ($course_avg >= 70) $course_grade = 'B';
                                elseif ($course_avg >= 65) $course_grade = 'B-';
                                elseif ($course_avg >= 60) $course_grade = 'C+';
                                elseif ($course_avg >= 55) $course_grade = 'C';
                                elseif ($course_avg >= 50) $course_grade = 'C-';
                                else $course_grade = 'F';
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($course_code); ?></strong><br>
                                    <small><?php echo htmlspecialchars($course_marks['course_name']); ?></small>
                                </td>
                                <td><?php echo $quiz !== null ? number_format($quiz, 2) : '-'; ?></td>
                                <td><?php echo $midterm !== null ? number_format($midterm, 2) : '-'; ?></td>
                                <td><?php echo $final !== null ? number_format($final, 2) : '-'; ?></td>
                                <td>
                                    <?php if ($course_avg !== null): ?>
                                        <strong><?php echo number_format($course_avg, 2); ?></strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($course_grade !== null): ?>
                                        <span class="grade-badge" style="color: <?php echo $course_avg >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                            <strong><?php echo $course_grade; ?></strong>
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
            
            <!-- Overall GPA Calculation -->
            <?php
            $all_averages = [];
            foreach ($all_marks as $course_marks) {
                $scores = array_filter([
                    $course_marks['quiz'] ?? null,
                    $course_marks['midterm'] ?? null,
                    $course_marks['final'] ?? null
                ], function($v) { return $v !== null; });
                
                if (!empty($scores)) {
                    $all_averages[] = array_sum($scores) / count($scores);
                }
            }
            
            if (!empty($all_averages)):
                $overall_avg = array_sum($all_averages) / count($all_averages);
                if ($overall_avg >= 90) $overall_grade = 'A+';
                elseif ($overall_avg >= 85) $overall_grade = 'A';
                elseif ($overall_avg >= 80) $overall_grade = 'A-';
                elseif ($overall_avg >= 75) $overall_grade = 'B+';
                elseif ($overall_avg >= 70) $overall_grade = 'B';
                elseif ($overall_avg >= 65) $overall_grade = 'B-';
                elseif ($overall_avg >= 60) $overall_grade = 'C+';
                elseif ($overall_avg >= 55) $overall_grade = 'C';
                elseif ($overall_avg >= 50) $overall_grade = 'C-';
                else $overall_grade = 'F';
            ?>
                <div class="courses-grid" style="margin-top: 24px;">
                    <div class="course-card">
                        <div class="course-header">
                            <div class="course-code">OVERALL</div>
                            <div class="course-students">GPA</div>
                        </div>
                        <div class="course-title">Overall Average</div>
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Score:</span>
                                <span class="stat-value" style="color: <?php echo $overall_avg >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                    <?php echo number_format($overall_avg, 2); ?>
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Grade:</span>
                                <span class="stat-value" style="color: <?php echo $overall_avg >= 60 ? '#16a34a' : '#dc2626'; ?>;">
                                    <?php echo $overall_grade; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

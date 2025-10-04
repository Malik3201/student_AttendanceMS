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

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_marks') {
    $exam_type = $_POST['exam_type'] ?? '';
    $marks_data = $_POST['marks'] ?? [];
    
    if (empty($exam_type)) {
        $error = 'Please select an exam type.';
    } elseif (!in_array($exam_type, ['quiz', 'midterm', 'final'])) {
        $error = 'Invalid exam type.';
    } elseif (empty($marks_data)) {
        $error = 'No students to enter marks for.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update or insert marks
            $stmt = $pdo->prepare("
                INSERT INTO marks (student_id, course_id, exam_type, score) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE score = VALUES(score), updated_at = CURRENT_TIMESTAMP
            ");
            
            foreach ($marks_data as $student_id => $score) {
                if (is_numeric($score) && $score >= 0) {
                    $stmt->execute([$student_id, $course_id, $exam_type, $score]);
                }
            }
            
            $pdo->commit();
            $success = 'Marks saved successfully for ' . ucfirst($exam_type) . '!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error saving marks. Please try again.';
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

// Get existing marks for all exam types
$existing_marks = [];
if ($course_id) {
    try {
        $stmt = $pdo->prepare("SELECT student_id, exam_type, score FROM marks WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $marks_records = $stmt->fetchAll();
        
        foreach ($marks_records as $record) {
            $existing_marks[$record['student_id']][$record['exam_type']] = $record['score'];
        }
    } catch (Exception $e) {
        $existing_marks = [];
    }
}
?>

<h1>Manage Marks</h1>

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
        <!-- Enter Marks Form -->
        <div class="card">
            <h3>Enter/Update Marks</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_marks">
                
                <div class="form-group">
                    <label for="exam_type">Exam Type:</label>
                    <select id="exam_type" name="exam_type" required>
                        <option value="">Select exam type</option>
                        <option value="quiz">Quiz</option>
                        <option value="midterm">Midterm</option>
                        <option value="final">Final</option>
                    </select>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolled_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td>
                                    <input type="number" 
                                           name="marks[<?php echo $student['user_id']; ?>]" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           placeholder="0.00"
                                           style="width: 100px;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" class="btn btn-success">Save Marks</button>
            </form>
        </div>

        <!-- Current Marks Overview -->
        <div class="card">
            <h3>Current Marks Overview</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Quiz</th>
                        <th>Midterm</th>
                        <th>Final</th>
                        <th>Average</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrolled_students as $student): ?>
                        <?php
                        $quiz = $existing_marks[$student['user_id']]['quiz'] ?? null;
                        $midterm = $existing_marks[$student['user_id']]['midterm'] ?? null;
                        $final = $existing_marks[$student['user_id']]['final'] ?? null;
                        
                        $scores = array_filter([$quiz, $midterm, $final], function($v) { return $v !== null; });
                        $average = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo $quiz !== null ? number_format($quiz, 2) : '-'; ?></td>
                            <td><?php echo $midterm !== null ? number_format($midterm, 2) : '-'; ?></td>
                            <td><?php echo $final !== null ? number_format($final, 2) : '-'; ?></td>
                            <td><strong><?php echo $average !== null ? number_format($average, 2) : '-'; ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
// Auto-populate marks when exam type is selected
document.getElementById('exam_type').addEventListener('change', function() {
    const examType = this.value;
    const existingMarks = <?php echo json_encode($existing_marks); ?>;
    
    // Clear all inputs first
    document.querySelectorAll('input[name^="marks["]').forEach(input => {
        input.value = '';
    });
    
    // Populate existing marks for selected exam type
    if (examType && existingMarks) {
        Object.keys(existingMarks).forEach(studentId => {
            if (existingMarks[studentId][examType] !== undefined) {
                const input = document.querySelector(`input[name="marks[${studentId}]"]`);
                if (input) {
                    input.value = existingMarks[studentId][examType];
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
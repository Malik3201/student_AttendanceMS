<?php
require_once '../includes/guard.php';
require_once '../includes/db.php';

require_role(['lecturer']);

$user = current_user();
$course_id = $_POST['course_id'] ?? '';
$assessment = $_POST['assessment'] ?? '';
$total = $_POST['total'] ?? 0;
$obtained = $_POST['obtained'] ?? [];

if (!$course_id || !$assessment || $total <= 0) {
    header('Location: ../marks.php');
    exit;
}

// Verify course belongs to current lecturer
$stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$course_id, $user['id']]);
if (!$stmt->fetch()) {
    header('Location: ../dashboard.php?denied=1');
    exit;
}

// Process each student's marks
foreach ($obtained as $student_id => $obtained_marks) {
    $obtained_marks = (int)$obtained_marks;
    if ($obtained_marks >= 0 && $obtained_marks <= $total) {
        $stmt = $pdo->prepare("
            INSERT INTO marks(course_id, student_id, assessment, total, obtained)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE total = VALUES(total), obtained = VALUES(obtained)
        ");
        $stmt->execute([$course_id, $student_id, $assessment, $total, $obtained_marks]);
    }
}

header('Location: ../marks.php?course=' . $course_id . '&saved=1');
exit;

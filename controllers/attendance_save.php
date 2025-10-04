<?php
require_once '../includes/guard.php';
require_once '../includes/db.php';

require_role(['lecturer']);

$user = current_user();
$course_id = $_POST['course_id'] ?? '';
$att_date = $_POST['att_date'] ?? '';
$statuses = $_POST['status'] ?? [];

if (!$course_id || !$att_date) {
    header('Location: ../attendance.php');
    exit;
}

// Verify course belongs to current lecturer
$stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$course_id, $user['id']]);
if (!$stmt->fetch()) {
    header('Location: ../dashboard.php?denied=1');
    exit;
}

// Process each student's attendance
foreach ($statuses as $student_id => $status) {
    if (in_array($status, ['Present', 'Absent', 'Late'])) {
        $stmt = $pdo->prepare("
            INSERT INTO attendance(course_id, student_id, att_date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmt->execute([$course_id, $student_id, $att_date, $status]);
    }
}

header('Location: ../attendance.php?course=' . $course_id . '&att_date=' . $att_date . '&saved=1');
exit;
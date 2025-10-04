<?php
require_once 'includes/db.php';

echo "<h2>Fix Lecturer Records</h2>";

// Find lecturer users without lecturer records
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email 
    FROM users u 
    WHERE u.role = 'lecturer' 
    AND u.id NOT IN (SELECT id FROM lecturers)
");
$stmt->execute();
$missing_lecturers = $stmt->fetchAll();

if (empty($missing_lecturers)) {
    echo "<p>✅ All lecturer users have corresponding lecturer records.</p>";
} else {
    echo "<p>Found " . count($missing_lecturers) . " lecturer users without lecturer records:</p>";
    
    foreach ($missing_lecturers as $lecturer) {
        echo "<p>Fixing: " . htmlspecialchars($lecturer['full_name']) . " (" . htmlspecialchars($lecturer['email']) . ")</p>";
        
        // Create lecturer record
        $employee_no = 'EMP' . str_pad($lecturer['id'], 3, '0', STR_PAD_LEFT);
        $dept = 'Department';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO lecturers (id, employee_no, dept) VALUES (?, ?, ?)");
            $stmt->execute([$lecturer['id'], $employee_no, $dept]);
            echo "<p>✅ Created lecturer record with employee_no: " . $employee_no . "</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}

// Find student users without student records
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email 
    FROM users u 
    WHERE u.role = 'student' 
    AND u.id NOT IN (SELECT id FROM students)
");
$stmt->execute();
$missing_students = $stmt->fetchAll();

if (empty($missing_students)) {
    echo "<p>✅ All student users have corresponding student records.</p>";
} else {
    echo "<p>Found " . count($missing_students) . " student users without student records:</p>";
    
    foreach ($missing_students as $student) {
        echo "<p>Fixing: " . htmlspecialchars($student['full_name']) . " (" . htmlspecialchars($student['email']) . ")</p>";
        
        // Create student record
        $reg_no = 'STU' . str_pad($student['id'], 3, '0', STR_PAD_LEFT);
        $program = 'Program';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO students (id, reg_no, program) VALUES (?, ?, ?)");
            $stmt->execute([$student['id'], $reg_no, $program]);
            echo "<p>✅ Created student record with reg_no: " . $reg_no . "</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<p><a href='courses.php'>Go to Courses</a></p>";
?>
<?php
require_once 'includes/header.php';
require_once 'includes/guard.php';
require_once 'includes/db.php';

require_role(['student']);

$user = current_user();
$course_filter = $_GET['course'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Get student's enrolled courses for filter
$stmt = $pdo->prepare("
    SELECT c.id, c.code, c.title 
    FROM enrollments e 
    JOIN courses c ON c.id = e.course_id 
    WHERE e.student_id = ? 
    ORDER BY c.code
");
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll();

echo '<h2>My Records</h2>';

// Filter form
echo '<form method="GET">';
echo '<div>';
echo '<label for="course">Course:</label>';
echo '<select id="course" name="course">';
echo '<option value="">All Courses</option>';
foreach ($courses as $course) {
    $selected = ($course_filter == $course['id']) ? 'selected' : '';
    echo '<option value="' . $course['id'] . '" ' . $selected . '>' . htmlspecialchars($course['code'] . ' - ' . $course['title']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div>';
echo '<label for="from_date">From Date:</label>';
echo '<input type="date" id="from_date" name="from_date" value="' . htmlspecialchars($from_date) . '">';
echo '</div>';

echo '<div>';
echo '<label for="to_date">To Date:</label>';
echo '<input type="date" id="to_date" name="to_date" value="' . htmlspecialchars($to_date) . '">';
echo '</div>';

echo '<div>';
echo '<button type="submit">Filter</button>';
echo '</div>';
echo '</form>';

// Build attendance query
$attQuery = "
    SELECT a.att_date, a.status, c.code, c.title
    FROM attendance a
    JOIN courses c ON c.id = a.course_id
    WHERE a.student_id = ?
";
$attParams = [$user['id']];

if ($course_filter) {
    $attQuery .= " AND a.course_id = ?";
    $attParams[] = $course_filter;
}

if ($from_date) {
    $attQuery .= " AND a.att_date >= ?";
    $attParams[] = $from_date;
}

if ($to_date) {
    $attQuery .= " AND a.att_date <= ?";
    $attParams[] = $to_date;
}

$attQuery .= " ORDER BY a.att_date DESC, c.code";

$stmt = $pdo->prepare($attQuery);
$stmt->execute($attParams);
$attendance = $stmt->fetchAll();

echo '<h3>Attendance Records</h3>';
if (empty($attendance)) {
    echo '<p>No attendance records found.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Date</th><th>Course</th><th>Status</th></tr>';
    foreach ($attendance as $record) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($record['att_date']) . '</td>';
        echo '<td>' . htmlspecialchars($record['code'] . ' - ' . $record['title']) . '</td>';
        echo '<td>' . htmlspecialchars($record['status']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Show attendance percentage for selected course or all courses
    if ($course_filter) {
        $stmt = $pdo->prepare("
            SELECT ROUND(
                (COUNT(CASE WHEN status = 'Present' THEN 1 END) * 100.0 / COUNT(*)), 
                2
            ) AS attendance_percentage
            FROM attendance 
            WHERE course_id = ? AND student_id = ?
        ");
        $stmt->execute([$course_filter, $user['id']]);
        $pct = $stmt->fetch();
        echo '<p><strong>Attendance Percentage:</strong> ' . ($pct['attendance_percentage'] ?? 0) . '%</p>';
    } else {
        $stmt = $pdo->prepare("
            SELECT c.code, c.title,
                ROUND(
                    (COUNT(CASE WHEN a.status = 'Present' THEN 1 END) * 100.0 / COUNT(*)), 
                    2
                ) AS attendance_percentage
            FROM attendance a
            JOIN courses c ON c.id = a.course_id
            WHERE a.student_id = ?
            GROUP BY c.id, c.code, c.title
            ORDER BY c.code
        ");
        $stmt->execute([$user['id']]);
        $percentages = $stmt->fetchAll();
        
        if (!empty($percentages)) {
            echo '<h4>Attendance Summary by Course</h4>';
            echo '<table border="1">';
            echo '<tr><th>Course</th><th>Attendance %</th></tr>';
            foreach ($percentages as $pct) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($pct['code'] . ' - ' . $pct['title']) . '</td>';
                echo '<td>' . $pct['attendance_percentage'] . '%</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}

// Build marks query
$marksQuery = "
    SELECT m.assessment, m.obtained, m.total, c.code, c.title, m.created_at
    FROM marks m
    JOIN courses c ON c.id = m.course_id
    WHERE m.student_id = ?
";
$marksParams = [$user['id']];

if ($course_filter) {
    $marksQuery .= " AND m.course_id = ?";
    $marksParams[] = $course_filter;
}

$marksQuery .= " ORDER BY m.created_at DESC, c.code";

$stmt = $pdo->prepare($marksQuery);
$stmt->execute($marksParams);
$marks = $stmt->fetchAll();

echo '<h3>Marks Records</h3>';
if (empty($marks)) {
    echo '<p>No marks records found.</p>';
} else {
    echo '<table border="1">';
    echo '<tr><th>Course</th><th>Assessment</th><th>Obtained</th><th>Total</th><th>Percentage</th><th>Date</th></tr>';
    foreach ($marks as $mark) {
        $percentage = $mark['total'] > 0 ? round(($mark['obtained'] / $mark['total']) * 100, 2) : 0;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($mark['code'] . ' - ' . $mark['title']) . '</td>';
        echo '<td>' . htmlspecialchars($mark['assessment']) . '</td>';
        echo '<td>' . $mark['obtained'] . '</td>';
        echo '<td>' . $mark['total'] . '</td>';
        echo '<td>' . $percentage . '%</td>';
        echo '<td>' . htmlspecialchars($mark['created_at']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Show marks totals for selected course or all courses
    if ($course_filter) {
        $stmt = $pdo->prepare("
            SELECT SUM(obtained) AS total_obtained, SUM(total) AS total_possible
            FROM marks 
            WHERE course_id = ? AND student_id = ?
        ");
        $stmt->execute([$course_filter, $user['id']]);
        $totals = $stmt->fetch();
        echo '<p><strong>Total Marks:</strong> ' . ($totals['total_obtained'] ?? 0) . '/' . ($totals['total_possible'] ?? 0) . '</p>';
    } else {
        $stmt = $pdo->prepare("
            SELECT c.code, c.title, SUM(m.obtained) AS total_obtained, SUM(m.total) AS total_possible
            FROM marks m
            JOIN courses c ON c.id = m.course_id
            WHERE m.student_id = ?
            GROUP BY c.id, c.code, c.title
            ORDER BY c.code
        ");
        $stmt->execute([$user['id']]);
        $totals = $stmt->fetchAll();
        
        if (!empty($totals)) {
            echo '<h4>Marks Summary by Course</h4>';
            echo '<table border="1">';
            echo '<tr><th>Course</th><th>Total Obtained</th><th>Total Possible</th><th>Percentage</th></tr>';
            foreach ($totals as $total) {
                $percentage = $total['total_possible'] > 0 ? round(($total['total_obtained'] / $total['total_possible']) * 100, 2) : 0;
                echo '<tr>';
                echo '<td>' . htmlspecialchars($total['code'] . ' - ' . $total['title']) . '</td>';
                echo '<td>' . $total['total_obtained'] . '</td>';
                echo '<td>' . $total['total_possible'] . '</td>';
                echo '<td>' . $percentage . '%</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}

require_once 'includes/footer.php';
?>

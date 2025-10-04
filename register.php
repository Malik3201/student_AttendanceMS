<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $reg_no = trim($_POST['reg_no'] ?? '');
    $employee_no = trim($_POST['employee_no'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $dept = trim($_POST['dept'] ?? '');
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!in_array($role, ['student', 'lecturer'])) {
        $error = 'Invalid role selected.';
    } elseif ($role === 'student' && empty($reg_no)) {
        $error = 'Registration number is required for students.';
    } elseif ($role === 'lecturer' && empty($employee_no)) {
        $error = 'Employee number is required for lecturers.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Email already exists.');
            }
            
            // Check if reg_no already exists (for students)
            if ($role === 'student') {
                $stmt = $pdo->prepare("SELECT id FROM students WHERE reg_no = ?");
                $stmt->execute([$reg_no]);
                if ($stmt->fetch()) {
                    throw new Exception('Registration number already exists.');
                }
            }
            
            // Check if employee_no already exists (for lecturers)
            if ($role === 'lecturer') {
                $stmt = $pdo->prepare("SELECT id FROM lecturers WHERE employee_no = ?");
                $stmt->execute([$employee_no]);
                if ($stmt->fetch()) {
                    throw new Exception('Employee number already exists.');
                }
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $password_hash, $role]);
            $user_id = $pdo->lastInsertId();
            
            // Insert role-specific record
            if ($role === 'student') {
                $stmt = $pdo->prepare("INSERT INTO students (id, reg_no, program) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $reg_no, $program]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO lecturers (id, employee_no, dept) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $employee_no, $dept]);
            }
            
            $pdo->commit();
            $success = 'Registration successful! You can now login.';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<h2>Register New User</h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <p><a href="login.php">Go to Login</a></p>
<?php else: ?>

<form method="POST">
    <div>
        <label for="full_name">Full Name:</label>
        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
    </div>
    
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
    </div>
    
    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    
    <div>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    
    <div>
        <label for="role">Role:</label>
        <select id="role" name="role" required onchange="toggleRoleFields()">
            <option value="">Select Role</option>
            <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
            <option value="lecturer" <?php echo (($_POST['role'] ?? '') === 'lecturer') ? 'selected' : ''; ?>>Lecturer</option>
        </select>
    </div>
    
    <!-- Student fields -->
    <div id="student_fields" style="display: none;">
        <div>
            <label for="reg_no">Registration Number:</label>
            <input type="text" id="reg_no" name="reg_no" value="<?php echo htmlspecialchars($_POST['reg_no'] ?? ''); ?>">
        </div>
        <div>
            <label for="program">Program:</label>
            <input type="text" id="program" name="program" value="<?php echo htmlspecialchars($_POST['program'] ?? ''); ?>">
        </div>
    </div>
    
    <!-- Lecturer fields -->
    <div id="lecturer_fields" style="display: none;">
        <div>
            <label for="employee_no">Employee Number:</label>
            <input type="text" id="employee_no" name="employee_no" value="<?php echo htmlspecialchars($_POST['employee_no'] ?? ''); ?>">
        </div>
        <div>
            <label for="dept">Department:</label>
            <input type="text" id="dept" name="dept" value="<?php echo htmlspecialchars($_POST['dept'] ?? ''); ?>">
        </div>
    </div>
    
    <div>
        <button type="submit">Register</button>
        <a href="login.php">Already have an account? Login</a>
    </div>
</form>

<script>
function toggleRoleFields() {
    const role = document.getElementById('role').value;
    const studentFields = document.getElementById('student_fields');
    const lecturerFields = document.getElementById('lecturer_fields');
    
    studentFields.style.display = (role === 'student') ? 'block' : 'none';
    lecturerFields.style.display = (role === 'lecturer') ? 'block' : 'none';
    
    // Set required attributes
    document.getElementById('reg_no').required = (role === 'student');
    document.getElementById('employee_no').required = (role === 'lecturer');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleRoleFields();
});
</script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

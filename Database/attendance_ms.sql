-- Student Attendance & Marks Management System
-- Create the database first
CREATE DATABASE IF NOT EXISTS attendance_ms;
-- Select it (activate it)
USE attendance_ms;
-- Drop existing tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS marks;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

-- Create users table (user_id, name, email, password, role)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create courses table (course_id, course_name, course_code, semester, lecturer_id)
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    semester VARCHAR(50) NOT NULL,
    lecturer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create enrollments table (enroll_id, student_id, course_id)
CREATE TABLE enrollments (
    enroll_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Create attendance table (attendance_id, student_id, course_id, date, status)
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_id, date)
);

-- Create marks table (mark_id, student_id, course_id, exam_type, score)
CREATE TABLE marks (
    mark_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    exam_type ENUM('quiz', 'midterm', 'final') NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_mark (student_id, course_id, exam_type)
);

-- Insert sample data for testing

-- Sample users (lecturers and students)
-- Password: '123456' hashed with password_hash()
INSERT INTO users (name, email, password, role) VALUES
('Dr. John Smith', 'lecturer1@university.edu', '$2y$10$6Lrbr/qQdXT5O.VbFXImrOQcDTFdVLVjRItVju6HFL76z/dhkDG52', 'lecturer'),
('Prof. Sarah Johnson', 'lecturer2@university.edu', '$2y$10$6Lrbr/qQdXT5O.VbFXImrOQcDTFdVLVjRItVju6HFL76z/dhkDG52', 'lecturer'),
('Rehan', 'student1@university.edu', '$2y$10$6Lrbr/qQdXT5O.VbFXImrOQcDTFdVLVjRItVju6HFL76z/dhkDG52', 'student'),
('Awais', 'student2@university.edu', '$2y$10$6Lrbr/qQdXT5O.VbFXImrOQcDTFdVLVjRItVju6HFL76z/dhkDG52', 'student'),
('Ali Raza', 'student3@university.edu', '$2y$10$6Lrbr/qQdXT5O.VbFXImrOQcDTFdVLVjRItVju6HFL76z/dhkDG52', 'student'),
('Kiril', 'student4@university.edu', '$2y$10$6Lrbr/qQdXT5O.VbFXImrOQcDTFdVLVjRItVju6HFL76z/dhkDG52', 'student');

-- Sample courses
INSERT INTO courses (course_name, course_code, semester, lecturer_id) VALUES
('Introduction to Computer Science', 'CS101', 'Fall 2025', 1),
('Data Structures and Algorithms', 'CS201', 'Fall 2025', 1),
('Calculus I', 'MATH101', 'Fall 2025', 2),
('Linear Algebra', 'MATH201', 'Spring 2025', 2);

-- Sample enrollments
INSERT INTO enrollments (student_id, course_id) VALUES
(3, 1), (3, 3), -- Alice enrolled in CS101 and MATH101
(4, 1), (4, 2), -- Bob enrolled in CS101 and CS201
(5, 2), (5, 4), -- Carol enrolled in CS201 and MATH201
(6, 1), (6, 3), (6, 4); -- David enrolled in CS101, MATH101, and MATH201

-- Sample attendance records
INSERT INTO attendance (student_id, course_id, date, status) VALUES
-- CS101 attendance
(3, 1, '2025-08-01', 'Present'),
(4, 1, '2025-08-01', 'Present'),
(6, 1, '2025-08-01', 'Absent'),
(3, 1, '2025-08-03', 'Present'),
(4, 1, '2025-08-03', 'Present'),
(6, 1, '2025-08-03', 'Present'),
-- CS201 attendance
(4, 2, '2025-08-02', 'Present'),
(5, 2, '2025-08-02', 'Present'),
(4, 2, '2025-08-04', 'Present'),
(5, 2, '2025-08-04', 'Absent');

-- Sample marks
INSERT INTO marks (student_id, course_id, exam_type, score) VALUES
-- CS101 marks
(3, 1, 'quiz', 85.00),
(4, 1, 'quiz', 92.00),
(6, 1, 'quiz', 78.00),
(3, 1, 'midterm', 88.50),
(4, 1, 'midterm', 95.00),
(6, 1, 'midterm', 82.00),
-- CS201 marks
(4, 2, 'quiz', 90.00),
(5, 2, 'quiz', 87.50),
(4, 2, 'midterm', 88.00),
(5, 2, 'midterm', 91.00);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_courses_lecturer ON courses(lecturer_id);
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_enrollments_course ON enrollments(course_id);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_marks_student_course ON marks(student_id, course_id);

SELECT 'Database schema created successfully with sample data!' as message;
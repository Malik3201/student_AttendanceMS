# Canberra Student Attendance and Marks Management System

A comprehensive web-based system for managing student attendance and academic marks in educational institutions. Built with PHP, MySQL, and modern responsive design.

## 🎯 Project Overview

This system provides a complete solution for educational institutions to manage:
- **Student Attendance Tracking** - Mark and monitor class attendance
- **Academic Marks Management** - Record and track student performance
- **Course Management** - Create and manage courses
- **User Management** - Handle lecturers and students
- **Reports & Analytics** - Generate comprehensive reports

## 🚀 Features

### For Lecturers
- **Dashboard** - Overview of courses, students, and statistics
- **Course Management** - Create, edit, and manage courses
- **Student Management** - Enroll and manage students
- **Attendance Tracking** - Mark student attendance
- **Marks Management** - Record quiz, midterm, and final exam scores
- **Reports Generation** - Generate PDF reports and analytics

### For Students
- **Personal Dashboard** - View enrolled courses and performance
- **Attendance Tracking** - Monitor personal attendance records
- **Marks Viewing** - Check exam scores and grades
- **Progress Monitoring** - Track academic performance

### System Features
- **Responsive Design** - Works on desktop, tablet, and mobile
- **Role-based Access** - Different interfaces for lecturers and students
- **Secure Authentication** - Password hashing and session management
- **PDF Export** - Generate professional reports
- **Modern UI/UX** - Clean, intuitive interface

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Styling**: Custom CSS with CSS Grid and Flexbox
- **Icons**: Heroicons (SVG)
- **Fonts**: Inter (Google Fonts)

## 📁 Project Structure

```
student_AttendanceMS/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── app.js             # JavaScript functionality
├── includes/
│   ├── auth.php               # Authentication functions
│   ├── config.php             # Database configuration
│   ├── db.php                 # Database connection
│   ├── footer.php             # Footer template
│   ├── guard.php              # Access control functions
│   ├── header.php             # Header template
│   └── pdf_generator.php      # PDF report generation
├── 001_schema_and_seed.sql    # Database schema and sample data
├── attendance.php             # Attendance management (lecturer)
├── courses.php                # Course management (lecturer)
├── dashboard.php              # Main dashboard
├── index.php                  # Homepage
├── login.php                  # User login
├── logout.php                 # User logout
├── marks.php                  # Marks management (lecturer)
├── my-attendance.php          # Student attendance view
├── my-courses.php             # Student courses view
├── my-marks.php               # Student marks view
├── register.php               # User registration
├── reports.php                # Reports generation
├── students.php               # Student management (lecturer)
└── README.md                  # This file
```

## 🗄️ Database Schema

The system uses 5 main tables:

### Users Table
- `user_id` (Primary Key)
- `name` - Full name
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - 'lecturer' or 'student'
- `created_at` - Registration timestamp

### Courses Table
- `course_id` (Primary Key)
- `course_name` - Course title
- `course_code` - Course identifier
- `semester` - Academic semester
- `lecturer_id` - Foreign key to users table
- `created_at` - Creation timestamp

### Enrollments Table
- `enrollment_id` (Primary Key)
- `student_id` - Foreign key to users table
- `course_id` - Foreign key to courses table
- `enrolled_at` - Enrollment timestamp

### Attendance Table
- `attendance_id` (Primary Key)
- `student_id` - Foreign key to users table
- `course_id` - Foreign key to courses table
- `date` - Attendance date
- `status` - 'Present' or 'Absent'
- `created_at` - Record timestamp

### Marks Table
- `mark_id` (Primary Key)
- `student_id` - Foreign key to users table
- `course_id` - Foreign key to courses table
- `exam_type` - 'quiz', 'midterm', or 'final'
- `score` - Exam score
- `created_at` - Record timestamp

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/LAMP (recommended for development)

### Installation Steps

1. **Clone/Download the project**
   ```bash
   git clone [repository-url]
   # or download and extract the ZIP file
   ```

2. **Set up the database**
   - Create a new MySQL database
   - Import the schema: `001_schema_and_seed.sql`
   - This will create all tables and insert sample data

3. **Configure the database connection**
   - Open `includes/config.php`
   - Update database credentials:
```php
define('DB_HOST', 'localhost');
   define('DB_NAME', 'student_attendance_ms');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Set up the web server**
   - Place the project in your web server directory
   - For XAMPP: `C:\xampp\htdocs\student_AttendanceMS\`
   - For WAMP: `C:\wamp64\www\student_AttendanceMS\`

5. **Access the system**
   - Open your browser
   - Navigate to `http://localhost/student_AttendanceMS/`
   - The system should load with the homepage

## 👥 Demo Accounts

The system comes with pre-configured demo accounts:

### Lecturer Account
- **Email**: lecturer1@university.edu
- **Password**: password123
- **Access**: Full system access (courses, students, attendance, marks, reports)

### Student Account
- **Email**: student1@university.edu
- **Password**: password123
- **Access**: Student features (my courses, attendance, marks)

## 🎨 Design System

### Color Palette
- **Primary Green**: #16a34a (Canberra Green)
- **Success Green**: #16a34a
- **Warning Orange**: #d97706
- **Error Red**: #dc2626
- **Text Primary**: #111827
- **Text Secondary**: #6b7280
- **Background**: #f9fafb
- **Surface**: #ffffff

### Typography
- **Font Family**: Inter (Google Fonts)
- **Headings**: 600-700 weight
- **Body Text**: 400 weight
- **Small Text**: 300 weight

### Responsive Breakpoints
- **Desktop**: >768px
- **Tablet**: 768px
- **Mobile**: 480px
- **Small Mobile**: 360px

## 🔧 Key Features Implementation

### Authentication System
- Secure password hashing using PHP's `password_hash()`
- Session management with proper security
- Role-based access control
- Login/logout functionality

### Database Operations
- PDO for secure database interactions
- Prepared statements to prevent SQL injection
- Error handling and logging
- Transaction support for data integrity

### Responsive Design
- Mobile-first CSS approach
- CSS Grid and Flexbox for layouts
- Smooth animations and transitions
- Touch-friendly interface elements

### PDF Generation
- HTML to PDF conversion
- Professional report templates
- Responsive PDF layouts
- Print-optimized styling

## 📱 Mobile Responsiveness

The system is fully responsive and optimized for:
- **Desktop computers** (1024px+)
- **Tablets** (768px - 1023px)
- **Mobile phones** (320px - 767px)

### Mobile Features
- Touch-friendly navigation
- Optimized form layouts
- Responsive tables with horizontal scroll
- Mobile-specific UI adjustments

## 🔒 Security Features

- **Password Security**: Bcrypt hashing
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Session Security**: Secure session handling
- **Access Control**: Role-based permissions
- **CSRF Protection**: Form token validation

## 🚀 Performance Optimizations

- **Database Indexing**: Optimized queries
- **CSS Minification**: Compressed stylesheets
- **Image Optimization**: Optimized images
- **Caching**: Browser caching headers
- **Lazy Loading**: Deferred content loading

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Page Not Loading**
   - Check web server configuration
   - Verify file permissions
   - Check PHP error logs

3. **Login Issues**
   - Ensure demo accounts exist in database
   - Check password hashing compatibility
   - Verify session configuration

### Debug Mode
To enable debug mode, add this to the top of any PHP file:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📚 Learning Resources

### PHP & MySQL
- [PHP Official Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PDO Tutorial](https://www.php.net/manual/en/book.pdo.php)

### Frontend Development
- [CSS Grid Guide](https://css-tricks.com/snippets/css/complete-guide-grid/)
- [Flexbox Guide](https://css-tricks.com/snippets/css/a-guide-to-flexbox/)
- [Responsive Design](https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Responsive_Design)

### Security Best Practices
- [OWASP PHP Security](https://owasp.org/www-project-php-security/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)

## 🤝 Contributing

This is an educational project. To contribute:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is for educational purposes. Please ensure you have proper permissions before using in production environments.

## 👨‍💻 Developer Notes

### Code Structure
- **MVC Pattern**: Separation of concerns
- **DRY Principle**: Don't Repeat Yourself
- **Security First**: All inputs validated and sanitized
- **Mobile First**: Responsive design approach

### File Organization
- **Includes**: Reusable PHP components
- **Assets**: Static files (CSS, JS, images)
- **Pages**: Main application pages
- **Database**: Schema and seed data

### Best Practices
- Consistent code formatting
- Comprehensive comments
- Error handling
- Input validation
- Security considerations

---

**Developed for Educational Purposes**  
*Canberra Student Attendance and Marks Management System*  
*Version 1.0 - 2024*
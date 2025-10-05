<?php
/**
 * Homepage - Canberra Student Attendance and Marks Management System
 * 
 * This is the main landing page of the system. It displays different content
 * based on whether the user is logged in or not, providing a personalized
 * experience for both lecturers and students.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2025
 */

// Include header template
require_once 'includes/header.php';

// Get current user information (null if not logged in)
$user = current_user();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <?php if ($user): ?>
                    <h1>Welcome back, <span class="text-accent"><?php echo htmlspecialchars($user['name']); ?></span>!</h1>
                    <p class="hero-description">Continue managing your <?php echo $user['role'] === 'lecturer' ? 'courses, students, and reports' : 'attendance and marks'; ?> with our comprehensive Student Attendance and Marks Management System.</p>
                    <div class="hero-cta">
                        <a href="dashboard.php" class="btn btn-primary btn-large">Go to Dashboard</a>
                        <a href="#features" class="btn btn-secondary btn-large">Learn More</a>
                    </div>
                <?php else: ?>
                    <h1>Welcome to <span class="text-accent">Canberra System</span></h1>
                    <p class="hero-description">The most comprehensive and user-friendly Student Attendance and Marks Management System. Streamline your educational institution's administrative tasks with our modern, secure, and intuitive platform.</p>
                    <div class="hero-cta">
                        <a href="login.php" class="btn btn-primary btn-large">Get Started</a>
                        <a href="#features" class="btn btn-secondary btn-large">Learn More</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hero-image">
                <img src="https://img.freepik.com/premium-vector/learning-concept-illustration_114360-3454.jpg" alt="Students studying illustration" style="width: 100%; max-width: 600px; height: auto; border-radius: 12px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="section">
    <div class="container">
        <h2 class="section-title">Why Choose Canberra System?</h2>
        <p class="section-subtitle">Experience the future of educational management with our comprehensive suite of features designed for modern institutions.</p>
        
        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 30px; height: 30px;">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                        <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                        <path d="M12 3c0 1-1 3-3 3s-3-2-3-3 1-3 3-3 3 2 3 3"/>
                        <path d="M12 21c0-1 1-3 3-3s3 2 3 3-1 3-3 3-3-2-3-3"/>
                    </svg>
                </div>
                <h3>Easy Attendance</h3>
                <p>Streamline attendance tracking with our intuitive interface. Mark attendance in seconds with just a few clicks, and get instant reports.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 30px; height: 30px;">
                        <path d="M3 3v18h18"/>
                        <path d="M18.7 17l-5.1-5.2-2.8 2.7L6 14.3"/>
                    </svg>
                </div>
                <h3>Marks Tracking</h3>
                <p>Comprehensive grade management system supporting quizzes, midterms, and finals. Automatic grade calculations and detailed analytics.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon feature-icon-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 30px; height: 30px;">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </div>
                <h3>Secure Login</h3>
                <p>Bank-level security with encrypted passwords, session management, and role-based access control to protect your data.</p>
            </div>
            
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 30px; height: 30px;">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <h3>Responsive Design</h3>
                <p>Perfect experience across all devices - desktop, tablet, and mobile. Access your data anywhere, anytime with our responsive interface.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials" class="section testimonials-section">
    <div class="container">
        <h2 class="section-title">What Our Users Say</h2>
        <p class="section-subtitle">Discover why thousands of educators and students trust Canberra System for their academic management needs.</p>
        
        <div class="testimonials-grid">
            <div class="testimonial-card fade-in">
                <div class="testimonial-content">
                    <div class="testimonial-quote">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 24px; height: 24px; color: var(--accent);">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/>
                        </svg>
                    </div>
                    <p>"Canberra System has completely transformed how we manage attendance and grades. The interface is intuitive, and the reporting features are incredibly powerful. It has saved us hours of administrative work every week."</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Dr. Sarah Johnson</h4>
                        <span>Head of Computer Science, University of Canberra</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card fade-in">
                <div class="testimonial-content">
                    <div class="testimonial-quote">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 24px; height: 24px; color: var(--accent);">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/>
                        </svg>
                    </div>
                    <p>"As a student, I love how easy it is to check my attendance and grades. The mobile interface is perfect, and I can access all my information anywhere. It's made tracking my academic progress so much simpler."</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Michael Chen</h4>
                        <span>Computer Science Student, Year 3</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card fade-in">
                <div class="testimonial-content">
                    <div class="testimonial-quote">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 24px; height: 24px; color: var(--accent);">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/>
                        </svg>
                    </div>
                    <p>"The security features and role-based access control give me confidence that our data is safe. The system is reliable, fast, and the support team is always helpful when we need assistance."</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4>Prof. David Williams</h4>
                        <span>IT Administrator, Canberra Institute of Technology</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section id="stats" class="section stats-section">
    <div class="container">
        <h2 class="section-title">Trusted by Educational Institutions</h2>
        <p class="section-subtitle">Join thousands of users who rely on Canberra System for their academic management needs.</p>
        
        <div class="stats-grid">
            <div class="stat-item fade-in">
                <div class="stat-number">500+</div>
                <div class="stat-label">Educational Institutions</div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">50,000+</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">1M+</div>
                <div class="stat-label">Attendance Records</div>
            </div>
            <div class="stat-item fade-in">
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Uptime</div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->


<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <?php if ($user): ?>
                <h2>Ready to Continue Your Work?</h2>
                <p>Access your <?php echo $user['role'] === 'lecturer' ? 'courses, students, and reports' : 'attendance and marks'; ?> with our comprehensive Student Attendance and Marks Management System. Everything you need is just a click away.</p>
                <div class="cta-buttons">
                    <a href="dashboard.php" class="btn btn-primary btn-large">Go to Dashboard</a>
                    <?php if ($user['role'] === 'lecturer'): ?>
                        <a href="courses.php" class="btn btn-secondary btn-large">Manage Courses</a>
                    <?php else: ?>
                        <a href="my-courses.php" class="btn btn-secondary btn-large">View My Courses</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h2>Ready to Transform Your Institution?</h2>
                <p>Join thousands of educators and students who have already made the switch to Canberra Student Attendance and Marks Management System. Experience the difference that modern technology can make in educational management.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary btn-large">Start Free Trial</a>
                    <a href="login.php" class="btn btn-secondary btn-large">Login to Account</a>
                </div>
                
                <div class="demo-accounts">
                    <h4>Try Demo Accounts</h4>
                    <p class="demo-description">Experience the system with pre-configured demo accounts</p>
                    <div class="demo-grid">
                        <div class="demo-card">
                            <div class="demo-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="demo-content">
                                <h5>Lecturer Account</h5>
                                <p class="demo-email">lecturer1@university.edu</p>
                                <p class="demo-password">123456</p>
                                <a href="login.php" class="btn btn-outline btn-small">Try Now</a>
                            </div>
                        </div>
                        <div class="demo-card">
                            <div class="demo-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                </svg>
                            </div>
                            <div class="demo-content">
                                <h5>Student Account</h5>
                                <p class="demo-email">student1@university.edu</p>
                                <p class="demo-password">123456</p>
                                <a href="login.php" class="btn btn-outline btn-small">Try Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Contact Us Section -->
<section class="contact" id="contact">
    <div class="container">
        <div class="contact-content">
            <div class="contact-text">
                <h2>Get in Touch</h2>
                <p>Have questions about our Student Attendance and Marks Management System? We're here to help you get started and make the most of our platform.</p>
                
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                        <div class="contact-details">
                            <h4>Email Us</h4>
                            <p>support@canberra.edu.au</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                        </div>
                        <div class="contact-details">
                            <h4>Call Us</h4>
                            <p>+61 2 6201 5111</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="contact-details">
                            <h4>Visit Us</h4>
                            <p>University of Canberra<br>Bruce, ACT 2617, Australia</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <h3>Send us a Message</h3>
                <form class="form" action="#" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="general">General Inquiry</option>
                            <option value="technical">Technical Support</option>
                            <option value="demo">Request Demo</option>
                            <option value="pricing">Pricing Information</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us how we can help you..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Fade in animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

document.querySelectorAll('.fade-in').forEach(el => {
    observer.observe(el);
});

// Header scroll effect
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
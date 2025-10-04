    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Canberra System</h3>
                    <p>Professional Student Attendance & Marks Management System designed for modern educational institutions. Streamline your administrative tasks with our secure and intuitive platform.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                        <li><a href="#stats">Statistics</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>For Users</h3>
                    <ul>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><strong>Email:</strong> support@canberra.edu.au</p>
                    <p><strong>Support:</strong> 24/7 Available</p>
                    <p><strong>Version:</strong> 1.0.0</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Canberra Student Attendance and Marks Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile navigation toggle
        const mobileNavToggle = document.getElementById('mobileNavToggle');
        const mobileNav = document.getElementById('mobileNav');
        const mobileNavClose = document.getElementById('mobileNavClose');

        if (mobileNavToggle && mobileNav) {
            mobileNavToggle.addEventListener('click', () => {
                const isExpanded = mobileNavToggle.getAttribute('aria-expanded') === 'true';
                mobileNavToggle.setAttribute('aria-expanded', !isExpanded);
                mobileNav.classList.toggle('open');
            });
        }

        if (mobileNavClose && mobileNav) {
            mobileNavClose.addEventListener('click', () => {
                mobileNavToggle.setAttribute('aria-expanded', 'false');
                mobileNav.classList.remove('open');
            });
        }

        // Close mobile nav when clicking outside
        document.addEventListener('click', (e) => {
            if (mobileNav && mobileNav.classList.contains('open')) {
                if (!mobileNav.contains(e.target) && !mobileNavToggle.contains(e.target)) {
                    mobileNavToggle.setAttribute('aria-expanded', 'false');
                    mobileNav.classList.remove('open');
                }
            }
        });

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
    </script>
</body>
</html>
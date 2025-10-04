/**
 * Canberra Student Attendance and Marks Management System
 * JavaScript Application File
 * 
 * This file contains all client-side JavaScript functionality for the system,
 * including mobile navigation, smooth scrolling, animations, and UI interactions.
 * 
 * @author Student Developer
 * @version 1.0
 * @since 2024
 */

// ============================================================================
// MOBILE NAVIGATION SYSTEM
// ============================================================================

// Get mobile navigation elements
const mobileNavToggle = document.getElementById('mobileNavToggle');
const mobileNav = document.getElementById('mobileNav');
const mobileNavClose = document.getElementById('mobileNavClose');

/**
 * Toggle mobile navigation menu
 * 
 * Opens or closes the mobile navigation menu and updates ARIA attributes
 * for accessibility compliance.
 */
function toggleMobileNav() {
    const isOpen = mobileNav.classList.contains('open');
    mobileNav.classList.toggle('open');
    mobileNavToggle.setAttribute('aria-expanded', !isOpen);
}

/**
 * Close mobile navigation menu
 * 
 * Closes the mobile navigation menu and resets ARIA attributes.
 */
function closeMobileNav() {
    mobileNav.classList.remove('open');
    mobileNavToggle.setAttribute('aria-expanded', 'false');
}

// ============================================================================
// EVENT LISTENERS
// ============================================================================

// Mobile navigation event listeners
mobileNavToggle.addEventListener('click', toggleMobileNav);
mobileNavClose.addEventListener('click', closeMobileNav);

// Close mobile navigation on escape key press
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileNav.classList.contains('open')) {
        closeMobileNav();
    }
});

// Close mobile navigation when clicking outside
document.addEventListener('click', (e) => {
    if (mobileNav.classList.contains('open') && 
        !mobileNav.contains(e.target) && 
        !mobileNavToggle.contains(e.target)) {
        closeMobileNav();
    }
});

// ============================================================================
// SMOOTH SCROLLING SYSTEM
// ============================================================================

/**
 * Smooth scrolling for anchor links
 * 
 * Implements smooth scrolling behavior for internal page links.
 * Automatically adjusts for fixed header height and closes mobile nav.
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            // Calculate target position accounting for fixed header
            const headerHeight = document.querySelector('.header').offsetHeight;
            const targetPosition = target.offsetTop - headerHeight;
            
            // Smooth scroll to target
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
            
            // Close mobile navigation if open
            closeMobileNav();
        }
    });
});

// ============================================================================
// HEADER SCROLL EFFECTS
// ============================================================================

// Get header element and initialize scroll tracking
const header = document.getElementById('header');
let lastScrollY = window.scrollY;

/**
 * Update header appearance based on scroll position
 * 
 * Adds/removes 'scrolled' class to header when user scrolls past 100px
 * to create a visual effect indicating page scroll state.
 */
function updateHeader() {
    const currentScrollY = window.scrollY;
    
    if (currentScrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
    
    lastScrollY = currentScrollY;
}

// Add scroll event listener
window.addEventListener('scroll', updateHeader);

// ============================================================================
// ANIMATION SYSTEM
// ============================================================================

/**
 * Intersection Observer for fade-in animations
 * 
 * Observes elements entering the viewport and triggers fade-in animations
 * for better user experience and performance.
 */
const observerOptions = {
    threshold: 0.1,                    // Trigger when 10% of element is visible
    rootMargin: '0px 0px -50px 0px'    // Start animation 50px before element enters viewport
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Observe all elements with fade-in class
document.querySelectorAll('.fade-in').forEach(el => {
    observer.observe(el);
});

// ============================================================================
// ACCESSIBILITY FEATURES
// ============================================================================

/**
 * Respect user's reduced motion preference
 * 
 * Disables animations for users who prefer reduced motion for accessibility.
 */
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.fade-in').forEach(el => {
        el.classList.add('visible');
    });
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize application when DOM is fully loaded
 * 
 * Sets up animations and initializes all interactive elements.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in class to elements that should animate
    const elementsToAnimate = document.querySelectorAll('.feature-card, .timeline-item, .role-card, .stat-item, .security-item');
    elementsToAnimate.forEach(el => {
        el.classList.add('fade-in');
    });
    
    // Observe the newly added fade-in elements
    document.querySelectorAll('.fade-in').forEach(el => {
        observer.observe(el);
    });
});
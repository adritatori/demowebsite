/**
 * JavaScript for Secure Web Application
 * Client-side validation and UX enhancements
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔒 Secure Web Application Loaded');

    // Initialize all components
    initFormValidation();
    initSecurityMonitoring();
    initUIEnhancements();
});

/**
 * Form Validation (Client-side)
 * Note: This is NOT a security feature - server-side validation is required!
 * This is just for better UX
 */
function initFormValidation() {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            // Basic client-side validation
            if (username.trim() === '' || password.trim() === '') {
                e.preventDefault();
                showAlert('Please fill in all fields', 'error');
                return false;
            }

            // Username format validation
            const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
            if (!usernameRegex.test(username)) {
                e.preventDefault();
                showAlert('Username must be 3-20 characters (letters, numbers, underscore only)', 'error');
                return false;
            }

            // Password length check
            if (password.length < 8) {
                e.preventDefault();
                showAlert('Password must be at least 8 characters', 'error');
                return false;
            }

            console.log('✅ Form validation passed');
        });

        // Real-time username validation
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                const value = this.value;
                const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;

                if (value.length > 0 && !usernameRegex.test(value)) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#10b981';
                }
            });
        }
    }
}

/**
 * Security Monitoring
 * Log security-relevant events
 */
function initSecurityMonitoring() {
    // Monitor for potential XSS attempts in forms
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], textarea');

    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value;

            // Check for suspicious patterns
            const xssPatterns = [
                /<script/i,
                /javascript:/i,
                /onerror=/i,
                /onclick=/i,
                /<iframe/i
            ];

            const suspicious = xssPatterns.some(pattern => pattern.test(value));

            if (suspicious) {
                console.warn('⚠️ Suspicious input detected:', value);
                // Server-side will sanitize this
            }
        });
    });

    // Log CSRF token presence
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    if (csrfToken) {
        console.log('✅ CSRF token present:', csrfToken.value.substring(0, 10) + '...');
    }

    // Log cookie security
    checkCookieSecurity();
}

/**
 * Check cookie security settings
 */
function checkCookieSecurity() {
    const cookies = document.cookie;

    if (cookies) {
        console.log('🍪 Cookies present');
        console.log('Note: HttpOnly and Secure flags cannot be viewed from JavaScript (by design)');
        console.log('This is a security feature! Check browser DevTools → Application → Cookies for details');
    } else {
        console.log('🍪 No cookies or HttpOnly cookies (good for security)');
    }
}

/**
 * UI Enhancements
 */
function initUIEnhancements() {
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Add loading state to buttons on form submit
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }
        });
    });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;

    const container = document.querySelector('.login-form') || document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s';
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }
}

/**
 * Security Testing Helper Functions
 * These functions can be called from browser console for testing
 */
window.securityTest = {
    // Test XSS input
    testXSS: function() {
        console.log('🧪 XSS Test: Try entering <script>alert("XSS")</script> in the username field');
        console.log('Expected: Input should be sanitized by server');
    },

    // Test SQL Injection
    testSQLi: function() {
        console.log('🧪 SQL Injection Test: Try username: \' OR \'1\'=\'1');
        console.log('Expected: Login should fail, PDO prevents injection');
    },

    // Test CSRF
    testCSRF: function() {
        const csrfToken = document.querySelector('input[name="csrf_token"]');
        if (csrfToken) {
            console.log('🧪 CSRF Test: Remove or modify the csrf_token field in DevTools');
            console.log('Current token:', csrfToken.value);
            console.log('Expected: Form submission should be rejected');
        }
    },

    // View session info
    viewSession: function() {
        console.log('🧪 Session Test: Check Application → Cookies in DevTools');
        console.log('Expected: Cookie should have HttpOnly and SameSite=Strict flags');
    },

    // Test HTTPS
    testHTTPS: function() {
        console.log('🧪 HTTPS Test: Access site via https://172.19.12.158/');
        console.log('Expected: Connection should be encrypted (after SSL setup)');
        console.log('Current protocol:', window.location.protocol);
    },

    // Run all tests
    runAll: function() {
        console.log('🧪 Running all security tests...\n');
        this.testXSS();
        console.log('');
        this.testSQLi();
        console.log('');
        this.testCSRF();
        console.log('');
        this.viewSession();
        console.log('');
        this.testHTTPS();
    }
};

// Log helpful message
console.log('💡 Tip: Type securityTest.runAll() in console to see all security tests');

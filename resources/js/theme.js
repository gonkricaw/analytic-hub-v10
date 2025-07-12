/**
 * Theme Management JavaScript
 * Handles dark theme implementation, responsive navigation, and UI interactions
 */

// Theme Manager Class
class ThemeManager {
    constructor() {
        this.init();
    }

    /**
     * Initialize theme manager
     */
    init() {
        this.initializeTheme();
        this.initializeNavigation();
        this.initializeNotifications();
        this.initializeTooltips();
        this.initializeLoadingStates();
        this.bindEvents();
    }

    /**
     * Initialize theme settings
     */
    initializeTheme() {
        // Apply saved theme preference or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        this.applyTheme(savedTheme);
    }

    /**
     * Apply theme to document
     * @param {string} theme - Theme name ('dark' or 'light')
     */
    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update theme toggle button if exists
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
    }

    /**
     * Toggle between dark and light themes
     */
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
    }

    /**
     * Initialize responsive navigation
     */
    initializeNavigation() {
        // Handle navbar collapse on mobile
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            // Close navbar when clicking outside
            document.addEventListener('click', (e) => {
                if (!navbarToggler.contains(e.target) && !navbarCollapse.contains(e.target)) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bsCollapse && navbarCollapse.classList.contains('show')) {
                        bsCollapse.hide();
                    }
                }
            });

            // Close navbar when clicking on nav links (mobile)
            const navLinks = navbarCollapse.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) { // Bootstrap lg breakpoint
                        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                        if (bsCollapse) {
                            bsCollapse.hide();
                        }
                    }
                });
            });
        }
    }

    /**
     * Initialize notification system
     */
    initializeNotifications() {
        this.loadNotificationCount();
        
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            this.loadNotificationCount();
        }, 30000);
    }

    /**
     * Load notification count from API
     */
    async loadNotificationCount() {
        try {
            const response = await fetch('/api/notifications/count');
            if (response.ok) {
                const data = await response.json();
                this.updateNotificationBadge(data.count || 0);
            }
        } catch (error) {
            console.warn('Failed to load notification count:', error);
        }
    }

    /**
     * Update notification badge
     * @param {number} count - Notification count
     */
    updateNotificationBadge(count) {
        const badge = document.getElementById('notification-count');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    /**
     * Initialize Bootstrap tooltips
     */
    initializeTooltips() {
        // Initialize all tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Initialize loading states
     */
    initializeLoadingStates() {
        // Hide loading screen after page load
        window.addEventListener('load', () => {
            const loadingScreen = document.getElementById('loading-screen');
            if (loadingScreen) {
                setTimeout(() => {
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 300);
                }, 500);
            }
        });
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Theme toggle button
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Handle form submissions with loading states
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.showFormLoading(form);
            }
        });

        // Handle AJAX requests with loading states
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[data-ajax]');
            if (button) {
                e.preventDefault();
                this.handleAjaxRequest(button);
            }
        });

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }, 5000);
        });
    }

    /**
     * Show loading state for forms
     * @param {HTMLFormElement} form - Form element
     */
    showFormLoading(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            submitButton.disabled = true;
            
            // Store original text for restoration
            submitButton.dataset.originalText = originalText;
        }
    }

    /**
     * Handle AJAX requests
     * @param {HTMLElement} button - Button element
     */
    async handleAjaxRequest(button) {
        const url = button.dataset.ajax;
        const method = button.dataset.method || 'GET';
        const confirm = button.dataset.confirm;
        
        // Show confirmation if required
        if (confirm && !window.confirm(confirm)) {
            return;
        }
        
        // Show loading state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showToast(data.message || 'Operation completed successfully', 'success');
                
                // Reload page if specified
                if (button.dataset.reload === 'true') {
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                this.showToast(data.message || 'An error occurred', 'error');
            }
        } catch (error) {
            this.showToast('Network error occurred', 'error');
        } finally {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type ('success', 'error', 'warning', 'info')
     */
    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${this.getBootstrapColor(type)} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${this.getToastIcon(type)} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        // Initialize and show toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    /**
     * Get Bootstrap color class for toast type
     * @param {string} type - Toast type
     * @returns {string} Bootstrap color class
     */
    getBootstrapColor(type) {
        const colors = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return colors[type] || 'info';
    }

    /**
     * Get Font Awesome icon for toast type
     * @param {string} type - Toast type
     * @returns {string} Font Awesome icon class
     */
    getToastIcon(type) {
        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
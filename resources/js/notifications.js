/**
 * Real-time Notifications Handler
 * 
 * This module handles real-time notifications using Laravel Echo and Pusher.
 * It manages notification display, sound alerts, and UI updates.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

class NotificationManager {
    constructor() {
        this.echo = null;
        this.userId = null;
        this.notificationSound = null;
        this.init();
    }

    /**
     * Initialize the notification manager
     */
    init() {
        this.setupEcho();
        this.setupNotificationSound();
        this.bindEvents();
        this.loadUnreadCount();
    }

    /**
     * Setup Laravel Echo with Pusher
     */
    setupEcho() {
        // Get user ID from meta tag or global variable
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        this.userId = userIdMeta ? userIdMeta.getAttribute('content') : window.userId;

        if (!this.userId) {
            console.warn('User ID not found. Real-time notifications will not work.');
            return;
        }

        // Configure Pusher
        window.Pusher = Pusher;

        this.echo = new Echo({
            broadcaster: 'pusher',
            key: process.env.MIX_PUSHER_APP_KEY || window.pusherConfig?.key,
            cluster: process.env.MIX_PUSHER_APP_CLUSTER || window.pusherConfig?.cluster,
            forceTLS: true,
            encrypted: true
        });

        this.listenForNotifications();
    }

    /**
     * Setup notification sound
     */
    setupNotificationSound() {
        this.notificationSound = new Audio('/sounds/notification.mp3');
        this.notificationSound.volume = 0.5;
    }

    /**
     * Listen for real-time notifications
     */
    listenForNotifications() {
        if (!this.echo || !this.userId) return;

        this.echo.private(`user.${this.userId}`)
            .listen('NotificationSent', (data) => {
                this.handleNewNotification(data);
            });
    }

    /**
     * Handle incoming notification
     * @param {Object} data - Notification data
     */
    handleNewNotification(data) {
        console.log('New notification received:', data);

        // Update notification count
        this.updateNotificationCount(data.unread_count);

        // Add notification to dropdown
        this.addNotificationToDropdown(data.notification);

        // Show browser notification if permission granted
        this.showBrowserNotification(data.notification);

        // Play sound
        this.playNotificationSound();

        // Show toast notification
        this.showToastNotification(data.notification);
    }

    /**
     * Update notification count badge
     * @param {number} count - Unread count
     */
    updateNotificationCount(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    /**
     * Add notification to dropdown
     * @param {Object} notification - Notification object
     */
    addNotificationToDropdown(notification) {
        const dropdown = document.querySelector('.notification-dropdown-menu');
        if (!dropdown) return;

        const notificationHtml = this.createNotificationHtml(notification);
        
        // Remove "No notifications" message if exists
        const noNotifications = dropdown.querySelector('.no-notifications');
        if (noNotifications) {
            noNotifications.remove();
        }

        // Add new notification at the top
        dropdown.insertAdjacentHTML('afterbegin', notificationHtml);

        // Limit to 10 notifications in dropdown
        const notifications = dropdown.querySelectorAll('.notification-item');
        if (notifications.length > 10) {
            notifications[notifications.length - 1].remove();
        }
    }

    /**
     * Create notification HTML
     * @param {Object} notification - Notification object
     * @returns {string} HTML string
     */
    createNotificationHtml(notification) {
        const priorityClass = this.getPriorityClass(notification.priority);
        const timeAgo = this.formatTimeAgo(notification.created_at);
        
        return `
            <div class="notification-item ${priorityClass}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                    <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
                <div class="notification-actions">
                    <button class="btn btn-sm btn-outline-primary mark-read" data-id="${notification.id}">
                        <i class="fas fa-check"></i>
                    </button>
                    ${notification.action_url ? `
                        <a href="${notification.action_url}" class="btn btn-sm btn-primary">
                            ${notification.action_text || 'View'}
                        </a>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Get priority CSS class
     * @param {string} priority - Priority level
     * @returns {string} CSS class
     */
    getPriorityClass(priority) {
        const classes = {
            'high': 'notification-high',
            'normal': 'notification-normal',
            'low': 'notification-low'
        };
        return classes[priority] || 'notification-normal';
    }

    /**
     * Show browser notification
     * @param {Object} notification - Notification object
     */
    showBrowserNotification(notification) {
        if (Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico',
                tag: `notification-${notification.id}`
            });
        }
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        if (this.notificationSound) {
            this.notificationSound.play().catch(e => {
                console.log('Could not play notification sound:', e);
            });
        }
    }

    /**
     * Show toast notification
     * @param {Object} notification - Notification object
     */
    showToastNotification(notification) {
        // Using SweetAlert2 for toast notifications
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                icon: this.getNotificationIcon(notification.type),
                title: notification.title,
                text: notification.message
            });
        }
    }

    /**
     * Get notification icon based on type
     * @param {string} type - Notification type
     * @returns {string} Icon name
     */
    getNotificationIcon(type) {
        const icons = {
            'system': 'info',
            'announcement': 'info',
            'alert': 'warning',
            'info': 'info'
        };
        return icons[type] || 'info';
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Request notification permission
        this.requestNotificationPermission();

        // Mark notification as read
        document.addEventListener('click', (e) => {
            if (e.target.closest('.mark-read')) {
                const button = e.target.closest('.mark-read');
                const notificationId = button.dataset.id;
                this.markAsRead(notificationId);
            }
        });

        // Clear all notifications
        const clearAllBtn = document.querySelector('.clear-all-notifications');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => {
                this.clearAllNotifications();
            });
        }
    }

    /**
     * Request browser notification permission
     */
    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    /**
     * Mark notification as read
     * @param {number} notificationId - Notification ID
     */
    markAsRead(notificationId) {
        fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove notification from dropdown
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.remove();
                }
                
                // Update count
                this.updateNotificationCount(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    /**
     * Clear all notifications
     */
    clearAllNotifications() {
        fetch('/api/notifications/clear-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear dropdown
                const dropdown = document.querySelector('.notification-dropdown-menu');
                if (dropdown) {
                    dropdown.innerHTML = '<div class="no-notifications p-3 text-center text-muted">No notifications</div>';
                }
                
                // Update count
                this.updateNotificationCount(0);
            }
        })
        .catch(error => {
            console.error('Error clearing notifications:', error);
        });
    }

    /**
     * Load initial unread count
     */
    loadUnreadCount() {
        fetch('/api/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                this.updateNotificationCount(data.count);
            })
            .catch(error => {
                console.error('Error loading unread count:', error);
            });
    }

    /**
     * Format time ago
     * @param {string} dateString - Date string
     * @returns {string} Formatted time
     */
    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) {
            return 'Just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Disconnect Echo
     */
    disconnect() {
        if (this.echo) {
            this.echo.disconnect();
        }
    }
}

// Initialize notification manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.notificationManager) {
        window.notificationManager.disconnect();
    }
});

export default NotificationManager;
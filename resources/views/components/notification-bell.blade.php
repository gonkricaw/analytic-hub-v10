<!-- Notification Bell Component -->
<div class="nav-item dropdown no-arrow mx-1 notification-bell">
    <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell fa-fw"></i>
        <!-- Counter - Alerts -->
        <span class="badge badge-danger badge-counter notification-badge" style="display: none;">0</span>
    </a>
    <!-- Dropdown - Alerts -->
    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
         aria-labelledby="alertsDropdown" style="width: 400px; max-height: 500px; overflow-y: auto;">
        <h6 class="dropdown-header">
            <i class="fas fa-bell me-2"></i>
            Notifications
            <span class="float-end">
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </span>
        </h6>
        
        <!-- Notifications Container -->
        <div id="notification-dropdown-container">
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0 text-muted small">Loading notifications...</p>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="dropdown-footer text-center border-top">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAllNotificationsAsRead()">
                    <i class="fas fa-check-double"></i> Mark All Read
                </button>
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-list"></i> View All
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.notification-bell .dropdown-list {
    border: 0;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.notification-bell .dropdown-header {
    background-color: #4e73df;
    color: white;
    padding: 1rem;
    margin: 0;
    border-radius: 0.35rem 0.35rem 0 0;
}

.notification-bell .dropdown-footer {
    background-color: #f8f9fc;
    padding: 0.75rem;
    margin: 0;
    border-radius: 0 0 0.35rem 0.35rem;
}

.notification-bell-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e3e6f0;
    transition: background-color 0.15s ease-in-out;
    cursor: pointer;
}

.notification-bell-item:hover {
    background-color: #f8f9fc;
}

.notification-bell-item:last-child {
    border-bottom: none;
}

.notification-bell-item.unread {
    background-color: #f8f9fc;
    border-left: 3px solid #4e73df;
}

.notification-bell-item .notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.notification-bell-item .notification-content {
    flex: 1;
    min-width: 0;
}

.notification-bell-item .notification-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    line-height: 1.2;
}

.notification-bell-item .notification-message {
    font-size: 0.8rem;
    color: #858796;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.notification-bell-item .notification-time {
    font-size: 0.75rem;
    color: #858796;
}

.notification-bell-item .notification-actions {
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
}

.notification-bell-item:hover .notification-actions {
    opacity: 1;
}

.badge-counter {
    position: absolute;
    top: -2px;
    right: -6px;
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e74a3b;
    color: white;
    border: 2px solid white;
}

.notification-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: #858796;
}

.notification-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}
</style>

<script>
// Notification Bell JavaScript
$(document).ready(function() {
    // Load initial notifications
    loadNotificationDropdown();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        loadNotificationDropdown();
        updateNotificationBadge();
    }, 30000);
    
    // Load notifications when dropdown is opened
    $('#alertsDropdown').on('click', function() {
        loadNotificationDropdown();
    });
});

function loadNotificationDropdown() {
    $.ajax({
        url: '{{ route("user.notifications.get") }}',
        data: {
            limit: 5,
            dropdown: true
        },
        success: function(response) {
            $('#notification-dropdown-container').html(response.html);
            updateNotificationBadge(response.unread_count);
        },
        error: function() {
            $('#notification-dropdown-container').html(
                '<div class="notification-empty">' +
                '<i class="fas fa-exclamation-triangle"></i>' +
                '<p class="mb-0">Failed to load notifications</p>' +
                '</div>'
            );
        }
    });
}

function updateNotificationBadge(count = null) {
    if (count === null) {
        // Fetch current count
        $.ajax({
            url: '{{ route("api.notifications.unread-count") }}',
            success: function(response) {
                updateBadgeDisplay(response.count || 0);
            }
        });
    } else {
        updateBadgeDisplay(count);
    }
}

function updateBadgeDisplay(count) {
    const $badge = $('.notification-badge');
    
    if (count > 0) {
        $badge.text(count > 99 ? '99+' : count).show();
    } else {
        $badge.hide();
    }
}

function markNotificationAsReadFromDropdown(notificationId) {
    $.ajax({
        url: '{{ route("user.notifications.mark-read", ":id") }}'.replace(':id', notificationId),
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Update the notification item in dropdown
                const $item = $(`.notification-bell-item[data-id="${notificationId}"]`);
                $item.removeClass('unread');
                $item.find('.mark-read-btn').remove();
                
                // Update badge
                updateNotificationBadge();
            }
        }
    });
}

function markAllNotificationsAsRead() {
    $.ajax({
        url: '{{ route("user.notifications.mark-all-read") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Update all notification items in dropdown
                $('.notification-bell-item.unread').removeClass('unread');
                $('.mark-read-btn').remove();
                
                // Update badge
                updateNotificationBadge(0);
                
                // Show success message
                showToast('All notifications marked as read', 'success');
            }
        },
        error: function() {
            showToast('Failed to mark notifications as read', 'error');
        }
    });
}

function showNotificationFromDropdown(notificationId) {
    // Mark as read and redirect to notifications page
    markNotificationAsReadFromDropdown(notificationId);
    
    // Close dropdown
    $('.dropdown-toggle').dropdown('hide');
    
    // Redirect to notifications page with specific notification
    window.location.href = '{{ route("notifications.index") }}#notification-' + notificationId;
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toastClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    
    const toast = $(`
        <div class="toast align-items-center text-white ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    // Add to toast container (create if doesn't exist)
    if ($('#toast-container').length === 0) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    $('#toast-container').append(toast);
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}
</script>
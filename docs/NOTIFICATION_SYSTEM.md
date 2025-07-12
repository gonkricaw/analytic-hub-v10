# Notification System Documentation

## Overview

The Analytics Hub notification system provides a comprehensive solution for managing and delivering notifications to users. It supports various notification types, priorities, targeting options, scheduling, and real-time delivery.

## Features

### Core Features
- **Multi-type Notifications**: System, announcement, alert, and reminder notifications
- **Priority Levels**: High, medium, and low priority notifications
- **Flexible Targeting**: All users, specific users, role-based, or active/inactive users
- **Scheduling**: Schedule notifications for future delivery
- **Expiry Management**: Set expiration dates for notifications
- **Read/Unread Tracking**: Track notification read status per user
- **Dismissal Support**: Allow users to dismiss notifications
- **Rich Content**: Support for HTML content, action URLs, and custom styling

### User Interface
- **Notification Bell**: Real-time notification bell with unread counter
- **Dropdown Preview**: Quick preview of recent notifications
- **Notification Center**: Comprehensive notification management interface
- **Admin Interface**: Full CRUD operations for notification management

## Database Schema

### Notifications Table
```sql
CREATE TABLE idbi_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('system', 'announcement', 'alert', 'reminder') NOT NULL DEFAULT 'system',
    priority ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'medium',
    category VARCHAR(100),
    message TEXT NOT NULL,
    action_url VARCHAR(500),
    action_text VARCHAR(100),
    target_type ENUM('all_users', 'specific_users', 'role_based', 'active_users', 'inactive_users') NOT NULL,
    target_user_ids JSON,
    target_role_ids JSON,
    status ENUM('draft', 'scheduled', 'sent', 'cancelled') NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_expires_at (expires_at)
);
```

### User Notifications Table
```sql
CREATE TABLE idbi_user_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_id BIGINT UNSIGNED NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    is_dismissed BOOLEAN NOT NULL DEFAULT FALSE,
    dismissed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_notification (user_id, notification_id),
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_user_dismissed (user_id, is_dismissed)
);
```

## Models

### Notification Model
Location: `app/Models/Notification.php`

**Key Methods:**
- `scopeActive()`: Get active (non-expired) notifications
- `scopeByType($type)`: Filter by notification type
- `scopeByPriority($priority)`: Filter by priority
- `scopeScheduled()`: Get scheduled notifications
- `scopeExpired()`: Get expired notifications
- `isExpired()`: Check if notification is expired
- `getTargetUsersAttribute()`: Get target users based on targeting rules

### UserNotification Model
Location: `app/Models/UserNotification.php`

**Key Methods:**
- `scopeUnread()`: Get unread notifications
- `scopeRead()`: Get read notifications
- `scopeNotDismissed()`: Get non-dismissed notifications
- `markAsRead()`: Mark notification as read
- `markAsDismissed()`: Mark notification as dismissed

## Services

### NotificationService
Location: `app/Services/NotificationService.php`

**Key Methods:**
- `createNotification($data)`: Create a new notification
- `sendNotification($notification)`: Send notification to target users
- `scheduleNotification($notification, $scheduledAt)`: Schedule notification
- `getUserNotifications($userId, $filters)`: Get user notifications with filters
- `markAsRead($userId, $notificationId)`: Mark notification as read
- `markAllAsRead($userId)`: Mark all notifications as read
- `dismissNotification($userId, $notificationId)`: Dismiss notification
- `getUnreadCount($userId)`: Get unread notification count
- `getNotificationStats($userId)`: Get notification statistics
- `processScheduledNotifications()`: Process scheduled notifications
- `cleanupExpiredNotifications($days)`: Cleanup expired notifications

## Controllers

### NotificationController
Location: `app/Http/Controllers/NotificationController.php`

**Admin Routes:**
- `GET /admin/notifications` - List notifications
- `GET /admin/notifications/data` - DataTable data
- `GET /admin/notifications/create` - Create form
- `POST /admin/notifications` - Store notification
- `GET /admin/notifications/{id}` - Show notification
- `GET /admin/notifications/{id}/edit` - Edit form
- `PUT /admin/notifications/{id}` - Update notification
- `DELETE /admin/notifications/{id}` - Delete notification
- `GET /admin/notifications/statistics` - Get statistics

**User Routes:**
- `GET /notifications` - Notification center
- `GET /user/notifications` - Get user notifications
- `POST /user/notifications/{id}/read` - Mark as read
- `POST /user/notifications/read-all` - Mark all as read
- `POST /user/notifications/{id}/dismiss` - Dismiss notification
- `GET /user/notifications/stats` - Get user stats

**API Routes:**
- `GET /api/user/notifications` - Get notifications (API)
- `POST /api/user/notifications/{id}/read` - Mark as read (API)
- `POST /api/user/notifications/{id}/dismiss` - Dismiss (API)
- `POST /api/user/notifications/read-all` - Mark all as read (API)
- `GET /api/user/notifications/stats` - Get stats (API)
- `GET /api/user/notifications/unread-count` - Get unread count (API)

## Console Commands

### ProcessScheduledNotifications
Location: `app/Console/Commands/ProcessScheduledNotifications.php`

**Usage:**
```bash
php artisan notifications:process-scheduled [--limit=100]
```

**Scheduled:** Every 5 minutes via Kernel.php

### CleanupExpiredNotifications
Location: `app/Console/Commands/CleanupExpiredNotifications.php`

**Usage:**
```bash
php artisan notifications:cleanup-expired [--days=30]
```

**Scheduled:** Daily at 4:00 AM via Kernel.php

### NotificationStats
Location: `app/Console/Commands/NotificationStats.php`

**Usage:**
```bash
php artisan notifications:stats [--user-id=1]
```

## Views

### Admin Views
- `resources/views/admin/notifications/index.blade.php` - Notification list
- `resources/views/admin/notifications/create.blade.php` - Create form
- `resources/views/admin/notifications/edit.blade.php` - Edit form
- `resources/views/admin/notifications/show.blade.php` - Notification details

### User Views
- `resources/views/notifications/index.blade.php` - Notification center
- `resources/views/notifications/partials/notification-item.blade.php` - Notification item
- `resources/views/notifications/partials/notification-detail.blade.php` - Detail modal
- `resources/views/notifications/partials/dropdown-item.blade.php` - Dropdown item

### Components
- `resources/views/components/notification-bell.blade.php` - Notification bell

## Styling

### CSS Files
- `public/css/notifications.css` - Notification-specific styles
- Integrated with `layouts/app.blade.php` for notification bell styles

## Usage Examples

### Creating a Notification
```php
use App\Services\NotificationService;

$notificationService = new NotificationService();

$notification = $notificationService->createNotification([
    'title' => 'Welcome to Analytics Hub',
    'type' => 'announcement',
    'priority' => 'high',
    'message' => 'Welcome message here...',
    'target_type' => 'all_users',
    'action_url' => route('dashboard'),
    'action_text' => 'Get Started'
]);

// Send immediately
$notificationService->sendNotification($notification);

// Or schedule for later
$notificationService->scheduleNotification($notification, now()->addHours(2));
```

### Getting User Notifications
```php
$notifications = $notificationService->getUserNotifications(auth()->id(), [
    'status' => 'unread',
    'type' => 'alert',
    'limit' => 10
]);
```

### Marking as Read
```php
$notificationService->markAsRead(auth()->id(), $notificationId);
```

### Getting Statistics
```php
$stats = $notificationService->getNotificationStats(auth()->id());
// Returns: ['total' => 50, 'unread' => 5, 'read' => 45, 'high_priority' => 3]
```

## Testing

### Test Seeder
Location: `database/seeders/NotificationTestSeeder.php`

**Usage:**
```bash
php artisan db:seed --class=NotificationTestSeeder
```

Creates test notifications with various types, priorities, and targeting options.

### Manual Testing
1. Run the test seeder to create sample notifications
2. Visit `/admin/notifications` to manage notifications
3. Visit `/notifications` to view user notification center
4. Check the notification bell in the main navigation
5. Test marking notifications as read/dismissed
6. Test filtering and searching functionality

## Security Considerations

1. **Authorization**: All admin routes require appropriate permissions
2. **User Isolation**: Users can only access their own notifications
3. **Input Validation**: All inputs are validated and sanitized
4. **XSS Protection**: HTML content is properly escaped
5. **CSRF Protection**: All forms include CSRF tokens
6. **Rate Limiting**: API endpoints should implement rate limiting

## Performance Optimization

1. **Database Indexing**: Proper indexes on frequently queried columns
2. **Eager Loading**: Relationships are eager loaded to prevent N+1 queries
3. **Pagination**: Large result sets are paginated
4. **Caching**: Consider caching notification counts and statistics
5. **Cleanup**: Automated cleanup of old notifications

## Future Enhancements

1. **Real-time Updates**: WebSocket/Pusher integration for real-time notifications
2. **Email Notifications**: Send notifications via email
3. **Push Notifications**: Browser push notifications
4. **Notification Templates**: Predefined notification templates
5. **Advanced Targeting**: More sophisticated targeting rules
6. **Analytics**: Detailed notification analytics and reporting
7. **Bulk Operations**: Bulk notification management
8. **Notification Preferences**: User notification preferences

## Troubleshooting

### Common Issues

1. **Notifications not appearing**: Check if user is in target audience
2. **Scheduled notifications not sending**: Ensure cron job is running
3. **Performance issues**: Check database indexes and query optimization
4. **Permission errors**: Verify user roles and permissions

### Debug Commands
```bash
# Check notification stats
php artisan notifications:stats

# Process scheduled notifications manually
php artisan notifications:process-scheduled

# Clean up old notifications
php artisan notifications:cleanup-expired --days=7
```

## Support

For technical support or questions about the notification system, please refer to the main project documentation or contact the development team.
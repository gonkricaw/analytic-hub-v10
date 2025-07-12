# WebSocket/Pusher Integration Documentation

## Overview

This document provides comprehensive instructions for setting up and configuring WebSocket/Pusher integration in Analytics Hub for real-time notifications and broadcasting.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Pusher Account Setup](#pusher-account-setup)
3. [Environment Configuration](#environment-configuration)
4. [Broadcasting Configuration](#broadcasting-configuration)
5. [Channel Authorization](#channel-authorization)
6. [Queue Worker Setup](#queue-worker-setup)
7. [Frontend Integration](#frontend-integration)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)
10. [Security Considerations](#security-considerations)

## Prerequisites

### Required Packages

The following packages are already installed in the project:

- **Backend**: `pusher/pusher-php-server` (^7.2)
- **Frontend**: `laravel-echo` (^2.1.6), `pusher-js` (^8.4.0)

### System Requirements

- PHP 8.2+
- Laravel 12.0+
- Node.js 16+ (for frontend assets)
- Active internet connection for Pusher service

## Pusher Account Setup

### 1. Create Pusher Account

1. Visit [https://pusher.com](https://pusher.com)
2. Sign up for a free account
3. Create a new app in your dashboard
4. Choose your preferred cluster (e.g., `us-east-1`, `eu-west-1`)

### 2. Get Credentials

From your Pusher app dashboard, collect:

- **App ID**: Your application identifier
- **Key**: Public key for client-side connections
- **Secret**: Private key for server-side operations
- **Cluster**: Geographic cluster location

## Environment Configuration

### 1. Update .env File

Add the following configuration to your `.env` file:

```env
# Broadcasting Configuration
BROADCAST_CONNECTION=pusher

# Pusher Configuration
PUSHER_APP_ID=your_app_id_here
PUSHER_APP_KEY=your_app_key_here
PUSHER_APP_SECRET=your_app_secret_here
PUSHER_APP_CLUSTER=your_cluster_here
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

### 2. Queue Configuration

Ensure your queue is properly configured:

```env
QUEUE_CONNECTION=database
```

**Note**: For production, consider using Redis or other queue drivers for better performance.

## Broadcasting Configuration

### 1. Broadcasting Config

The broadcasting configuration is located in `config/broadcasting.php`. The Pusher connection is already configured to use environment variables.

### 2. Service Provider Registration

The `BroadcastServiceProvider` is automatically registered in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
];
```

## Channel Authorization

### 1. Channel Routes

Channel authorization is defined in `routes/channels.php`:

#### Private User Channels
```php
// Format: user.{userId}
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (int) $user->id === (int) $userId;
});
```

#### Admin Channels
```php
// Admin-only channel
Broadcast::channel('admin', function (User $user) {
    return $user->hasRole('admin');
});
```

#### Role-based Channels
```php
// Format: role.{roleName}
Broadcast::channel('role.{roleName}', function (User $user, string $roleName) {
    return $user->hasRole($roleName);
});
```

#### System Channels
```php
// System-wide notifications
Broadcast::channel('system', function (User $user) {
    return true; // All authenticated users
});
```

### 2. Authentication Endpoint

The broadcasting authentication endpoint is automatically registered at `/broadcasting/auth` by the `BroadcastServiceProvider`.

## Queue Worker Setup

### 1. Using Artisan Command

Start the broadcast queue worker using the custom command:

```bash
php artisan broadcast:start
```

#### Command Options

```bash
php artisan broadcast:start \
    --queue=default \
    --timeout=60 \
    --memory=128 \
    --tries=3 \
    --delay=5
```

### 2. Using Batch File (Windows)

For Windows users, use the provided batch file:

```cmd
start-broadcast-worker.bat
```

### 3. Using PowerShell Script (Windows)

For advanced Windows management:

```powershell
# Start worker
.\broadcast-worker.ps1 -Action start

# Start with auto-restart
.\broadcast-worker.ps1 -Action start -AutoRestart

# Check status
.\broadcast-worker.ps1 -Action status

# Monitor worker
.\broadcast-worker.ps1 -Action monitor

# Stop worker
.\broadcast-worker.ps1 -Action stop
```

### 4. Production Setup

For production environments, use a process manager like Supervisor:

```ini
[program:analytics-hub-broadcast-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/analytics-hub/artisan broadcast:start
directory=/path/to/analytics-hub
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/analytics-hub/storage/logs/broadcast-worker.log
```

## Frontend Integration

### 1. Laravel Echo Configuration

Laravel Echo is configured in `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

if (process.env.MIX_PUSHER_APP_KEY) {
    window.Pusher = Pusher;
    
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.MIX_PUSHER_APP_KEY,
        cluster: process.env.MIX_PUSHER_APP_CLUSTER,
        forceTLS: true,
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        },
    });
}
```

### 2. Environment Variables for Frontend

Add to your `.env` file:

```env
MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 3. Notification Manager

The `NotificationManager` class in `resources/js/notifications.js` handles:

- Real-time notification reception
- UI updates
- Sound notifications
- Browser notifications
- Notification counting

## Testing

### 1. Test Broadcasting Setup

```bash
# Test Pusher connection
php artisan tinker

# In tinker:
use App\Events\NotificationSent;
use App\Models\User;
use App\Models\Notification;

$user = User::first();
$notification = Notification::first();
event(new NotificationSent($notification, $user));
```

### 2. Frontend Testing

Open browser console and check for:

```javascript
// Check if Echo is loaded
console.log(window.Echo);

// Check Pusher connection
console.log(window.Echo.connector.pusher.connection.state);

// Listen for test events
Echo.private('user.' + userId)
    .listen('notification.sent', (e) => {
        console.log('Notification received:', e);
    });
```

### 3. Using Test Seeder

Run the notification test seeder to generate test notifications:

```bash
php artisan db:seed --class=NotificationTestSeeder
```

## Troubleshooting

### Common Issues

#### 1. Connection Refused

**Problem**: Cannot connect to Pusher

**Solutions**:
- Verify Pusher credentials in `.env`
- Check internet connectivity
- Ensure correct cluster setting
- Verify Pusher app is active

#### 2. Authentication Failed

**Problem**: Private channel authorization fails

**Solutions**:
- Check CSRF token is present
- Verify user is authenticated
- Check channel authorization logic
- Ensure `/broadcasting/auth` endpoint is accessible

#### 3. Events Not Broadcasting

**Problem**: Events are fired but not received

**Solutions**:
- Ensure queue worker is running
- Check event implements `ShouldBroadcast`
- Verify `broadcastOn()` returns correct channels
- Check `shouldBroadcast()` returns true

#### 4. Frontend Not Receiving Events

**Problem**: Backend broadcasts but frontend doesn't receive

**Solutions**:
- Check Echo configuration
- Verify channel names match
- Ensure user is subscribed to correct channel
- Check browser console for errors

### Debug Commands

```bash
# Check queue status
php artisan queue:work --once

# Monitor queue in real-time
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear queue
php artisan queue:clear
```

### Log Files

Check these log files for debugging:

- `storage/logs/laravel.log` - General application logs
- `storage/logs/broadcast-worker.log` - Broadcast worker logs
- Browser console - Frontend errors
- Pusher dashboard - Connection and event logs

## Security Considerations

### 1. Environment Variables

- Never commit `.env` file to version control
- Use different Pusher apps for different environments
- Rotate Pusher secrets regularly

### 2. Channel Authorization

- Always validate user permissions in channel callbacks
- Use strict comparison for user IDs
- Implement proper role checking
- Log authorization failures

### 3. Rate Limiting

Implement rate limiting for broadcasting endpoints:

```php
// In RouteServiceProvider
RateLimiter::for('broadcasting', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 4. Data Sanitization

Ensure all broadcast data is properly sanitized:

```php
public function broadcastWith(): array
{
    return [
        'id' => $this->notification->id,
        'title' => e($this->notification->title), // Escape HTML
        'message' => e($this->notification->message),
        // ... other fields
    ];
}
```

## Performance Optimization

### 1. Queue Configuration

For high-volume applications:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. Multiple Workers

Run multiple queue workers:

```bash
# Start multiple workers
php artisan queue:work --queue=default &
php artisan queue:work --queue=default &
php artisan queue:work --queue=default &
```

### 3. Event Optimization

- Use `ShouldBroadcastNow` for immediate broadcasting
- Implement `shouldBroadcast()` to conditionally broadcast
- Minimize data in `broadcastWith()`
- Use appropriate channel types

## Monitoring and Maintenance

### 1. Health Checks

Implement health checks for:

- Pusher connection status
- Queue worker status
- Failed job count
- Broadcasting latency

### 2. Metrics

Monitor:

- Events broadcast per minute
- Channel subscription count
- Authentication success rate
- Queue processing time

### 3. Maintenance Tasks

```bash
# Clean up old failed jobs
php artisan queue:prune-failed --hours=48

# Monitor queue size
php artisan queue:monitor default:50,notifications:20

# Restart workers (for code updates)
php artisan queue:restart
```

## Conclusion

This WebSocket/Pusher integration provides real-time notification capabilities for Analytics Hub. Follow this documentation for proper setup, testing, and maintenance of the broadcasting system.

For additional support:

- Check Laravel Broadcasting documentation
- Review Pusher documentation
- Monitor application logs
- Test thoroughly in development before deploying to production
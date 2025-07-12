# Analytics Hub - Email Queue System

This document provides comprehensive information about the Analytics Hub email queue system, including setup, usage, monitoring, and troubleshooting.

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Setup and Configuration](#setup-and-configuration)
4. [Queue Management](#queue-management)
5. [Monitoring and Statistics](#monitoring-and-statistics)
6. [Command Line Tools](#command-line-tools)
7. [Web Interface](#web-interface)
8. [Production Deployment](#production-deployment)
9. [Troubleshooting](#troubleshooting)
10. [API Reference](#api-reference)

## Overview

The Analytics Hub email queue system provides a robust, scalable solution for managing email delivery in the application. It handles email queuing, processing, retry logic, delivery tracking, and comprehensive monitoring.

### Key Components

- **EmailQueue Model**: Database model for email queue records
- **EmailQueueService**: Core service for email queue operations
- **SendEmailJob**: Laravel job for processing email delivery
- **ProcessEmailQueue Command**: Console command for queue processing
- **EmailQueueMonitor Command**: Console command for monitoring and maintenance
- **EmailQueueController**: Web interface for queue management

## Features

### Core Functionality
- ✅ **Email Queuing**: Queue emails for background processing
- ✅ **Retry Logic**: Automatic retry with exponential backoff (max 3 attempts)
- ✅ **Priority Handling**: Support for urgent, high, normal, and low priority emails
- ✅ **Scheduled Delivery**: Schedule emails for future delivery
- ✅ **Bulk Operations**: Send, retry, and cancel multiple emails
- ✅ **Template Integration**: Full integration with email template system
- ✅ **Delivery Tracking**: Track email opens, clicks, and delivery status

### Monitoring and Management
- ✅ **Real-time Statistics**: Live queue statistics and performance metrics
- ✅ **Health Monitoring**: Automated health checks and alerts
- ✅ **Failed Email Handling**: Comprehensive failed email management
- ✅ **Queue Cleanup**: Automated cleanup of old records
- ✅ **Web Interface**: Full-featured web interface for queue management
- ✅ **Command Line Tools**: Powerful CLI tools for automation

### Advanced Features
- ✅ **Email Types**: Support for transactional, notification, marketing, and system emails
- ✅ **Attachment Support**: Handle email attachments
- ✅ **Variable Replacement**: Dynamic content with template variables
- ✅ **Expiry Handling**: Automatic expiry of old queued emails
- ✅ **Rate Limiting**: Configurable sending rate limits
- ✅ **Multi-language Support**: Localized email content

## Setup and Configuration

### 1. Environment Configuration

Add the following to your `.env` file:

```env
# Queue Configuration
QUEUE_CONNECTION=database

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Analytics Hub"

# Email Queue Settings
EMAIL_QUEUE_MAX_ATTEMPTS=3
EMAIL_QUEUE_RETRY_DELAY=300
EMAIL_QUEUE_CLEANUP_DAYS=30
```

### 2. Database Migration

The email queue table is created automatically during installation:

```bash
php artisan migrate
```

### 3. Queue Worker Setup

#### For Development (Windows)

```bash
# Start queue worker manually
php artisan queue:work --queue=emails --tries=3 --timeout=60

# Or use the provided batch script
start-queue-worker.bat

# Or use PowerShell script
powershell -ExecutionPolicy Bypass -File queue-worker.ps1 -Action start
```

#### For Production (Linux)

1. Copy supervisor configuration:
```bash
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/analytics-hub-queue-worker.conf
```

2. Update paths in the configuration file

3. Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start analytics-hub-queue-worker:*
```

## Queue Management

### Adding Emails to Queue

```php
use App\Services\EmailQueueService;

$emailQueueService = app(EmailQueueService::class);

// Queue a simple email
$emailQueueService->queueEmail([
    'to' => 'user@example.com',
    'subject' => 'Welcome to Analytics Hub',
    'template_id' => 'welcome-email',
    'variables' => [
        'user_name' => 'John Doe',
        'login_url' => 'https://app.example.com/login'
    ],
    'priority' => 'high'
]);

// Queue with scheduling
$emailQueueService->queueEmail([
    'to' => 'user@example.com',
    'subject' => 'Scheduled Newsletter',
    'template_id' => 'newsletter',
    'scheduled_at' => now()->addHours(2),
    'priority' => 'normal'
]);
```

### Bulk Operations

```php
// Retry multiple emails
$emailQueueService->bulkRetry([1, 2, 3, 4, 5]);

// Cancel multiple emails
$emailQueueService->bulkCancel([6, 7, 8, 9, 10]);

// Send bulk emails
$emailQueueService->sendBulkEmails([
    [
        'to' => 'user1@example.com',
        'subject' => 'Bulk Email 1',
        'template_id' => 'bulk-template'
    ],
    [
        'to' => 'user2@example.com',
        'subject' => 'Bulk Email 2',
        'template_id' => 'bulk-template'
    ]
]);
```

## Monitoring and Statistics

### Queue Statistics

Get comprehensive queue statistics:

```php
$stats = $emailQueueService->getQueueStatistics();

// Returns:
// [
//     'total' => 1500,
//     'queued' => 45,
//     'processing' => 3,
//     'sent' => 1420,
//     'failed' => 25,
//     'cancelled' => 5,
//     'expired' => 2,
//     'success_rate' => 94.67,
//     'by_priority' => [...],
//     'by_type' => [...]
// ]
```

### Performance Metrics

```php
$metrics = $emailQueueService->getPerformanceMetrics();

// Returns:
// [
//     'avg_processing_time' => 2.5,
//     'emails_per_hour' => 450,
//     'peak_hour' => '14:00',
//     'failure_rate' => 1.2,
//     'retry_rate' => 5.8
// ]
```

## Command Line Tools

### Email Queue Processing

```bash
# Process queued emails
php artisan email:process-queue

# Process with options
php artisan email:process-queue --limit=50 --priority=high --dry-run

# Retry failed emails
php artisan email:process-queue --retry-failed

# Clean up old emails
php artisan email:process-queue --cleanup --days=30
```

### Email Queue Monitoring

```bash
# Show queue statistics
php artisan email:monitor --stats

# Perform health check
php artisan email:monitor --health

# Show failed emails
php artisan email:monitor --failed --limit=20

# Clean up old records
php artisan email:monitor --cleanup --days=30

# Check for alerts
php artisan email:monitor --alerts

# Watch mode (continuous monitoring)
php artisan email:monitor --watch --interval=30
```

### PowerShell Queue Management (Windows)

```powershell
# Start queue worker
.\queue-worker.ps1 -Action start

# Check status
.\queue-worker.ps1 -Action status

# Stop queue worker
.\queue-worker.ps1 -Action stop

# Restart queue worker
.\queue-worker.ps1 -Action restart

# Monitor continuously
.\queue-worker.ps1 -Action monitor
```

## Web Interface

### Accessing the Interface

Navigate to `/admin/email-queue` in your application to access the web interface.

### Features

- **Queue Overview**: Real-time statistics and charts
- **Email List**: Searchable and filterable email list
- **Individual Actions**: View, retry, cancel individual emails
- **Bulk Actions**: Select and perform actions on multiple emails
- **Advanced Filtering**: Filter by status, type, priority, date range
- **Export**: Export email data to CSV/Excel
- **Real-time Updates**: Auto-refreshing data

### Filtering Options

- **Status**: Queued, Processing, Sent, Failed, Cancelled, Expired
- **Type**: Transactional, Notification, Marketing, System
- **Priority**: Urgent, High, Normal, Low
- **Date Range**: Custom date range filtering
- **Search**: Search by recipient, subject, or content

## Production Deployment

### 1. Supervisor Configuration (Linux)

Use the provided `supervisor-queue-worker.conf` file:

```ini
[program:analytics-hub-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/analytic-hub-v10/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/analytics-hub-queue-worker.log
```

### 2. Cron Jobs

Add these cron jobs for maintenance:

```bash
# Process queue every minute
* * * * * cd /path/to/analytic-hub-v10 && php artisan email:process-queue --limit=100 >> /dev/null 2>&1

# Health check every 5 minutes
*/5 * * * * cd /path/to/analytic-hub-v10 && php artisan email:monitor --health >> /dev/null 2>&1

# Daily cleanup
0 2 * * * cd /path/to/analytic-hub-v10 && php artisan email:monitor --cleanup --days=30 >> /dev/null 2>&1
```

### 3. Monitoring Setup

```bash
# Set up log monitoring
tail -f storage/logs/laravel.log | grep "email"

# Monitor queue worker logs
tail -f /var/log/supervisor/analytics-hub-queue-worker.log

# Check queue worker status
sudo supervisorctl status analytics-hub-queue-worker:*
```

## Troubleshooting

### Common Issues

#### 1. Queue Worker Not Processing

**Symptoms**: Emails remain in queued status

**Solutions**:
```bash
# Check if worker is running
ps aux | grep "queue:work"

# Restart queue worker
sudo supervisorctl restart analytics-hub-queue-worker:*

# Check for errors
php artisan email:monitor --health
```

#### 2. High Failure Rate

**Symptoms**: Many emails in failed status

**Solutions**:
```bash
# Check failed emails
php artisan email:monitor --failed --limit=10

# Check SMTP configuration
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });

# Retry failed emails
php artisan email:process-queue --retry-failed
```

#### 3. Memory Issues

**Symptoms**: Queue worker crashes or high memory usage

**Solutions**:
```bash
# Reduce batch size
php artisan queue:work --max-jobs=50

# Increase memory limit
php -d memory_limit=512M artisan queue:work

# Monitor memory usage
php artisan email:monitor --watch
```

#### 4. Database Connection Issues

**Symptoms**: Database connection errors in logs

**Solutions**:
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Restart queue worker
sudo supervisorctl restart analytics-hub-queue-worker:*

# Check database configuration
php artisan config:cache
```

### Log Analysis

```bash
# Email-specific logs
grep "EmailQueue" storage/logs/laravel.log

# Failed job logs
grep "failed" storage/logs/laravel.log

# Performance logs
grep "processing_time" storage/logs/laravel.log
```

### Performance Optimization

1. **Database Indexing**: Ensure proper indexes on email queue table
2. **Queue Batching**: Process emails in batches
3. **Connection Pooling**: Use connection pooling for SMTP
4. **Caching**: Cache frequently accessed data
5. **Monitoring**: Regular performance monitoring

## API Reference

### EmailQueueService Methods

```php
// Queue single email
queueEmail(array $emailData): string

// Queue multiple emails
queueBulkEmails(array $emails): array

// Get queue statistics
getQueueStatistics(array $filters = []): array

// Get performance metrics
getPerformanceMetrics(): array

// Retry emails
bulkRetry(array $emailIds): array

// Cancel emails
bulkCancel(array $emailIds): array

// Clean up old emails
cleanupOldEmails(int $days = 30): int

// Get failed emails
getFailedEmails(int $limit = 100): Collection
```

### Email Data Structure

```php
[
    'to' => 'recipient@example.com',           // Required
    'cc' => 'cc@example.com',                  // Optional
    'bcc' => 'bcc@example.com',                // Optional
    'subject' => 'Email Subject',              // Required
    'body_html' => '<h1>HTML Content</h1>',    // Optional
    'body_text' => 'Plain text content',      // Optional
    'template_id' => 'template-slug',          // Optional
    'variables' => ['key' => 'value'],         // Optional
    'attachments' => ['/path/to/file.pdf'],    // Optional
    'priority' => 'high',                      // Optional: urgent, high, normal, low
    'email_type' => 'transactional',          // Optional: transactional, notification, marketing, system
    'scheduled_at' => '2024-01-01 12:00:00',  // Optional
    'expires_at' => '2024-01-02 12:00:00',    // Optional
    'language' => 'en',                       // Optional
    'category' => 'user-management'           // Optional
]
```

### Status Constants

```php
EmailQueue::STATUS_QUEUED     = 'queued';
EmailQueue::STATUS_PROCESSING = 'processing';
EmailQueue::STATUS_SENT       = 'sent';
EmailQueue::STATUS_FAILED     = 'failed';
EmailQueue::STATUS_CANCELLED  = 'cancelled';
EmailQueue::STATUS_EXPIRED    = 'expired';
```

### Priority Constants

```php
EmailQueue::PRIORITY_URGENT = 'urgent';
EmailQueue::PRIORITY_HIGH   = 'high';
EmailQueue::PRIORITY_NORMAL = 'normal';
EmailQueue::PRIORITY_LOW    = 'low';
```

### Type Constants

```php
EmailQueue::TYPE_TRANSACTIONAL = 'transactional';
EmailQueue::TYPE_NOTIFICATION  = 'notification';
EmailQueue::TYPE_MARKETING     = 'marketing';
EmailQueue::TYPE_SYSTEM        = 'system';
```

---

## Support

For additional support or questions about the email queue system:

1. Check the application logs: `storage/logs/laravel.log`
2. Run health checks: `php artisan email:monitor --health`
3. Review this documentation
4. Contact the development team

---

**Analytics Hub Email Queue System v1.0.0**  
*Last Updated: 2024-07-12*
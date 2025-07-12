<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 * 
 * Handles console command scheduling and registration for Analytics Hub.
 * Manages automated tasks including content expiry processing,
 * cleanup operations, and system maintenance.
 * 
 * @package App\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * 
     * Schedules automated tasks for content lifecycle management,
     * system cleanup, and maintenance operations.
     * 
     * @param Schedule $schedule Laravel scheduler instance
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // Content expiry processing - runs every hour
        $schedule->command('content:process-expired')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/content-expiry.log'));
        
        // Content expiry notifications - runs daily at 9 AM
        $schedule->command('content:notify-expiring')
                 ->dailyAt('09:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/content-notifications.log'));
        
        // Clean up old activity logs - runs weekly on Sunday at 2 AM
        $schedule->command('cleanup:activity-logs')
                 ->weeklyOn(0, '02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cleanup.log'));
        
        // Email queue processing - runs every minute
        $schedule->command('email:process-queue')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/email-queue.log'));
        
        // Email queue monitoring - runs every 5 minutes
        $schedule->command('email:monitor --alerts')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/email-monitor.log'));
        
        // Email queue cleanup - runs daily at 3 AM
        $schedule->command('email:monitor --cleanup --days=30')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/email-cleanup.log'));
        
        // Retry failed emails - runs every 30 minutes
        $schedule->command('email:process-queue --retry-failed --limit=100')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/email-retry.log'));
        
        // Clean up expired password reset tokens - runs daily at 3 AM
        $schedule->command('auth:clear-resets')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Update content statistics - runs every 30 minutes
        $schedule->command('content:update-stats')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Process email queue - runs every 5 minutes
        $schedule->command('queue:work --stop-when-empty --max-time=300')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Process scheduled notifications - runs every 5 minutes
        $schedule->command('notifications:process-scheduled --limit=100')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/notifications-scheduled.log'));
        
        // Clean up expired notifications - runs daily at 4 AM
        $schedule->command('notifications:cleanup-expired --days=30')
                 ->dailyAt('04:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/notifications-cleanup.log'));
    }

    /**
     * Register the commands for the application.
     * 
     * Automatically discovers and registers all commands in the Commands directory.
     * 
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
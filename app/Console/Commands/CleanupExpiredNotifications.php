<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * CleanupExpiredNotifications Command
 * 
 * Cleans up notifications that have expired.
 * This command should be run regularly via cron job or task scheduler.
 */
class CleanupExpiredNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:cleanup-expired 
                           {--dry-run : Show what would be cleaned up without actually cleaning}
                           {--days=30 : Clean up notifications expired more than X days ago}
                           {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired notifications and archive user notification records';

    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired notifications...');
        
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $force = $this->option('force');
        
        try {
            if ($dryRun) {
                $this->info('DRY RUN MODE - No notifications will be actually cleaned up');
                // In a real implementation, you would query for expired notifications
                // and display what would be cleaned up
                $this->info('Would clean up expired notifications here');
                return self::SUCCESS;
            }
            
            if (!$force) {
                if (!$this->confirm('Are you sure you want to clean up expired notifications?')) {
                    $this->info('Cleanup cancelled');
                    return self::SUCCESS;
                }
            }
            
            $cleaned = $this->notificationService->cleanupExpiredNotifications();
            
            if ($cleaned > 0) {
                $this->info("Successfully cleaned up {$cleaned} expired notifications");
                Log::info('Expired notifications cleaned up', [
                    'count' => $cleaned,
                    'command' => 'notifications:cleanup-expired'
                ]);
            } else {
                $this->info('No expired notifications to clean up');
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to clean up expired notifications: ' . $e->getMessage());
            Log::error('Failed to clean up expired notifications', [
                'error' => $e->getMessage(),
                'command' => 'notifications:cleanup-expired'
            ]);
            
            return self::FAILURE;
        }
    }
}
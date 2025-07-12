<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * ProcessScheduledNotifications Command
 * 
 * Processes notifications that are scheduled for delivery.
 * This command should be run regularly via cron job or task scheduler.
 */
class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:process-scheduled 
                           {--dry-run : Show what would be processed without actually processing}
                           {--limit=100 : Maximum number of notifications to process}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled notifications that are ready for delivery';

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
        $this->info('Processing scheduled notifications...');
        
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        
        try {
            if ($dryRun) {
                $this->info('DRY RUN MODE - No notifications will be actually processed');
                // In a real implementation, you would query for ready notifications
                // and display what would be processed
                $this->info('Would process scheduled notifications here');
                return self::SUCCESS;
            }
            
            $processed = $this->notificationService->processScheduledNotifications();
            
            if ($processed > 0) {
                $this->info("Successfully processed {$processed} scheduled notifications");
                Log::info('Scheduled notifications processed', [
                    'count' => $processed,
                    'command' => 'notifications:process-scheduled'
                ]);
            } else {
                $this->info('No scheduled notifications to process');
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to process scheduled notifications: ' . $e->getMessage());
            Log::error('Failed to process scheduled notifications', [
                'error' => $e->getMessage(),
                'command' => 'notifications:process-scheduled'
            ]);
            
            return self::FAILURE;
        }
    }
}
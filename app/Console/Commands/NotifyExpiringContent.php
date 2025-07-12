<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Class NotifyExpiringContent
 * 
 * Sends notifications for content that will expire soon in Analytics Hub.
 * Provides advance warning to content managers and authors.
 * 
 * Features:
 * - Configurable notification periods (1 day, 3 days, 1 week)
 * - Multiple notification channels
 * - Batch processing for performance
 * - Duplicate notification prevention
 * - Detailed reporting and logging
 * 
 * @package App\Console\Commands
 */
class NotifyExpiringContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:notify-expiring 
                           {--days=1,3,7 : Comma-separated list of days before expiry to notify}
                           {--dry-run : Show what notifications would be sent without sending them}
                           {--batch-size=50 : Number of items to process in each batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for content that will expire soon';

    /**
     * Execute the console command.
     * 
     * Identifies content approaching expiry and sends notifications
     * to relevant users based on configured notification periods.
     * 
     * @return int Command exit code
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $notificationDays = array_map('intval', explode(',', $this->option('days')));
        
        $this->info('Starting expiring content notification process...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
        }
        
        $this->info('Notification periods: ' . implode(', ', $notificationDays) . ' days before expiry');
        
        try {
            $totalNotifications = 0;
            $totalContent = 0;
            $errorCount = 0;
            
            foreach ($notificationDays as $days) {
                $this->info("\nProcessing content expiring in {$days} day(s)...");
                
                $expiringContent = $this->getExpiringContent($days);
                $contentCount = $expiringContent->count();
                $totalContent += $contentCount;
                
                if ($contentCount === 0) {
                    $this->line("No content found expiring in {$days} day(s).");
                    continue;
                }
                
                $this->info("Found {$contentCount} content items expiring in {$days} day(s).");
                
                $notificationCount = 0;
                
                // Process in batches
                $expiringContent->chunk($batchSize, function ($batch) use (
                    $isDryRun, $days, &$notificationCount, &$errorCount
                ) {
                    foreach ($batch as $content) {
                        try {
                            if (!$isDryRun) {
                                $sent = $this->sendExpiryWarningNotification($content, $days);
                                $notificationCount += $sent;
                            } else {
                                $this->line("Would notify for: {$content->title} (ID: {$content->id})");
                                $notificationCount++;
                            }
                            
                        } catch (\Exception $e) {
                            $errorCount++;
                            $this->error("Error processing content {$content->id}: {$e->getMessage()}");
                            Log::error('Content expiry notification error', [
                                'content_id' => $content->id,
                                'days_before_expiry' => $days,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                });
                
                $this->info("Sent {$notificationCount} notifications for content expiring in {$days} day(s).");
                $totalNotifications += $notificationCount;
            }
            
            // Generate summary report
            $this->generateSummaryReport($totalContent, $totalNotifications, $errorCount, $startTime);
            
            // Log the operation
            Log::info('Content expiry notification process completed', [
                'total_content_checked' => $totalContent,
                'total_notifications_sent' => $totalNotifications,
                'errors' => $errorCount,
                'notification_days' => $notificationDays,
                'dry_run' => $isDryRun,
                'execution_time' => microtime(true) - $startTime
            ]);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Fatal error during notification process: {$e->getMessage()}");
            Log::error('Fatal error in content expiry notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }
    
    /**
     * Get content that will expire in specified number of days.
     * 
     * @param int $days Number of days before expiry
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getExpiringContent(int $days)
    {
        $targetDate = Carbon::now()->addDays($days);
        $startOfDay = $targetDate->copy()->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();
        
        return Content::where('status', Content::STATUS_PUBLISHED)
                     ->whereNotNull('expires_at')
                     ->whereBetween('expires_at', [$startOfDay, $endOfDay])
                     ->with(['author', 'editor', 'roles'])
                     ->orderBy('expires_at')
                     ->get()
                     ->filter(function ($content) use ($days) {
                         // Check if notification was already sent for this period
                         return !$this->wasNotificationSent($content, $days);
                     });
    }
    
    /**
     * Check if notification was already sent for this content and period.
     * 
     * @param Content $content Content to check
     * @param int $days Days before expiry
     * @return bool True if notification was already sent
     */
    private function wasNotificationSent(Content $content, int $days): bool
    {
        $today = Carbon::now()->format('Y-m-d');
        
        return Notification::where('type', 'content_expiring')
                          ->whereJsonContains('data->content_id', $content->id)
                          ->whereJsonContains('data->days_before_expiry', $days)
                          ->whereDate('created_at', $today)
                          ->exists();
    }
    
    /**
     * Send expiry warning notification for content.
     * 
     * @param Content $content Content that will expire
     * @param int $days Days before expiry
     * @return int Number of notifications sent
     */
    private function sendExpiryWarningNotification(Content $content, int $days): int
    {
        try {
            // Get users who should be notified
            $usersToNotify = collect();
            
            // Add content author
            if ($content->author) {
                $usersToNotify->push($content->author);
            }
            
            // Add content editor
            if ($content->editor && $content->editor->id !== $content->author_id) {
                $usersToNotify->push($content->editor);
            }
            
            // Add users with content management permissions
            $contentManagers = User::whereHas('roles.permissions', function ($query) {
                $query->where('name', 'content.manage');
            })->get();
            
            $usersToNotify = $usersToNotify->merge($contentManagers)->unique('id');
            
            $notificationCount = 0;
            
            // Create notification for each user
            foreach ($usersToNotify as $user) {
                $message = $this->generateNotificationMessage($content, $days);
                $priority = $this->getNotificationPriority($days);
                
                Notification::create([
                    'title' => 'Content Expiring Soon',
                    'message' => $message,
                    'type' => 'content_expiring',
                    'priority' => $priority,
                    'data' => [
                        'content_id' => $content->id,
                        'content_title' => $content->title,
                        'expires_at' => $content->expires_at->toISOString(),
                        'days_before_expiry' => $days,
                        'action_url' => route('admin.contents.edit', $content->id)
                    ],
                    'user_id' => $user->id,
                    'is_read' => false
                ]);
                
                $notificationCount++;
            }
            
            // Log the notification activity
            activity()
                ->performedOn($content)
                ->withProperties([
                    'action' => 'content_expiry_warning',
                    'days_before_expiry' => $days,
                    'notifications_sent' => $notificationCount,
                    'expires_at' => $content->expires_at->toISOString()
                ])
                ->log("Expiry warning sent ({$days} days before expiry)");
            
            return $notificationCount;
            
        } catch (\Exception $e) {
            Log::warning('Failed to send content expiry warning', [
                'content_id' => $content->id,
                'days_before_expiry' => $days,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Generate notification message based on days before expiry.
     * 
     * @param Content $content Content that will expire
     * @param int $days Days before expiry
     * @return string Notification message
     */
    private function generateNotificationMessage(Content $content, int $days): string
    {
        $dayText = $days === 1 ? 'day' : 'days';
        $expiryDate = $content->expires_at->format('M j, Y \a\t g:i A');
        
        return "The content '{$content->title}' will expire in {$days} {$dayText} on {$expiryDate}. " .
               "Please review and extend the expiry date if needed.";
    }
    
    /**
     * Get notification priority based on days before expiry.
     * 
     * @param int $days Days before expiry
     * @return string Notification priority
     */
    private function getNotificationPriority(int $days): string
    {
        if ($days <= 1) {
            return 'high';
        } elseif ($days <= 3) {
            return 'normal';
        } else {
            return 'low';
        }
    }
    
    /**
     * Generate and display summary report.
     * 
     * @param int $totalContent Total content checked
     * @param int $totalNotifications Total notifications sent
     * @param int $errors Number of errors encountered
     * @param float $startTime Processing start time
     * @return void
     */
    private function generateSummaryReport(int $totalContent, int $totalNotifications, int $errors, float $startTime): void
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->info('\n=== CONTENT EXPIRY NOTIFICATION SUMMARY ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Content Checked', $totalContent],
                ['Notifications Sent', $totalNotifications],
                ['Errors Encountered', $errors],
                ['Execution Time', "{$executionTime} seconds"],
                ['Success Rate', $totalContent > 0 ? round((($totalContent - $errors) / $totalContent) * 100, 1) . '%' : 'N/A']
            ]
        );
        
        if ($errors > 0) {
            $this->warn("\n{$errors} errors occurred during processing. Check logs for details.");
        } else {
            $this->info('\nAll notifications processed successfully!');
        }
    }
}
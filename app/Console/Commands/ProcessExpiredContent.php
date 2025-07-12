<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Class ProcessExpiredContent
 * 
 * Handles automated processing of expired content in Analytics Hub.
 * Manages content lifecycle transitions, notifications, and cleanup.
 * 
 * Features:
 * - Automatic content expiry processing
 * - Status transitions for expired content
 * - Notification generation for stakeholders
 * - Audit logging and reporting
 * - Batch processing for performance
 * 
 * @package App\Console\Commands
 */
class ProcessExpiredContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:process-expired 
                           {--dry-run : Show what would be processed without making changes}
                           {--batch-size=100 : Number of items to process in each batch}
                           {--notify : Send notifications for expired content}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired content and update their status automatically';

    /**
     * Execute the console command.
     * 
     * Processes all expired content, updates their status,
     * and optionally sends notifications to relevant users.
     * 
     * @return int Command exit code
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $shouldNotify = $this->option('notify');
        
        $this->info('Starting expired content processing...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        try {
            // Get expired content that is still published
            $expiredContent = $this->getExpiredContent();
            $totalCount = $expiredContent->count();
            
            if ($totalCount === 0) {
                $this->info('No expired content found.');
                return self::SUCCESS;
            }
            
            $this->info("Found {$totalCount} expired content items to process.");
            
            $processedCount = 0;
            $errorCount = 0;
            $notificationsSent = 0;
            
            // Process in batches for better performance
            $expiredContent->chunk($batchSize, function ($batch) use (
                $isDryRun, $shouldNotify, &$processedCount, &$errorCount, &$notificationsSent
            ) {
                foreach ($batch as $content) {
                    try {
                        if (!$isDryRun) {
                            $this->processExpiredContent($content);
                            
                            if ($shouldNotify) {
                                $this->sendExpiryNotification($content);
                                $notificationsSent++;
                            }
                        }
                        
                        $processedCount++;
                        $this->line("Processed: {$content->title} (ID: {$content->id})");
                        
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->error("Error processing content {$content->id}: {$e->getMessage()}");
                        Log::error('Content expiry processing error', [
                            'content_id' => $content->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            });
            
            // Generate summary report
            $this->generateSummaryReport($processedCount, $errorCount, $notificationsSent, $startTime);
            
            // Log the operation
            Log::info('Content expiry processing completed', [
                'total_found' => $totalCount,
                'processed' => $processedCount,
                'errors' => $errorCount,
                'notifications_sent' => $notificationsSent,
                'dry_run' => $isDryRun,
                'execution_time' => microtime(true) - $startTime
            ]);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Fatal error during content expiry processing: {$e->getMessage()}");
            Log::error('Fatal error in content expiry processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }
    
    /**
     * Get expired content that needs processing.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getExpiredContent()
    {
        return Content::where('status', Content::STATUS_PUBLISHED)
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', Carbon::now())
                     ->with(['author', 'editor', 'roles'])
                     ->orderBy('expires_at')
                     ->get();
    }
    
    /**
     * Process a single expired content item.
     * 
     * @param Content $content Content to process
     * @return void
     */
    private function processExpiredContent(Content $content): void
    {
        DB::transaction(function () use ($content) {
            // Update content status to expired
            $content->update([
                'status' => Content::STATUS_ARCHIVED,
                'expired_at' => Carbon::now(),
                'editor_id' => null // System processed
            ]);
            
            // Log the expiry action
            activity()
                ->performedOn($content)
                ->withProperties([
                    'action' => 'content_expired',
                    'expired_at' => Carbon::now()->toISOString(),
                    'original_expires_at' => $content->expires_at->toISOString(),
                    'automated' => true
                ])
                ->log('Content automatically expired');
        });
    }
    
    /**
     * Send expiry notification to relevant users.
     * 
     * @param Content $content Expired content
     * @return void
     */
    private function sendExpiryNotification(Content $content): void
    {
        try {
            // Get users who should be notified (author, editors, admins)
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
            
            // Create notification for each user
            foreach ($usersToNotify as $user) {
                Notification::create([
                    'title' => 'Content Expired',
                    'message' => "The content '{$content->title}' has expired and been automatically archived.",
                    'type' => 'content_expired',
                    'priority' => 'normal',
                    'data' => [
                        'content_id' => $content->id,
                        'content_title' => $content->title,
                        'expired_at' => Carbon::now()->toISOString(),
                        'action_url' => route('admin.contents.show', $content->id)
                    ],
                    'user_id' => $user->id,
                    'is_read' => false
                ]);
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to send content expiry notification', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate and display summary report.
     * 
     * @param int $processed Number of items processed
     * @param int $errors Number of errors encountered
     * @param int $notifications Number of notifications sent
     * @param float $startTime Processing start time
     * @return void
     */
    private function generateSummaryReport(int $processed, int $errors, int $notifications, float $startTime): void
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->info('\n=== CONTENT EXPIRY PROCESSING SUMMARY ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Content Processed', $processed],
                ['Errors Encountered', $errors],
                ['Notifications Sent', $notifications],
                ['Execution Time', "{$executionTime} seconds"],
                ['Success Rate', $processed > 0 ? round((($processed - $errors) / $processed) * 100, 1) . '%' : 'N/A']
            ]
        );
        
        if ($errors > 0) {
            $this->warn("\n{$errors} errors occurred during processing. Check logs for details.");
        } else {
            $this->info('\nAll content processed successfully!');
        }
    }
}
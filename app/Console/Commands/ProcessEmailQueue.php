<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailQueue;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Exception;

/**
 * ProcessEmailQueue Command
 * 
 * Console command for processing the email queue.
 * Handles queued emails, failed email retry, and queue maintenance.
 * 
 * Features:
 * - Process pending emails
 * - Retry failed emails
 * - Clean up expired emails
 * - Queue monitoring and statistics
 * 
 * Usage:
 * php artisan email:process-queue
 * php artisan email:process-queue --retry-failed
 * php artisan email:process-queue --cleanup
 * 
 * @package App\Console\Commands
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class ProcessEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:process-queue 
                            {--retry-failed : Retry failed emails}
                            {--cleanup : Clean up old and expired emails}
                            {--limit=100 : Maximum number of emails to process}
                            {--timeout=300 : Maximum execution time in seconds}
                            {--priority=normal : Minimum priority to process (urgent,high,normal,low)}
                            {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     */
    protected $description = 'Process the email queue and handle email sending operations';

    /**
     * Priority mapping for filtering
     */
    protected array $priorityOrder = [
        'urgent' => 4,
        'high' => 3,
        'normal' => 2,
        'low' => 1
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('Starting email queue processing...');
        
        try {
            // Set execution time limit
            $timeout = (int) $this->option('timeout');
            set_time_limit($timeout);
            
            $processedCount = 0;
            
            if ($this->option('cleanup')) {
                $processedCount += $this->cleanupEmails();
            } elseif ($this->option('retry-failed')) {
                $processedCount += $this->retryFailedEmails();
            } else {
                $processedCount += $this->processQueuedEmails();
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $this->info("Email queue processing completed.");
            $this->info("Processed: {$processedCount} emails");
            $this->info("Execution time: {$executionTime} seconds");
            
            // Log the operation
            Log::info('Email queue processing completed', [
                'processed_count' => $processedCount,
                'execution_time' => $executionTime,
                'options' => $this->options()
            ]);
            
            return self::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('Email queue processing failed: ' . $e->getMessage());
            
            Log::error('Email queue processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }

    /**
     * Process queued emails
     * 
     * @return int Number of emails processed
     */
    protected function processQueuedEmails(): int
    {
        $limit = (int) $this->option('limit');
        $priority = $this->option('priority');
        $dryRun = $this->option('dry-run');
        
        $this->info("Processing queued emails (limit: {$limit}, priority: {$priority})...");
        
        // Build query for queued emails
        $query = EmailQueue::where('status', EmailQueue::STATUS_QUEUED)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            })
            ->whereRaw('attempts < max_attempts');
        
        // Apply priority filter
        if ($priority && isset($this->priorityOrder[$priority])) {
            $minPriorityValue = $this->priorityOrder[$priority];
            $allowedPriorities = array_keys(array_filter(
                $this->priorityOrder,
                fn($value) => $value >= $minPriorityValue
            ));
            $query->whereIn('priority', $allowedPriorities);
        }
        
        // Order by priority and creation time (PostgreSQL compatible)
        $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
              ->orderBy('created_at');
        
        $emails = $query->limit($limit)->get();
        
        if ($emails->isEmpty()) {
            $this->info('No queued emails found to process.');
            return 0;
        }
        
        $this->info("Found {$emails->count()} emails to process.");
        
        if ($dryRun) {
            $this->table(
                ['ID', 'To', 'Subject', 'Priority', 'Created'],
                $emails->map(function ($email) {
                    return [
                        $email->id,
                        $email->to_email,
                        substr($email->subject, 0, 50) . '...',
                        $email->priority,
                        $email->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            );
            
            $this->info('Dry run completed. No emails were actually processed.');
            return $emails->count();
        }
        
        $processedCount = 0;
        $bar = $this->output->createProgressBar($emails->count());
        $bar->start();
        
        foreach ($emails as $email) {
            try {
                // Update status to processing
                $email->update([
                    'status' => EmailQueue::STATUS_PROCESSING,
                    'attempts' => $email->attempts + 1,
                    'updated_at' => now()
                ]);
                
                // Dispatch the job
                SendEmailJob::dispatch($email->id)
                    ->onQueue($this->getQueueName($email->priority));
                
                $processedCount++;
                $bar->advance();
                
            } catch (Exception $e) {
                $this->error("\nFailed to process email {$email->id}: " . $e->getMessage());
                
                // Mark as failed
                $email->update([
                    'status' => EmailQueue::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'failed_at' => now()
                ]);
                
                Log::error('Failed to process email', [
                    'email_id' => $email->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $bar->finish();
        $this->newLine();
        
        return $processedCount;
    }

    /**
     * Retry failed emails
     * 
     * @return int Number of emails retried
     */
    protected function retryFailedEmails(): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        
        $this->info("Retrying failed emails (limit: {$limit})...");
        
        // Get failed emails that can be retried
        $emails = EmailQueue::where('status', EmailQueue::STATUS_FAILED)
            ->where('attempts', '<', 'max_attempts')
            ->where('failed_at', '>=', now()->subHours(24)) // Only retry emails failed in last 24 hours
            ->orderBy('priority', 'desc')
            ->orderBy('failed_at')
            ->limit($limit)
            ->get();
        
        if ($emails->isEmpty()) {
            $this->info('No failed emails found to retry.');
            return 0;
        }
        
        $this->info("Found {$emails->count()} failed emails to retry.");
        
        if ($dryRun) {
            $this->table(
                ['ID', 'To', 'Subject', 'Attempts', 'Failed At', 'Error'],
                $emails->map(function ($email) {
                    return [
                        $email->id,
                        $email->to_email,
                        substr($email->subject, 0, 30) . '...',
                        $email->attempts . '/' . $email->max_attempts,
                        $email->failed_at->format('Y-m-d H:i:s'),
                        substr($email->error_message, 0, 50) . '...'
                    ];
                })->toArray()
            );
            
            $this->info('Dry run completed. No emails were actually retried.');
            return $emails->count();
        }
        
        $retriedCount = 0;
        $bar = $this->output->createProgressBar($emails->count());
        $bar->start();
        
        foreach ($emails as $email) {
            try {
                // Reset status to queued
                $email->update([
                    'status' => EmailQueue::STATUS_QUEUED,
                    'error_message' => null,
                    'error_details' => null,
                    'failed_at' => null,
                    'updated_at' => now()
                ]);
                
                $retriedCount++;
                $bar->advance();
                
            } catch (Exception $e) {
                $this->error("\nFailed to retry email {$email->id}: " . $e->getMessage());
                
                Log::error('Failed to retry email', [
                    'email_id' => $email->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $bar->finish();
        $this->newLine();
        
        return $retriedCount;
    }

    /**
     * Clean up old and expired emails
     * 
     * @return int Number of emails cleaned up
     */
    protected function cleanupEmails(): int
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Cleaning up old and expired emails...');
        
        $cleanupCount = 0;
        
        // Clean up expired emails (older than 7 days and not sent)
        $expiredEmails = EmailQueue::where('status', '!=', EmailQueue::STATUS_SENT)
            ->where('created_at', '<', now()->subDays(7))
            ->where('attempts', '>=', 'max_attempts');
        
        $expiredCount = $expiredEmails->count();
        
        if ($expiredCount > 0) {
            $this->info("Found {$expiredCount} expired emails to clean up.");
            
            if (!$dryRun) {
                // Mark as expired instead of deleting
                $expiredEmails->update([
                    'status' => EmailQueue::STATUS_EXPIRED,
                    'updated_at' => now()
                ]);
                
                $cleanupCount += $expiredCount;
            }
        }
        
        // Clean up very old sent emails (older than 90 days)
        $oldSentEmails = EmailQueue::where('status', EmailQueue::STATUS_SENT)
            ->where('sent_at', '<', now()->subDays(90));
        
        $oldSentCount = $oldSentEmails->count();
        
        if ($oldSentCount > 0) {
            $this->info("Found {$oldSentCount} old sent emails to archive.");
            
            if (!$dryRun) {
                // For now, just log this. In production, you might want to archive to another table
                Log::info('Old sent emails found for archival', [
                    'count' => $oldSentCount,
                    'oldest_date' => $oldSentEmails->min('sent_at')
                ]);
                
                // Optionally delete very old records
                // $oldSentEmails->delete();
                // $cleanupCount += $oldSentCount;
            }
        }
        
        if ($dryRun) {
            $this->info('Dry run completed. No emails were actually cleaned up.');
            return $expiredCount + $oldSentCount;
        }
        
        return $cleanupCount;
    }

    /**
     * Get queue name based on priority
     * 
     * @param string $priority
     * @return string
     */
    protected function getQueueName(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'emails-urgent',
            'high' => 'emails-high',
            'normal' => 'emails',
            'low' => 'emails-low',
            default => 'emails'
        };
    }

    /**
     * Display queue statistics
     */
    protected function displayStatistics(): void
    {
        $stats = [
            'Queued' => EmailQueue::where('status', EmailQueue::STATUS_QUEUED)->count(),
            'Processing' => EmailQueue::where('status', EmailQueue::STATUS_PROCESSING)->count(),
            'Sent' => EmailQueue::where('status', EmailQueue::STATUS_SENT)->count(),
            'Failed' => EmailQueue::where('status', EmailQueue::STATUS_FAILED)->count(),
            'Cancelled' => EmailQueue::where('status', EmailQueue::STATUS_CANCELLED)->count(),
            'Expired' => EmailQueue::where('status', EmailQueue::STATUS_EXPIRED)->count(),
        ];
        
        $this->info('\nQueue Statistics:');
        $this->table(['Status', 'Count'], collect($stats)->map(function ($count, $status) {
            return [$status, $count];
        })->toArray());
    }
}
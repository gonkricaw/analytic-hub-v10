<?php

namespace App\Services;

use App\Models\EmailQueue;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

/**
 * EmailQueueService
 * 
 * Manages email queue operations for the Analytics Hub system.
 * Handles email queuing, monitoring, bulk operations, and delivery tracking.
 * 
 * Features:
 * - Queue email sending with priority support
 * - Bulk email operations
 * - Email delivery monitoring
 * - Queue statistics and reporting
 * - Failed email handling and retry management
 * 
 * @package App\Services
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class EmailQueueService
{
    /**
     * Queue an email for sending
     * 
     * @param array $emailData Email configuration data
     * @return EmailQueue Created email queue record
     * @throws Exception If email data is invalid
     */
    public function queueEmail(array $emailData): EmailQueue
    {
        try {
            // Validate required fields
            $this->validateEmailData($emailData);
            
            // Create email queue record
            $emailQueue = EmailQueue::create([
                'message_id' => $this->generateMessageId(),
                'template_id' => $emailData['template_id'] ?? null,
                'subject' => $emailData['subject'],
                'queue_name' => $emailData['queue_name'] ?? 'emails',
                'to_email' => $emailData['to_email'],
                'to_name' => $emailData['to_name'] ?? null,
                'cc_recipients' => $emailData['cc_recipients'] ?? null,
                'bcc_recipients' => $emailData['bcc_recipients'] ?? null,
                'reply_to' => $emailData['reply_to'] ?? null,
                'from_email' => $emailData['from_email'] ?? config('mail.from.address'),
                'from_name' => $emailData['from_name'] ?? config('mail.from.name'),
                'sender_email' => $emailData['sender_email'] ?? null,
                'sender_name' => $emailData['sender_name'] ?? null,
                'html_body' => $emailData['html_body'] ?? null,
                'text_body' => $emailData['text_body'] ?? null,
                'template_data' => $emailData['template_data'] ?? null,
                'attachments' => $emailData['attachments'] ?? null,
                'email_type' => $emailData['email_type'] ?? EmailQueue::TYPE_TRANSACTIONAL,
                'category' => $emailData['category'] ?? null,
                'priority' => $emailData['priority'] ?? EmailQueue::PRIORITY_NORMAL,
                'language' => $emailData['language'] ?? 'en',
                'scheduled_at' => $emailData['scheduled_at'] ?? now(),
                'expires_at' => $emailData['expires_at'] ?? now()->addDays(7),
                'max_attempts' => $emailData['max_attempts'] ?? 3,
                'status' => EmailQueue::STATUS_QUEUED,
                'status_message' => 'Email queued for sending'
            ]);
            
            // Dispatch the job
            $delay = $emailData['scheduled_at'] ? Carbon::parse($emailData['scheduled_at']) : null;
            $priority = $this->getJobPriority($emailData['priority'] ?? EmailQueue::PRIORITY_NORMAL);
            
            SendEmailJob::dispatch($emailQueue->id)
                ->onQueue($emailData['queue_name'] ?? 'emails')
                ->delay($delay)
                ->priority($priority);
            
            Log::info('Email queued successfully', [
                'id' => $emailQueue->id,
                'to' => $emailQueue->to_email,
                'subject' => $emailQueue->subject,
                'priority' => $emailQueue->priority
            ]);
            
            return $emailQueue;
            
        } catch (Exception $e) {
            Log::error('Failed to queue email', [
                'error' => $e->getMessage(),
                'email_data' => $emailData
            ]);
            throw $e;
        }
    }
    
    /**
     * Queue email using template
     * 
     * @param string $templateName Template name or ID
     * @param string $toEmail Recipient email
     * @param array $templateData Template variables
     * @param array $options Additional options
     * @return EmailQueue Created email queue record
     */
    public function queueTemplateEmail(string $templateName, string $toEmail, array $templateData = [], array $options = []): EmailQueue
    {
        // Load template
        $template = $this->getTemplate($templateName);
        
        if (!$template) {
            throw new Exception("Email template '{$templateName}' not found");
        }
        
        // Prepare email data
        $emailData = array_merge([
            'template_id' => $template->id,
            'subject' => $template->subject,
            'to_email' => $toEmail,
            'html_body' => $template->body_html,
            'text_body' => $template->body_text,
            'template_data' => $templateData,
            'email_type' => $template->type,
            'category' => $template->category,
            'from_email' => $template->from_email ?: config('mail.from.address'),
            'from_name' => $template->from_name ?: config('mail.from.name'),
            'reply_to' => $template->reply_to ? [['email' => $template->reply_to]] : null,
            'priority' => $template->priority ?: EmailQueue::PRIORITY_NORMAL,
            'language' => $template->language ?: 'en'
        ], $options);
        
        return $this->queueEmail($emailData);
    }
    
    /**
     * Queue bulk emails
     * 
     * @param array $recipients Array of recipient data
     * @param array $emailData Base email data
     * @param int $batchSize Number of emails per batch
     * @return array Array of created email queue records
     */
    public function queueBulkEmails(array $recipients, array $emailData, int $batchSize = 50): array
    {
        $queuedEmails = [];
        $batches = array_chunk($recipients, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $recipient) {
                try {
                    $recipientEmailData = array_merge($emailData, [
                        'to_email' => $recipient['email'],
                        'to_name' => $recipient['name'] ?? null,
                        'template_data' => array_merge(
                            $emailData['template_data'] ?? [],
                            $recipient['template_data'] ?? []
                        ),
                        // Add delay between batches to prevent overwhelming
                        'scheduled_at' => now()->addSeconds($batchIndex * 30)
                    ]);
                    
                    $queuedEmails[] = $this->queueEmail($recipientEmailData);
                    
                } catch (Exception $e) {
                    Log::error('Failed to queue bulk email', [
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        Log::info('Bulk emails queued', [
            'total_recipients' => count($recipients),
            'queued_count' => count($queuedEmails),
            'batch_count' => count($batches)
        ]);
        
        return $queuedEmails;
    }
    
    /**
     * Get queue statistics
     * 
     * @param array $filters Optional filters
     * @return array Queue statistics
     */
    public function getQueueStatistics(array $filters = []): array
    {
        $query = EmailQueue::query();
        
        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['email_type'])) {
            $query->where('email_type', $filters['email_type']);
        }
        
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        // Get statistics
        $stats = [
            'total' => $query->count(),
            'queued' => $query->where('status', EmailQueue::STATUS_QUEUED)->count(),
            'processing' => $query->where('status', EmailQueue::STATUS_PROCESSING)->count(),
            'sent' => $query->where('status', EmailQueue::STATUS_SENT)->count(),
            'failed' => $query->where('status', EmailQueue::STATUS_FAILED)->count(),
            'cancelled' => $query->where('status', EmailQueue::STATUS_CANCELLED)->count(),
            'expired' => $query->where('status', EmailQueue::STATUS_EXPIRED)->count(),
        ];
        
        // Calculate success rate
        $stats['success_rate'] = $stats['total'] > 0 
            ? round(($stats['sent'] / $stats['total']) * 100, 2) 
            : 0;
        
        // Get priority breakdown
        $stats['by_priority'] = [
            'urgent' => $query->where('priority', EmailQueue::PRIORITY_URGENT)->count(),
            'high' => $query->where('priority', EmailQueue::PRIORITY_HIGH)->count(),
            'normal' => $query->where('priority', EmailQueue::PRIORITY_NORMAL)->count(),
            'low' => $query->where('priority', EmailQueue::PRIORITY_LOW)->count(),
        ];
        
        // Get type breakdown
        $stats['by_type'] = [
            'transactional' => $query->where('email_type', EmailQueue::TYPE_TRANSACTIONAL)->count(),
            'notification' => $query->where('email_type', EmailQueue::TYPE_NOTIFICATION)->count(),
            'marketing' => $query->where('email_type', EmailQueue::TYPE_MARKETING)->count(),
            'system' => $query->where('email_type', EmailQueue::TYPE_SYSTEM)->count(),
        ];
        
        return $stats;
    }
    
    /**
     * Get failed emails for retry
     * 
     * @param int $limit Maximum number of emails to return
     * @return Collection Failed emails that can be retried
     */
    public function getFailedEmailsForRetry(int $limit = 100): Collection
    {
        return EmailQueue::where('status', EmailQueue::STATUS_FAILED)
            ->where('attempts', '<', 'max_attempts')
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Retry failed emails
     * 
     * @param array $emailIds Optional specific email IDs to retry
     * @return int Number of emails queued for retry
     */
    public function retryFailedEmails(array $emailIds = []): int
    {
        $query = EmailQueue::where('status', EmailQueue::STATUS_FAILED)
            ->where('attempts', '<', 'max_attempts');
        
        if (!empty($emailIds)) {
            $query->whereIn('id', $emailIds);
        }
        
        $failedEmails = $query->get();
        $retryCount = 0;
        
        foreach ($failedEmails as $email) {
            try {
                // Reset status and schedule retry
                $email->update([
                    'status' => EmailQueue::STATUS_QUEUED,
                    'status_message' => 'Queued for retry',
                    'next_retry_at' => null
                ]);
                
                // Dispatch job again
                SendEmailJob::dispatch($email->id)
                    ->onQueue($email->queue_name)
                    ->priority($this->getJobPriority($email->priority));
                
                $retryCount++;
                
            } catch (Exception $e) {
                Log::error('Failed to retry email', [
                    'id' => $email->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Failed emails queued for retry', ['count' => $retryCount]);
        
        return $retryCount;
    }
    
    /**
     * Cancel queued emails
     * 
     * @param array $emailIds Email IDs to cancel
     * @param string $reason Cancellation reason
     * @return int Number of emails cancelled
     */
    public function cancelEmails(array $emailIds, string $reason = 'Cancelled by administrator'): int
    {
        $cancelledCount = EmailQueue::whereIn('id', $emailIds)
            ->whereIn('status', [EmailQueue::STATUS_QUEUED, EmailQueue::STATUS_PROCESSING])
            ->update([
                'status' => EmailQueue::STATUS_CANCELLED,
                'status_message' => $reason,
                'cancelled_at' => now()
            ]);
        
        Log::info('Emails cancelled', [
            'count' => $cancelledCount,
            'reason' => $reason
        ]);
        
        return $cancelledCount;
    }
    
    /**
     * Clean up old email records
     * 
     * @param int $daysOld Number of days to keep records
     * @return int Number of records deleted
     */
    public function cleanupOldEmails(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $deletedCount = EmailQueue::where('created_at', '<', $cutoffDate)
            ->whereIn('status', [
                EmailQueue::STATUS_SENT,
                EmailQueue::STATUS_FAILED,
                EmailQueue::STATUS_CANCELLED,
                EmailQueue::STATUS_EXPIRED
            ])
            ->delete();
        
        Log::info('Old email records cleaned up', [
            'count' => $deletedCount,
            'cutoff_date' => $cutoffDate
        ]);
        
        return $deletedCount;
    }
    
    /**
     * Validate email data
     * 
     * @param array $emailData Email data to validate
     * @throws Exception If validation fails
     */
    protected function validateEmailData(array $emailData): void
    {
        $required = ['subject', 'to_email'];
        
        foreach ($required as $field) {
            if (empty($emailData[$field])) {
                throw new Exception("Required field '{$field}' is missing");
            }
        }
        
        // Validate email format
        if (!filter_var($emailData['to_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address: {$emailData['to_email']}");
        }
        
        // Validate that we have some content
        if (empty($emailData['html_body']) && empty($emailData['text_body'])) {
            throw new Exception('Email must have either HTML or text body');
        }
    }
    
    /**
     * Get email template
     * 
     * @param string $templateName Template name or ID
     * @return EmailTemplate|null
     */
    protected function getTemplate(string $templateName): ?EmailTemplate
    {
        // Try to find by ID first
        if (is_numeric($templateName) || preg_match('/^[0-9a-f-]{36}$/i', $templateName)) {
            return EmailTemplate::find($templateName);
        }
        
        // Find by name
        return EmailTemplate::where('name', $templateName)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Generate unique message ID
     * 
     * @return string
     */
    protected function generateMessageId(): string
    {
        return uniqid('email_', true) . '@' . config('app.url', 'localhost');
    }
    
    /**
     * Get job priority for queue
     * 
     * @param string $priority Email priority
     * @return int Job priority
     */
    protected function getJobPriority(string $priority): int
    {
        return match ($priority) {
            EmailQueue::PRIORITY_URGENT => 100,
            EmailQueue::PRIORITY_HIGH => 75,
            EmailQueue::PRIORITY_NORMAL => 50,
            EmailQueue::PRIORITY_LOW => 25,
            default => 50
        };
    }
}
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\EmailQueue;
use App\Models\EmailTemplate;
use App\Traits\EmailTracking;
use Carbon\Carbon;
use Exception;/**
 * SendEmailJob
 * 
 * Handles sending emails from the queue system.
 * Implements retry logic, delivery tracking, and error handling.
 * 
 * Features:
 * - Automatic retry on failure (max 3 attempts)
 * - Email delivery tracking
 * - Template variable replacement
 * - Attachment support
 * - Error logging and reporting
 * 
 * @package App\Jobs
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, EmailTracking;

    /**
     * The email queue record ID
     */
    protected string $emailQueueId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // 30s, 1min, 2min
    }

    /**
     * Create a new job instance.
     */
    public function __construct(string $emailQueueId)
    {
        $this->emailQueueId = $emailQueueId;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load email queue record
            $emailQueue = EmailQueue::find($this->emailQueueId);
            
            if (!$emailQueue) {
                Log::error('Email queue record not found', ['id' => $this->emailQueueId]);
                return;
            }

            // Check if email is already sent or cancelled
            if ($emailQueue->isSent() || $emailQueue->isCancelled()) {
                Log::info('Email already processed', [
                    'id' => $this->emailQueueId,
                    'status' => $emailQueue->status
                ]);
                return;
            }

            // Check if email is expired
            if ($emailQueue->isExpired()) {
                $emailQueue->markAsExpired('Email expired before sending');
                Log::warning('Email expired', ['id' => $this->emailQueueId]);
                return;
            }

            // Update attempts count
            $emailQueue->increment('attempts');
            $emailQueue->update([
                'status' => EmailQueue::STATUS_PROCESSING,
                'status_message' => 'Processing email'
            ]);

            // Send the email
            $this->sendEmail($emailQueue);

            // Mark as sent
            $emailQueue->markAsSent();
            
            Log::info('Email sent successfully', [
                'id' => $this->emailQueueId,
                'to' => $emailQueue->to_email,
                'subject' => $emailQueue->subject
            ]);

        } catch (Exception $e) {
            $this->handleFailure($e);
        }
    }

    /**
     * Send the actual email
     */
    protected function sendEmail(EmailQueue $emailQueue): void
    {
        // Prepare email data with variable replacement and tracking
        $emailData = $this->prepareEmailData($emailQueue);
        
        // Send email using Laravel Mail
        Mail::send([], [], function (Message $message) use ($emailData, $emailQueue) {
            // Set recipients
            $message->to($emailQueue->to_email, $emailQueue->to_name);
            
            // Set CC recipients
            if ($emailQueue->cc_recipients) {
                foreach ($emailQueue->cc_recipients as $cc) {
                    $message->cc($cc['email'], $cc['name'] ?? null);
                }
            }
            
            // Set BCC recipients
            if ($emailQueue->bcc_recipients) {
                foreach ($emailQueue->bcc_recipients as $bcc) {
                    $message->bcc($bcc['email'], $bcc['name'] ?? null);
                }
            }
            
            // Set sender
            $message->from($emailQueue->from_email, $emailQueue->from_name);
            
            // Set reply-to
            if ($emailQueue->reply_to) {
                foreach ($emailQueue->reply_to as $replyTo) {
                    $message->replyTo($replyTo['email'], $replyTo['name'] ?? null);
                }
            }
            
            // Set subject
            $message->subject($emailQueue->subject);
            
            // Set body with tracking
            if ($emailQueue->html_body) {
                $trackedHtml = $this->wrapWithTracking(
                    $emailData['html_body'], 
                    $emailQueue->message_id,
                    [
                        'track_opens' => $emailQueue->track_opens,
                        'track_clicks' => $emailQueue->track_clicks,
                        'user_email' => $emailQueue->to_email
                    ]
                );
                $message->setBody($trackedHtml, 'text/html');
            }
            
            if ($emailQueue->text_body) {
                $message->addPart($emailData['text_body'], 'text/plain');
            }
            
            // Add attachments
            if ($emailQueue->attachments) {
                foreach ($emailQueue->attachments as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }
            }
            
            // Set headers
            $headers = $message->getHeaders();
            $headers->addTextHeader('X-Email-Queue-ID', $emailQueue->id);
            $headers->addTextHeader('X-Email-Type', $emailQueue->email_type);
            $headers->addTextHeader('X-Email-Priority', $emailQueue->priority);
            
            // Add tracking headers
            if ($emailQueue->message_id) {
                $headers->addTextHeader('X-Message-ID', $emailQueue->message_id);
                $headers->addTextHeader('X-Tracking-ID', $emailQueue->tracking_id);
            }
            
            // Add List-Unsubscribe header for better deliverability
            $unsubscribeUrl = $this->generateUnsubscribeUrl($emailQueue->message_id, $emailQueue->to_email);
            $headers->addTextHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');
            $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        });
    }

    /**
     * Prepare email data with template variables replaced
     */
    protected function prepareEmailData(EmailQueue $emailQueue): array
    {
        $htmlBody = $emailQueue->html_body;
        $textBody = $emailQueue->text_body;
        
        // Add tracking variables to template data
        $templateData = $emailQueue->template_data ?? [];
        $trackingVariables = $this->createTrackingVariables($emailQueue->message_id, $emailQueue->to_email);
        $templateData = array_merge($templateData, $trackingVariables);
        
        // Replace template variables with tracking-enabled content
        if ($templateData) {
            $htmlBody = $this->replaceVariablesWithTracking(
                $htmlBody, 
                $templateData, 
                $emailQueue->message_id
            );
            
            // For text body, use simple replacement without tracking
            foreach ($templateData as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $textBody = str_replace($placeholder, $value, $textBody);
            }
        }
        
        return [
            'html_body' => $htmlBody,
            'text_body' => $textBody
        ];
    }

    /**
     * Handle job failure
     */
    protected function handleFailure(Exception $exception): void
    {
        try {
            $emailQueue = EmailQueue::find($this->emailQueueId);
            
            if ($emailQueue) {
                $errorMessage = $exception->getMessage();
                $errorDetails = [
                    'exception' => get_class($exception),
                    'message' => $errorMessage,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ];
                
                // Check if we should retry
                if ($emailQueue->attempts < $emailQueue->max_attempts) {
                    // Schedule retry
                    $emailQueue->scheduleRetry($errorMessage, $errorDetails);
                    
                    Log::warning('Email send failed, will retry', [
                        'id' => $this->emailQueueId,
                        'attempt' => $emailQueue->attempts,
                        'max_attempts' => $emailQueue->max_attempts,
                        'error' => $errorMessage
                    ]);
                } else {
                    // Mark as permanently failed
                    $emailQueue->markAsFailed($errorMessage, $errorDetails);
                    
                    Log::error('Email send permanently failed', [
                        'id' => $this->emailQueueId,
                        'attempts' => $emailQueue->attempts,
                        'error' => $errorMessage
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to handle email job failure', [
                'original_error' => $exception->getMessage(),
                'handling_error' => $e->getMessage()
            ]);
        }
        
        // Re-throw the exception to trigger Laravel's retry mechanism
        throw $exception;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Email job failed permanently', [
            'id' => $this->emailQueueId,
            'error' => $exception->getMessage()
        ]);
        
        try {
            $emailQueue = EmailQueue::find($this->emailQueueId);
            if ($emailQueue && !$emailQueue->isFailed()) {
                $emailQueue->markAsFailed(
                    'Job failed permanently: ' . $exception->getMessage(),
                    [
                        'exception' => get_class($exception),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine()
                    ]
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to mark email as failed', [
                'id' => $this->emailQueueId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
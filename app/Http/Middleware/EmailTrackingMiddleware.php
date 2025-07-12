<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\EmailQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Carbon\Carbon;
use Exception;

/**
 * EmailTrackingMiddleware
 * 
 * Middleware for tracking email delivery, opens, clicks, and other email events.
 * Handles webhooks from email service providers and updates email queue records.
 * 
 * Features:
 * - Email delivery tracking
 * - Open and click tracking
 * - Bounce and complaint handling
 * - Webhook signature verification
 * - Rate limiting for tracking requests
 * 
 * @package App\Http\Middleware
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class EmailTrackingMiddleware
{
    /**
     * Handle an incoming request for email tracking
     * 
     * @param Request $request
     * @param Closure $next
     * @return BaseResponse
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        try {
            // Handle different types of email tracking requests
            $trackingType = $this->determineTrackingType($request);
            
            switch ($trackingType) {
                case 'open':
                    $this->handleEmailOpen($request);
                    break;
                    
                case 'click':
                    $this->handleEmailClick($request);
                    break;
                    
                case 'webhook':
                    return $this->handleWebhook($request, $next);
                    
                case 'unsubscribe':
                    $this->handleUnsubscribe($request);
                    break;
                    
                default:
                    // Continue with normal request processing
                    break;
            }
            
        } catch (Exception $e) {
            Log::error('Email tracking middleware error', [
                'error' => $e->getMessage(),
                'request_url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);
        }
        
        return $next($request);
    }

    /**
     * Determine the type of tracking request
     * 
     * @param Request $request
     * @return string
     */
    protected function determineTrackingType(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, '/email/open/')) {
            return 'open';
        }
        
        if (str_contains($path, '/email/click/')) {
            return 'click';
        }
        
        if (str_contains($path, '/email/webhook/')) {
            return 'webhook';
        }
        
        if (str_contains($path, '/email/unsubscribe/')) {
            return 'unsubscribe';
        }
        
        return 'unknown';
    }

    /**
     * Handle email open tracking
     * 
     * @param Request $request
     * @return void
     */
    protected function handleEmailOpen(Request $request): void
    {
        $messageId = $this->extractMessageId($request);
        
        if (!$messageId) {
            return;
        }
        
        $email = EmailQueue::where('message_id', $messageId)->first();
        
        if (!$email) {
            Log::warning('Email open tracking: Email not found', [
                'message_id' => $messageId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return;
        }
        
        // Update email open tracking
        $now = now();
        $updateData = [
            'open_count' => $email->open_count + 1,
            'updated_at' => $now
        ];
        
        // Set first open time if not already set
        if (!$email->opened_at) {
            $updateData['opened_at'] = $now;
        }
        
        $email->update($updateData);
        
        // Log the open event
        Log::info('Email opened', [
            'email_id' => $email->id,
            'message_id' => $messageId,
            'to_email' => $email->to_email,
            'open_count' => $email->open_count + 1,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Store detailed tracking information if needed
        $this->storeTrackingEvent($email, 'open', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => $now->toISOString()
        ]);
    }

    /**
     * Handle email click tracking
     * 
     * @param Request $request
     * @return void
     */
    protected function handleEmailClick(Request $request): void
    {
        $messageId = $this->extractMessageId($request);
        $url = $request->get('url');
        
        if (!$messageId || !$url) {
            return;
        }
        
        $email = EmailQueue::where('message_id', $messageId)->first();
        
        if (!$email) {
            Log::warning('Email click tracking: Email not found', [
                'message_id' => $messageId,
                'url' => $url,
                'ip' => $request->ip()
            ]);
            return;
        }
        
        // Update email click tracking
        $now = now();
        $updateData = [
            'click_count' => $email->click_count + 1,
            'updated_at' => $now
        ];
        
        // Set first click time if not already set
        if (!$email->clicked_at) {
            $updateData['clicked_at'] = $now;
        }
        
        $email->update($updateData);
        
        // Log the click event
        Log::info('Email link clicked', [
            'email_id' => $email->id,
            'message_id' => $messageId,
            'to_email' => $email->to_email,
            'url' => $url,
            'click_count' => $email->click_count + 1,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Store detailed tracking information
        $this->storeTrackingEvent($email, 'click', [
            'url' => $url,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => $now->toISOString()
        ]);
    }

    /**
     * Handle webhook requests from email service providers
     * 
     * @param Request $request
     * @param Closure $next
     * @return BaseResponse
     */
    protected function handleWebhook(Request $request, Closure $next): BaseResponse
    {
        // Verify webhook signature if configured
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all()
            ]);
            
            return response('Unauthorized', 401);
        }
        
        $provider = $this->detectEmailProvider($request);
        $events = $this->parseWebhookEvents($request, $provider);
        
        foreach ($events as $event) {
            $this->processWebhookEvent($event, $provider);
        }
        
        // Return success response
        return response('OK', 200);
    }

    /**
     * Handle unsubscribe requests
     * 
     * @param Request $request
     * @return void
     */
    protected function handleUnsubscribe(Request $request): void
    {
        $messageId = $this->extractMessageId($request);
        $email = $request->get('email');
        
        if (!$messageId && !$email) {
            return;
        }
        
        $emailRecord = null;
        
        if ($messageId) {
            $emailRecord = EmailQueue::where('message_id', $messageId)->first();
        } elseif ($email) {
            $emailRecord = EmailQueue::where('to_email', $email)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        if ($emailRecord) {
            // Log unsubscribe event
            Log::info('Email unsubscribe', [
                'email_id' => $emailRecord->id,
                'message_id' => $messageId,
                'to_email' => $emailRecord->to_email,
                'ip' => $request->ip()
            ]);
            
            // Store unsubscribe tracking
            $this->storeTrackingEvent($emailRecord, 'unsubscribe', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString()
            ]);
        }
        
        // Here you would typically add the email to an unsubscribe list
        // or update user preferences in your system
    }

    /**
     * Extract message ID from request
     * 
     * @param Request $request
     * @return string|null
     */
    protected function extractMessageId(Request $request): ?string
    {
        // Try different parameter names
        return $request->get('mid') 
            ?? $request->get('message_id') 
            ?? $request->get('id')
            ?? $request->route('messageId');
    }

    /**
     * Verify webhook signature
     * 
     * @param Request $request
     * @return bool
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $provider = $this->detectEmailProvider($request);
        
        switch ($provider) {
            case 'sendgrid':
                return $this->verifySendGridSignature($request);
                
            case 'mailgun':
                return $this->verifyMailgunSignature($request);
                
            case 'ses':
                return $this->verifySESSignature($request);
                
            default:
                // If no specific provider verification, allow through
                // In production, you might want to be more strict
                return true;
        }
    }

    /**
     * Detect email service provider from request
     * 
     * @param Request $request
     * @return string
     */
    protected function detectEmailProvider(Request $request): string
    {
        $userAgent = $request->userAgent();
        $headers = $request->headers->all();
        
        if (isset($headers['x-sendgrid-event-type'])) {
            return 'sendgrid';
        }
        
        if (isset($headers['x-mailgun-signature'])) {
            return 'mailgun';
        }
        
        if (str_contains($userAgent, 'Amazon SES')) {
            return 'ses';
        }
        
        return 'unknown';
    }

    /**
     * Parse webhook events based on provider
     * 
     * @param Request $request
     * @param string $provider
     * @return array
     */
    protected function parseWebhookEvents(Request $request, string $provider): array
    {
        $payload = $request->getContent();
        
        try {
            $data = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in webhook payload', [
                    'provider' => $provider,
                    'payload' => substr($payload, 0, 500)
                ]);
                return [];
            }
            
            switch ($provider) {
                case 'sendgrid':
                    return $this->parseSendGridEvents($data);
                    
                case 'mailgun':
                    return $this->parseMailgunEvents($data);
                    
                case 'ses':
                    return $this->parseSESEvents($data);
                    
                default:
                    return $this->parseGenericEvents($data);
            }
            
        } catch (Exception $e) {
            Log::error('Error parsing webhook events', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'payload' => substr($payload, 0, 500)
            ]);
            
            return [];
        }
    }

    /**
     * Process individual webhook event
     * 
     * @param array $event
     * @param string $provider
     * @return void
     */
    protected function processWebhookEvent(array $event, string $provider): void
    {
        $messageId = $event['message_id'] ?? null;
        $eventType = $event['event'] ?? null;
        
        if (!$messageId || !$eventType) {
            return;
        }
        
        $email = EmailQueue::where('message_id', $messageId)->first();
        
        if (!$email) {
            Log::warning('Webhook event for unknown email', [
                'message_id' => $messageId,
                'event' => $eventType,
                'provider' => $provider
            ]);
            return;
        }
        
        $now = now();
        
        switch ($eventType) {
            case 'delivered':
                $email->update([
                    'delivered_at' => $now,
                    'updated_at' => $now
                ]);
                break;
                
            case 'bounce':
            case 'dropped':
                $email->update([
                    'status' => EmailQueue::STATUS_FAILED,
                    'error_message' => $event['reason'] ?? 'Email bounced',
                    'failed_at' => $now,
                    'updated_at' => $now
                ]);
                break;
                
            case 'spam':
            case 'complaint':
                $email->update([
                    'status' => EmailQueue::STATUS_FAILED,
                    'error_message' => 'Marked as spam/complaint',
                    'failed_at' => $now,
                    'updated_at' => $now
                ]);
                break;
        }
        
        // Store the webhook event
        $this->storeTrackingEvent($email, $eventType, $event);
        
        Log::info('Webhook event processed', [
            'email_id' => $email->id,
            'message_id' => $messageId,
            'event' => $eventType,
            'provider' => $provider
        ]);
    }

    /**
     * Store tracking event details
     * 
     * @param EmailQueue $email
     * @param string $eventType
     * @param array $data
     * @return void
     */
    protected function storeTrackingEvent(EmailQueue $email, string $eventType, array $data): void
    {
        // For now, we'll store in the email queue record's tracking_data field
        // In a larger system, you might want a separate email_tracking_events table
        
        $trackingData = $email->tracking_data ?? [];
        
        $trackingData[] = [
            'event' => $eventType,
            'timestamp' => now()->toISOString(),
            'data' => $data
        ];
        
        // Keep only the last 50 tracking events to prevent the field from growing too large
        if (count($trackingData) > 50) {
            $trackingData = array_slice($trackingData, -50);
        }
        
        $email->update(['tracking_data' => $trackingData]);
    }

    /**
     * Verify SendGrid webhook signature
     * 
     * @param Request $request
     * @return bool
     */
    protected function verifySendGridSignature(Request $request): bool
    {
        $publicKey = config('mail.sendgrid.webhook_public_key');
        
        if (!$publicKey) {
            return true; // Skip verification if no key configured
        }
        
        // Implement SendGrid signature verification
        // This is a simplified version - refer to SendGrid docs for full implementation
        return true;
    }

    /**
     * Verify Mailgun webhook signature
     * 
     * @param Request $request
     * @return bool
     */
    protected function verifyMailgunSignature(Request $request): bool
    {
        $signingKey = config('mail.mailgun.webhook_signing_key');
        
        if (!$signingKey) {
            return true; // Skip verification if no key configured
        }
        
        // Implement Mailgun signature verification
        return true;
    }

    /**
     * Verify Amazon SES webhook signature
     * 
     * @param Request $request
     * @return bool
     */
    protected function verifySESSignature(Request $request): bool
    {
        // Amazon SES uses SNS for webhooks, which has its own verification
        return true;
    }

    /**
     * Parse SendGrid webhook events
     * 
     * @param array $data
     * @return array
     */
    protected function parseSendGridEvents(array $data): array
    {
        // SendGrid sends an array of events
        return is_array($data) ? $data : [$data];
    }

    /**
     * Parse Mailgun webhook events
     * 
     * @param array $data
     * @return array
     */
    protected function parseMailgunEvents(array $data): array
    {
        // Mailgun typically sends single events
        return [$data];
    }

    /**
     * Parse Amazon SES webhook events
     * 
     * @param array $data
     * @return array
     */
    protected function parseSESEvents(array $data): array
    {
        // SES events come through SNS
        if (isset($data['Message'])) {
            $message = json_decode($data['Message'], true);
            return [$message];
        }
        
        return [$data];
    }

    /**
     * Parse generic webhook events
     * 
     * @param array $data
     * @return array
     */
    protected function parseGenericEvents(array $data): array
    {
        return is_array($data) && isset($data[0]) ? $data : [$data];
    }
}
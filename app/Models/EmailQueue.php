<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasUuid;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

/**
 * EmailQueue Model
 * 
 * Manages email queue for the Analytics Hub system.
 * Handles email scheduling, delivery tracking, retry mechanisms, and performance metrics.
 * 
 * @property string $id
 * @property string|null $message_id
 * @property string|null $template_id
 * @property string $subject
 * @property string $queue_name
 * @property string $to_email
 * @property string|null $to_name
 * @property array|null $cc_recipients
 * @property array|null $bcc_recipients
 * @property array|null $reply_to
 * @property string $from_email
 * @property string|null $from_name
 * @property string|null $sender_email
 * @property string|null $return_path
 * @property string|null $html_body
 * @property string|null $text_body
 * @property array|null $template_data
 * @property array|null $attachments
 * @property string $email_type
 * @property string|null $category
 * @property string $priority
 * @property string $language
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $send_after
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_immediate
 * @property string $status
 * @property string|null $status_message
 * @property array|null $status_data
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $opened_at
 * @property \Carbon\Carbon|null $clicked_at
 * @property int $open_count
 * @property int $click_count
 * @property int $attempts
 * @property int $max_attempts
 * @property \Carbon\Carbon|null $next_retry_at
 * @property int $retry_delay
 * @property array|null $retry_history
 * @property string|null $error_message
 * @property string|null $error_details
 * @property string|null $error_code
 * @property \Carbon\Carbon|null $failed_at
 * @property array|null $bounce_data
 * @property string|null $tracking_id
 * @property array|null $tracking_data
 * @property bool $track_opens
 * @property bool $track_clicks
 * @property string|null $campaign_id
 * @property string|null $user_id
 * @property string|null $sender_user_id
 * @property string|null $session_id
 * @property string|null $ip_address
 * @property array|null $user_context
 * @property array|null $custom_headers
 * @property string|null $message_stream
 * @property array|null $tags
 * @property array|null $metadata
 * @property string|null $batch_id
 * @property int|null $batch_size
 * @property int|null $batch_position
 * @property array|null $batch_data
 * @property int|null $processing_time_ms
 * @property int|null $send_time_ms
 * @property int|null $queue_wait_time_ms
 * @property array|null $performance_data
 * @property bool $is_encrypted
 * @property string|null $encryption_method
 * @property bool $requires_consent
 * @property bool $consent_given
 * @property array|null $compliance_data
 * @property bool $notify_on_delivery
 * @property bool $notify_on_failure
 * @property array|null $notification_recipients
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class EmailQueue extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_email_queue';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'message_id',
        'template_id',
        'subject',
        'queue_name',
        'to_email',
        'to_name',
        'cc_recipients',
        'bcc_recipients',
        'reply_to',
        'from_email',
        'from_name',
        'sender_email',
        'return_path',
        'html_body',
        'text_body',
        'template_data',
        'attachments',
        'email_type',
        'category',
        'priority',
        'language',
        'scheduled_at',
        'send_after',
        'expires_at',
        'is_immediate',
        'status',
        'status_message',
        'status_data',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
        'attempts',
        'max_attempts',
        'next_retry_at',
        'retry_delay',
        'retry_history',
        'error_message',
        'error_details',
        'error_code',
        'failed_at',
        'bounce_data',
        'tracking_id',
        'tracking_data',
        'track_opens',
        'track_clicks',
        'campaign_id',
        'user_id',
        'sender_user_id',
        'session_id',
        'ip_address',
        'user_context',
        'custom_headers',
        'message_stream',
        'tags',
        'metadata',
        'batch_id',
        'batch_size',
        'batch_position',
        'batch_data',
        'processing_time_ms',
        'send_time_ms',
        'queue_wait_time_ms',
        'performance_data',
        'is_encrypted',
        'encryption_method',
        'requires_consent',
        'consent_given',
        'compliance_data',
        'notify_on_delivery',
        'notify_on_failure',
        'notification_recipients',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'reply_to' => 'array',
        'template_data' => 'array',
        'attachments' => 'array',
        'scheduled_at' => 'datetime',
        'send_after' => 'datetime',
        'expires_at' => 'datetime',
        'is_immediate' => 'boolean',
        'status_data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'next_retry_at' => 'datetime',
        'retry_delay' => 'integer',
        'retry_history' => 'array',
        'failed_at' => 'datetime',
        'bounce_data' => 'array',
        'tracking_data' => 'array',
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'user_context' => 'array',
        'custom_headers' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'batch_size' => 'integer',
        'batch_position' => 'integer',
        'batch_data' => 'array',
        'processing_time_ms' => 'integer',
        'send_time_ms' => 'integer',
        'queue_wait_time_ms' => 'integer',
        'performance_data' => 'array',
        'is_encrypted' => 'boolean',
        'requires_consent' => 'boolean',
        'consent_given' => 'boolean',
        'compliance_data' => 'array',
        'notify_on_delivery' => 'boolean',
        'notify_on_failure' => 'boolean',
        'notification_recipients' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Email type constants
     */
    const TYPE_TRANSACTIONAL = 'transactional';
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_MARKETING = 'marketing';
    const TYPE_SYSTEM = 'system';
    const TYPE_INVITATION = 'invitation';

    /**
     * Priority constants
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * Language constants
     */
    const LANGUAGE_ENGLISH = 'en';
    const LANGUAGE_INDONESIAN = 'id';

    /**
     * Check if email is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if email is queued
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Check if email is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if email is sent
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if email failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if email is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if email is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if email is ready to send
     */
    public function isReadyToSend(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }

        if ($this->send_after && $this->send_after->isFuture()) {
            return false;
        }

        if ($this->requires_consent && !$this->consent_given) {
            return false;
        }

        return true;
    }

    /**
     * Check if email can be retried
     */
    public function canRetry(): bool
    {
        return $this->isFailed() && 
               $this->attempts < $this->max_attempts &&
               !$this->isExpired();
    }

    /**
     * Get the email template relationship
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Get the target user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the sender user relationship
     */
    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * Get the creator relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater relationship
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Mark email as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'status_message' => 'Email is being processed',
        ]);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(array $data = []): void
    {
        $this->update(array_merge([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'status_message' => 'Email sent successfully',
        ], $data));

        // Increment template usage if template exists
        if ($this->template) {
            $this->template->incrementUsage();
        }
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $errorMessage, array $errorDetails = []): void
    {
        $this->increment('attempts');
        
        $updateData = [
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => json_encode($errorDetails),
            'status_message' => 'Email failed to send',
        ];

        // Set next retry time if retries are available
        if ($this->canRetry()) {
            $updateData['next_retry_at'] = now()->addSeconds($this->retry_delay * $this->attempts);
            $updateData['status'] = self::STATUS_PENDING; // Reset to pending for retry
        }

        // Add to retry history
        $retryHistory = $this->retry_history ?? [];
        $retryHistory[] = [
            'attempt' => $this->attempts,
            'failed_at' => now()->toISOString(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ];
        $updateData['retry_history'] = $retryHistory;

        $this->update($updateData);
    }

    /**
     * Mark email as cancelled
     */
    public function markAsCancelled(string $reason = 'Cancelled by user'): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'status_message' => $reason,
        ]);
    }

    /**
     * Mark email as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'status_message' => 'Email expired',
        ]);
    }

    /**
     * Track email open
     */
    public function trackOpen(): void
    {
        if (!$this->track_opens) {
            return;
        }

        $this->increment('open_count');
        
        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Track email click
     */
    public function trackClick(): void
    {
        if (!$this->track_clicks) {
            return;
        }

        $this->increment('click_count');
        
        if (!$this->clicked_at) {
            $this->update(['clicked_at' => now()]);
        }
    }

    /**
     * Get encrypted email body
     */
    public function getEncryptedHtmlBodyAttribute(): string
    {
        return $this->is_encrypted ? Crypt::encrypt($this->attributes['html_body']) : $this->attributes['html_body'];
    }

    /**
     * Set encrypted email body
     */
    public function setHtmlBodyAttribute($value): void
    {
        $this->attributes['html_body'] = $this->is_encrypted ? Crypt::decrypt($value) : $value;
    }

    /**
     * Calculate delivery time
     */
    public function getDeliveryTimeAttribute(): ?int
    {
        if (!$this->sent_at || !$this->delivered_at) {
            return null;
        }

        return $this->sent_at->diffInSeconds($this->delivered_at);
    }

    /**
     * Scope: Pending emails
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Failed emails
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Sent emails
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope: Ready to send
     */
    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where(function ($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('send_after')
                          ->orWhere('send_after', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->where(function ($q) {
                        $q->where('requires_consent', false)
                          ->orWhere('consent_given', true);
                    });
    }

    /**
     * Scope: Ready for retry
     */
    public function scopeReadyForRetry(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('attempts', '>', 0)
                    ->where('next_retry_at', '<=', now())
                    ->whereRaw('attempts < max_attempts');
    }

    /**
     * Scope: High priority
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope: Filter by email type
     */
    public function scopeEmailType(Builder $query, string $type): Builder
    {
        return $query->where('email_type', $type);
    }

    /**
     * Scope: Filter by queue name
     */
    public function scopeQueue(Builder $query, string $queueName): Builder
    {
        return $query->where('queue_name', $queueName);
    }

    /**
     * Scope: Filter by recipient
     */
    public function scopeRecipient(Builder $query, string $email): Builder
    {
        return $query->where('to_email', $email);
    }

    /**
     * Scope: Filter by campaign
     */
    public function scopeCampaign(Builder $query, string $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope: Filter by batch
     */
    public function scopeBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope: Expired emails
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Recent emails
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * UserNotification Model
 * 
 * Manages the pivot relationship between users and notifications.
 * Tracks individual user's interaction with notifications including
 * read status, delivery tracking, user responses, and preferences.
 * 
 * @property string $id
 * @property string $user_id
 * @property string $notification_id
 * @property bool $is_read
 * @property \Carbon\Carbon|null $read_at
 * @property bool $is_dismissed
 * @property \Carbon\Carbon|null $dismissed_at
 * @property bool $is_archived
 * @property \Carbon\Carbon|null $archived_at
 * @property string $delivery_status
 * @property \Carbon\Carbon|null $delivered_at
 * @property string|null $delivery_message
 * @property array|null $delivery_data
 * @property bool $email_sent
 * @property \Carbon\Carbon|null $email_sent_at
 * @property bool $sms_sent
 * @property \Carbon\Carbon|null $sms_sent_at
 * @property bool $push_sent
 * @property \Carbon\Carbon|null $push_sent_at
 * @property bool $in_app_shown
 * @property \Carbon\Carbon|null $in_app_shown_at
 * @property int $view_count
 * @property \Carbon\Carbon|null $first_viewed_at
 * @property \Carbon\Carbon|null $last_viewed_at
 * @property string|null $action_taken
 * @property \Carbon\Carbon|null $action_taken_at
 * @property array|null $action_details
 * @property string|null $user_response
 * @property \Carbon\Carbon|null $response_at
 * @property string|null $response_notes
 * @property array|null $response_data
 * @property string $user_priority
 * @property bool $is_pinned
 * @property \Carbon\Carbon|null $pinned_at
 * @property bool $is_starred
 * @property \Carbon\Carbon|null $starred_at
 * @property \Carbon\Carbon|null $scheduled_for
 * @property bool $is_snoozed
 * @property \Carbon\Carbon|null $snoozed_until
 * @property int $snooze_count
 * @property int $retry_count
 * @property \Carbon\Carbon|null $next_retry_at
 * @property int $max_retries
 * @property array|null $retry_history
 * @property string|null $error_message
 * @property array|null $error_details
 * @property \Carbon\Carbon|null $failed_at
 * @property int $failure_count
 * @property string|null $device_type
 * @property string|null $device_id
 * @property string|null $platform
 * @property string|null $app_version
 * @property array|null $device_info
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Notification $notification
 */
class UserNotification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_user_notifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'notification_id',
        'is_read',
        'read_at',
        'is_dismissed',
        'dismissed_at',
        'is_archived',
        'archived_at',
        'delivery_status',
        'delivered_at',
        'delivery_message',
        'delivery_data',
        'email_sent',
        'email_sent_at',
        'sms_sent',
        'sms_sent_at',
        'push_sent',
        'push_sent_at',
        'in_app_shown',
        'in_app_shown_at',
        'view_count',
        'first_viewed_at',
        'last_viewed_at',
        'action_taken',
        'action_taken_at',
        'action_details',
        'user_response',
        'response_at',
        'response_notes',
        'response_data',
        'user_priority',
        'is_pinned',
        'pinned_at',
        'is_starred',
        'starred_at',
        'scheduled_for',
        'is_snoozed',
        'snoozed_until',
        'snooze_count',
        'retry_count',
        'next_retry_at',
        'max_retries',
        'retry_history',
        'error_message',
        'error_details',
        'failed_at',
        'failure_count',
        'device_type',
        'device_id',
        'platform',
        'app_version',
        'device_info',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_data' => 'array',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'sms_sent' => 'boolean',
        'sms_sent_at' => 'datetime',
        'push_sent' => 'boolean',
        'push_sent_at' => 'datetime',
        'in_app_shown' => 'boolean',
        'in_app_shown_at' => 'datetime',
        'view_count' => 'integer',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'action_taken_at' => 'datetime',
        'action_details' => 'array',
        'response_at' => 'datetime',
        'response_data' => 'array',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
        'is_starred' => 'boolean',
        'starred_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'is_snoozed' => 'boolean',
        'snoozed_until' => 'datetime',
        'snooze_count' => 'integer',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'max_retries' => 'integer',
        'retry_history' => 'array',
        'error_details' => 'array',
        'failed_at' => 'datetime',
        'failure_count' => 'integer',
        'device_info' => 'array',
    ];

    /**
     * Delivery status constants
     */
    const DELIVERY_STATUS_PENDING = 'pending';
    const DELIVERY_STATUS_SENT = 'sent';
    const DELIVERY_STATUS_DELIVERED = 'delivered';
    const DELIVERY_STATUS_FAILED = 'failed';
    const DELIVERY_STATUS_BOUNCED = 'bounced';
    const DELIVERY_STATUS_CANCELLED = 'cancelled';

    /**
     * User priority constants
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Device type constants
     */
    const DEVICE_TYPE_WEB = 'web';
    const DEVICE_TYPE_MOBILE = 'mobile';
    const DEVICE_TYPE_TABLET = 'tablet';
    const DEVICE_TYPE_DESKTOP = 'desktop';

    /**
     * Platform constants
     */
    const PLATFORM_WINDOWS = 'windows';
    const PLATFORM_MACOS = 'macos';
    const PLATFORM_LINUX = 'linux';
    const PLATFORM_ANDROID = 'android';
    const PLATFORM_IOS = 'ios';
    const PLATFORM_WEB = 'web';

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notification that belongs to the user.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Mark the notification as read for this user.
     */
    public function markAsRead(): bool
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
                'view_count' => $this->view_count + 1,
                'last_viewed_at' => now(),
            ]);

            // Set first viewed at if not set
            if (!$this->first_viewed_at) {
                $this->update(['first_viewed_at' => now()]);
            }

            return true;
        }

        return false;
    }

    /**
     * Mark the notification as unread for this user.
     */
    public function markAsUnread(): bool
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Dismiss the notification for this user.
     */
    public function dismiss(): bool
    {
        if (!$this->is_dismissed) {
            $this->update([
                'is_dismissed' => true,
                'dismissed_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Archive the notification for this user.
     */
    public function archive(): bool
    {
        if (!$this->is_archived) {
            $this->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Pin the notification for this user.
     */
    public function pin(): bool
    {
        if (!$this->is_pinned) {
            $this->update([
                'is_pinned' => true,
                'pinned_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Unpin the notification for this user.
     */
    public function unpin(): bool
    {
        if ($this->is_pinned) {
            $this->update([
                'is_pinned' => false,
                'pinned_at' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Star the notification for this user.
     */
    public function star(): bool
    {
        if (!$this->is_starred) {
            $this->update([
                'is_starred' => true,
                'starred_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Unstar the notification for this user.
     */
    public function unstar(): bool
    {
        if ($this->is_starred) {
            $this->update([
                'is_starred' => false,
                'starred_at' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Snooze the notification until a specific time.
     */
    public function snooze(Carbon $until): bool
    {
        $this->update([
            'is_snoozed' => true,
            'snoozed_until' => $until,
            'snooze_count' => $this->snooze_count + 1,
        ]);

        return true;
    }

    /**
     * Unsnooze the notification.
     */
    public function unsnooze(): bool
    {
        if ($this->is_snoozed) {
            $this->update([
                'is_snoozed' => false,
                'snoozed_until' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Record user action on the notification.
     */
    public function recordAction(string $action, array $details = []): bool
    {
        $this->update([
            'action_taken' => $action,
            'action_taken_at' => now(),
            'action_details' => $details,
        ]);

        return true;
    }

    /**
     * Record user response to the notification.
     */
    public function recordResponse(string $response, string $notes = null, array $data = []): bool
    {
        $this->update([
            'user_response' => $response,
            'response_at' => now(),
            'response_notes' => $notes,
            'response_data' => $data,
        ]);

        return true;
    }

    /**
     * Mark delivery as sent for a specific channel.
     */
    public function markDelivered(string $channel, array $data = []): bool
    {
        $updates = [
            'delivery_status' => self::DELIVERY_STATUS_DELIVERED,
            'delivered_at' => now(),
            'delivery_data' => $data,
        ];

        switch ($channel) {
            case 'email':
                $updates['email_sent'] = true;
                $updates['email_sent_at'] = now();
                break;
            case 'sms':
                $updates['sms_sent'] = true;
                $updates['sms_sent_at'] = now();
                break;
            case 'push':
                $updates['push_sent'] = true;
                $updates['push_sent_at'] = now();
                break;
            case 'in_app':
                $updates['in_app_shown'] = true;
                $updates['in_app_shown_at'] = now();
                break;
        }

        $this->update($updates);

        return true;
    }

    /**
     * Mark delivery as failed.
     */
    public function markFailed(string $error, array $details = []): bool
    {
        $this->update([
            'delivery_status' => self::DELIVERY_STATUS_FAILED,
            'failed_at' => now(),
            'failure_count' => $this->failure_count + 1,
            'error_message' => $error,
            'error_details' => $details,
        ]);

        return true;
    }

    /**
     * Increment retry count and set next retry time.
     */
    public function incrementRetry(Carbon $nextRetry = null): bool
    {
        $retryHistory = $this->retry_history ?? [];
        $retryHistory[] = [
            'attempt' => $this->retry_count + 1,
            'attempted_at' => now()->toISOString(),
            'error' => $this->error_message,
        ];

        $this->update([
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => $nextRetry,
            'retry_history' => $retryHistory,
        ]);

        return true;
    }

    /**
     * Check if notification is snoozed and still within snooze period.
     */
    public function isSnoozed(): bool
    {
        return $this->is_snoozed && 
               $this->snoozed_until && 
               $this->snoozed_until->isFuture();
    }

    /**
     * Check if notification can be retried.
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries &&
               $this->delivery_status === self::DELIVERY_STATUS_FAILED;
    }

    /**
     * Check if notification is ready for retry.
     */
    public function isReadyForRetry(): bool
    {
        return $this->canRetry() &&
               $this->next_retry_at &&
               $this->next_retry_at->isPast();
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to only include read notifications.
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope a query to only include dismissed notifications.
     */
    public function scopeDismissed(Builder $query): Builder
    {
        return $query->where('is_dismissed', true);
    }

    /**
     * Scope a query to only include non-dismissed notifications.
     */
    public function scopeNotDismissed(Builder $query): Builder
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope a query to only include archived notifications.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope a query to only include non-archived notifications.
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope a query to only include pinned notifications.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to only include starred notifications.
     */
    public function scopeStarred(Builder $query): Builder
    {
        return $query->where('is_starred', true);
    }

    /**
     * Scope a query to only include snoozed notifications.
     */
    public function scopeSnoozed(Builder $query): Builder
    {
        return $query->where('is_snoozed', true)
                    ->where('snoozed_until', '>', now());
    }

    /**
     * Scope a query to only include notifications with specific delivery status.
     */
    public function scopeWithDeliveryStatus(Builder $query, string $status): Builder
    {
        return $query->where('delivery_status', $status);
    }

    /**
     * Scope a query to only include failed notifications.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('delivery_status', self::DELIVERY_STATUS_FAILED);
    }

    /**
     * Scope a query to only include notifications ready for retry.
     */
    public function scopeReadyForRetry(Builder $query): Builder
    {
        return $query->where('delivery_status', self::DELIVERY_STATUS_FAILED)
                    ->where('retry_count', '<', 'max_retries')
                    ->where('next_retry_at', '<=', now());
    }

    /**
     * Scope a query to only include notifications for a specific user priority.
     */
    public function scopeWithUserPriority(Builder $query, string $priority): Builder
    {
        return $query->where('user_priority', $priority);
    }

    /**
     * Scope a query to only include high priority notifications.
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('user_priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope a query to only include notifications for a specific device type.
     */
    public function scopeForDevice(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope a query to only include notifications for a specific platform.
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }
}
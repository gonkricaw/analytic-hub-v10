<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasUuid;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

/**
 * Notification Model
 * 
 * Manages system notifications for the Analytics Hub system.
 * Handles notification delivery, scheduling, tracking, and user interactions.
 * 
 * @property string $id
 * @property string $user_id
 * @property string $notifiable_type
 * @property string $notifiable_id
 * @property string $title
 * @property string $message
 * @property string $type
 * @property string|null $category
 * @property array|null $data
 * @property array|null $action_data
 * @property string|null $action_url
 * @property string|null $action_text
 * @property \Carbon\Carbon|null $read_at
 * @property bool $is_read
 * @property bool $is_important
 * @property bool $is_dismissible
 * @property string $delivery_method
 * @property bool $email_sent
 * @property bool $sms_sent
 * @property bool $push_sent
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $expires_at
 * @property int $retry_count
 * @property \Carbon\Carbon|null $last_retry_at
 * @property string|null $sender_id
 * @property string|null $source_type
 * @property string|null $source_reference
 * @property string|null $icon
 * @property string|null $color
 * @property array|null $metadata
 * @property int $priority
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Notification extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_notifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'notifiable_type',
        'notifiable_id',
        'title',
        'message',
        'type',
        'category',
        'data',
        'action_data',
        'action_url',
        'action_text',
        'read_at',
        'is_read',
        'is_important',
        'is_dismissible',
        'delivery_method',
        'email_sent',
        'sms_sent',
        'push_sent',
        'delivered_at',
        'scheduled_at',
        'expires_at',
        'retry_count',
        'last_retry_at',
        'sender_id',
        'source_type',
        'source_reference',
        'icon',
        'color',
        'metadata',
        'priority',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array',
        'action_data' => 'array',
        'read_at' => 'datetime',
        'is_read' => 'boolean',
        'is_important' => 'boolean',
        'is_dismissible' => 'boolean',
        'email_sent' => 'boolean',
        'sms_sent' => 'boolean',
        'push_sent' => 'boolean',
        'delivered_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'retry_count' => 'integer',
        'last_retry_at' => 'datetime',
        'metadata' => 'array',
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Notification type constants
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_SYSTEM = 'system';

    /**
     * Notification category constants
     */
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_REPORT = 'report';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_USER = 'user';
    const CATEGORY_CONTENT = 'content';
    const CATEGORY_AUTHENTICATION = 'authentication';

    /**
     * Delivery method constants
     */
    const DELIVERY_DATABASE = 'database';
    const DELIVERY_EMAIL = 'email';
    const DELIVERY_SMS = 'sms';
    const DELIVERY_PUSH = 'push';
    const DELIVERY_ALL = 'all';

    /**
     * Source type constants
     */
    const SOURCE_SYSTEM = 'system';
    const SOURCE_USER = 'user';
    const SOURCE_AUTOMATED = 'automated';

    /**
     * Priority constants
     */
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 5;

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Check if notification is important
     */
    public function isImportant(): bool
    {
        return $this->is_important;
    }

    /**
     * Check if notification is dismissible
     */
    public function isDismissible(): bool
    {
        return $this->is_dismissible;
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if notification is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /**
     * Check if notification is ready to deliver
     */
    public function isReadyToDeliver(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->isScheduled()) {
            return false;
        }

        return true;
    }

    /**
     * Check if notification has high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    /**
     * Get the target user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the notifiable entity relationship (polymorphic)
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the sender relationship
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
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
     * Get the user notifications relationship
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'notification_id');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }

    /**
     * Mark notification as delivered
     */
    public function markAsDelivered(string $method = null): void
    {
        $updateData = ['delivered_at' => now()];

        if ($method) {
            switch ($method) {
                case self::DELIVERY_EMAIL:
                    $updateData['email_sent'] = true;
                    break;
                case self::DELIVERY_SMS:
                    $updateData['sms_sent'] = true;
                    break;
                case self::DELIVERY_PUSH:
                    $updateData['push_sent'] = true;
                    break;
            }
        }

        $this->update($updateData);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->update(['last_retry_at' => now()]);
    }

    /**
     * Get notification age in human readable format
     */
    public function getAgeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get notification icon with fallback
     */
    public function getIconAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Default icons based on type
        return match ($this->type) {
            self::TYPE_SUCCESS => 'check-circle',
            self::TYPE_WARNING => 'exclamation-triangle',
            self::TYPE_ERROR => 'times-circle',
            self::TYPE_SYSTEM => 'cog',
            default => 'info-circle',
        };
    }

    /**
     * Get notification color with fallback
     */
    public function getColorAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Default colors based on type
        return match ($this->type) {
            self::TYPE_SUCCESS => 'green',
            self::TYPE_WARNING => 'yellow',
            self::TYPE_ERROR => 'red',
            self::TYPE_SYSTEM => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get encrypted message content
     */
    public function getEncryptedMessageAttribute(): string
    {
        return Crypt::encrypt($this->attributes['message']);
    }

    /**
     * Set encrypted message content
     */
    public function setMessageAttribute($value): void
    {
        $this->attributes['message'] = $value;
    }

    /**
     * Create notification for user
     */
    public static function createForUser(
        User $user,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        array $options = []
    ): self {
        return self::create(array_merge([
            'user_id' => $user->id,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ], $options));
    }

    /**
     * Create system notification
     */
    public static function createSystem(
        string $title,
        string $message,
        array $userIds = [],
        array $options = []
    ): array {
        $notifications = [];
        $defaultOptions = array_merge([
            'type' => self::TYPE_SYSTEM,
            'category' => self::CATEGORY_SYSTEM,
            'source_type' => self::SOURCE_SYSTEM,
            'is_important' => true,
        ], $options);

        if (empty($userIds)) {
            $userIds = User::active()->pluck('id')->toArray();
        }

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $notifications[] = self::createForUser(
                    $user,
                    $title,
                    $message,
                    $defaultOptions['type'],
                    array_merge($defaultOptions, [
                        'notifiable_type' => get_class($user),
                        'notifiable_id' => $user->id,
                    ])
                );
            }
        }

        return $notifications;
    }

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Read notifications
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Important notifications
     */
    public function scopeImportant(Builder $query): Builder
    {
        return $query->where('is_important', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by sender
     */
    public function scopeFromSender(Builder $query, $senderId): Builder
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope: High priority notifications
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_HIGH);
    }

    /**
     * Scope: Scheduled notifications
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>', now());
    }

    /**
     * Scope: Ready to deliver
     */
    public function scopeReadyToDeliver(Builder $query): Builder
    {
        return $query->where(function ($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope: Expired notifications
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Recent notifications
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Filter by delivery method
     */
    public function scopeDeliveryMethod(Builder $query, string $method): Builder
    {
        return $query->where('delivery_method', $method);
    }

    /**
     * Scope: Delivered notifications
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->whereNotNull('delivered_at');
    }

    /**
     * Scope: Pending delivery
     */
    public function scopePendingDelivery(Builder $query): Builder
    {
        return $query->whereNull('delivered_at');
    }

    /**
     * Scope: Search notifications
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('message', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }
}
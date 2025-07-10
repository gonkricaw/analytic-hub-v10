<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasUuid;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

/**
 * UserActivity Model
 * 
 * Manages comprehensive user activity tracking for the Analytics Hub system.
 * Handles activity logging, performance tracking, security monitoring, and audit trails.
 * 
 * @property string $id
 * @property string|null $user_id
 * @property string|null $user_email
 * @property string|null $user_name
 * @property string|null $impersonated_by
 * @property string $activity_type
 * @property string $activity_name
 * @property string|null $event
 * @property string|null $action
 * @property string|null $description
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $subject_name
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property array|null $properties
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $changes
 * @property array|null $attributes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property string|null $method
 * @property array|null $request_data
 * @property array|null $response_data
 * @property int|null $response_code
 * @property string|null $session_id
 * @property string|null $csrf_token
 * @property bool $is_authenticated
 * @property string|null $auth_method
 * @property array|null $auth_data
 * @property string|null $module
 * @property string|null $category
 * @property string $severity
 * @property string $type
 * @property bool $is_sensitive
 * @property bool $is_suspicious
 * @property int|null $risk_score
 * @property array|null $risk_factors
 * @property bool $requires_review
 * @property int|null $execution_time_ms
 * @property int|null $memory_usage_mb
 * @property int|null $query_count
 * @property array|null $performance_data
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $timezone
 * @property string|null $device_type
 * @property string|null $device_name
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $platform
 * @property string|null $platform_version
 * @property array|null $device_info
 * @property string|null $batch_id
 * @property string|null $correlation_id
 * @property string|null $trace_id
 * @property string|null $parent_activity_id
 * @property array|null $related_activities
 * @property string $status
 * @property string|null $status_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $duration_ms
 * @property string|null $error_message
 * @property string|null $error_details
 * @property string|null $stack_trace
 * @property string|null $error_code
 * @property array|null $error_context
 * @property bool $notify_admin
 * @property bool $alert_sent
 * @property \Carbon\Carbon|null $alert_sent_at
 * @property array|null $alert_recipients
 * @property bool $is_archived
 * @property \Carbon\Carbon|null $archived_at
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $can_be_deleted
 * @property bool $is_audit_required
 * @property bool $is_compliance_relevant
 * @property array|null $compliance_data
 * @property string|null $audit_notes
 * @property array|null $tags
 * @property array|null $metadata
 * @property array|null $custom_fields
 * @property string|null $notes
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class UserActivity extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_user_activities';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'user_email',
        'user_name',
        'impersonated_by',
        'activity_type',
        'activity_name',
        'event',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'subject_name',
        'causer_type',
        'causer_id',
        'properties',
        'old_values',
        'new_values',
        'changes',
        'attributes',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'request_data',
        'response_data',
        'response_code',
        'session_id',
        'csrf_token',
        'is_authenticated',
        'auth_method',
        'auth_data',
        'module',
        'category',
        'severity',
        'type',
        'is_sensitive',
        'is_suspicious',
        'risk_score',
        'risk_factors',
        'requires_review',
        'execution_time_ms',
        'memory_usage_mb',
        'query_count',
        'performance_data',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'device_type',
        'device_name',
        'browser',
        'browser_version',
        'platform',
        'platform_version',
        'device_info',
        'batch_id',
        'correlation_id',
        'trace_id',
        'parent_activity_id',
        'related_activities',
        'status',
        'status_message',
        'started_at',
        'completed_at',
        'duration_ms',
        'error_message',
        'error_details',
        'stack_trace',
        'error_code',
        'error_context',
        'notify_admin',
        'alert_sent',
        'alert_sent_at',
        'alert_recipients',
        'is_archived',
        'archived_at',
        'expires_at',
        'can_be_deleted',
        'is_audit_required',
        'is_compliance_relevant',
        'compliance_data',
        'audit_notes',
        'tags',
        'metadata',
        'custom_fields',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'attributes' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'response_code' => 'integer',
        'is_authenticated' => 'boolean',
        'auth_data' => 'array',
        'is_sensitive' => 'boolean',
        'is_suspicious' => 'boolean',
        'risk_score' => 'integer',
        'risk_factors' => 'array',
        'requires_review' => 'boolean',
        'execution_time_ms' => 'integer',
        'memory_usage_mb' => 'integer',
        'query_count' => 'integer',
        'performance_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'device_info' => 'array',
        'related_activities' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
        'error_context' => 'array',
        'notify_admin' => 'boolean',
        'alert_sent' => 'boolean',
        'alert_sent_at' => 'datetime',
        'alert_recipients' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'expires_at' => 'datetime',
        'can_be_deleted' => 'boolean',
        'is_audit_required' => 'boolean',
        'is_compliance_relevant' => 'boolean',
        'compliance_data' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Activity severity constants
     */
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Activity type constants
     */
    const TYPE_CREATE = 'create';
    const TYPE_READ = 'read';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_OTHER = 'other';

    /**
     * Activity status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Risk score thresholds
     */
    const RISK_LOW = 30;
    const RISK_MEDIUM = 60;
    const RISK_HIGH = 80;

    /**
     * Check if activity is sensitive
     */
    public function isSensitive(): bool
    {
        return $this->is_sensitive;
    }

    /**
     * Check if activity is suspicious
     */
    public function isSuspicious(): bool
    {
        return $this->is_suspicious;
    }

    /**
     * Check if activity requires review
     */
    public function requiresReview(): bool
    {
        return $this->requires_review;
    }

    /**
     * Check if activity is archived
     */
    public function isArchived(): bool
    {
        return $this->is_archived;
    }

    /**
     * Check if activity is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if activity is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if activity failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if activity has high risk
     */
    public function isHighRisk(): bool
    {
        return $this->risk_score && $this->risk_score >= self::RISK_HIGH;
    }

    /**
     * Get the user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the impersonator relationship
     */
    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_by');
    }

    /**
     * Get the subject relationship (polymorphic)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer relationship (polymorphic)
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
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
     * Mark activity as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Calculate duration if started_at is set
        if ($this->started_at) {
            $this->update([
                'duration_ms' => $this->started_at->diffInMilliseconds(now()),
            ]);
        }
    }

    /**
     * Mark activity as failed
     */
    public function markAsFailed(string $errorMessage, array $errorDetails = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'error_details' => json_encode($errorDetails),
            'completed_at' => now(),
        ]);
    }

    /**
     * Archive activity
     */
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Send alert for suspicious activity
     */
    public function sendAlert(array $recipients = []): void
    {
        if (!$this->alert_sent) {
            $this->update([
                'alert_sent' => true,
                'alert_sent_at' => now(),
                'alert_recipients' => $recipients,
            ]);
        }
    }

    /**
     * Calculate risk score based on factors
     */
    public function calculateRiskScore(): int
    {
        $score = 0;
        $factors = [];

        // Check for suspicious patterns
        if ($this->is_suspicious) {
            $score += 30;
            $factors[] = 'suspicious_activity';
        }

        // Check for sensitive operations
        if ($this->is_sensitive) {
            $score += 20;
            $factors[] = 'sensitive_operation';
        }

        // Check for failed operations
        if ($this->isFailed()) {
            $score += 15;
            $factors[] = 'failed_operation';
        }

        // Check for unusual timing
        if ($this->created_at->isWeekend() || $this->created_at->hour < 6 || $this->created_at->hour > 22) {
            $score += 10;
            $factors[] = 'unusual_timing';
        }

        // Check for high severity
        if ($this->severity === self::SEVERITY_CRITICAL) {
            $score += 25;
            $factors[] = 'critical_severity';
        } elseif ($this->severity === self::SEVERITY_ERROR) {
            $score += 15;
            $factors[] = 'error_severity';
        }

        // Update risk score and factors
        $this->update([
            'risk_score' => min($score, 100), // Cap at 100
            'risk_factors' => $factors,
        ]);

        return $score;
    }

    /**
     * Get activity duration in human readable format
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->duration_ms) {
            return null;
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        return round($this->duration_ms / 1000, 2) . 's';
    }

    /**
     * Get encrypted sensitive data
     */
    public function getEncryptedPropertiesAttribute(): string
    {
        return $this->is_sensitive ? Crypt::encrypt($this->attributes['properties']) : $this->attributes['properties'];
    }

    /**
     * Set encrypted sensitive data
     */
    public function setPropertiesAttribute($value): void
    {
        $this->attributes['properties'] = $this->is_sensitive ? Crypt::decrypt($value) : $value;
    }

    /**
     * Create activity log entry
     */
    public static function log(
        string $activityType,
        string $activityName,
        array $properties = [],
        ?User $user = null
    ): self {
        $request = request();
        
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'user_email' => $user?->email ?? auth()->user()?->email,
            'user_name' => $user?->name ?? auth()->user()?->name,
            'activity_type' => $activityType,
            'activity_name' => $activityName,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'session_id' => session()->getId(),
            'is_authenticated' => auth()->check(),
            'started_at' => now(),
        ]);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeActivityType(Builder $query, string $type): Builder
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Filter by severity
     */
    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Sensitive activities
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope: Suspicious activities
     */
    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope: High risk activities
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->where('risk_score', '>=', self::RISK_HIGH);
    }

    /**
     * Scope: Requires review
     */
    public function scopeRequiresReview(Builder $query): Builder
    {
        return $query->where('requires_review', true);
    }

    /**
     * Scope: Failed activities
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Completed activities
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Recent activities
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Filter by IP address
     */
    public function scopeFromIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope: Filter by session
     */
    public function scopeFromSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: Filter by module
     */
    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Archived activities
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Non-archived activities
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Expired activities
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Search activities
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('activity_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('user_email', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%");
        });
    }
}
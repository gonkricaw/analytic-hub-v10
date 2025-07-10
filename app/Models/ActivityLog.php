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
 * ActivityLog Model
 * 
 * Manages comprehensive activity logging and audit trails for the Analytics Hub system.
 * Tracks all user actions, system events, and security-related activities.
 * 
 * @property string $id
 * @property string|null $log_name
 * @property string $description
 * @property string $event
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array|null $subject_data
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property array|null $causer_data
 * @property array|null $properties
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $request_method
 * @property string|null $request_url
 * @property array|null $request_data
 * @property string|null $session_id
 * @property string|null $auth_method
 * @property bool $is_authenticated
 * @property string|null $module
 * @property string|null $category
 * @property string $severity
 * @property string $type
 * @property bool $is_sensitive
 * @property bool $is_suspicious
 * @property int $risk_score
 * @property array|null $security_flags
 * @property int|null $execution_time
 * @property int|null $memory_usage
 * @property int|null $query_count
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $platform
 * @property string|null $batch_id
 * @property string|null $correlation_id
 * @property string|null $parent_activity_id
 * @property string $status
 * @property string|null $error_message
 * @property array|null $error_details
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ActivityLog extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_activity_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'log_name',
        'description',
        'event',
        'action',
        'subject_type',
        'subject_id',
        'subject_data',
        'causer_type',
        'causer_id',
        'causer_data',
        'properties',
        'old_values',
        'new_values',
        'changes',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
        'request_data',
        'session_id',
        'auth_method',
        'is_authenticated',
        'module',
        'category',
        'severity',
        'type',
        'is_sensitive',
        'is_suspicious',
        'risk_score',
        'security_flags',
        'execution_time',
        'memory_usage',
        'query_count',
        'country',
        'region',
        'city',
        'device_type',
        'browser',
        'platform',
        'batch_id',
        'correlation_id',
        'parent_activity_id',
        'status',
        'error_message',
        'error_details',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'subject_data' => 'array',
        'causer_data' => 'array',
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'request_data' => 'array',
        'is_authenticated' => 'boolean',
        'is_sensitive' => 'boolean',
        'is_suspicious' => 'boolean',
        'risk_score' => 'integer',
        'security_flags' => 'array',
        'execution_time' => 'integer',
        'memory_usage' => 'integer',
        'query_count' => 'integer',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Activity severity constants
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Activity type constants
     */
    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';
    const TYPE_SECURITY = 'security';
    const TYPE_ADMIN = 'admin';
    const TYPE_API = 'api';

    /**
     * Activity status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Event type constants
     */
    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_DELETED = 'deleted';
    const EVENT_VIEWED = 'viewed';
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_FAILED_LOGIN = 'failed_login';
    const EVENT_PASSWORD_RESET = 'password_reset';
    const EVENT_PERMISSION_CHANGED = 'permission_changed';
    const EVENT_ROLE_ASSIGNED = 'role_assigned';
    const EVENT_ROLE_REMOVED = 'role_removed';

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
        return $this->risk_score >= self::RISK_HIGH;
    }

    /**
     * Check if activity is critical severity
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if activity is security-related
     */
    public function isSecurityRelated(): bool
    {
        return $this->type === self::TYPE_SECURITY;
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
     * Get the parent activity relationship
     */
    public function parentActivity(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_activity_id');
    }

    /**
     * Get child activities relationship
     */
    public function childActivities()
    {
        return $this->hasMany(self::class, 'parent_activity_id');
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

        // Calculate execution time if started_at is set
        if ($this->started_at) {
            $this->update([
                'execution_time' => $this->started_at->diffInMilliseconds(now()),
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
            'error_details' => $errorDetails,
            'completed_at' => now(),
        ]);
    }

    /**
     * Flag activity as suspicious
     */
    public function flagAsSuspicious(array $securityFlags = []): void
    {
        $this->update([
            'is_suspicious' => true,
            'security_flags' => array_merge($this->security_flags ?? [], $securityFlags),
            'risk_score' => min($this->risk_score + 25, 100),
        ]);
    }

    /**
     * Calculate and update risk score
     */
    public function calculateRiskScore(): int
    {
        $score = 0;
        $flags = $this->security_flags ?? [];

        // Base risk factors
        if ($this->is_suspicious) {
            $score += 30;
        }

        if ($this->is_sensitive) {
            $score += 20;
        }

        if ($this->isFailed()) {
            $score += 15;
        }

        // Severity-based scoring
        switch ($this->severity) {
            case self::SEVERITY_CRITICAL:
                $score += 25;
                break;
            case self::SEVERITY_HIGH:
                $score += 15;
                break;
            case self::SEVERITY_MEDIUM:
                $score += 5;
                break;
        }

        // Type-based scoring
        if ($this->type === self::TYPE_SECURITY) {
            $score += 20;
        }

        // Time-based risk (unusual hours)
        if ($this->created_at) {
            $hour = $this->created_at->hour;
            if ($hour < 6 || $hour > 22 || $this->created_at->isWeekend()) {
                $score += 10;
                $flags[] = 'unusual_timing';
            }
        }

        // Update risk score and flags
        $this->update([
            'risk_score' => min($score, 100),
            'security_flags' => $flags,
        ]);

        return $score;
    }

    /**
     * Get activity duration in human readable format
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->execution_time) {
            return null;
        }

        if ($this->execution_time < 1000) {
            return $this->execution_time . 'ms';
        }

        return round($this->execution_time / 1000, 2) . 's';
    }

    /**
     * Get memory usage in human readable format
     */
    public function getFormattedMemoryUsageAttribute(): ?string
    {
        if (!$this->memory_usage) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->memory_usage;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get encrypted sensitive properties
     */
    public function getEncryptedPropertiesAttribute(): string
    {
        return $this->is_sensitive ? Crypt::encrypt($this->attributes['properties']) : $this->attributes['properties'];
    }

    /**
     * Set encrypted sensitive properties
     */
    public function setPropertiesAttribute($value): void
    {
        $this->attributes['properties'] = $this->is_sensitive ? Crypt::decrypt($value) : $value;
    }

    /**
     * Create activity log entry
     */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        string $logName = null
    ): self {
        $request = request();
        $user = $causer ?? auth()->user();
        
        return self::create([
            'log_name' => $logName ?? 'default',
            'description' => $description,
            'event' => $event,
            'action' => $event,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'subject_data' => $subject ? $subject->toArray() : null,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'causer_data' => $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : null,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_method' => $request?->method(),
            'request_url' => $request?->fullUrl(),
            'session_id' => session()->getId(),
            'is_authenticated' => auth()->check(),
            'started_at' => now(),
        ]);
    }

    /**
     * Log user login activity
     */
    public static function logLogin(User $user, bool $successful = true): self
    {
        return self::log(
            $successful ? self::EVENT_LOGIN : self::EVENT_FAILED_LOGIN,
            $successful ? 'User logged in successfully' : 'Failed login attempt',
            null,
            $user,
            ['successful' => $successful],
            'authentication'
        )->update([
            'type' => self::TYPE_SECURITY,
            'severity' => $successful ? self::SEVERITY_LOW : self::SEVERITY_MEDIUM,
        ]);
    }

    /**
     * Log user logout activity
     */
    public static function logLogout(User $user): self
    {
        return self::log(
            self::EVENT_LOGOUT,
            'User logged out',
            null,
            $user,
            [],
            'authentication'
        )->update([
            'type' => self::TYPE_SECURITY,
            'severity' => self::SEVERITY_LOW,
        ]);
    }

    /**
     * Scope: Filter by log name
     */
    public function scopeLogName(Builder $query, string $logName): Builder
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope: Filter by event
     */
    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: Filter by subject
     */
    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', get_class($subject))
                    ->where('subject_id', $subject->id);
    }

    /**
     * Scope: Filter by causer
     */
    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query->where('causer_type', get_class($causer))
                    ->where('causer_id', $causer->id);
    }

    /**
     * Scope: Filter by severity
     */
    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
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
     * Scope: Filter by batch
     */
    public function scopeBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope: Filter by correlation
     */
    public function scopeCorrelation(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Scope: Security-related activities
     */
    public function scopeSecurityRelated(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SECURITY);
    }

    /**
     * Scope: Authentication activities
     */
    public function scopeAuthentication(Builder $query): Builder
    {
        return $query->whereIn('event', [self::EVENT_LOGIN, self::EVENT_LOGOUT, self::EVENT_FAILED_LOGIN]);
    }

    /**
     * Scope: Search activities
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
              ->orWhere('action', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%")
              ->orWhereHas('causer', function ($subQuery) use ($search) {
                  $subQuery->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }
}
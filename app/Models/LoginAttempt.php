<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * LoginAttempt Model
 * 
 * Tracks and manages user login attempts for security and analytics purposes.
 * Handles attempt logging, risk assessment, rate limiting, and security analysis.
 * 
 * @property string $id
 * @property string|null $user_id
 * @property string|null $email
 * @property string|null $username
 * @property string $status
 * @property string|null $failure_reason
 * @property \Carbon\Carbon $attempted_at
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $platform
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $is_suspicious
 * @property int $risk_score
 * @property array|null $risk_factors
 * @property bool $is_blocked
 * @property string|null $session_id
 * @property bool $remember_me
 * @property string|null $two_factor_method
 * @property bool $two_factor_success
 * @property int $attempts_count
 * @property \Carbon\Carbon|null $first_attempt_at
 * @property \Carbon\Carbon|null $last_attempt_at
 * @property \Carbon\Carbon|null $blocked_until
 * @property string|null $referrer
 * @property array|null $request_headers
 * @property string|null $login_method
 * @property string|null $client_id
 * @property int|null $response_time
 * @property string|null $redirect_url
 * @property array|null $response_data
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class LoginAttempt extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_login_attempts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'email',
        'username',
        'status',
        'failure_reason',
        'attempted_at',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'is_suspicious',
        'risk_score',
        'risk_factors',
        'is_blocked',
        'session_id',
        'remember_me',
        'two_factor_method',
        'two_factor_success',
        'attempts_count',
        'first_attempt_at',
        'last_attempt_at',
        'blocked_until',
        'referrer',
        'request_headers',
        'login_method',
        'client_id',
        'response_time',
        'redirect_url',
        'response_data',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'attempted_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_suspicious' => 'boolean',
        'risk_score' => 'integer',
        'risk_factors' => 'array',
        'is_blocked' => 'boolean',
        'remember_me' => 'boolean',
        'two_factor_success' => 'boolean',
        'attempts_count' => 'integer',
        'first_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'blocked_until' => 'datetime',
        'request_headers' => 'array',
        'response_time' => 'integer',
        'response_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_PENDING = 'pending';
    const STATUS_TIMEOUT = 'timeout';
    const STATUS_RATE_LIMITED = 'rate_limited';

    /**
     * Failure reason constants
     */
    const FAILURE_INVALID_CREDENTIALS = 'invalid_credentials';
    const FAILURE_ACCOUNT_LOCKED = 'account_locked';
    const FAILURE_ACCOUNT_DISABLED = 'account_disabled';
    const FAILURE_TWO_FACTOR_FAILED = 'two_factor_failed';
    const FAILURE_RATE_LIMITED = 'rate_limited';
    const FAILURE_IP_BLOCKED = 'ip_blocked';
    const FAILURE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const FAILURE_INVALID_SESSION = 'invalid_session';
    const FAILURE_EXPIRED_TOKEN = 'expired_token';
    const FAILURE_MAINTENANCE_MODE = 'maintenance_mode';

    /**
     * Login method constants
     */
    const METHOD_PASSWORD = 'password';
    const METHOD_SOCIAL = 'social';
    const METHOD_SSO = 'sso';
    const METHOD_API_TOKEN = 'api_token';
    const METHOD_REMEMBER_TOKEN = 'remember_token';
    const METHOD_TWO_FACTOR = 'two_factor';

    /**
     * Two factor method constants
     */
    const TWO_FACTOR_SMS = 'sms';
    const TWO_FACTOR_EMAIL = 'email';
    const TWO_FACTOR_TOTP = 'totp';
    const TWO_FACTOR_BACKUP_CODE = 'backup_code';

    /**
     * Risk score thresholds
     */
    const RISK_LOW = 30;
    const RISK_MEDIUM = 60;
    const RISK_HIGH = 80;
    const RISK_CRITICAL = 95;

    /**
     * Device type constants
     */
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_MOBILE = 'mobile';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_BOT = 'bot';
    const DEVICE_UNKNOWN = 'unknown';

    /**
     * Check if attempt was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if attempt failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if attempt was blocked
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED || $this->is_blocked;
    }

    /**
     * Check if attempt is suspicious
     */
    public function isSuspicious(): bool
    {
        return $this->is_suspicious;
    }

    /**
     * Check if attempt is high risk
     */
    public function isHighRisk(): bool
    {
        return $this->risk_score >= self::RISK_HIGH;
    }

    /**
     * Check if attempt is critical risk
     */
    public function isCriticalRisk(): bool
    {
        return $this->risk_score >= self::RISK_CRITICAL;
    }

    /**
     * Check if two factor authentication was used
     */
    public function hasTwoFactor(): bool
    {
        return !empty($this->two_factor_method);
    }

    /**
     * Check if two factor authentication was successful
     */
    public function isTwoFactorSuccessful(): bool
    {
        return $this->two_factor_success;
    }

    /**
     * Check if remember me was used
     */
    public function hasRememberMe(): bool
    {
        return $this->remember_me;
    }

    /**
     * Check if attempt is currently blocked
     */
    public function isCurrentlyBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    /**
     * Get the user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Get risk level based on score
     */
    public function getRiskLevel(): string
    {
        if ($this->risk_score >= self::RISK_CRITICAL) {
            return 'critical';
        } elseif ($this->risk_score >= self::RISK_HIGH) {
            return 'high';
        } elseif ($this->risk_score >= self::RISK_MEDIUM) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get human readable response time
     */
    public function getHumanResponseTime(): string
    {
        if (!$this->response_time) {
            return 'N/A';
        }

        if ($this->response_time < 1000) {
            return $this->response_time . 'ms';
        }

        return round($this->response_time / 1000, 2) . 's';
    }

    /**
     * Get location string
     */
    public function getLocationString(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);
        return implode(', ', $parts) ?: 'Unknown';
    }

    /**
     * Get device info string
     */
    public function getDeviceInfo(): string
    {
        $parts = array_filter([$this->device_type, $this->browser, $this->platform]);
        return implode(' / ', $parts) ?: 'Unknown';
    }

    /**
     * Mark attempt as suspicious
     */
    public function markAsSuspicious(array $reasons = []): void
    {
        $this->update([
            'is_suspicious' => true,
            'risk_factors' => array_merge($this->risk_factors ?? [], $reasons),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Block IP address
     */
    public function blockIp(Carbon $until = null): void
    {
        $this->update([
            'is_blocked' => true,
            'blocked_until' => $until ?? now()->addHours(24),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Unblock IP address
     */
    public function unblockIp(): void
    {
        $this->update([
            'is_blocked' => false,
            'blocked_until' => null,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Calculate and update risk score
     */
    public function calculateRiskScore(): int
    {
        $score = 0;
        $factors = [];

        // Failed attempt
        if ($this->isFailed()) {
            $score += 20;
            $factors[] = 'failed_attempt';
        }

        // Multiple attempts from same IP
        $recentAttempts = self::where('ip_address', $this->ip_address)
                             ->where('attempted_at', '>=', now()->subHour())
                             ->count();
        if ($recentAttempts > 5) {
            $score += 30;
            $factors[] = 'multiple_attempts';
        }

        // Unknown location
        if (empty($this->country)) {
            $score += 10;
            $factors[] = 'unknown_location';
        }

        // Suspicious user agent
        if (empty($this->user_agent) || str_contains(strtolower($this->user_agent), 'bot')) {
            $score += 25;
            $factors[] = 'suspicious_user_agent';
        }

        // Off-hours attempt (assuming business hours 9-17)
        $hour = $this->attempted_at->hour;
        if ($hour < 9 || $hour > 17) {
            $score += 5;
            $factors[] = 'off_hours';
        }

        // Weekend attempt
        if ($this->attempted_at->isWeekend()) {
            $score += 5;
            $factors[] = 'weekend';
        }

        // Two factor failure
        if ($this->hasTwoFactor() && !$this->isTwoFactorSuccessful()) {
            $score += 15;
            $factors[] = 'two_factor_failed';
        }

        $this->update([
            'risk_score' => min($score, 100),
            'risk_factors' => $factors,
            'is_suspicious' => $score >= self::RISK_MEDIUM,
        ]);

        return $score;
    }

    /**
     * Log a login attempt
     */
    public static function logAttempt(array $data): self
    {
        $attempt = self::create(array_merge($data, [
            'attempted_at' => now(),
            'attempts_count' => 1,
            'first_attempt_at' => now(),
            'last_attempt_at' => now(),
            'created_by' => auth()->id(),
        ]));

        // Calculate risk score
        $attempt->calculateRiskScore();

        return $attempt;
    }

    /**
     * Get recent failed attempts for IP
     */
    public static function getRecentFailedAttempts(string $ip, int $minutes = 60): int
    {
        return self::where('ip_address', $ip)
                  ->where('status', self::STATUS_FAILED)
                  ->where('attempted_at', '>=', now()->subMinutes($minutes))
                  ->count();
    }

    /**
     * Get recent failed attempts for user
     */
    public static function getRecentFailedAttemptsForUser(string $identifier, int $minutes = 60): int
    {
        return self::where(function ($query) use ($identifier) {
                      $query->where('email', $identifier)
                            ->orWhere('username', $identifier);
                  })
                  ->where('status', self::STATUS_FAILED)
                  ->where('attempted_at', '>=', now()->subMinutes($minutes))
                  ->count();
    }

    /**
     * Check if IP is blocked
     */
    public static function isIpBlocked(string $ip): bool
    {
        return self::where('ip_address', $ip)
                  ->where('is_blocked', true)
                  ->where(function ($query) {
                      $query->whereNull('blocked_until')
                            ->orWhere('blocked_until', '>', now());
                  })
                  ->exists();
    }

    /**
     * Get login statistics
     */
    public static function getLoginStats(int $days = 30): array
    {
        $cacheKey = "login_stats_{$days}_days";
        
        return Cache::remember($cacheKey, 300, function () use ($days) {
            $startDate = now()->subDays($days);
            
            $total = self::where('attempted_at', '>=', $startDate)->count();
            $successful = self::where('attempted_at', '>=', $startDate)
                             ->where('status', self::STATUS_SUCCESS)
                             ->count();
            $failed = self::where('attempted_at', '>=', $startDate)
                         ->where('status', self::STATUS_FAILED)
                         ->count();
            $blocked = self::where('attempted_at', '>=', $startDate)
                          ->where('status', self::STATUS_BLOCKED)
                          ->count();
            $suspicious = self::where('attempted_at', '>=', $startDate)
                             ->where('is_suspicious', true)
                             ->count();
            
            return [
                'total' => $total,
                'successful' => $successful,
                'failed' => $failed,
                'blocked' => $blocked,
                'suspicious' => $suspicious,
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Successful attempts
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope: Failed attempts
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Blocked attempts
     */
    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BLOCKED)
                    ->orWhere('is_blocked', true);
    }

    /**
     * Scope: Suspicious attempts
     */
    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope: High risk attempts
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->where('risk_score', '>=', self::RISK_HIGH);
    }

    /**
     * Scope: Filter by IP address
     */
    public function scopeIpAddress(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by email
     */
    public function scopeEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope: Filter by username
     */
    public function scopeUsername(Builder $query, string $username): Builder
    {
        return $query->where('username', $username);
    }

    /**
     * Scope: Recent attempts
     */
    public function scopeRecent(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope: Filter by country
     */
    public function scopeCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: Filter by device type
     */
    public function scopeDeviceType(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope: Filter by login method
     */
    public function scopeLoginMethod(Builder $query, string $method): Builder
    {
        return $query->where('login_method', $method);
    }

    /**
     * Scope: With two factor
     */
    public function scopeWithTwoFactor(Builder $query): Builder
    {
        return $query->whereNotNull('two_factor_method');
    }

    /**
     * Scope: Two factor successful
     */
    public function scopeTwoFactorSuccessful(Builder $query): Builder
    {
        return $query->where('two_factor_success', true);
    }

    /**
     * Scope: Currently blocked
     */
    public function scopeCurrentlyBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true)
                    ->where(function ($q) {
                        $q->whereNull('blocked_until')
                          ->orWhere('blocked_until', '>', now());
                    });
    }

    /**
     * Scope: Search attempts
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('email', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%")
              ->orWhere('country', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('device_type', 'like', "%{$search}%")
              ->orWhere('browser', 'like', "%{$search}%")
              ->orWhere('platform', 'like', "%{$search}%");
        });
    }
}
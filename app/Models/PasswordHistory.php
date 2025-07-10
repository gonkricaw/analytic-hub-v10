<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

/**
 * PasswordHistory Model
 * 
 * Tracks password history for users to enforce password reuse policies
 * and maintain security compliance in the Analytics Hub system.
 * 
 * @property string $id
 * @property string $user_id
 * @property string $password_hash
 * @property string $hash_algorithm
 * @property int|null $hash_cost
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_current
 * @property bool $was_forced_change
 * @property int|null $strength_score
 * @property array|null $strength_analysis
 * @property bool $meets_policy
 * @property array|null $policy_violations
 * @property string $change_reason
 * @property string $change_method
 * @property string|null $change_notes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $is_compromised
 * @property \Carbon\Carbon|null $compromised_at
 * @property string|null $compromise_reason
 * @property int $login_count
 * @property \Carbon\Carbon|null $first_used_at
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $retired_at
 * @property bool $is_temporary
 * @property bool $requires_change
 * @property int|null $days_until_expiry
 * @property array|null $validation_errors
 * @property string|null $changed_by
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $updated_at
 */
class PasswordHistory extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_password_histories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'password_hash',
        'hash_algorithm',
        'hash_cost',
        'expires_at',
        'is_current',
        'was_forced_change',
        'strength_score',
        'strength_analysis',
        'meets_policy',
        'policy_violations',
        'change_reason',
        'change_method',
        'change_notes',
        'ip_address',
        'user_agent',
        'is_compromised',
        'compromised_at',
        'compromise_reason',
        'login_count',
        'first_used_at',
        'last_used_at',
        'retired_at',
        'is_temporary',
        'requires_change',
        'days_until_expiry',
        'validation_errors',
        'changed_by',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hash_cost' => 'integer',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_current' => 'boolean',
        'was_forced_change' => 'boolean',
        'strength_score' => 'integer',
        'strength_analysis' => 'array',
        'meets_policy' => 'boolean',
        'policy_violations' => 'array',
        'is_compromised' => 'boolean',
        'compromised_at' => 'datetime',
        'login_count' => 'integer',
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
        'retired_at' => 'datetime',
        'is_temporary' => 'boolean',
        'requires_change' => 'boolean',
        'days_until_expiry' => 'integer',
        'validation_errors' => 'array',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Change reason constants
     */
    const CHANGE_REASON_INITIAL = 'initial';
    const CHANGE_REASON_USER_REQUESTED = 'user_requested';
    const CHANGE_REASON_ADMIN_FORCED = 'admin_forced';
    const CHANGE_REASON_POLICY_EXPIRED = 'policy_expired';
    const CHANGE_REASON_SECURITY_BREACH = 'security_breach';
    const CHANGE_REASON_FORGOT_PASSWORD = 'forgot_password';
    const CHANGE_REASON_FIRST_LOGIN = 'first_login';

    /**
     * Change method constants
     */
    const CHANGE_METHOD_FORM = 'form';
    const CHANGE_METHOD_API = 'api';
    const CHANGE_METHOD_ADMIN = 'admin';
    const CHANGE_METHOD_RESET = 'reset';
    const CHANGE_METHOD_IMPORT = 'import';

    /**
     * Hash algorithm constants
     */
    const HASH_BCRYPT = 'bcrypt';
    const HASH_ARGON2I = 'argon2i';
    const HASH_ARGON2ID = 'argon2id';

    /**
     * Strength score thresholds
     */
    const STRENGTH_WEAK = 30;
    const STRENGTH_FAIR = 50;
    const STRENGTH_GOOD = 70;
    const STRENGTH_STRONG = 85;

    /**
     * Check if password is current
     */
    public function isCurrent(): bool
    {
        return $this->is_current;
    }

    /**
     * Check if password was forced change
     */
    public function wasForcedChange(): bool
    {
        return $this->was_forced_change;
    }

    /**
     * Check if password is compromised
     */
    public function isCompromised(): bool
    {
        return $this->is_compromised;
    }

    /**
     * Check if password is temporary
     */
    public function isTemporary(): bool
    {
        return $this->is_temporary;
    }

    /**
     * Check if password requires change
     */
    public function requiresChange(): bool
    {
        return $this->requires_change;
    }

    /**
     * Check if password meets policy
     */
    public function meetsPolicy(): bool
    {
        return $this->meets_policy;
    }

    /**
     * Check if password is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if password is retired
     */
    public function isRetired(): bool
    {
        return !is_null($this->retired_at);
    }

    /**
     * Get the user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who changed the password
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
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
     * Get password strength level
     */
    public function getStrengthLevel(): string
    {
        if (!$this->strength_score) {
            return 'unknown';
        }

        if ($this->strength_score >= self::STRENGTH_STRONG) {
            return 'strong';
        } elseif ($this->strength_score >= self::STRENGTH_GOOD) {
            return 'good';
        } elseif ($this->strength_score >= self::STRENGTH_FAIR) {
            return 'fair';
        } else {
            return 'weak';
        }
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Get password age in days
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get human readable age
     */
    public function getHumanAge(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if password matches given password
     */
    public function checkPassword(string $password): bool
    {
        return Hash::check($password, $this->password_hash);
    }

    /**
     * Mark password as compromised
     */
    public function markAsCompromised(string $reason = null): void
    {
        $this->update([
            'is_compromised' => true,
            'compromised_at' => now(),
            'compromise_reason' => $reason,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Retire password
     */
    public function retire(): void
    {
        $this->update([
            'is_current' => false,
            'retired_at' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Record password usage
     */
    public function recordUsage(): void
    {
        $this->increment('login_count');
        
        $updates = [
            'last_used_at' => now(),
            'updated_by' => auth()->id(),
        ];
        
        if (!$this->first_used_at) {
            $updates['first_used_at'] = now();
        }
        
        $this->update($updates);
    }

    /**
     * Set as current password
     */
    public function setAsCurrent(): void
    {
        // First, mark all other passwords for this user as not current
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);
        
        // Then mark this one as current
        $this->update([
            'is_current' => true,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Calculate password strength score
     */
    public static function calculateStrengthScore(string $password): array
    {
        $score = 0;
        $analysis = [];
        $violations = [];
        
        // Length check
        $length = strlen($password);
        if ($length >= 12) {
            $score += 25;
            $analysis['length'] = 'excellent';
        } elseif ($length >= 8) {
            $score += 15;
            $analysis['length'] = 'good';
        } elseif ($length >= 6) {
            $score += 5;
            $analysis['length'] = 'fair';
            $violations[] = 'Password should be at least 8 characters';
        } else {
            $analysis['length'] = 'poor';
            $violations[] = 'Password must be at least 6 characters';
        }
        
        // Character variety
        if (preg_match('/[a-z]/', $password)) {
            $score += 5;
            $analysis['lowercase'] = true;
        } else {
            $analysis['lowercase'] = false;
            $violations[] = 'Password should contain lowercase letters';
        }
        
        if (preg_match('/[A-Z]/', $password)) {
            $score += 5;
            $analysis['uppercase'] = true;
        } else {
            $analysis['uppercase'] = false;
            $violations[] = 'Password should contain uppercase letters';
        }
        
        if (preg_match('/[0-9]/', $password)) {
            $score += 5;
            $analysis['numbers'] = true;
        } else {
            $analysis['numbers'] = false;
            $violations[] = 'Password should contain numbers';
        }
        
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 10;
            $analysis['special_chars'] = true;
        } else {
            $analysis['special_chars'] = false;
            $violations[] = 'Password should contain special characters';
        }
        
        // Pattern checks
        if (!preg_match('/(..).*\1/', $password)) {
            $score += 10;
            $analysis['no_repeating_patterns'] = true;
        } else {
            $analysis['no_repeating_patterns'] = false;
            $violations[] = 'Password contains repeating patterns';
        }
        
        // Common password check (simplified)
        $commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein'];
        if (!in_array(strtolower($password), $commonPasswords)) {
            $score += 15;
            $analysis['not_common'] = true;
        } else {
            $analysis['not_common'] = false;
            $violations[] = 'Password is too common';
        }
        
        // Sequential characters check
        if (!preg_match('/(?:abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz|123|234|345|456|567|678|789)/i', $password)) {
            $score += 10;
            $analysis['no_sequences'] = true;
        } else {
            $analysis['no_sequences'] = false;
            $violations[] = 'Password contains sequential characters';
        }
        
        // Keyboard patterns check
        if (!preg_match('/(?:qwe|wer|ert|rty|tyu|yui|uio|iop|asd|sdf|dfg|fgh|ghj|hjk|jkl|zxc|xcv|cvb|vbn|bnm)/i', $password)) {
            $score += 10;
            $analysis['no_keyboard_patterns'] = true;
        } else {
            $analysis['no_keyboard_patterns'] = false;
            $violations[] = 'Password contains keyboard patterns';
        }
        
        $score = min($score, 100);
        $meetsPolicy = $score >= self::STRENGTH_FAIR && empty($violations);
        
        return [
            'score' => $score,
            'analysis' => $analysis,
            'violations' => $violations,
            'meets_policy' => $meetsPolicy,
            'level' => self::getStrengthLevelFromScore($score),
        ];
    }

    /**
     * Get strength level from score
     */
    protected static function getStrengthLevelFromScore(int $score): string
    {
        if ($score >= self::STRENGTH_STRONG) {
            return 'strong';
        } elseif ($score >= self::STRENGTH_GOOD) {
            return 'good';
        } elseif ($score >= self::STRENGTH_FAIR) {
            return 'fair';
        } else {
            return 'weak';
        }
    }

    /**
     * Create password history entry
     */
    public static function createEntry(array $data): self
    {
        // Calculate strength if password is provided
        if (isset($data['password'])) {
            $strength = self::calculateStrengthScore($data['password']);
            $data['strength_score'] = $strength['score'];
            $data['strength_analysis'] = $strength['analysis'];
            $data['meets_policy'] = $strength['meets_policy'];
            $data['policy_violations'] = $strength['violations'];
            
            // Hash the password
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }
        
        return self::create(array_merge($data, [
            'created_by' => auth()->id(),
        ]));
    }

    /**
     * Check if password was used before by user
     */
    public static function wasPasswordUsedBefore(string $userId, string $password, int $historyLimit = 5): bool
    {
        $recentPasswords = self::where('user_id', $userId)
                              ->orderBy('created_at', 'desc')
                              ->limit($historyLimit)
                              ->pluck('password_hash');
        
        foreach ($recentPasswords as $hash) {
            if (Hash::check($password, $hash)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get password statistics for user
     */
    public static function getUserPasswordStats(string $userId): array
    {
        $cacheKey = "password_stats_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $total = self::where('user_id', $userId)->count();
            $current = self::where('user_id', $userId)->where('is_current', true)->first();
            $compromised = self::where('user_id', $userId)->where('is_compromised', true)->count();
            $temporary = self::where('user_id', $userId)->where('is_temporary', true)->count();
            $avgStrength = self::where('user_id', $userId)
                              ->whereNotNull('strength_score')
                              ->avg('strength_score');
            
            return [
                'total_passwords' => $total,
                'current_password_age' => $current ? $current->getAgeInDays() : null,
                'current_strength' => $current ? $current->strength_score : null,
                'compromised_count' => $compromised,
                'temporary_count' => $temporary,
                'average_strength' => $avgStrength ? round($avgStrength, 2) : null,
                'last_changed' => $current ? $current->created_at : null,
            ];
        });
    }

    /**
     * Scope: Filter by user
     */
    public function scopeUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Current passwords
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: Compromised passwords
     */
    public function scopeCompromised(Builder $query): Builder
    {
        return $query->where('is_compromised', true);
    }

    /**
     * Scope: Temporary passwords
     */
    public function scopeTemporary(Builder $query): Builder
    {
        return $query->where('is_temporary', true);
    }

    /**
     * Scope: Expired passwords
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Requires change
     */
    public function scopeRequiresChange(Builder $query): Builder
    {
        return $query->where('requires_change', true);
    }

    /**
     * Scope: Filter by change reason
     */
    public function scopeChangeReason(Builder $query, string $reason): Builder
    {
        return $query->where('change_reason', $reason);
    }

    /**
     * Scope: Filter by strength level
     */
    public function scopeStrengthLevel(Builder $query, string $level): Builder
    {
        switch ($level) {
            case 'weak':
                return $query->where('strength_score', '<', self::STRENGTH_FAIR);
            case 'fair':
                return $query->whereBetween('strength_score', [self::STRENGTH_FAIR, self::STRENGTH_GOOD - 1]);
            case 'good':
                return $query->whereBetween('strength_score', [self::STRENGTH_GOOD, self::STRENGTH_STRONG - 1]);
            case 'strong':
                return $query->where('strength_score', '>=', self::STRENGTH_STRONG);
            default:
                return $query;
        }
    }

    /**
     * Scope: Recent passwords
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Policy violations
     */
    public function scopePolicyViolations(Builder $query): Builder
    {
        return $query->where('meets_policy', false);
    }

    /**
     * Check if password has been used before (last 5 passwords)
     * 
     * @param string $userId User UUID
     * @param string $password Plain text password to check
     * @return bool True if password was used before
     */
    public static function isPasswordReused(string $userId, string $password): bool
    {
        // Get last 5 password hashes for the user
        $recentPasswords = self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('password_hash');
        
        // Check if any of the recent passwords match
        foreach ($recentPasswords as $hash) {
            if (Hash::check($password, $hash)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add new password to history and cleanup old entries
     * 
     * @param string $userId User UUID
     * @param string $password Plain text password
     * @param array $options Additional options for password tracking
     * @return PasswordHistory Created password history record
     */
    public static function addPasswordToHistory(string $userId, string $password, array $options = []): self
    {
        // Mark all previous passwords as not current
        self::where('user_id', $userId)
            ->where('is_current', true)
            ->update(['is_current' => false, 'retired_at' => now()]);
        
        // Analyze password strength
        $analysis = self::analyzePasswordStrength($password);
        
        // Create new password history record
        $passwordHistory = self::create([
            'user_id' => $userId,
            'password_hash' => Hash::make($password),
            'hash_algorithm' => 'bcrypt',
            'hash_cost' => config('hashing.bcrypt.rounds', 12),
            'is_current' => true,
            'strength_score' => $analysis['score'],
            'strength_analysis' => $analysis['analysis'],
            'meets_policy' => $analysis['meets_policy'],
            'policy_violations' => $analysis['violations'],
            'change_reason' => $options['reason'] ?? self::CHANGE_REASON_USER_REQUESTED,
            'change_method' => $options['method'] ?? self::CHANGE_METHOD_FORM,
            'change_notes' => $options['notes'] ?? null,
            'ip_address' => $options['ip_address'] ?? request()->ip(),
            'user_agent' => $options['user_agent'] ?? request()->userAgent(),
            'was_forced_change' => $options['forced'] ?? false,
            'is_temporary' => $options['temporary'] ?? false,
            'requires_change' => $options['requires_change'] ?? false,
            'expires_at' => $options['expires_at'] ?? now()->addDays(90),
            'created_by' => $options['created_by'] ?? auth()->id(),
            'changed_by' => $options['changed_by'] ?? auth()->id(),
        ]);
        
        // Cleanup old password history (keep only last 5)
        self::cleanupOldPasswords($userId);
        
        return $passwordHistory;
    }
    
    /**
     * Cleanup old password history entries (keep only last 5)
     * 
     * @param string $userId User UUID
     * @return int Number of deleted records
     */
    public static function cleanupOldPasswords(string $userId): int
    {
        // Get IDs of passwords to keep (last 5)
        $keepIds = self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('id');
        
        // Delete older passwords
        return self::where('user_id', $userId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
    
    /**
     * Validate password against policy and history
     * 
     * @param string $userId User UUID
     * @param string $password Plain text password
     * @return array Validation result with errors if any
     */
    public static function validatePassword(string $userId, string $password): array
    {
        $errors = [];
        
        // Check password strength
        $analysis = self::analyzePasswordStrength($password);
        if (!$analysis['meets_policy']) {
            $errors = array_merge($errors, $analysis['violations']);
        }
        
        // Check password reuse
        if (self::isPasswordReused($userId, $password)) {
            $errors[] = 'Password has been used recently. Please choose a different password.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $analysis,
        ];
    }
    
    /**
     * Get password expiry information for user
     * 
     * @param string $userId User UUID
     * @return array Password expiry details
     */
    public static function getPasswordExpiry(string $userId): array
    {
        $current = self::where('user_id', $userId)
            ->where('is_current', true)
            ->first();
        
        if (!$current) {
            return [
                'has_password' => false,
                'is_expired' => true,
                'days_until_expiry' => 0,
                'expires_at' => null,
            ];
        }
        
        $expiresAt = $current->expires_at;
        $daysUntilExpiry = $expiresAt ? now()->diffInDays($expiresAt, false) : null;
        
        return [
            'has_password' => true,
            'is_expired' => $expiresAt && $expiresAt->isPast(),
            'days_until_expiry' => $daysUntilExpiry,
            'expires_at' => $expiresAt,
            'age_in_days' => $current->getAgeInDays(),
            'requires_change' => $current->requires_change,
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Clear cache when password history is updated
        static::saved(function ($passwordHistory) {
            Cache::forget("password_stats_{$passwordHistory->user_id}");
        });
        
        static::deleted(function ($passwordHistory) {
            Cache::forget("password_stats_{$passwordHistory->user_id}");
        });
    }
}
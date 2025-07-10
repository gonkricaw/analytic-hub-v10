<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Traits\HasUuid;
use Carbon\Carbon;

class BlacklistedIp extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'idbi_blacklisted_ips';

    protected $fillable = [
        'ip_address',
        'ip_range',
        'ip_version',
        'subnet_mask',
        'blacklist_type',
        'reason',
        'description',
        'severity',
        'is_active',
        'is_permanent',
        'blacklisted_at',
        'expires_at',
        'duration_hours',
        'country',
        'country_code',
        'region',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'organization',
        'threat_indicators',
        'threat_score',
        'threat_sources',
        'is_known_threat',
        'malware_signatures',
        'failed_login_count',
        'suspicious_activity_count',
        'total_requests',
        'first_seen_at',
        'last_seen_at',
        'last_activity_at',
        'associated_user_id',
        'affected_users',
        'user_agent',
        'session_data',
        'blacklisted_by',
        'approved_by',
        'approved_at',
        'removed_by',
        'removed_at',
        'removal_reason',
        'has_whitelist_override',
        'whitelisted_by',
        'whitelisted_at',
        'whitelist_reason',
        'whitelist_expires_at',
        'monitor_activity',
        'alert_on_activity',
        'alert_recipients',
        'last_alert_sent_at',
        'alert_count',
        'bypass_rules',
        'allow_api_access',
        'allow_admin_access',
        'exception_rules',
        'rate_limit_requests',
        'rate_limit_window',
        'enforce_rate_limit',
        'rate_limit_data',
        'incident_id',
        'incident_data',
        'is_part_of_attack',
        'attack_signature',
        'evidence_data',
        'log_entries',
        'network_data',
        'forensic_notes',
        'legal_hold',
        'legal_notes',
        'compliance_data',
        'reported_to_authorities',
        'requires_review',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'review_status',
        'is_archived',
        'archived_at',
        'delete_after',
        'can_be_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'blacklisted_at' => 'datetime',
        'expires_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'approved_at' => 'datetime',
        'removed_at' => 'datetime',
        'whitelisted_at' => 'datetime',
        'whitelist_expires_at' => 'datetime',
        'last_alert_sent_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'archived_at' => 'datetime',
        'delete_after' => 'datetime',
        'threat_indicators' => 'array',
        'threat_sources' => 'array',
        'malware_signatures' => 'array',
        'affected_users' => 'array',
        'session_data' => 'array',
        'alert_recipients' => 'array',
        'bypass_rules' => 'array',
        'exception_rules' => 'array',
        'rate_limit_data' => 'array',
        'incident_data' => 'array',
        'evidence_data' => 'array',
        'log_entries' => 'array',
        'network_data' => 'array',
        'compliance_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_permanent' => 'boolean',
        'is_known_threat' => 'boolean',
        'has_whitelist_override' => 'boolean',
        'monitor_activity' => 'boolean',
        'alert_on_activity' => 'boolean',
        'allow_api_access' => 'boolean',
        'allow_admin_access' => 'boolean',
        'enforce_rate_limit' => 'boolean',
        'is_part_of_attack' => 'boolean',
        'legal_hold' => 'boolean',
        'reported_to_authorities' => 'boolean',
        'requires_review' => 'boolean',
        'is_archived' => 'boolean',
        'can_be_deleted' => 'boolean',
    ];

    // Constants
    const BLACKLIST_TYPES = [
        'manual',
        'automatic',
        'failed_login',
        'suspicious_activity',
        'security_threat',
        'spam',
        'abuse',
        'malware'
    ];

    const SEVERITIES = [
        'low',
        'medium',
        'high',
        'critical'
    ];

    const IP_VERSIONS = [
        'ipv4',
        'ipv6'
    ];

    const REVIEW_STATUSES = [
        'pending',
        'approved',
        'rejected'
    ];

    // Status check methods
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPermanent(): bool
    {
        return $this->is_permanent;
    }

    public function isWhitelisted(): bool
    {
        return $this->has_whitelist_override && 
               (!$this->whitelist_expires_at || $this->whitelist_expires_at->isFuture());
    }

    public function isKnownThreat(): bool
    {
        return $this->is_known_threat;
    }

    public function isHighRisk(): bool
    {
        return in_array($this->severity, ['high', 'critical']) || $this->threat_score >= 70;
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical' || $this->threat_score >= 90;
    }

    public function requiresReview(): bool
    {
        return $this->requires_review;
    }

    public function isApproved(): bool
    {
        return $this->review_status === 'approved';
    }

    public function isPendingReview(): bool
    {
        return $this->review_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->review_status === 'rejected';
    }

    public function isArchived(): bool
    {
        return $this->is_archived;
    }

    public function canBeDeleted(): bool
    {
        return $this->can_be_deleted && !$this->legal_hold;
    }

    public function isUnderLegalHold(): bool
    {
        return $this->legal_hold;
    }

    public function isPartOfAttack(): bool
    {
        return $this->is_part_of_attack;
    }

    // Relationships
    public function associatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'associated_user_id');
    }

    public function blacklistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blacklisted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function whitelistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'whitelisted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Utility methods
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public function getAgeInDays(): int
    {
        return $this->blacklisted_at->diffInDays(now());
    }

    public function getHumanReadableAge(): string
    {
        return $this->blacklisted_at->diffForHumans();
    }

    public function getLocationString(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);
        return implode(', ', $parts) ?: 'Unknown';
    }

    public function getThreatLevel(): string
    {
        if ($this->threat_score >= 90) return 'Critical';
        if ($this->threat_score >= 70) return 'High';
        if ($this->threat_score >= 40) return 'Medium';
        return 'Low';
    }

    // Action methods
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function extend(int $hours): bool
    {
        $newExpiry = $this->expires_at ? 
            $this->expires_at->addHours($hours) : 
            now()->addHours($hours);
            
        return $this->update([
            'expires_at' => $newExpiry,
            'duration_hours' => ($this->duration_hours ?? 0) + $hours
        ]);
    }

    public function whitelist(string $reason, ?Carbon $expiresAt = null, ?string $userId = null): bool
    {
        return $this->update([
            'has_whitelist_override' => true,
            'whitelisted_by' => $userId,
            'whitelisted_at' => now(),
            'whitelist_reason' => $reason,
            'whitelist_expires_at' => $expiresAt
        ]);
    }

    public function removeWhitelist(?string $userId = null): bool
    {
        return $this->update([
            'has_whitelist_override' => false,
            'whitelisted_by' => null,
            'whitelisted_at' => null,
            'whitelist_reason' => null,
            'whitelist_expires_at' => null,
            'updated_by' => $userId
        ]);
    }

    public function approve(?string $userId = null): bool
    {
        return $this->update([
            'review_status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'requires_review' => false
        ]);
    }

    public function reject(string $notes = '', ?string $userId = null): bool
    {
        return $this->update([
            'review_status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'requires_review' => false,
            'is_active' => false
        ]);
    }

    public function archive(?string $userId = null): bool
    {
        return $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'updated_by' => $userId
        ]);
    }

    public function recordActivity(): bool
    {
        return $this->increment('total_requests', 1, [
            'last_activity_at' => now(),
            'last_seen_at' => now()
        ]);
    }

    public function incrementFailedLogins(): bool
    {
        return $this->increment('failed_login_count');
    }

    public function incrementSuspiciousActivity(): bool
    {
        return $this->increment('suspicious_activity_count');
    }

    // Static methods
    public static function isBlacklisted(string $ipAddress): bool
    {
        $cacheKey = "blacklisted_ip_{$ipAddress}";
        
        return Cache::remember($cacheKey, 300, function () use ($ipAddress) {
            return static::where('ip_address', $ipAddress)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->where(function ($query) {
                    $query->where('has_whitelist_override', false)
                          ->orWhere(function ($q) {
                              $q->where('has_whitelist_override', true)
                                ->where(function ($wq) {
                                    $wq->whereNull('whitelist_expires_at')
                                       ->orWhere('whitelist_expires_at', '<', now());
                                });
                          });
                })
                ->exists();
        });
    }

    public static function blacklistIp(
        string $ipAddress,
        string $reason,
        string $type = 'manual',
        string $severity = 'medium',
        ?int $durationHours = null,
        ?string $userId = null
    ): static {
        $expiresAt = $durationHours ? now()->addHours($durationHours) : null;
        
        return static::create([
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'blacklist_type' => $type,
            'severity' => $severity,
            'blacklisted_at' => now(),
            'expires_at' => $expiresAt,
            'duration_hours' => $durationHours,
            'blacklisted_by' => $userId,
            'created_by' => $userId
        ]);
    }

    public static function getActiveBlacklist(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('active_blacklisted_ips', 300, function () {
            return static::where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->orderBy('blacklisted_at', 'desc')
                ->get();
        });
    }

    public static function getExpiringBlacklists(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addHours($hours)])
            ->orderBy('expires_at')
            ->get();
    }

    public static function getPendingReviews(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('requires_review', true)
            ->where('review_status', 'pending')
            ->orderBy('blacklisted_at')
            ->get();
    }

    public static function getStatistics(): array
    {
        return Cache::remember('blacklisted_ips_stats', 600, function () {
            return [
                'total' => static::count(),
                'active' => static::where('is_active', true)->count(),
                'permanent' => static::where('is_permanent', true)->count(),
                'temporary' => static::whereNotNull('expires_at')->count(),
                'expired' => static::where('expires_at', '<', now())->count(),
                'whitelisted' => static::where('has_whitelist_override', true)->count(),
                'pending_review' => static::where('requires_review', true)->count(),
                'high_risk' => static::whereIn('severity', ['high', 'critical'])->count(),
                'known_threats' => static::where('is_known_threat', true)->count(),
                'archived' => static::where('is_archived', true)->count()
            ];
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopePermanent($query)
    {
        return $query->where('is_permanent', true);
    }

    public function scopeTemporary($query)
    {
        return $query->where('is_permanent', false)
                    ->whereNotNull('expires_at');
    }

    public function scopeWhitelisted($query)
    {
        return $query->where('has_whitelist_override', true)
                    ->where(function ($q) {
                        $q->whereNull('whitelist_expires_at')
                          ->orWhere('whitelist_expires_at', '>', now());
                    });
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('severity', ['high', 'critical'])
                    ->orWhere('threat_score', '>=', 70);
    }

    public function scopeKnownThreats($query)
    {
        return $query->where('is_known_threat', true);
    }

    public function scopePendingReview($query)
    {
        return $query->where('requires_review', true)
                    ->where('review_status', 'pending');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('blacklist_type', $type);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeExpiringWithin($query, int $hours)
    {
        return $query->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addHours($hours)]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('blacklisted_at', '>=', now()->subDays($days));
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ip_address', 'like', "%{$term}%")
              ->orWhere('reason', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('country', 'like', "%{$term}%")
              ->orWhere('city', 'like', "%{$term}%")
              ->orWhere('isp', 'like', "%{$term}%")
              ->orWhere('organization', 'like', "%{$term}%");
        });
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function () {
            Cache::forget('active_blacklisted_ips');
            Cache::forget('blacklisted_ips_stats');
        });
        
        static::deleted(function () {
            Cache::forget('active_blacklisted_ips');
            Cache::forget('blacklisted_ips_stats');
        });
    }
}
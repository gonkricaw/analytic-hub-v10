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
 * UserPermission Model
 * 
 * Manages direct user-to-permission assignments in the Analytics Hub RBAC system.
 * Handles permission granting, revocation, temporal permissions, and overrides.
 * 
 * @property string $id
 * @property string $user_id
 * @property string $permission_id
 * @property bool $granted
 * @property string $assignment_type
 * @property string|null $assignment_reason
 * @property array|null $conditions
 * @property array|null $restrictions
 * @property array|null $scope_data
 * @property \Carbon\Carbon|null $granted_at
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_temporary
 * @property int|null $duration_hours
 * @property string|null $granted_by
 * @property string|null $revoked_by
 * @property \Carbon\Carbon|null $revoked_at
 * @property string|null $revocation_reason
 * @property bool $overrides_role
 * @property string|null $overridden_role_id
 * @property string|null $override_justification
 * @property int $usage_count
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $first_used_at
 * @property bool $is_active
 * @property bool $requires_approval
 * @property string $approval_status
 * @property string|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property bool $is_sensitive
 * @property bool $requires_justification
 * @property array|null $compliance_notes
 * @property string $risk_level
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class UserPermission extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_user_permissions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'permission_id',
        'granted',
        'assignment_type',
        'assignment_reason',
        'conditions',
        'restrictions',
        'scope_data',
        'granted_at',
        'expires_at',
        'is_temporary',
        'duration_hours',
        'granted_by',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'overrides_role',
        'overridden_role_id',
        'override_justification',
        'usage_count',
        'last_used_at',
        'first_used_at',
        'is_active',
        'requires_approval',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_sensitive',
        'requires_justification',
        'compliance_notes',
        'risk_level',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'granted' => 'boolean',
        'conditions' => 'array',
        'restrictions' => 'array',
        'scope_data' => 'array',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_temporary' => 'boolean',
        'duration_hours' => 'integer',
        'revoked_at' => 'datetime',
        'overrides_role' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'first_used_at' => 'datetime',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'is_sensitive' => 'boolean',
        'requires_justification' => 'boolean',
        'compliance_notes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Assignment type constants
     */
    const ASSIGNMENT_DIRECT = 'direct';
    const ASSIGNMENT_INHERITED = 'inherited';
    const ASSIGNMENT_TEMPORARY = 'temporary';
    const ASSIGNMENT_OVERRIDE = 'override';

    /**
     * Approval status constants
     */
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';
    const APPROVAL_NOT_REQUIRED = 'not_required';

    /**
     * Risk level constants
     */
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    /**
     * Check if permission is granted
     */
    public function isGranted(): bool
    {
        return $this->granted && $this->is_active && !$this->isExpired() && !$this->isRevoked();
    }

    /**
     * Check if permission is denied
     */
    public function isDenied(): bool
    {
        return !$this->granted;
    }

    /**
     * Check if permission is temporary
     */
    public function isTemporary(): bool
    {
        return $this->is_temporary;
    }

    /**
     * Check if permission is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if permission is revoked
     */
    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    /**
     * Check if permission is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if permission is sensitive
     */
    public function isSensitive(): bool
    {
        return $this->is_sensitive;
    }

    /**
     * Check if permission requires approval
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    /**
     * Check if permission requires justification
     */
    public function requiresJustification(): bool
    {
        return $this->requires_justification;
    }

    /**
     * Check if permission overrides role
     */
    public function overridesRole(): bool
    {
        return $this->overrides_role;
    }

    /**
     * Check if permission is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Check if permission is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Check if permission is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /**
     * Check if permission is high risk
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Check if permission is critical risk
     */
    public function isCriticalRisk(): bool
    {
        return $this->risk_level === self::RISK_CRITICAL;
    }

    /**
     * Get the user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the permission relationship
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the user who granted the permission
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Get the user who revoked the permission
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Get the overridden role
     */
    public function overriddenRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'overridden_role_id');
    }

    /**
     * Get the user who approved the permission
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
     * Get permission age in days
     */
    public function getAgeInDays(): int
    {
        return $this->granted_at ? $this->granted_at->diffInDays(now()) : 0;
    }

    /**
     * Get human readable age
     */
    public function getHumanAge(): string
    {
        return $this->granted_at ? $this->granted_at->diffForHumans() : 'Unknown';
    }

    /**
     * Get usage frequency (uses per day)
     */
    public function getUsageFrequency(): float
    {
        $ageInDays = max(1, $this->getAgeInDays());
        return round($this->usage_count / $ageInDays, 2);
    }

    /**
     * Grant permission
     */
    public function grant(array $options = []): void
    {
        $updates = array_merge([
            'granted' => true,
            'granted_at' => now(),
            'granted_by' => auth()->id(),
            'is_active' => true,
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null,
            'updated_by' => auth()->id(),
        ], $options);

        $this->update($updates);
        $this->clearUserPermissionCache();
    }

    /**
     * Revoke permission
     */
    public function revoke(string $reason = null): void
    {
        $this->update([
            'granted' => false,
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
            'revocation_reason' => $reason,
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        $this->clearUserPermissionCache();
    }

    /**
     * Approve permission
     */
    public function approve(string $approvedBy = null): void
    {
        $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        $this->clearUserPermissionCache();
    }

    /**
     * Reject permission
     */
    public function reject(string $reason = null): void
    {
        $this->update([
            'approval_status' => self::APPROVAL_REJECTED,
            'revocation_reason' => $reason,
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        $this->clearUserPermissionCache();
    }

    /**
     * Extend permission expiry
     */
    public function extend(Carbon $newExpiry, string $reason = null): void
    {
        $this->update([
            'expires_at' => $newExpiry,
            'assignment_reason' => $reason ? $this->assignment_reason . ' | Extended: ' . $reason : $this->assignment_reason,
            'updated_by' => auth()->id(),
        ]);

        $this->clearUserPermissionCache();
    }

    /**
     * Record permission usage
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        
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
     * Set as temporary permission
     */
    public function makeTemporary(int $hours, string $reason = null): void
    {
        $this->update([
            'is_temporary' => true,
            'duration_hours' => $hours,
            'expires_at' => now()->addHours($hours),
            'assignment_type' => self::ASSIGNMENT_TEMPORARY,
            'assignment_reason' => $reason ?? 'Temporary access granted',
            'updated_by' => auth()->id(),
        ]);

        $this->clearUserPermissionCache();
    }

    /**
     * Clear user permission cache
     */
    protected function clearUserPermissionCache(): void
    {
        Cache::forget("user_permissions_{$this->user_id}");
        Cache::forget("user_permission_stats_{$this->user_id}");
        Cache::forget('permission_assignments_summary');
    }

    /**
     * Grant permission to user
     */
    public static function grantToUser(string $userId, string $permissionId, array $options = []): self
    {
        $userPermission = self::updateOrCreate(
            [
                'user_id' => $userId,
                'permission_id' => $permissionId,
            ],
            array_merge([
                'granted' => true,
                'granted_at' => now(),
                'granted_by' => auth()->id(),
                'is_active' => true,
                'assignment_type' => self::ASSIGNMENT_DIRECT,
                'approval_status' => self::APPROVAL_NOT_REQUIRED,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ], $options)
        );

        $userPermission->clearUserPermissionCache();
        return $userPermission;
    }

    /**
     * Revoke permission from user
     */
    public static function revokeFromUser(string $userId, string $permissionId, string $reason = null): bool
    {
        $userPermission = self::where('user_id', $userId)
                             ->where('permission_id', $permissionId)
                             ->first();

        if ($userPermission) {
            $userPermission->revoke($reason);
            return true;
        }

        return false;
    }

    /**
     * Get user permissions summary
     */
    public static function getUserPermissionsSummary(string $userId): array
    {
        $cacheKey = "user_permission_stats_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $total = self::where('user_id', $userId)->count();
            $granted = self::where('user_id', $userId)->where('granted', true)->count();
            $active = self::where('user_id', $userId)->where('is_active', true)->count();
            $temporary = self::where('user_id', $userId)->where('is_temporary', true)->count();
            $expired = self::where('user_id', $userId)
                          ->where('expires_at', '<', now())
                          ->count();
            $sensitive = self::where('user_id', $userId)->where('is_sensitive', true)->count();
            $pendingApproval = self::where('user_id', $userId)
                                  ->where('approval_status', self::APPROVAL_PENDING)
                                  ->count();
            $highRisk = self::where('user_id', $userId)
                           ->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL])
                           ->count();
            
            return [
                'total' => $total,
                'granted' => $granted,
                'active' => $active,
                'temporary' => $temporary,
                'expired' => $expired,
                'sensitive' => $sensitive,
                'pending_approval' => $pendingApproval,
                'high_risk' => $highRisk,
                'grant_rate' => $total > 0 ? round(($granted / $total) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Get permissions expiring soon
     */
    public static function getExpiringPermissions(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('expires_at', '<=', now()->addDays($days))
                  ->where('expires_at', '>', now())
                  ->where('is_active', true)
                  ->with(['user', 'permission'])
                  ->orderBy('expires_at')
                  ->get();
    }

    /**
     * Get permissions requiring approval
     */
    public static function getPendingApprovals(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('approval_status', self::APPROVAL_PENDING)
                  ->where('is_active', true)
                  ->with(['user', 'permission', 'grantedBy'])
                  ->orderBy('created_at')
                  ->get();
    }

    /**
     * Scope: Filter by user
     */
    public function scopeUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by permission
     */
    public function scopePermission(Builder $query, string $permissionId): Builder
    {
        return $query->where('permission_id', $permissionId);
    }

    /**
     * Scope: Granted permissions
     */
    public function scopeGranted(Builder $query): Builder
    {
        return $query->where('granted', true);
    }

    /**
     * Scope: Denied permissions
     */
    public function scopeDenied(Builder $query): Builder
    {
        return $query->where('granted', false);
    }

    /**
     * Scope: Active permissions
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Temporary permissions
     */
    public function scopeTemporary(Builder $query): Builder
    {
        return $query->where('is_temporary', true);
    }

    /**
     * Scope: Expired permissions
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Expiring soon
     */
    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('expires_at', '<=', now()->addDays($days))
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope: Revoked permissions
     */
    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope: Sensitive permissions
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope: Filter by assignment type
     */
    public function scopeAssignmentType(Builder $query, string $type): Builder
    {
        return $query->where('assignment_type', $type);
    }

    /**
     * Scope: Filter by approval status
     */
    public function scopeApprovalStatus(Builder $query, string $status): Builder
    {
        return $query->where('approval_status', $status);
    }

    /**
     * Scope: Pending approval
     */
    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Scope: Filter by risk level
     */
    public function scopeRiskLevel(Builder $query, string $level): Builder
    {
        return $query->where('risk_level', $level);
    }

    /**
     * Scope: High risk permissions
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Scope: Override permissions
     */
    public function scopeOverrides(Builder $query): Builder
    {
        return $query->where('overrides_role', true);
    }

    /**
     * Scope: Recently used
     */
    public function scopeRecentlyUsed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Unused permissions
     */
    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('usage_count', 0)
                    ->orWhereNull('last_used_at');
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Clear cache when user permission is updated or deleted
        static::saved(function ($userPermission) {
            $userPermission->clearUserPermissionCache();
        });
        
        static::deleted(function ($userPermission) {
            $userPermission->clearUserPermissionCache();
        });
    }
}
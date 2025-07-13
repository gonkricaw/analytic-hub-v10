<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class UserRole
 * 
 * Represents the pivot table for user-role relationships.
 * Handles role assignments with additional metadata like expiration and assignment tracking.
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $user_id Foreign key to users table
 * @property string $role_id Foreign key to roles table
 * @property bool $is_active Whether the role assignment is active
 * @property Carbon|null $assigned_at When the role was assigned
 * @property Carbon|null $expires_at When the role assignment expires
 * @property string|null $assignment_reason Reason for role assignment
 * @property string|null $assigned_by Who assigned this role
 * @property string|null $revoked_by Who revoked this role
 * @property Carbon|null $revoked_at When the role was revoked
 * @property string|null $revocation_reason Reason for role revocation
 * @property string|null $created_by Who created this record
 * @property string|null $updated_by Who last updated this record
 * @property Carbon $created_at Creation timestamp
 * @property Carbon $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft deletion timestamp
 */
class UserRole extends Model
{
    use HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'idbi_user_roles';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'is_active',
        'assigned_at',
        'expires_at',
        'assignment_reason',
        'assigned_by',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns this role assignment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the role for this assignment.
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the user who assigned this role.
     *
     * @return BelongsTo
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who revoked this role.
     *
     * @return BelongsTo
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Get the user who created this record.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active role assignments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope to get expired role assignments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Check if the role assignment is currently active.
     *
     * @return bool
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Revoke this role assignment.
     *
     * @param string|null $revokedBy
     * @param string|null $reason
     * @return bool
     */
    public function revoke(?string $revokedBy = null, ?string $reason = null): bool
    {
        $this->is_active = false;
        $this->revoked_at = now();
        $this->revoked_by = $revokedBy;
        $this->revocation_reason = $reason;

        return $this->save();
    }
}
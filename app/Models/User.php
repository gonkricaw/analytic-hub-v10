<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class User
 * 
 * Represents a user in the Analytics Hub system.
 * Uses UUID as primary key and includes comprehensive user management features.
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $first_name User's first name
 * @property string $last_name User's last name
 * @property string $email User's email address
 * @property string|null $username Optional username
 * @property string $password Hashed password
 * @property Carbon|null $email_verified_at Email verification timestamp
 * @property bool $is_first_login Whether this is user's first login
 * @property Carbon|null $password_changed_at Last password change timestamp
 * @property Carbon|null $last_login_at Last login timestamp
 * @property string|null $last_login_ip Last login IP address
 * @property string $status User status (active, suspended, pending, deleted)
 * @property bool $terms_accepted Whether user accepted terms
 * @property Carbon|null $terms_accepted_at Terms acceptance timestamp
 * @property bool $email_notifications Email notification preference
 * @property string|null $bio User biography
 * @property string|null $phone Phone number
 * @property string|null $department Department
 * @property string|null $position Position/title
 * @property int $failed_login_attempts Failed login attempt count
 * @property Carbon|null $locked_until Account lock expiration
 * @property string|null $remember_token Remember me token
 * @property string|null $created_by UUID of user who created this record
 * @property string|null $updated_by UUID of user who last updated this record
 * @property Carbon|null $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft deletion timestamp
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'email_verified_at',
        'is_first_login',
        'password_changed_at',
        'password_expires_at',
        'force_password_change',
        'last_login_at',
        'last_login_ip',
        'last_seen_at',
        'last_ip',
        'status',
        'terms_accepted',
        'terms_accepted_at',
        'terms_version_accepted',
        'email_notifications',
        'bio',
        'phone',
        'department',
        'position',
        'failed_login_attempts',
        'locked_until',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_first_login' => 'boolean',
            'password_changed_at' => 'datetime',
            'password_expires_at' => 'datetime',
            'force_password_change' => 'boolean',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'terms_accepted' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'email_notifications' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * User status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELETED = 'deleted';

    /**
     * Get the user's full name.
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if user is active.
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user is locked.
     * 
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Get user roles relationship.
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'idbi_user_roles', 'user_id', 'role_id')
                    ->withPivot('id', 'is_active', 'assigned_at', 'expires_at', 'assignment_reason', 'assigned_by', 'revoked_by', 'revoked_at', 'revocation_reason')
                    ->withTimestamps();
    }

    /**
     * Get user permissions directly assigned.
     * 
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'idbi_user_permissions', 'user_id', 'permission_id')
                    ->withPivot('id', 'granted', 'conditions', 'restrictions', 'expires_at', 'granted_by', 'revoked_by', 'revoked_at', 'revocation_reason')
                    ->withTimestamps();
    }

    /**
     * Get user activities relationship.
     * 
     * @return HasMany
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class, 'user_id');
    }

    /**
     * Get user login attempts relationship.
     * 
     * @return HasMany
     */
    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class, 'user_id');
    }

    /**
     * Get user password history relationship.
     * 
     * @return HasMany
     */
    public function passwordHistory(): HasMany
    {
        return $this->hasMany(PasswordHistory::class, 'user_id');
    }

    /**
     * Get user avatar relationship.
     * 
     * @return HasOne
     */
    public function avatar(): HasOne
    {
        return $this->hasOne(UserAvatar::class, 'user_id');
    }

    /**
     * Get user notifications relationship.
     * 
     * @return HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * Scope for active users.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for verified users.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for users with recent activity.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyActive($query, int $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Check if user has a specific role.
     * 
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->where('status', 'active')->exists();
    }

    /**
     * Check if user has any of the given roles.
     * 
     * @param array|string $roles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles()->whereIn('name', $roles)->where('status', 'active')->exists();
    }

    /**
     * Check if user has all of the given roles.
     * 
     * @param array|string $roles
     * @return bool
     */
    public function hasAllRoles($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $userRoles = $this->roles()->where('status', 'active')->pluck('name')->toArray();
        
        foreach ($roles as $role) {
            if (!in_array($role, $userRoles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a specific permission.
     * 
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        // Check through roles
        foreach ($this->roles()->where('status', 'active')->get() as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     * 
     * @param array|string $permissions
     * @return bool
     */
    public function hasAnyPermission($permissions): bool
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     * 
     * @param array|string $permissions
     * @return bool
     */
    public function hasAllPermissions($permissions): bool
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all user permissions through roles.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        $permissions = collect();
        
        foreach ($this->roles()->where('status', 'active')->get() as $role) {
            $rolePermissions = $role->permissions()->where('status', 'active')->get();
            $permissions = $permissions->merge($rolePermissions);
        }

        return $permissions->unique('id');
    }

    /**
     * Assign a role to the user.
     * 
     * @param string|Role $role
     * @return void
     */
    public function assignRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Remove a role from the user.
     * 
     * @param string|Role $role
     * @return void
     */
    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    /**
     * Sync user roles.
     * 
     * @param array $roles
     * @return void
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = [];
        
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('name', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            } elseif ($role instanceof Role) {
                $roleIds[] = $role->id;
            }
        }

        $this->roles()->sync($roleIds);
    }
}

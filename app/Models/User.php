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
        'last_login_at',
        'last_login_ip',
        'status',
        'terms_accepted',
        'terms_accepted_at',
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
            'last_login_at' => 'datetime',
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
                    ->withTimestamps();
    }

    /**
     * Get user permissions through roles.
     * 
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'idbi_role_permissions', 'role_id', 'permission_id')
                    ->through('roles');
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
}

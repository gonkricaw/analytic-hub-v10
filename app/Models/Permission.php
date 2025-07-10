<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Permission
 * 
 * Represents a permission in the Analytics Hub RBAC system.
 * Permissions define specific actions that can be performed on resources.
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $name Unique permission name (e.g., 'users.create')
 * @property string $display_name Human-readable permission name
 * @property string|null $description Permission description
 * @property string $module Module name (e.g., 'users', 'reports')
 * @property string $action Action name (e.g., 'create', 'read', 'update', 'delete')
 * @property string|null $resource Specific resource if applicable
 * @property string|null $parent_id Parent permission UUID for hierarchy
 * @property string|null $group Permission group for UI organization
 * @property int $sort_order Sort order for UI display
 * @property bool $is_system_permission Whether permission is system-protected
 * @property array|null $conditions Additional conditions for permission
 * @property array|null $metadata Additional metadata
 * @property string $status Permission status (active, inactive)
 * @property string|null $created_by UUID of user who created this record
 * @property string|null $updated_by UUID of user who last updated this record
 * @property Carbon|null $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft deletion timestamp
 */
class Permission extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'resource',
        'parent_id',
        'group',
        'sort_order',
        'is_system_permission',
        'conditions',
        'metadata',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system_permission' => 'boolean',
            'conditions' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Permission status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Common action constants
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_READ = 'read';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_MANAGE = 'manage';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';

    /**
     * Common module constants
     */
    public const MODULE_USERS = 'users';
    public const MODULE_ROLES = 'roles';
    public const MODULE_PERMISSIONS = 'permissions';
    public const MODULE_DASHBOARD = 'dashboard';
    public const MODULE_REPORTS = 'reports';
    public const MODULE_CONTENT = 'content';
    public const MODULE_SYSTEM = 'system';
    public const MODULE_ANALYTICS = 'analytics';

    /**
     * Check if permission is active.
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if permission is system protected.
     * 
     * @return bool
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system_permission;
    }

    /**
     * Get parent permission.
     * 
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * Get child permissions.
     * 
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }

    /**
     * Get roles that have this permission.
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'idbi_role_permissions', 'permission_id', 'role_id')
                    ->withTimestamps();
    }

    /**
     * Get users who have this permission through roles.
     * 
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idbi_user_roles', 'role_id', 'user_id')
                    ->through('roles');
    }

    /**
     * Get the full permission path (including parent hierarchy).
     * 
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode('.', $path);
    }

    /**
     * Check if permission matches a given pattern.
     * 
     * @param string $pattern
     * @return bool
     */
    public function matches(string $pattern): bool
    {
        // Support wildcard matching
        $pattern = str_replace('*', '.*', $pattern);
        return preg_match('/^' . $pattern . '$/', $this->name) === 1;
    }

    /**
     * Scope for active permissions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for system permissions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemPermissions($query)
    {
        return $query->where('is_system_permission', true);
    }

    /**
     * Scope for custom permissions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomPermissions($query)
    {
        return $query->where('is_system_permission', false);
    }

    /**
     * Scope for permissions by module.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for permissions by action.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for permissions by group.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope for root permissions (no parent).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRootPermissions($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for child permissions of a specific parent.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $parentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChildrenOf($query, string $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope for ordered permissions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }
}
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Models\Content;
use App\Models\ContentRole;

/**
 * Class Role
 * 
 * Represents a role in the Analytics Hub RBAC system.
 * Roles define sets of permissions that can be assigned to users.
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $name Unique role name
 * @property string $display_name Human-readable role name
 * @property string|null $description Role description
 * @property int $level Role hierarchy level (1=highest)
 * @property bool $is_system_role Whether role is system-protected
 * @property bool $is_default Whether role is default for new users
 * @property string $status Role status (active, inactive)
 * @property array|null $permissions_cache Cached permissions for performance
 * @property array|null $settings Role-specific settings
 * @property string|null $created_by UUID of user who created this record
 * @property string|null $updated_by UUID of user who last updated this record
 * @property Carbon|null $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft deletion timestamp
 */
class Role extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
        'is_system_role',
        'is_default',
        'status',
        'permissions_cache',
        'settings',
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
            'level' => 'integer',
            'is_system_role' => 'boolean',
            'is_default' => 'boolean',
            'permissions_cache' => 'array',
            'settings' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Role status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * System role names
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAKEHOLDER = 'stakeholder';
    public const ROLE_USER = 'user';

    /**
     * Check if role is active.
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if role is system protected.
     * 
     * @return bool
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    /**
     * Check if role is default for new users.
     * 
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Get users with this role.
     * 
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idbi_user_roles', 'role_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Get permissions assigned to this role.
     * 
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'idbi_role_permissions', 'role_id', 'permission_id')
                    ->withTimestamps();
    }

    /**
     * Get menus accessible by this role.
     * 
     * @return BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'idbi_role_menus', 'role_id', 'menu_id')
                    ->withTimestamps();
    }

    /**
     * Get content accessible by this role.
     * 
     * @return BelongsToMany
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'idbi_content_roles', 'role_id', 'content_id')
                    ->using(ContentRole::class)
                    ->withPivot([
                        'id', 'is_granted', 'access_type', 'access_conditions', 'restrictions',
                        'can_view', 'can_edit', 'can_delete', 'can_publish', 'can_comment', 'can_share',
                        'is_visible', 'show_in_listings', 'show_metadata', 'allow_download',
                        'assignment_reason', 'assignment_data', 'notes', 'priority',
                        'granted_at', 'expires_at', 'is_temporary', 'duration_hours',
                        'granted_by', 'revoked_by', 'revoked_at', 'revocation_reason',
                        'overrides_default', 'overridden_content_id', 'override_justification',
                        'view_count', 'edit_count', 'last_viewed_at', 'last_edited_at', 'first_accessed_at', 'access_statistics',
                        'comment_count', 'share_count', 'download_count', 'interaction_data',
                        'is_active', 'requires_approval', 'approval_status', 'approved_by', 'approved_at',
                        'is_sensitive', 'requires_justification', 'compliance_notes', 'risk_level', 'audit_access',
                        'workflow_status', 'reviewer_id', 'reviewed_at', 'review_notes',
                        'notify_on_update', 'notify_on_comment', 'notify_on_share',
                        'created_by', 'updated_by'
                    ])
                    ->withTimestamps();
    }

    /**
     * Get content role assignments for this role.
     * 
     * @return HasMany
     */
    public function contentRoles(): HasMany
    {
        return $this->hasMany(ContentRole::class, 'role_id');
    }

    /**
     * Get active content role assignments for this role.
     * 
     * @return HasMany
     */
    public function activeContentRoles(): HasMany
    {
        return $this->hasMany(ContentRole::class, 'role_id')
                    ->active();
    }

    /**
     * Check if this role has access to specific content.
     * 
     * @param string $contentId Content ID to check
     * @param string $permission Specific permission to check (view, edit, etc.)
     * @return bool
     */
    public function hasContentAccess(string $contentId, string $permission = 'view'): bool
    {
        $permissionColumn = 'can_' . $permission;
        
        return ContentRole::where('role_id', $this->id)
                         ->where('content_id', $contentId)
                         ->active()
                         ->where($permissionColumn, true)
                         ->exists();
    }

    /**
     * Get all content this role can access with specific permission.
     * 
     * @param string $permission Permission to check (view, edit, etc.)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleContent(string $permission = 'view')
    {
        $permissionColumn = 'can_' . $permission;
        
        return Content::whereHas('contentRoles', function ($query) use ($permissionColumn) {
            $query->where('role_id', $this->id)
                  ->active()
                  ->where($permissionColumn, true);
        })->get();
    }

    /**
     * Check if role has a specific permission.
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Check cached permissions first
        if ($this->permissions_cache && is_array($this->permissions_cache)) {
            return in_array($permission, $this->permissions_cache);
        }

        // Fallback to database query
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Refresh permissions cache.
     * 
     * @return void
     */
    public function refreshPermissionsCache(): void
    {
        $permissions = $this->permissions()->pluck('name')->toArray();
        $this->update(['permissions_cache' => $permissions]);
    }

    /**
     * Scope for active roles.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for system roles.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Scope for non-system roles.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    /**
     * Scope for default role.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for roles by level.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for roles with level greater than or equal to specified level.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMinLevel($query, int $level)
    {
        return $query->where('level', '>=', $level);
    }

    /**
     * Scope for roles with level less than or equal to specified level.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMaxLevel($query, int $level)
    {
        return $query->where('level', '<=', $level);
    }
}
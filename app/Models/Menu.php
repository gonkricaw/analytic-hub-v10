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
use Illuminate\Support\Collection;

/**
 * Class Menu
 * 
 * Represents a menu item in the Analytics Hub dynamic menu system.
 * Supports hierarchical menu structures with role-based access control.
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $name Internal menu name
 * @property string $title Display title
 * @property string|null $description Menu description
 * @property string|null $parent_id Parent menu UUID for hierarchy
 * @property int $sort_order Menu ordering
 * @property int $level Menu depth level
 * @property string|null $url Menu URL/route
 * @property string|null $route_name Laravel route name
 * @property string|null $icon Icon class (e.g., FontAwesome)
 * @property string $target Link target (_self, _blank)
 * @property string $type Menu type (link, dropdown, separator, header)
 * @property bool $is_external External link flag
 * @property bool $is_active Menu visibility
 * @property bool $is_system_menu Whether menu is system-protected
 * @property string|null $required_permission_id Required permission UUID to view
 * @property array|null $required_roles Required roles (array of role IDs)
 * @property array|null $visibility_conditions Additional visibility conditions
 * @property array|null $attributes Additional HTML attributes
 * @property array|null $metadata Additional metadata
 * @property string|null $css_class Custom CSS classes
 * @property string|null $created_by UUID of user who created this record
 * @property string|null $updated_by UUID of user who last updated this record
 * @property Carbon|null $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft deletion timestamp
 */
class Menu extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_menus';

    /**
     * The primary key associated with the table.
     * 
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     * 
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'title',
        'description',
        'parent_id',
        'sort_order',
        'level',
        'url',
        'route_name',
        'icon',
        'target',
        'type',
        'is_external',
        'is_active',
        'is_system_menu',
        'required_permission_id',
        'required_roles',
        'visibility_conditions',
        'attributes',
        'metadata',
        'css_class',
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
            'level' => 'integer',
            'is_external' => 'boolean',
            'is_active' => 'boolean',
            'is_system_menu' => 'boolean',
            'required_roles' => 'array',
            'visibility_conditions' => 'array',
            'attributes' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Menu type constants
     */
    public const TYPE_LINK = 'link';
    public const TYPE_DROPDOWN = 'dropdown';
    public const TYPE_SEPARATOR = 'separator';
    public const TYPE_HEADER = 'header';

    /**
     * Target constants
     */
    public const TARGET_SELF = '_self';
    public const TARGET_BLANK = '_blank';
    public const TARGET_PARENT = '_parent';
    public const TARGET_TOP = '_top';

    /**
     * Check if menu is active.
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if menu is system protected.
     * 
     * @return bool
     */
    public function isSystemMenu(): bool
    {
        return $this->is_system_menu;
    }

    /**
     * Check if menu is external link.
     * 
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->is_external;
    }

    /**
     * Check if menu has children.
     * 
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get parent menu.
     * 
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Get child menus.
     * 
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('title');
    }

    /**
     * Get all descendants (recursive children).
     * 
     * @return HasMany
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->with('descendants')
                    ->orderBy('sort_order')
                    ->orderBy('title');
    }

    /**
     * Get required permission.
     * 
     * @return BelongsTo
     */
    public function requiredPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission_id');
    }

    /**
     * Get roles that have access to this menu.
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'idbi_menu_roles', 'menu_id', 'role_id')
                    ->withPivot([
                        'id', 'is_granted', 'access_type', 'access_conditions', 'restrictions',
                        'is_visible', 'show_in_navigation', 'show_children', 'custom_order',
                        'assignment_reason', 'assignment_data', 'notes', 'granted_at',
                        'expires_at', 'is_temporary', 'duration_hours', 'granted_by',
                        'revoked_by', 'revoked_at', 'revocation_reason', 'overrides_parent',
                        'overridden_menu_id', 'override_justification', 'access_count',
                        'last_accessed_at', 'first_accessed_at', 'access_statistics',
                        'is_active', 'requires_approval', 'approval_status', 'approved_by',
                        'approved_at', 'is_sensitive', 'requires_justification',
                        'compliance_notes', 'risk_level', 'created_by', 'updated_by'
                    ])
                    ->withTimestamps();
    }

    /**
     * Get the full menu path (breadcrumb).
     * 
     * @return Collection
     */
    public function getPathAttribute(): Collection
    {
        $path = collect([$this]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    /**
     * Get the menu URL with proper handling of routes and external links.
     * 
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->is_external || !$this->route_name) {
            return $this->attributes['url'] ?? null;
        }

        try {
            // Check if route requires parameters (like admin.menus.edit)
            if ($this->route_name === 'admin.menus.edit') {
                // For edit routes, we need a menu ID parameter
                // Return a placeholder or the stored URL instead
                return $this->attributes['url'] ?? '#';
            }
            
            return route($this->route_name);
        } catch (\Exception $e) {
            return $this->attributes['url'] ?? '#';
        }
    }

    /**
     * Check if user can access this menu.
     * 
     * @param User $user
     * @return bool
     */
    public function canAccess(User $user): bool
    {
        // Check if menu is active
        if (!$this->is_active) {
            return false;
        }

        // Check required permission
        if ($this->required_permission_id) {
            $hasPermission = $user->roles()
                ->whereHas('permissions', function ($query) {
                    $query->where('id', $this->required_permission_id);
                })
                ->exists();
            
            if (!$hasPermission) {
                return false;
            }
        }

        // Check required roles
        if ($this->required_roles && is_array($this->required_roles)) {
            $hasRole = $user->roles()
                ->whereIn('id', $this->required_roles)
                ->exists();
            
            if (!$hasRole) {
                return false;
            }
        }

        // Check role-based access through menu_roles pivot table
        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        
        // Check if menu has any role assignments
        $menuHasRoleAssignments = $this->roles()->exists();
        
        if ($menuHasRoleAssignments) {
            // Menu has role restrictions, check if user has access
            if (empty($userRoleIds)) {
                // User has no roles but menu requires roles
                return false;
            }
            
            $hasRoleAccess = $this->roles()
                ->whereIn('idbi_roles.id', $userRoleIds)
                ->where('idbi_menu_roles.is_granted', true)
                ->where('idbi_menu_roles.is_active', true)
                ->exists();
            
            if (!$hasRoleAccess) {
                return false;
            }
        }
        // If menu has no role assignments, allow access (public menu)

        // Check additional visibility conditions
        if ($this->visibility_conditions) {
            // This can be extended based on specific business logic
            // For now, we'll assume all conditions are met
        }

        return true;
    }

    /**
     * Get menu tree structure.
     * 
     * @param User|null $user
     * @return Collection
     */
    public static function getMenuTree(?User $user = null): Collection
    {
        $query = static::with(['children.descendants', 'requiredPermission'])
                      ->whereNull('parent_id')
                      ->where('is_active', true)
                      ->orderBy('sort_order')
                      ->orderBy('title');

        $menus = $query->get();

        if ($user) {
            return $menus->filter(function ($menu) use ($user) {
                return $menu->canAccess($user);
            });
        }

        return $menus;
    }

    /**
     * Scope for active menus.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root menus (no parent).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRootMenus($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for system menus.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemMenus($query)
    {
        return $query->where('is_system_menu', true);
    }

    /**
     * Scope for custom menus.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomMenus($query)
    {
        return $query->where('is_system_menu', false);
    }

    /**
     * Scope for menus by type.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for ordered menus.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Scope for menus accessible by user.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('required_permission_id')
              ->orWhereHas('requiredPermission.roles.users', function ($subQuery) use ($user) {
                  $subQuery->where('users.id', $user->id);
              });
        })->where(function ($q) use ($user) {
            $q->whereNull('required_roles')
              ->orWhereHas('roles.users', function ($subQuery) use ($user) {
                  $subQuery->where('users.id', $user->id);
              });
        });
    }

    /**
     * Scope for menus visible to specific role.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|int $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleToRole($query, $roleId)
    {
        return $query->whereHas('roles', function ($q) use ($roleId) {
            $q->where('idbi_roles.id', $roleId)
              ->where('idbi_menu_roles.is_visible', true)
              ->where('idbi_menu_roles.is_granted', true);
        });
    }

    /**
     * Scope for navigation menus for specific role.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|int $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNavigationForRole($query, $roleId)
    {
        return $query->whereHas('roles', function ($q) use ($roleId) {
            $q->where('idbi_roles.id', $roleId)
              ->where('idbi_menu_roles.show_in_navigation', true)
              ->where('idbi_menu_roles.is_granted', true);
        });
    }

    /**
     * Check if menu is visible to user based on role assignments.
     * 
     * @param User $user
     * @return bool
     */
    public function isVisibleToUser(User $user): bool
    {
        // Check if menu is active
        if (!$this->is_active) {
            return false;
        }

        // Get user's role IDs
        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        
        if (empty($userRoleIds)) {
            return false;
        }

        // Check if any of user's roles have visibility access
        return $this->roles()
            ->whereIn('idbi_roles.id', $userRoleIds)
            ->where('idbi_menu_roles.is_visible', true)
            ->where('idbi_menu_roles.is_granted', true)
            ->exists();
    }

    /**
     * Check if menu should show in navigation for user.
     * 
     * @param User $user
     * @return bool
     */
    public function showInNavigationForUser(User $user): bool
    {
        if (!$this->isVisibleToUser($user)) {
            return false;
        }

        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        
        return $this->roles()
            ->whereIn('idbi_roles.id', $userRoleIds)
            ->where('idbi_menu_roles.show_in_navigation', true)
            ->where('idbi_menu_roles.is_granted', true)
            ->exists();
    }

    /**
     * Get menu tree with role-based filtering and caching.
     * 
     * @param User $user
     * @param bool $useCache
     * @return \Illuminate\Support\Collection
     */
    public static function getMenuTreeForUser(User $user, bool $useCache = true): \Illuminate\Support\Collection
    {
        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        $cacheKey = 'menu_tree_user_' . $user->id . '_roles_' . md5(implode(',', $userRoleIds));

        if ($useCache && \Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        $menus = static::with(['roles', 'children.roles', 'parent'])
            ->active()
            ->where(function ($query) use ($userRoleIds) {
                $query->whereHas('roles', function ($q) use ($userRoleIds) {
                    $q->whereIn('idbi_roles.id', $userRoleIds)
                      ->where('idbi_menu_roles.is_visible', true)
                      ->where('idbi_menu_roles.is_granted', true);
                })->orWhereDoesntHave('roles'); // Include menus with no role restrictions
            })
            ->ordered()
            ->get();

        $tree = static::buildHierarchicalTree($menus, $user);

        if ($useCache) {
            \Cache::put($cacheKey, $tree, now()->addHours(2));
        }

        return $tree;
    }

    /**
     * Get navigation menu for user with caching.
     * 
     * @param User $user
     * @param bool $useCache
     * @return \Illuminate\Support\Collection
     */
    public static function getNavigationForUser(User $user, bool $useCache = true): \Illuminate\Support\Collection
    {
        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        $cacheKey = 'navigation_menu_user_' . $user->id . '_roles_' . md5(implode(',', $userRoleIds));

        if ($useCache && \Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        $menus = static::with(['roles', 'children.roles', 'parent'])
            ->active()
            ->where(function ($query) use ($userRoleIds) {
                $query->whereHas('roles', function ($q) use ($userRoleIds) {
                    $q->whereIn('idbi_roles.id', $userRoleIds)
                      ->where('idbi_menu_roles.show_in_navigation', true)
                      ->where('idbi_menu_roles.is_granted', true);
                })->orWhereDoesntHave('roles'); // Include menus with no role restrictions
            })
            ->ordered()
            ->get();

        $navigation = static::buildHierarchicalTree($menus, $user, true);

        if ($useCache) {
            \Cache::put($cacheKey, $navigation, now()->addHours(2));
        }

        return $navigation;
    }

    /**
     * Build hierarchical tree structure.
     * 
     * @param \Illuminate\Support\Collection $menus
     * @param User $user
     * @param bool $navigationOnly
     * @return \Illuminate\Support\Collection
     */
    private static function buildHierarchicalTree(\Illuminate\Support\Collection $menus, User $user, bool $navigationOnly = false): \Illuminate\Support\Collection
    {
        $tree = collect();
        $menuMap = $menus->keyBy('id');

        foreach ($menus as $menu) {
            if ($menu->parent_id === null) {
                $menuItem = static::buildMenuBranch($menu, $menuMap, $user, $navigationOnly);
                if ($menuItem) {
                    $tree->push($menuItem);
                }
            }
        }

        return $tree;
    }

    /**
     * Build menu branch recursively.
     * 
     * @param Menu $menu
     * @param \Illuminate\Support\Collection $menuMap
     * @param User $user
     * @param bool $navigationOnly
     * @return Menu|null
     */
    private static function buildMenuBranch(Menu $menu, \Illuminate\Support\Collection $menuMap, User $user, bool $navigationOnly = false): ?Menu
    {
        // Check visibility
        if ($navigationOnly && !$menu->showInNavigationForUser($user)) {
            return null;
        } elseif (!$navigationOnly && !$menu->isVisibleToUser($user)) {
            return null;
        }

        // Get children
        $children = $menuMap->filter(function ($item) use ($menu) {
            return $item->parent_id === $menu->id;
        })->sortBy('sort_order');

        $validChildren = collect();
        foreach ($children as $child) {
            $childBranch = static::buildMenuBranch($child, $menuMap, $user, $navigationOnly);
            if ($childBranch) {
                $validChildren->push($childBranch);
            }
        }

        $menu->setRelation('children', $validChildren);
        return $menu;
    }

    /**
     * Detect active menu state based on current URL.
     * 
     * @param string $currentUrl
     * @return bool
     */
    public function isActiveForUrl(string $currentUrl): bool
    {
        if (!$this->url) {
            return false;
        }

        // Exact match
        if ($this->url === $currentUrl) {
            return true;
        }

        // Check if current URL starts with menu URL (for parent menus)
        if (str_starts_with($currentUrl, rtrim($this->url, '/') . '/')) {
            return true;
        }

        // Check children for active state
        foreach ($this->children as $child) {
            if ($child->isActiveForUrl($currentUrl)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate breadcrumb trail for current menu.
     * 
     * @param bool $includeHome
     * @return array
     */
    public function getBreadcrumbTrail(bool $includeHome = true): array
    {
        $breadcrumb = [];
        $current = $this;

        // Build breadcrumb from current to root
        while ($current) {
            array_unshift($breadcrumb, [
                'title' => $current->title,
                'url' => $current->url,
                'icon' => $current->icon,
                'is_active' => false
            ]);
            $current = $current->parent;
        }

        // Add home if requested
        if ($includeHome && !empty($breadcrumb)) {
            array_unshift($breadcrumb, [
                'title' => 'Home',
                'url' => '/',
                'icon' => 'fas fa-home',
                'is_active' => false
            ]);
        }

        // Mark last item as active
        if (!empty($breadcrumb)) {
            $breadcrumb[count($breadcrumb) - 1]['is_active'] = true;
        }

        return $breadcrumb;
    }

    /**
     * Clear menu cache for user.
     * 
     * @param User $user
     * @return void
     */
    public static function clearCacheForUser(User $user): void
    {
        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        $cacheKeys = [
            'menu_tree_user_' . $user->id . '_roles_' . md5(implode(',', $userRoleIds)),
            'navigation_menu_user_' . $user->id . '_roles_' . md5(implode(',', $userRoleIds))
        ];

        foreach ($cacheKeys as $key) {
            \Cache::forget($key);
        }
    }

    /**
     * Clear all menu cache.
     * 
     * @return void
     */
    public static function clearAllCache(): void
    {
        $cacheKeys = [
            'menu_tree',
            'navigation_menu'
        ];

        foreach ($cacheKeys as $key) {
            \Cache::forget($key);
        }

        // Clear role-specific caches
        $roles = \App\Models\Role::pluck('id');
        foreach ($roles as $roleId) {
            \Cache::forget("role_menu_{$roleId}");
            \Cache::forget("menu_tree_role_{$roleId}");
            \Cache::forget("navigation_menu_role_{$roleId}");
        }
    }
}
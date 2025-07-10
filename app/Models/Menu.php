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
}
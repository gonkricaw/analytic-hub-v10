<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Class RolePermissionCacheService
 * 
 * Handles caching operations for roles and permissions to improve performance.
 * Implements intelligent cache invalidation and hierarchical permission caching.
 * 
 * @package App\Services
 */
class RolePermissionCacheService
{
    /**
     * Cache TTL in seconds (24 hours)
     */
    private const CACHE_TTL = 86400;

    /**
     * Cache key prefixes
     */
    private const PREFIX_USER_PERMISSIONS = 'user_permissions_';
    private const PREFIX_USER_ROLES = 'user_roles_';
    private const PREFIX_ROLE_PERMISSIONS = 'role_permissions_';
    private const PREFIX_ROLE_MENU = 'role_menu_';
    private const PREFIX_PERMISSION_HIERARCHY = 'permission_hierarchy_';
    private const KEY_ALL_ROLES = 'all_roles';
    private const KEY_ALL_PERMISSIONS = 'all_permissions';
    private const KEY_SYSTEM_ROLES = 'system_roles';
    private const KEY_ACTIVE_PERMISSIONS = 'active_permissions';

    /**
     * Get user permissions with caching.
     * 
     * @param string $userId
     * @return Collection
     */
    public function getUserPermissions(string $userId): Collection
    {
        return Cache::remember(
            self::PREFIX_USER_PERMISSIONS . $userId,
            self::CACHE_TTL,
            function () use ($userId) {
                $user = User::with(['roles.permissions' => function ($query) {
                    $query->where('status', 'active');
                }])->find($userId);

                if (!$user) {
                    return collect();
                }

                $permissions = collect();
                foreach ($user->roles()->where('status', 'active')->get() as $role) {
                    $rolePermissions = $role->permissions;
                    $permissions = $permissions->merge($rolePermissions);
                }

                return $permissions->unique('id')->values();
            }
        );
    }

    /**
     * Get user roles with caching.
     * 
     * @param string $userId
     * @return Collection
     */
    public function getUserRoles(string $userId): Collection
    {
        return Cache::remember(
            self::PREFIX_USER_ROLES . $userId,
            self::CACHE_TTL,
            function () use ($userId) {
                return User::find($userId)
                          ?->roles()
                          ->where('status', 'active')
                          ->orderBy('level')
                          ->get() ?? collect();
            }
        );
    }

    /**
     * Get role permissions with caching.
     * 
     * @param string $roleId
     * @return Collection
     */
    public function getRolePermissions(string $roleId): Collection
    {
        return Cache::remember(
            self::PREFIX_ROLE_PERMISSIONS . $roleId,
            self::CACHE_TTL,
            function () use ($roleId) {
                return Role::find($roleId)
                          ?->permissions()
                          ->where('status', 'active')
                          ->orderBy('module')
                          ->orderBy('group')
                          ->orderBy('sort_order')
                          ->get() ?? collect();
            }
        );
    }

    /**
     * Get all roles with caching.
     * 
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        return Cache::remember(
            self::KEY_ALL_ROLES,
            self::CACHE_TTL,
            function () {
                return Role::where('status', 'active')
                          ->orderBy('level')
                          ->orderBy('name')
                          ->get();
            }
        );
    }

    /**
     * Get all permissions with caching.
     * 
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return Cache::remember(
            self::KEY_ALL_PERMISSIONS,
            self::CACHE_TTL,
            function () {
                return Permission::where('status', 'active')
                                ->orderBy('module')
                                ->orderBy('group')
                                ->orderBy('sort_order')
                                ->get();
            }
        );
    }

    /**
     * Get system roles with caching.
     * 
     * @return Collection
     */
    public function getSystemRoles(): Collection
    {
        return Cache::remember(
            self::KEY_SYSTEM_ROLES,
            self::CACHE_TTL,
            function () {
                return Role::where('is_system_role', true)
                          ->where('status', 'active')
                          ->orderBy('level')
                          ->get();
            }
        );
    }

    /**
     * Get permission hierarchy with caching.
     * 
     * @param string $permissionId
     * @return Collection
     */
    public function getPermissionHierarchy(string $permissionId): Collection
    {
        return Cache::remember(
            self::PREFIX_PERMISSION_HIERARCHY . $permissionId,
            self::CACHE_TTL,
            function () use ($permissionId) {
                return Permission::with('children.children')
                               ->find($permissionId)
                               ?->children ?? collect();
            }
        );
    }

    /**
     * Check if user has permission with caching.
     * 
     * @param string $userId
     * @param string $permissionName
     * @return bool
     */
    public function userHasPermission(string $userId, string $permissionName): bool
    {
        $permissions = $this->getUserPermissions($userId);
        
        // Direct permission check
        if ($permissions->where('name', $permissionName)->isNotEmpty()) {
            return true;
        }

        // Wildcard permission check
        foreach ($permissions as $permission) {
            if ($this->matchesWildcard($permission->name, $permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has role with caching.
     * 
     * @param string $userId
     * @param string $roleName
     * @return bool
     */
    public function userHasRole(string $userId, string $roleName): bool
    {
        $roles = $this->getUserRoles($userId);
        return $roles->where('name', $roleName)->isNotEmpty();
    }

    /**
     * Clear user-specific caches.
     * 
     * @param string $userId
     * @return void
     */
    public function clearUserCaches(string $userId): void
    {
        Cache::forget(self::PREFIX_USER_PERMISSIONS . $userId);
        Cache::forget(self::PREFIX_USER_ROLES . $userId);
    }

    /**
     * Clear role-specific caches.
     * 
     * @param string $roleId
     * @return void
     */
    public function clearRoleCaches(string $roleId): void
    {
        Cache::forget(self::PREFIX_ROLE_PERMISSIONS . $roleId);
        Cache::forget(self::PREFIX_ROLE_MENU . $roleId);
        
        // Clear caches for users with this role
        $userIds = DB::table('idbi_user_roles')
                    ->where('role_id', $roleId)
                    ->pluck('user_id');
        
        foreach ($userIds as $userId) {
            $this->clearUserCaches($userId);
        }
    }

    /**
     * Clear permission-specific caches.
     * 
     * @param string $permissionId
     * @return void
     */
    public function clearPermissionCaches(string $permissionId): void
    {
        Cache::forget(self::PREFIX_PERMISSION_HIERARCHY . $permissionId);
        
        // Clear caches for roles with this permission
        $roleIds = DB::table('idbi_role_permissions')
                    ->where('permission_id', $permissionId)
                    ->pluck('role_id');
        
        foreach ($roleIds as $roleId) {
            $this->clearRoleCaches($roleId);
        }
    }

    /**
     * Clear all role and permission caches.
     * 
     * @return void
     */
    public function clearAllCaches(): void
    {
        // Clear general caches
        Cache::forget(self::KEY_ALL_ROLES);
        Cache::forget(self::KEY_ALL_PERMISSIONS);
        Cache::forget(self::KEY_SYSTEM_ROLES);
        Cache::forget(self::KEY_ACTIVE_PERMISSIONS);
        
        // Clear pattern-based caches
        $this->clearCachesByPattern(self::PREFIX_USER_PERMISSIONS);
        $this->clearCachesByPattern(self::PREFIX_USER_ROLES);
        $this->clearCachesByPattern(self::PREFIX_ROLE_PERMISSIONS);
        $this->clearCachesByPattern(self::PREFIX_ROLE_MENU);
        $this->clearCachesByPattern(self::PREFIX_PERMISSION_HIERARCHY);
    }

    /**
     * Warm up caches for active users.
     * 
     * @param int $limit
     * @return void
     */
    public function warmUpCaches(int $limit = 100): void
    {
        // Warm up general caches
        $this->getAllRoles();
        $this->getAllPermissions();
        $this->getSystemRoles();
        
        // Warm up user-specific caches for recently active users
        $recentUsers = User::where('status', 'active')
                          ->where('last_login_at', '>=', now()->subDays(7))
                          ->limit($limit)
                          ->pluck('id');
        
        foreach ($recentUsers as $userId) {
            $this->getUserPermissions($userId);
            $this->getUserRoles($userId);
        }
    }

    /**
     * Get cache statistics.
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        $stats = [
            'general_caches' => [
                'all_roles' => Cache::has(self::KEY_ALL_ROLES),
                'all_permissions' => Cache::has(self::KEY_ALL_PERMISSIONS),
                'system_roles' => Cache::has(self::KEY_SYSTEM_ROLES),
            ],
            'user_caches' => 0,
            'role_caches' => 0,
            'permission_caches' => 0
        ];
        
        // Count pattern-based caches (this is an approximation)
        // In a real implementation, you might want to use Redis SCAN or similar
        
        return $stats;
    }

    /**
     * Match permission name against wildcard pattern.
     * 
     * @param string $pattern
     * @param string $permission
     * @return bool
     */
    private function matchesWildcard(string $pattern, string $permission): bool
    {
        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], preg_quote($pattern, '/')) . '$/i';
        return preg_match($regex, $permission) === 1;
    }

    /**
     * Clear caches by pattern (implementation depends on cache driver).
     * 
     * @param string $pattern
     * @return void
     */
    private function clearCachesByPattern(string $pattern): void
    {
        // This is a simplified implementation
        // For Redis, you would use SCAN with pattern matching
        // For file cache, you would scan the cache directory
        // For array cache, you would iterate through keys
        
        // Note: This method should be implemented based on your cache driver
        // For now, we'll use a basic approach that works with most drivers
        
        try {
            if (config('cache.default') === 'redis') {
                // Redis implementation
                $redis = Cache::getRedis();
                $keys = $redis->keys(config('cache.prefix') . $pattern . '*');
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
            // For other cache drivers, you might need different approaches
        } catch (\Exception $e) {
            // Log error but don't fail
            \Log::warning('Failed to clear cache pattern: ' . $pattern, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
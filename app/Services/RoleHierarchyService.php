<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class RoleHierarchyService
 * 
 * Handles role hierarchy and inheritance logic.
 * Implements permission inheritance based on role levels and hierarchical structures.
 * 
 * @package App\Services
 */
class RoleHierarchyService
{
    /**
     * Cache TTL for hierarchy data
     */
    private const HIERARCHY_CACHE_TTL = 3600; // 1 hour

    /**
     * Role hierarchy cache service
     */
    private RolePermissionCacheService $cacheService;

    /**
     * Constructor
     */
    public function __construct(RolePermissionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get effective permissions for a user considering role hierarchy.
     * 
     * @param User $user
     * @return Collection
     */
    public function getUserEffectivePermissions(User $user): Collection
    {
        $cacheKey = "user_effective_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, self::HIERARCHY_CACHE_TTL, function () use ($user) {
            $permissions = collect();
            $userRoles = $user->roles()->where('status', 'active')->orderBy('level')->get();
            
            foreach ($userRoles as $role) {
                // Get direct permissions
                $rolePermissions = $role->permissions()->where('status', 'active')->get();
                $permissions = $permissions->merge($rolePermissions);
                
                // Get inherited permissions from higher hierarchy roles
                $inheritedPermissions = $this->getInheritedPermissions($role);
                $permissions = $permissions->merge($inheritedPermissions);
            }
            
            return $permissions->unique('id')->values();
        });
    }

    /**
     * Get inherited permissions for a role based on hierarchy.
     * 
     * @param Role $role
     * @return Collection
     */
    public function getInheritedPermissions(Role $role): Collection
    {
        $cacheKey = "role_inherited_permissions_{$role->id}";
        
        return Cache::remember($cacheKey, self::HIERARCHY_CACHE_TTL, function () use ($role) {
            $inheritedPermissions = collect();
            
            // Get roles with higher hierarchy (lower level numbers)
            $higherRoles = Role::where('status', 'active')
                             ->where('level', '<', $role->level)
                             ->orderBy('level')
                             ->get();
            
            foreach ($higherRoles as $higherRole) {
                // Check if this role should inherit from the higher role
                if ($this->shouldInheritFrom($role, $higherRole)) {
                    $rolePermissions = $higherRole->permissions()->where('status', 'active')->get();
                    $inheritedPermissions = $inheritedPermissions->merge($rolePermissions);
                }
            }
            
            return $inheritedPermissions->unique('id')->values();
        });
    }

    /**
     * Check if a role should inherit permissions from another role.
     * 
     * @param Role $childRole
     * @param Role $parentRole
     * @return bool
     */
    public function shouldInheritFrom(Role $childRole, Role $parentRole): bool
    {
        // Basic hierarchy rule: lower level roles inherit from higher level roles
        if ($parentRole->level < $childRole->level) {
            // Additional inheritance rules can be implemented here
            
            // Example: Admin roles inherit from user roles
            if (str_contains($childRole->name, 'admin') && $parentRole->name === 'user') {
                return true;
            }
            
            // Example: Manager roles inherit from employee roles
            if (str_contains($childRole->name, 'manager') && str_contains($parentRole->name, 'employee')) {
                return true;
            }
            
            // Example: Super admin inherits from all roles
            if ($childRole->name === 'super_admin') {
                return true;
            }
            
            // Default inheritance based on level difference
            $levelDifference = $childRole->level - $parentRole->level;
            return $levelDifference <= 2; // Only inherit from roles within 2 levels
        }
        
        return false;
    }

    /**
     * Get role hierarchy tree.
     * 
     * @return array
     */
    public function getRoleHierarchyTree(): array
    {
        $cacheKey = 'role_hierarchy_tree';
        
        return Cache::remember($cacheKey, self::HIERARCHY_CACHE_TTL, function () {
            $roles = Role::where('status', 'active')
                        ->orderBy('level')
                        ->orderBy('name')
                        ->get();
            
            $tree = [];
            $rolesByLevel = $roles->groupBy('level');
            
            foreach ($rolesByLevel as $level => $levelRoles) {
                $tree[$level] = [];
                
                foreach ($levelRoles as $role) {
                    $tree[$level][] = [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'level' => $role->level,
                        'is_system_role' => $role->is_system_role,
                        'permissions_count' => $role->permissions()->count(),
                        'users_count' => $role->users()->count(),
                        'inherited_from' => $this->getInheritanceChain($role)
                    ];
                }
            }
            
            return $tree;
        });
    }

    /**
     * Get inheritance chain for a role.
     * 
     * @param Role $role
     * @return array
     */
    public function getInheritanceChain(Role $role): array
    {
        $chain = [];
        
        $higherRoles = Role::where('status', 'active')
                         ->where('level', '<', $role->level)
                         ->orderBy('level')
                         ->get();
        
        foreach ($higherRoles as $higherRole) {
            if ($this->shouldInheritFrom($role, $higherRole)) {
                $chain[] = [
                    'id' => $higherRole->id,
                    'name' => $higherRole->name,
                    'display_name' => $higherRole->display_name,
                    'level' => $higherRole->level
                ];
            }
        }
        
        return $chain;
    }

    /**
     * Check if user can access resource based on role hierarchy.
     * 
     * @param User $user
     * @param string $resource
     * @param string $action
     * @return bool
     */
    public function canAccessResource(User $user, string $resource, string $action): bool
    {
        $permissionName = "{$resource}.{$action}";
        
        // Check direct permissions
        if ($user->hasPermission($permissionName)) {
            return true;
        }
        
        // Check effective permissions (including inherited)
        $effectivePermissions = $this->getUserEffectivePermissions($user);
        
        foreach ($effectivePermissions as $permission) {
            if ($permission->name === $permissionName || $permission->matches($permissionName)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get users who can access a specific resource.
     * 
     * @param string $resource
     * @param string $action
     * @return Collection
     */
    public function getUsersWithResourceAccess(string $resource, string $action): Collection
    {
        $permissionName = "{$resource}.{$action}";
        
        // Find permission
        $permission = Permission::where('name', $permissionName)
                               ->where('status', 'active')
                               ->first();
        
        if (!$permission) {
            return collect();
        }
        
        // Get roles with this permission (direct or inherited)
        $rolesWithPermission = collect();
        
        // Direct permission assignment
        $directRoles = $permission->roles()->where('status', 'active')->get();
        $rolesWithPermission = $rolesWithPermission->merge($directRoles);
        
        // Inherited permission assignment
        $allRoles = Role::where('status', 'active')->get();
        foreach ($allRoles as $role) {
            $inheritedPermissions = $this->getInheritedPermissions($role);
            if ($inheritedPermissions->contains('id', $permission->id)) {
                $rolesWithPermission->push($role);
            }
        }
        
        $rolesWithPermission = $rolesWithPermission->unique('id');
        
        // Get users with these roles
        $users = collect();
        foreach ($rolesWithPermission as $role) {
            $roleUsers = $role->users()->where('status', 'active')->get();
            $users = $users->merge($roleUsers);
        }
        
        return $users->unique('id')->values();
    }

    /**
     * Validate role hierarchy consistency.
     * 
     * @return array
     */
    public function validateHierarchy(): array
    {
        $issues = [];
        
        $roles = Role::where('status', 'active')->orderBy('level')->get();
        
        foreach ($roles as $role) {
            // Check for circular inheritance
            if ($this->hasCircularInheritance($role)) {
                $issues[] = [
                    'type' => 'circular_inheritance',
                    'role' => $role->name,
                    'message' => "Role {$role->name} has circular inheritance"
                ];
            }
            
            // Check for level inconsistencies
            $inheritedRoles = $this->getInheritanceChain($role);
            foreach ($inheritedRoles as $inheritedRole) {
                if ($inheritedRole['level'] >= $role->level) {
                    $issues[] = [
                        'type' => 'level_inconsistency',
                        'role' => $role->name,
                        'inherited_role' => $inheritedRole['name'],
                        'message' => "Role {$role->name} inherits from {$inheritedRole['name']} but has same or lower level"
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Check for circular inheritance in role hierarchy.
     * 
     * @param Role $role
     * @param array $visited
     * @return bool
     */
    private function hasCircularInheritance(Role $role, array $visited = []): bool
    {
        if (in_array($role->id, $visited)) {
            return true;
        }
        
        $visited[] = $role->id;
        
        $inheritedRoles = $this->getInheritanceChain($role);
        foreach ($inheritedRoles as $inheritedRoleData) {
            $inheritedRole = Role::find($inheritedRoleData['id']);
            if ($inheritedRole && $this->hasCircularInheritance($inheritedRole, $visited)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Clear hierarchy caches.
     * 
     * @return void
     */
    public function clearHierarchyCaches(): void
    {
        Cache::forget('role_hierarchy_tree');
        
        // Clear user effective permissions
        $users = User::where('status', 'active')->pluck('id');
        foreach ($users as $userId) {
            Cache::forget("user_effective_permissions_{$userId}");
        }
        
        // Clear role inherited permissions
        $roles = Role::where('status', 'active')->pluck('id');
        foreach ($roles as $roleId) {
            Cache::forget("role_inherited_permissions_{$roleId}");
        }
    }

    /**
     * Rebuild role hierarchy caches.
     * 
     * @return void
     */
    public function rebuildHierarchyCaches(): void
    {
        $this->clearHierarchyCaches();
        
        // Warm up hierarchy tree
        $this->getRoleHierarchyTree();
        
        // Warm up role inherited permissions
        $roles = Role::where('status', 'active')->get();
        foreach ($roles as $role) {
            $this->getInheritedPermissions($role);
        }
        
        // Warm up user effective permissions for active users
        $activeUsers = User::where('status', 'active')
                          ->where('last_login_at', '>=', now()->subDays(7))
                          ->limit(100)
                          ->get();
        
        foreach ($activeUsers as $user) {
            $this->getUserEffectivePermissions($user);
        }
        
        Log::info('Role hierarchy caches rebuilt', [
            'roles_count' => $roles->count(),
            'users_count' => $activeUsers->count()
        ]);
    }
}
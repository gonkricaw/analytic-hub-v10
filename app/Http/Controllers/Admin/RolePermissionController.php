<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Class RolePermissionController
 * 
 * Handles the assignment and management of permissions to roles.
 * Provides interfaces for bulk assignment, individual management,
 * and role-permission matrix operations.
 * 
 * @package App\Http\Controllers\Admin
 */
class RolePermissionController extends Controller
{
    /**
     * Display the role-permission assignment interface.
     * 
     * @return View
     */
    public function index(): View
    {
        $roles = Role::with('permissions')
                    ->where('status', 'active')
                    ->orderBy('level')
                    ->orderBy('name')
                    ->get();

        $permissions = Permission::with('parent', 'children')
                                ->where('status', 'active')
                                ->orderBy('module')
                                ->orderBy('group')
                                ->orderBy('sort_order')
                                ->get();

        // Group permissions by module for better organization
        $permissionsByModule = $permissions->groupBy('module');

        return view('admin.role-permissions.index', compact('roles', 'permissions', 'permissionsByModule'));
    }

    /**
     * Get role-permission matrix data for DataTables.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMatrixData(Request $request): JsonResponse
    {
        try {
            $roles = Role::with(['permissions' => function ($query) {
                        $query->where('status', 'active');
                    }])
                    ->where('status', 'active')
                    ->orderBy('level')
                    ->orderBy('name')
                    ->get();

            $permissions = Permission::where('status', 'active')
                                   ->orderBy('module')
                                   ->orderBy('group')
                                   ->orderBy('sort_order')
                                   ->get();

            $matrix = [];
            foreach ($permissions as $permission) {
                $row = [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->display_name,
                    'module' => $permission->module,
                    'group' => $permission->group,
                ];

                foreach ($roles as $role) {
                    $hasPermission = $role->permissions->contains('id', $permission->id);
                    $row['role_' . $role->id] = $hasPermission;
                }

                $matrix[] = $row;
            }

            return response()->json([
                'data' => $matrix,
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'is_system_role' => $role->is_system_role
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching role-permission matrix data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load matrix data'
            ], 500);
        }
    }

    /**
     * Assign a permission to a role.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function assignPermission(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|uuid|exists:idbi_roles,id',
                'permission_id' => 'required|uuid|exists:idbi_permissions,id'
            ]);

            $role = Role::findOrFail($request->role_id);
            $permission = Permission::findOrFail($request->permission_id);

            // Check if role is system role and user has permission to modify
            if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'error' => 'Cannot modify system role permissions'
                ], 403);
            }

            DB::beginTransaction();

            // Check if permission is already assigned
            if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
                $role->permissions()->attach($permission->id, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Refresh role permissions cache
                $role->refreshPermissionsCache();

                // Clear related caches
                $this->clearRolePermissionCaches($role->id);

                // Log the assignment
                Log::info('Permission assigned to role', [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name,
                    'assigned_by' => auth()->id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned successfully'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning permission to role', [
                'error' => $e->getMessage(),
                'role_id' => $request->role_id ?? null,
                'permission_id' => $request->permission_id ?? null
            ]);

            return response()->json([
                'error' => 'Failed to assign permission'
            ], 500);
        }
    }

    /**
     * Remove a permission from a role.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function removePermission(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|uuid|exists:idbi_roles,id',
                'permission_id' => 'required|uuid|exists:idbi_permissions,id'
            ]);

            $role = Role::findOrFail($request->role_id);
            $permission = Permission::findOrFail($request->permission_id);

            // Check if role is system role and user has permission to modify
            if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'error' => 'Cannot modify system role permissions'
                ], 403);
            }

            DB::beginTransaction();

            // Remove the permission
            $role->permissions()->detach($permission->id);

            // Refresh role permissions cache
            $role->refreshPermissionsCache();

            // Clear related caches
            $this->clearRolePermissionCaches($role->id);

            // Log the removal
            Log::info('Permission removed from role', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'removed_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission removed successfully'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing permission from role', [
                'error' => $e->getMessage(),
                'role_id' => $request->role_id ?? null,
                'permission_id' => $request->permission_id ?? null
            ]);

            return response()->json([
                'error' => 'Failed to remove permission'
            ], 500);
        }
    }

    /**
     * Bulk assign permissions to a role.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|uuid|exists:idbi_roles,id',
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'uuid|exists:idbi_permissions,id'
            ]);

            $role = Role::findOrFail($request->role_id);

            // Check if role is system role and user has permission to modify
            if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'error' => 'Cannot modify system role permissions'
                ], 403);
            }

            DB::beginTransaction();

            // Get current permissions
            $currentPermissions = $role->permissions()->pluck('permission_id')->toArray();
            
            // Find new permissions to add
            $newPermissions = array_diff($request->permission_ids, $currentPermissions);
            
            if (!empty($newPermissions)) {
                // Prepare data for bulk insert
                $attachData = [];
                foreach ($newPermissions as $permissionId) {
                    $attachData[$permissionId] = [
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                
                $role->permissions()->attach($attachData);

                // Refresh role permissions cache
                $role->refreshPermissionsCache();

                // Clear related caches
                $this->clearRolePermissionCaches($role->id);

                // Log the bulk assignment
                Log::info('Bulk permissions assigned to role', [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permission_count' => count($newPermissions),
                    'assigned_by' => auth()->id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($newPermissions) . ' permissions assigned successfully'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk permission assignment', [
                'error' => $e->getMessage(),
                'role_id' => $request->role_id ?? null
            ]);

            return response()->json([
                'error' => 'Failed to assign permissions'
            ], 500);
        }
    }

    /**
     * Sync permissions for a role (replace all permissions).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncPermissions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|uuid|exists:idbi_roles,id',
                'permission_ids' => 'array',
                'permission_ids.*' => 'uuid|exists:idbi_permissions,id'
            ]);

            $role = Role::findOrFail($request->role_id);

            // Check if role is system role and user has permission to modify
            if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'error' => 'Cannot modify system role permissions'
                ], 403);
            }

            DB::beginTransaction();

            // Sync permissions
            $permissionIds = $request->permission_ids ?? [];
            $role->permissions()->sync($permissionIds);

            // Refresh role permissions cache
            $role->refreshPermissionsCache();

            // Clear related caches
            $this->clearRolePermissionCaches($role->id);

            // Log the sync
            Log::info('Role permissions synchronized', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permission_count' => count($permissionIds),
                'synced_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permissions synchronized successfully'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error synchronizing role permissions', [
                'error' => $e->getMessage(),
                'role_id' => $request->role_id ?? null
            ]);

            return response()->json([
                'error' => 'Failed to synchronize permissions'
            ], 500);
        }
    }

    /**
     * Clear role-permission related caches.
     * 
     * @param string $roleId
     * @return void
     */
    private function clearRolePermissionCaches(string $roleId): void
    {
        // Clear role-specific caches
        Cache::forget("role_permissions_{$roleId}");
        Cache::forget("role_menu_{$roleId}");
        
        // Clear user permission caches for users with this role
        $userIds = DB::table('idbi_user_roles')
                    ->where('role_id', $roleId)
                    ->pluck('user_id');
        
        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
            Cache::forget("user_roles_{$userId}");
        }
        
        // Clear general caches
        Cache::forget('all_roles');
        Cache::forget('all_permissions');
    }
}
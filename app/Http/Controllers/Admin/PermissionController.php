<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

/**
 * Class PermissionController
 * 
 * Handles CRUD operations for permissions in the Analytics Hub RBAC system.
 * Provides functionality for creating, reading, updating, and deleting permissions,
 * as well as managing permission hierarchies and role assignments.
 * 
 * @package App\Http\Controllers\Admin
 */
class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     * 
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Check if this is an AJAX request for DataTables
            if ($request->ajax()) {
                return $this->getDataTablesData($request);
            }

            // Get modules for filter dropdown
            $modules = Permission::select('module')
                                ->distinct()
                                ->whereNotNull('module')
                                ->orderBy('module')
                                ->pluck('module');

            // Return the permissions index view
            return view('admin.permissions.index', compact('modules'));
        } catch (Exception $e) {
            Log::error('Error loading permissions index: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Failed to load permissions data'
                ], 500);
            }
            
            return back()->with('error', 'Failed to load permissions page.');
        }
    }

    /**
     * Get DataTables formatted data for permissions listing.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    private function getDataTablesData(Request $request): JsonResponse
    {
        $query = Permission::with(['parent:id,name,display_name', 'children:id,name,display_name,parent_id'])
                          ->withCount('roles');

        // Apply module filter
        if ($request->has('module') && !empty($request->module)) {
            $query->where('module', $request->module);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'ILIKE', "%{$searchValue}%")
                  ->orWhere('display_name', 'ILIKE', "%{$searchValue}%")
                  ->orWhere('description', 'ILIKE', "%{$searchValue}%")
                  ->orWhere('module', 'ILIKE', "%{$searchValue}%")
                  ->orWhere('action', 'ILIKE', "%{$searchValue}%");
            });
        }

        // Apply column ordering
        if ($request->has('order')) {
            $orderColumn = $request->columns[$request->order[0]['column']]['data'];
            $orderDirection = $request->order[0]['dir'];
            
            switch ($orderColumn) {
                case 'name':
                case 'display_name':
                case 'module':
                case 'action':
                case 'sort_order':
                case 'status':
                case 'created_at':
                    $query->orderBy($orderColumn, $orderDirection);
                    break;
                default:
                    $query->orderBy('module')->orderBy('sort_order')->orderBy('name');
            }
        } else {
            $query->orderBy('module')->orderBy('sort_order')->orderBy('name');
        }

        // Get total count before pagination
        $totalRecords = Permission::count();
        $filteredRecords = $query->count();

        // Apply pagination
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $permissions = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description ?? '-',
                'module' => $permission->module,
                'action' => $permission->action,
                'resource' => $permission->resource ?? '-',
                'parent' => $permission->parent ? $permission->parent->display_name : '-',
                'group' => $permission->group ?? '-',
                'sort_order' => $permission->sort_order,
                'is_system_permission' => $permission->is_system_permission,
                'status' => $permission->status,
                'roles_count' => $permission->roles_count,
                'children_count' => $permission->children->count(),
                'created_at' => $permission->created_at->format('Y-m-d H:i:s'),
                'actions' => $this->generateActionButtons($permission)
            ];
        });

        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Generate action buttons for DataTables.
     * 
     * @param Permission $permission
     * @return string
     */
    private function generateActionButtons(Permission $permission): string
    {
        $buttons = [];
        
        // View button
        $buttons[] = '<button type="button" class="btn btn-sm btn-info" onclick="viewPermission(\'' . $permission->id . '\')" title="View Details">' .
                    '<i class="fas fa-eye"></i></button>';
        
        // Edit button (only for non-system permissions or if user is super admin)
        if (!$permission->is_system_permission || auth()->user()->hasRole('super_admin')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" onclick="editPermission(\'' . $permission->id . '\')" title="Edit Permission">' .
                        '<i class="fas fa-edit"></i></button>';
        }
        
        // Roles button
        $buttons[] = '<button type="button" class="btn btn-sm btn-primary" onclick="manageRoles(\'' . $permission->id . '\')" title="Manage Roles">' .
                    '<i class="fas fa-users"></i></button>';
        
        // Delete button (only for non-system permissions with no roles and no children)
        if (!$permission->is_system_permission && $permission->roles_count == 0 && $permission->children->count() == 0) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" onclick="deletePermission(\'' . $permission->id . '\')" title="Delete Permission">' .
                        '<i class="fas fa-trash"></i></button>';
        }
        
        return '<div class="btn-group">' . implode('', $buttons) . '</div>';
    }

    /**
     * Show the form for creating a new permission.
     * 
     * @return View
     */
    public function create(): View
    {
        $parentPermissions = Permission::whereNull('parent_id')
                                      ->active()
                                      ->orderBy('module')
                                      ->orderBy('sort_order')
                                      ->orderBy('display_name')
                                      ->get();
        
        $modules = Permission::select('module')
                            ->distinct()
                            ->whereNotNull('module')
                            ->orderBy('module')
                            ->pluck('module');
        
        return view('admin.permissions.create', compact('parentPermissions', 'modules'));
    }

    /**
     * Store a newly created permission in storage.
     * 
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-z0-9_.]+$/',
                    Rule::unique('idbi_permissions', 'name')->whereNull('deleted_at')
                ],
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'module' => 'required|string|max:50',
                'action' => 'required|string|max:50',
                'resource' => 'nullable|string|max:100',
                'parent_id' => 'nullable|exists:idbi_permissions,id',
                'group' => 'nullable|string|max:50',
                'sort_order' => 'required|integer|min:0|max:9999',
                'status' => ['required', Rule::in([Permission::STATUS_ACTIVE, Permission::STATUS_INACTIVE])],
                'conditions' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);

            DB::beginTransaction();

            // Create the permission
            $permission = Permission::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'],
                'module' => $validated['module'],
                'action' => $validated['action'],
                'resource' => $validated['resource'],
                'parent_id' => $validated['parent_id'],
                'group' => $validated['group'],
                'sort_order' => $validated['sort_order'],
                'is_system_permission' => false,
                'conditions' => $validated['conditions'],
                'metadata' => $validated['metadata'],
                'status' => $validated['status'],
                'created_by' => auth()->id()
            ]);

            DB::commit();

            // Clear relevant caches
            Cache::tags(['permissions', 'roles'])->flush();

            // Log the activity
            Log::info('Permission created', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'created_by' => auth()->id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission created successfully.',
                    'permission' => $permission
                ]);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('success', 'Permission created successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating permission: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create permission: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                        ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified permission.
     * 
     * @param string $id
     * @param Request $request
     * @return View|JsonResponse
     */
    public function show(string $id, Request $request)
    {
        try {
            $permission = Permission::with(['parent', 'children', 'roles'])->findOrFail($id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'permission' => $permission
                ]);
            }

            return view('admin.permissions.show', compact('permission'));
        } catch (Exception $e) {
            Log::error('Error loading permission: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission not found'
                ], 404);
            }

            return back()->with('error', 'Permission not found.');
        }
    }

    /**
     * Show the form for editing the specified permission.
     * 
     * @param string $id
     * @return View
     */
    public function edit(string $id): View
    {
        $permission = Permission::with(['parent', 'children'])->findOrFail($id);
        
        // Prevent editing system permissions unless user is super admin
        if ($permission->is_system_permission && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Cannot edit system permissions.');
        }
        
        $parentPermissions = Permission::whereNull('parent_id')
                                      ->where('id', '!=', $id)
                                      ->active()
                                      ->orderBy('module')
                                      ->orderBy('sort_order')
                                      ->orderBy('display_name')
                                      ->get();
        
        $modules = Permission::select('module')
                            ->distinct()
                            ->whereNotNull('module')
                            ->orderBy('module')
                            ->pluck('module');
        
        return view('admin.permissions.edit', compact('permission', 'parentPermissions', 'modules'));
    }

    /**
     * Update the specified permission in storage.
     * 
     * @param Request $request
     * @param string $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, string $id)
    {
        try {
            $permission = Permission::findOrFail($id);
            
            // Prevent editing system permissions unless user is super admin
            if ($permission->is_system_permission && !auth()->user()->hasRole('super_admin')) {
                abort(403, 'Cannot edit system permissions.');
            }

            // Validate the request
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-z0-9_.]+$/',
                    Rule::unique('idbi_permissions', 'name')->ignore($id)->whereNull('deleted_at')
                ],
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'module' => 'required|string|max:50',
                'action' => 'required|string|max:50',
                'resource' => 'nullable|string|max:100',
                'parent_id' => [
                    'nullable',
                    'exists:idbi_permissions,id',
                    function ($attribute, $value, $fail) use ($id) {
                        if ($value == $id) {
                            $fail('Permission cannot be its own parent.');
                        }
                        // Check for circular reference
                        if ($value && $this->wouldCreateCircularReference($id, $value)) {
                            $fail('This would create a circular reference.');
                        }
                    }
                ],
                'group' => 'nullable|string|max:50',
                'sort_order' => 'required|integer|min:0|max:9999',
                'status' => ['required', Rule::in([Permission::STATUS_ACTIVE, Permission::STATUS_INACTIVE])],
                'conditions' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);

            DB::beginTransaction();

            // Update the permission
            $permission->update([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'],
                'module' => $validated['module'],
                'action' => $validated['action'],
                'resource' => $validated['resource'],
                'parent_id' => $validated['parent_id'],
                'group' => $validated['group'],
                'sort_order' => $validated['sort_order'],
                'conditions' => $validated['conditions'],
                'metadata' => $validated['metadata'],
                'status' => $validated['status'],
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            // Clear relevant caches
            Cache::tags(['permissions', 'roles'])->flush();

            // Log the activity
            Log::info('Permission updated', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'updated_by' => auth()->id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission updated successfully.',
                    'permission' => $permission
                ]);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('success', 'Permission updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating permission: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update permission: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                        ->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified permission from storage.
     * 
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(string $id, Request $request)
    {
        try {
            $permission = Permission::with(['children', 'roles'])->findOrFail($id);
            
            // Prevent deleting system permissions
            if ($permission->is_system_permission) {
                throw new Exception('Cannot delete system permissions.');
            }
            
            // Prevent deleting permissions with roles
            if ($permission->roles()->count() > 0) {
                throw new Exception('Cannot delete permission that is assigned to roles.');
            }
            
            // Prevent deleting permissions with children
            if ($permission->children()->count() > 0) {
                throw new Exception('Cannot delete permission that has child permissions.');
            }

            DB::beginTransaction();

            // Soft delete the permission
            $permission->update(['updated_by' => auth()->id()]);
            $permission->delete();

            DB::commit();

            // Clear relevant caches
            Cache::tags(['permissions', 'roles'])->flush();

            // Log the activity
            Log::info('Permission deleted', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'deleted_by' => auth()->id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission deleted successfully.'
                ]);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('success', 'Permission deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting permission: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get roles for permission assignment interface.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function getRoles(string $id): JsonResponse
    {
        try {
            $permission = Permission::with('roles')->findOrFail($id);
            
            $allRoles = Role::active()
                           ->orderBy('level')
                           ->orderBy('display_name')
                           ->get();
            
            $permissionRoles = $permission->roles->pluck('id')->toArray();
            
            return response()->json([
                'success' => true,
                'permission' => $permission,
                'roles' => $allRoles,
                'permission_roles' => $permissionRoles
            ]);
        } catch (Exception $e) {
            Log::error('Error loading permission roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load roles'
            ], 500);
        }
    }

    /**
     * Update permission roles.
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateRoles(Request $request, string $id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);
            
            // Prevent editing system permission roles unless user is super admin
            if ($permission->is_system_permission && !auth()->user()->hasRole('super_admin')) {
                throw new Exception('Cannot modify system permission roles.');
            }

            $validated = $request->validate([
                'roles' => 'nullable|array',
                'roles.*' => 'exists:idbi_roles,id'
            ]);

            DB::beginTransaction();

            // Sync roles
            $permission->roles()->sync($validated['roles'] ?? []);
            
            // Refresh permissions cache for affected roles
            if (!empty($validated['roles'])) {
                Role::whereIn('id', $validated['roles'])->get()->each(function ($role) {
                    $role->refreshPermissionsCache();
                });
            }
            
            $permission->update(['updated_by' => auth()->id()]);

            DB::commit();

            // Clear relevant caches
            Cache::tags(['permissions', 'roles'])->flush();

            // Log the activity
            Log::info('Permission roles updated', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'roles_count' => count($validated['roles'] ?? []),
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Roles updated successfully.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating permission roles: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get permission hierarchy tree.
     * 
     * @return JsonResponse
     */
    public function getHierarchy(): JsonResponse
    {
        try {
            $permissions = Permission::with(['children' => function ($query) {
                $query->orderBy('sort_order')->orderBy('display_name');
            }])
            ->whereNull('parent_id')
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get();

            return response()->json([
                'success' => true,
                'permissions' => $permissions
            ]);
        } catch (Exception $e) {
            Log::error('Error loading permission hierarchy: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load permission hierarchy'
            ], 500);
        }
    }

    /**
     * Check if setting a parent would create a circular reference.
     * 
     * @param string $permissionId
     * @param string $parentId
     * @return bool
     */
    private function wouldCreateCircularReference(string $permissionId, string $parentId): bool
    {
        $current = Permission::find($parentId);
        
        while ($current && $current->parent_id) {
            if ($current->parent_id === $permissionId) {
                return true;
            }
            $current = $current->parent;
        }
        
        return false;
    }
}
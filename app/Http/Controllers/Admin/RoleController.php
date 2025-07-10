<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
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
 * Class RoleController
 * 
 * Handles CRUD operations for roles in the Analytics Hub RBAC system.
 * Provides functionality for creating, reading, updating, and deleting roles,
 * as well as managing role-permission assignments.
 * 
 * @package App\Http\Controllers\Admin
 */
class RoleController extends Controller
{
    /**
     * Display a listing of roles.
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

            // Return the roles index view
            return view('admin.roles.index');
        } catch (Exception $e) {
            Log::error('Error loading roles index: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Failed to load roles data'
                ], 500);
            }
            
            return back()->with('error', 'Failed to load roles page.');
        }
    }

    /**
     * Get DataTables formatted data for roles listing.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    private function getDataTablesData(Request $request): JsonResponse
    {
        $query = Role::with(['permissions:id,name,display_name'])
                    ->withCount('users');

        // Apply search filter
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'ILIKE', "%{$searchValue}%")
                  ->orWhere('display_name', 'ILIKE', "%{$searchValue}%")
                  ->orWhere('description', 'ILIKE', "%{$searchValue}%");
            });
        }

        // Apply column ordering
        if ($request->has('order')) {
            $orderColumn = $request->columns[$request->order[0]['column']]['data'];
            $orderDirection = $request->order[0]['dir'];
            
            switch ($orderColumn) {
                case 'name':
                case 'display_name':
                case 'level':
                case 'status':
                case 'created_at':
                    $query->orderBy($orderColumn, $orderDirection);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('level')->orderBy('name');
        }

        // Get total count before pagination
        $totalRecords = Role::count();
        $filteredRecords = $query->count();

        // Apply pagination
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $roles = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description ?? '-',
                'level' => $role->level,
                'is_system_role' => $role->is_system_role,
                'is_default' => $role->is_default,
                'status' => $role->status,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('display_name')->join(', '),
                'created_at' => $role->created_at->format('Y-m-d H:i:s'),
                'actions' => $this->generateActionButtons($role)
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
     * @param Role $role
     * @return string
     */
    private function generateActionButtons(Role $role): string
    {
        $buttons = [];
        
        // View button
        $buttons[] = '<button type="button" class="btn btn-sm btn-info" onclick="viewRole(\'' . $role->id . '\')" title="View Details">' .
                    '<i class="fas fa-eye"></i></button>';
        
        // Edit button (only for non-system roles or if user is super admin)
        if (!$role->is_system_role || auth()->user()->hasRole('super_admin')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" onclick="editRole(\'' . $role->id . '\')" title="Edit Role">' .
                        '<i class="fas fa-edit"></i></button>';
        }
        
        // Permissions button
        $buttons[] = '<button type="button" class="btn btn-sm btn-primary" onclick="managePermissions(\'' . $role->id . '\')" title="Manage Permissions">' .
                    '<i class="fas fa-key"></i></button>';
        
        // Delete button (only for non-system roles with no users)
        if (!$role->is_system_role && $role->users_count == 0) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" onclick="deleteRole(\'' . $role->id . '\')" title="Delete Role">' .
                        '<i class="fas fa-trash"></i></button>';
        }
        
        return '<div class="btn-group">' . implode('', $buttons) . '</div>';
    }

    /**
     * Show the form for creating a new role.
     * 
     * @return View
     */
    public function create(): View
    {
        $permissions = Permission::active()
                                ->orderBy('module')
                                ->orderBy('sort_order')
                                ->orderBy('display_name')
                                ->get()
                                ->groupBy('module');
        
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
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
                    'max:50',
                    'regex:/^[a-z0-9_]+$/',
                    Rule::unique('idbi_roles', 'name')->whereNull('deleted_at')
                ],
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'level' => 'required|integer|min:1|max:100',
                'status' => ['required', Rule::in([Role::STATUS_ACTIVE, Role::STATUS_INACTIVE])],
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:idbi_permissions,id'
            ]);

            DB::beginTransaction();

            // Create the role
            $role = Role::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'],
                'level' => $validated['level'],
                'is_system_role' => false,
                'is_default' => false,
                'status' => $validated['status'],
                'created_by' => auth()->id()
            ]);

            // Attach permissions if provided
            if (!empty($validated['permissions'])) {
                $role->permissions()->attach($validated['permissions']);
                $role->refreshPermissionsCache();
            }

            DB::commit();

            // Clear relevant caches
            Cache::tags(['roles', 'permissions'])->flush();

            // Log the activity
            Log::info('Role created', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'created_by' => auth()->id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully.',
                    'role' => $role
                ]);
            }

            return redirect()->route('admin.roles.index')
                           ->with('success', 'Role created successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating role: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create role: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                        ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified role.
     * 
     * @param string $id
     * @return View|JsonResponse
     */
    public function show(string $id, Request $request)
    {
        try {
            $role = Role::with(['permissions', 'users'])->findOrFail($id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'role' => $role
                ]);
            }

            return view('admin.roles.show', compact('role'));
        } catch (Exception $e) {
            Log::error('Error loading role: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            return back()->with('error', 'Role not found.');
        }
    }

    /**
     * Show the form for editing the specified role.
     * 
     * @param string $id
     * @return View
     */
    public function edit(string $id): View
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        // Prevent editing system roles unless user is super admin
        if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Cannot edit system roles.');
        }
        
        $permissions = Permission::active()
                                ->orderBy('module')
                                ->orderBy('sort_order')
                                ->orderBy('display_name')
                                ->get()
                                ->groupBy('module');
        
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage.
     * 
     * @param Request $request
     * @param string $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, string $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Prevent editing system roles unless user is super admin
            if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
                abort(403, 'Cannot edit system roles.');
            }

            // Validate the request
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[a-z0-9_]+$/',
                    Rule::unique('idbi_roles', 'name')->ignore($id)->whereNull('deleted_at')
                ],
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'level' => 'required|integer|min:1|max:100',
                'status' => ['required', Rule::in([Role::STATUS_ACTIVE, Role::STATUS_INACTIVE])],
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:idbi_permissions,id'
            ]);

            DB::beginTransaction();

            // Update the role
            $role->update([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'],
                'level' => $validated['level'],
                'status' => $validated['status'],
                'updated_by' => auth()->id()
            ]);

            // Sync permissions
            $role->permissions()->sync($validated['permissions'] ?? []);
            $role->refreshPermissionsCache();

            DB::commit();

            // Clear relevant caches
            Cache::tags(['roles', 'permissions'])->flush();

            // Log the activity
            Log::info('Role updated', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'updated_by' => auth()->id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully.',
                    'role' => $role
                ]);
            }

            return redirect()->route('admin.roles.index')
                           ->with('success', 'Role updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating role: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update role: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                        ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified role from storage.
     * 
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(string $id, Request $request)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Prevent deleting system roles
            if ($role->is_system_role) {
                throw new Exception('Cannot delete system roles.');
            }
            
            // Prevent deleting roles with users
            if ($role->users()->count() > 0) {
                throw new Exception('Cannot delete role that has assigned users.');
            }

            DB::beginTransaction();

            // Detach all permissions
            $role->permissions()->detach();
            
            // Soft delete the role
            $role->update(['updated_by' => auth()->id()]);
            $role->delete();

            DB::commit();

            // Clear relevant caches
            Cache::tags(['roles', 'permissions'])->flush();

            // Log the activity
            Log::info('Role deleted', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'deleted_by' => auth()->id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully.'
                ]);
            }

            return redirect()->route('admin.roles.index')
                           ->with('success', 'Role deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting role: ' . $e->getMessage());

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
     * Get permissions for role assignment interface.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function getPermissions(string $id): JsonResponse
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            
            $allPermissions = Permission::active()
                                       ->orderBy('module')
                                       ->orderBy('sort_order')
                                       ->orderBy('display_name')
                                       ->get()
                                       ->groupBy('module');
            
            $rolePermissions = $role->permissions->pluck('id')->toArray();
            
            return response()->json([
                'success' => true,
                'role' => $role,
                'permissions' => $allPermissions,
                'role_permissions' => $rolePermissions
            ]);
        } catch (Exception $e) {
            Log::error('Error loading role permissions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load permissions'
            ], 500);
        }
    }

    /**
     * Update role permissions.
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updatePermissions(Request $request, string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            
            // Prevent editing system role permissions unless user is super admin
            if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
                throw new Exception('Cannot modify system role permissions.');
            }

            $validated = $request->validate([
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:idbi_permissions,id'
            ]);

            DB::beginTransaction();

            // Sync permissions
            $role->permissions()->sync($validated['permissions'] ?? []);
            $role->refreshPermissionsCache();
            $role->update(['updated_by' => auth()->id()]);

            DB::commit();

            // Clear relevant caches
            Cache::tags(['roles', 'permissions'])->flush();

            // Log the activity
            Log::info('Role permissions updated', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_count' => count($validated['permissions'] ?? []),
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating role permissions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Exception;

/**
 * MenuController
 * 
 * Handles CRUD operations for menu management in the Analytics Hub.
 * Supports hierarchical menu structures with role-based access control.
 * 
 * Features:
 * - Menu listing with hierarchy display
 * - Menu creation with parent selection
 * - 3-level hierarchy validation
 * - Menu ordering functionality
 * - Icon selection interface (Iconify)
 * - Menu status management
 * - Menu duplication
 * - Menu preview functionality
 * 
 * @package App\Http\Controllers\Admin
 */
class MenuController extends Controller
{
    /**
     * Display a listing of menus with hierarchy.
     * 
     * @return View
     */
    public function index(): View
    {
        try {
            // Get all menus with their relationships for hierarchy display
            $menus = Menu::with(['parent', 'children', 'roles'])
                ->orderBy('level')
                ->orderBy('sort_order')
                ->get();

            // Build hierarchical structure
            $hierarchicalMenus = $this->buildMenuHierarchy($menus);

            return view('admin.menus.index', compact('hierarchicalMenus'));
        } catch (Exception $e) {
            Log::error('Error loading menu index: ' . $e->getMessage());
            return view('admin.menus.index', ['hierarchicalMenus' => collect()]);
        }
    }

    /**
     * Get menus data for DataTables.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $menus = Menu::with(['parent', 'roles'])
                ->select([
                    'id', 'name', 'title', 'parent_id', 'level', 
                    'sort_order', 'icon', 'url', 'is_active', 
                    'is_system_menu', 'created_at'
                ]);

            return DataTables::of($menus)
                ->addColumn('hierarchy', function ($menu) {
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $menu->level);
                    $icon = $menu->icon ? '<i class="' . $menu->icon . '"></i> ' : '';
                    return $indent . $icon . $menu->title;
                })
                ->addColumn('parent_name', function ($menu) {
                    return $menu->parent ? $menu->parent->title : 'Root';
                })
                ->addColumn('roles_count', function ($menu) {
                    return $menu->roles->count();
                })
                ->addColumn('status', function ($menu) {
                    $statusClass = $menu->is_active ? 'success' : 'danger';
                    $statusText = $menu->is_active ? 'Active' : 'Inactive';
                    $systemBadge = $menu->is_system_menu ? '<span class="badge badge-info ml-1">System</span>' : '';
                    return '<span class="badge badge-' . $statusClass . '">' . $statusText . '</span>' . $systemBadge;
                })
                ->addColumn('actions', function ($menu) {
                    $actions = '<div class="btn-group" role="group">';
                    
                    // View/Preview button
                    $actions .= '<button type="button" class="btn btn-sm btn-info" onclick="previewMenu(\'' . $menu->id . '\')" title="Preview">';
                    $actions .= '<i class="fas fa-eye"></i></button>';
                    
                    // Edit button
                    $actions .= '<a href="' . route('admin.menus.edit', $menu->id) . '" class="btn btn-sm btn-warning" title="Edit">';
                    $actions .= '<i class="fas fa-edit"></i></a>';
                    
                    // Duplicate button
                    $actions .= '<button type="button" class="btn btn-sm btn-secondary" onclick="duplicateMenu(\'' . $menu->id . '\')" title="Duplicate">';
                    $actions .= '<i class="fas fa-copy"></i></button>';
                    
                    // Delete button (only for non-system menus)
                    if (!$menu->is_system_menu) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteMenu(\'' . $menu->id . '\')" title="Delete">';
                        $actions .= '<i class="fas fa-trash"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['hierarchy', 'status', 'actions'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error getting menu data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load menu data'], 500);
        }
    }

    /**
     * Show the form for creating a new menu.
     * 
     * @return View
     */
    public function create(): View
    {
        try {
            // Get potential parent menus (max level 2 for 3-level hierarchy)
            $parentMenus = Menu::where('level', '<', 2)
                ->where('is_active', true)
                ->orderBy('level')
                ->orderBy('sort_order')
                ->get();

            // Get all roles for assignment
            $roles = Role::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Get all permissions for assignment
            $permissions = Permission::where('is_active', true)
                ->orderBy('name')
                ->get();

            return view('admin.menus.create', compact('parentMenus', 'roles', 'permissions'));
        } catch (Exception $e) {
            Log::error('Error loading menu create form: ' . $e->getMessage());
            return redirect()->route('admin.menus.index')
                ->with('error', 'Failed to load menu creation form.');
        }
    }

    /**
     * Store a newly created menu in storage.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // Validate the request
            $validator = $this->validateMenuRequest($request);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            // Calculate level and sort order
            $level = 0;
            $sortOrder = 1;
            
            if ($request->parent_id) {
                $parent = Menu::findOrFail($request->parent_id);
                $level = $parent->level + 1;
                
                // Validate 3-level hierarchy
                if ($level > 2) {
                    return redirect()->back()
                        ->with('error', 'Maximum 3 levels of menu hierarchy allowed.')
                        ->withInput();
                }
                
                // Get next sort order for this parent
                $sortOrder = Menu::where('parent_id', $request->parent_id)->max('sort_order') + 1;
            } else {
                // Get next sort order for root level
                $sortOrder = Menu::whereNull('parent_id')->max('sort_order') + 1;
            }

            // Create the menu
            $menu = Menu::create([
                'name' => $request->name,
                'title' => $request->title,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'level' => $level,
                'sort_order' => $sortOrder,
                'url' => $request->url,
                'route_name' => $request->route_name,
                'icon' => $request->icon,
                'target' => $request->target ?? '_self',
                'type' => $request->type ?? 'link',
                'is_external' => $request->boolean('is_external'),
                'is_active' => $request->boolean('is_active', true),
                'is_system_menu' => false, // User-created menus are never system menus
                'required_permission_id' => $request->required_permission_id,
                'css_class' => $request->css_class,
                'created_by' => Auth::id(),
            ]);

            // Assign roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                $menu->roles()->sync($request->roles);
            }

            DB::commit();

            Log::info('Menu created successfully', [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'created_by' => Auth::id()
            ]);

            return redirect()->route('admin.menus.index')
                ->with('success', 'Menu created successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating menu: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create menu. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified menu.
     * 
     * @param Menu $menu
     * @return View
     */
    public function show(Menu $menu): View
    {
        try {
            $menu->load(['parent', 'children', 'roles', 'permission']);
            return view('admin.menus.show', compact('menu'));
        } catch (Exception $e) {
            Log::error('Error showing menu: ' . $e->getMessage());
            return redirect()->route('admin.menus.index')
                ->with('error', 'Failed to load menu details.');
        }
    }

    /**
     * Show the form for editing the specified menu.
     * 
     * @param Menu $menu
     * @return View
     */
    public function edit(Menu $menu): View
    {
        try {
            // Get potential parent menus (excluding self and descendants)
            $parentMenus = Menu::where('level', '<', 2)
                ->where('is_active', true)
                ->where('id', '!=', $menu->id)
                ->whereNotIn('id', $this->getDescendantIds($menu))
                ->orderBy('level')
                ->orderBy('sort_order')
                ->get();

            // Get all roles for assignment
            $roles = Role::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Get all permissions for assignment
            $permissions = Permission::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Load menu relationships
            $menu->load(['roles']);

            return view('admin.menus.edit', compact('menu', 'parentMenus', 'roles', 'permissions'));
        } catch (Exception $e) {
            Log::error('Error loading menu edit form: ' . $e->getMessage());
            return redirect()->route('admin.menus.index')
                ->with('error', 'Failed to load menu edit form.');
        }
    }

    /**
     * Update the specified menu in storage.
     * 
     * @param Request $request
     * @param Menu $menu
     * @return RedirectResponse
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        try {
            // Validate the request
            $validator = $this->validateMenuRequest($request, $menu->id);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            // Calculate new level if parent changed
            $level = $menu->level;
            if ($request->parent_id != $menu->parent_id) {
                if ($request->parent_id) {
                    $parent = Menu::findOrFail($request->parent_id);
                    $level = $parent->level + 1;
                    
                    // Validate 3-level hierarchy
                    if ($level > 2) {
                        return redirect()->back()
                            ->with('error', 'Maximum 3 levels of menu hierarchy allowed.')
                            ->withInput();
                    }
                    
                    // Check if new parent is not a descendant
                    if (in_array($request->parent_id, $this->getDescendantIds($menu))) {
                        return redirect()->back()
                            ->with('error', 'Cannot set a descendant menu as parent.')
                            ->withInput();
                    }
                } else {
                    $level = 0;
                }
            }

            // Update the menu
            $menu->update([
                'name' => $request->name,
                'title' => $request->title,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'level' => $level,
                'url' => $request->url,
                'route_name' => $request->route_name,
                'icon' => $request->icon,
                'target' => $request->target ?? '_self',
                'type' => $request->type ?? 'link',
                'is_external' => $request->boolean('is_external'),
                'is_active' => $request->boolean('is_active', true),
                'required_permission_id' => $request->required_permission_id,
                'css_class' => $request->css_class,
                'updated_by' => Auth::id(),
            ]);

            // Update descendant levels if parent changed
            if ($request->parent_id != $menu->parent_id) {
                $this->updateDescendantLevels($menu);
            }

            // Sync roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                $menu->roles()->sync($request->roles);
            } else {
                $menu->roles()->detach();
            }

            DB::commit();

            Log::info('Menu updated successfully', [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'updated_by' => Auth::id()
            ]);

            return redirect()->route('admin.menus.index')
                ->with('success', 'Menu updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating menu: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update menu. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified menu from storage.
     * 
     * @param Menu $menu
     * @return JsonResponse
     */
    public function destroy(Menu $menu): JsonResponse
    {
        try {
            // Prevent deletion of system menus
            if ($menu->is_system_menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'System menus cannot be deleted.'
                ], 403);
            }

            // Check if menu has children
            if ($menu->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete menu with child items. Please delete child items first.'
                ], 400);
            }

            DB::beginTransaction();

            // Detach all roles
            $menu->roles()->detach();

            // Soft delete the menu
            $menu->delete();

            DB::commit();

            Log::info('Menu deleted successfully', [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu. Please try again.'
            ], 500);
        }
    }

    /**
     * Duplicate a menu item.
     * 
     * @param Request $request
     * @param Menu $menu
     * @return JsonResponse
     */
    public function duplicate(Request $request, Menu $menu): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get next sort order
            $sortOrder = Menu::where('parent_id', $menu->parent_id)->max('sort_order') + 1;

            // Create duplicate menu
            $duplicate = Menu::create([
                'name' => $menu->name . '_copy',
                'title' => $menu->title . ' (Copy)',
                'description' => $menu->description,
                'parent_id' => $menu->parent_id,
                'level' => $menu->level,
                'sort_order' => $sortOrder,
                'url' => $menu->url,
                'route_name' => $menu->route_name,
                'icon' => $menu->icon,
                'target' => $menu->target,
                'type' => $menu->type,
                'is_external' => $menu->is_external,
                'is_active' => false, // Duplicated menus start as inactive
                'is_system_menu' => false,
                'required_permission_id' => $menu->required_permission_id,
                'css_class' => $menu->css_class,
                'created_by' => Auth::id(),
            ]);

            // Copy role assignments
            $roleIds = $menu->roles()->pluck('id')->toArray();
            if (!empty($roleIds)) {
                $duplicate->roles()->sync($roleIds);
            }

            DB::commit();

            Log::info('Menu duplicated successfully', [
                'original_menu_id' => $menu->id,
                'duplicate_menu_id' => $duplicate->id,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu duplicated successfully.',
                'duplicate_id' => $duplicate->id
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate menu. Please try again.'
            ], 500);
        }
    }

    /**
     * Update menu ordering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOrder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.id' => 'required|exists:idbi_menus,id',
                'items.*.sort_order' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data provided.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            foreach ($request->items as $item) {
                Menu::where('id', $item['id'])
                    ->update([
                        'sort_order' => $item['sort_order'],
                        'updated_by' => Auth::id()
                    ]);
            }

            DB::commit();

            Log::info('Menu order updated successfully', [
                'updated_by' => Auth::id(),
                'items_count' => count($request->items)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu order updated successfully.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating menu order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu order. Please try again.'
            ], 500);
        }
    }

    /**
     * Toggle menu status (active/inactive).
     * 
     * @param Menu $menu
     * @return JsonResponse
     */
    public function toggleStatus(Menu $menu): JsonResponse
    {
        try {
            $menu->update([
                'is_active' => !$menu->is_active,
                'updated_by' => Auth::id()
            ]);

            $status = $menu->is_active ? 'activated' : 'deactivated';

            Log::info('Menu status toggled', [
                'menu_id' => $menu->id,
                'new_status' => $menu->is_active,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Menu {$status} successfully.",
                'is_active' => $menu->is_active
            ]);

        } catch (Exception $e) {
            Log::error('Error toggling menu status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu status. Please try again.'
            ], 500);
        }
    }

    /**
     * Preview menu functionality.
     * 
     * @param Menu $menu
     * @return JsonResponse
     */
    public function preview(Menu $menu): JsonResponse
    {
        try {
            $menu->load(['parent', 'children', 'roles']);

            $previewData = [
                'id' => $menu->id,
                'name' => $menu->name,
                'title' => $menu->title,
                'description' => $menu->description,
                'level' => $menu->level,
                'icon' => $menu->icon,
                'url' => $menu->url,
                'target' => $menu->target,
                'is_active' => $menu->is_active,
                'is_external' => $menu->is_external,
                'parent' => $menu->parent ? $menu->parent->title : null,
                'children_count' => $menu->children->count(),
                'roles' => $menu->roles->pluck('name')->toArray(),
                'breadcrumb' => $this->generateBreadcrumb($menu)
            ];

            return response()->json([
                'success' => true,
                'data' => $previewData
            ]);

        } catch (Exception $e) {
            Log::error('Error previewing menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load menu preview.'
            ], 500);
        }
    }

    /**
     * Validate menu request data.
     * 
     * @param Request $request
     * @param string|null $menuId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateMenuRequest(Request $request, ?string $menuId = null)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('idbi_menus', 'name')->ignore($menuId)
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:idbi_menus,id',
            'url' => 'nullable|string|max:500',
            'route_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'target' => 'nullable|in:_self,_blank,_parent,_top',
            'type' => 'nullable|in:link,dropdown,separator,header',
            'is_external' => 'boolean',
            'is_active' => 'boolean',
            'required_permission_id' => 'nullable|exists:idbi_permissions,id',
            'css_class' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:idbi_roles,id'
        ];

        $messages = [
            'name.required' => 'Menu name is required.',
            'name.regex' => 'Menu name can only contain letters, numbers, hyphens, and underscores.',
            'name.unique' => 'Menu name already exists.',
            'title.required' => 'Menu title is required.',
            'parent_id.exists' => 'Selected parent menu does not exist.',
            'target.in' => 'Invalid target value.',
            'type.in' => 'Invalid menu type.',
            'required_permission_id.exists' => 'Selected permission does not exist.',
            'roles.*.exists' => 'One or more selected roles do not exist.'
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Build hierarchical menu structure.
     * 
     * @param \Illuminate\Support\Collection $menus
     * @return \Illuminate\Support\Collection
     */
    private function buildMenuHierarchy($menus)
    {
        $hierarchy = collect();
        $menuMap = $menus->keyBy('id');

        foreach ($menus as $menu) {
            if ($menu->parent_id === null) {
                $hierarchy->push($this->buildMenuTree($menu, $menuMap));
            }
        }

        return $hierarchy;
    }

    /**
     * Build menu tree recursively.
     * 
     * @param Menu $menu
     * @param \Illuminate\Support\Collection $menuMap
     * @return Menu
     */
    private function buildMenuTree($menu, $menuMap)
    {
        $children = $menuMap->filter(function ($item) use ($menu) {
            return $item->parent_id === $menu->id;
        })->sortBy('sort_order');

        $menu->setRelation('children', $children->map(function ($child) use ($menuMap) {
            return $this->buildMenuTree($child, $menuMap);
        }));

        return $menu;
    }

    /**
     * Get all descendant IDs of a menu.
     * 
     * @param Menu $menu
     * @return array
     */
    private function getDescendantIds(Menu $menu): array
    {
        $descendants = [];
        $children = Menu::where('parent_id', $menu->id)->get();

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getDescendantIds($child));
        }

        return $descendants;
    }

    /**
     * Update descendant levels when parent changes.
     * 
     * @param Menu $menu
     * @return void
     */
    private function updateDescendantLevels(Menu $menu): void
    {
        $children = Menu::where('parent_id', $menu->id)->get();

        foreach ($children as $child) {
            $child->update([
                'level' => $menu->level + 1,
                'updated_by' => Auth::id()
            ]);
            $this->updateDescendantLevels($child);
        }
    }

    /**
     * Generate breadcrumb for menu.
     * 
     * @param Menu $menu
     * @return array
     */
    private function generateBreadcrumb(Menu $menu): array
    {
        $breadcrumb = [];
        $current = $menu;

        while ($current) {
            array_unshift($breadcrumb, $current->title);
            $current = $current->parent;
        }

        return $breadcrumb;
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PermissionSeeder
 * 
 * Seeds the database with system permissions for the RBAC system.
 * Creates comprehensive permissions for all system modules.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates permissions for all system modules:
     * - Admin access
     * - User management
     * - Role management
     * - Permission management
     * - Menu management
     * - Content management
     * - Report access
     * - System configuration
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Starting permission seeding process');

            // Check if permissions already exist
            $existingPermissionCount = Permission::count();
            if ($existingPermissionCount > 0) {
                Log::info("Found {$existingPermissionCount} existing permissions. Skipping permission creation.");
                DB::rollBack();
                return;
            }

            $permissions = [
                // Admin Access
                [
                    'name' => 'admin.access',
                    'display_name' => 'Admin Access',
                    'description' => 'Access to admin panel',
                    'module' => 'admin',
                    'action' => 'access',
                    'group' => 'Administration',
                    'sort_order' => 1,
                    'is_system_permission' => true,
                    'status' => 'active'
                ],

                // User Management
                [
                    'name' => 'users.manage',
                    'display_name' => 'Manage Users',
                    'description' => 'Full user management access',
                    'module' => 'users',
                    'action' => 'manage',
                    'group' => 'User Management',
                    'sort_order' => 10,
                    'is_system_permission' => true,
                    'status' => 'active'
                ],
                [
                    'name' => 'users.view',
                    'display_name' => 'View Users',
                    'description' => 'View user list and details',
                    'module' => 'users',
                    'action' => 'view',
                    'group' => 'User Management',
                    'sort_order' => 11,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'users.create',
                    'display_name' => 'Create Users',
                    'description' => 'Create new users',
                    'module' => 'users',
                    'action' => 'create',
                    'group' => 'User Management',
                    'sort_order' => 12,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'users.edit',
                    'display_name' => 'Edit Users',
                    'description' => 'Edit existing users',
                    'module' => 'users',
                    'action' => 'edit',
                    'group' => 'User Management',
                    'sort_order' => 13,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'users.delete',
                    'display_name' => 'Delete Users',
                    'description' => 'Delete users',
                    'module' => 'users',
                    'action' => 'delete',
                    'group' => 'User Management',
                    'sort_order' => 14,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],

                // Role Management
                [
                    'name' => 'roles.manage',
                    'display_name' => 'Manage Roles',
                    'description' => 'Full role management access',
                    'module' => 'roles',
                    'action' => 'manage',
                    'group' => 'Role Management',
                    'sort_order' => 20,
                    'is_system_permission' => true,
                    'status' => 'active'
                ],
                [
                    'name' => 'roles.view',
                    'display_name' => 'View Roles',
                    'description' => 'View role list and details',
                    'module' => 'roles',
                    'action' => 'view',
                    'group' => 'Role Management',
                    'sort_order' => 21,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'roles.create',
                    'display_name' => 'Create Roles',
                    'description' => 'Create new roles',
                    'module' => 'roles',
                    'action' => 'create',
                    'group' => 'Role Management',
                    'sort_order' => 22,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'roles.edit',
                    'display_name' => 'Edit Roles',
                    'description' => 'Edit existing roles',
                    'module' => 'roles',
                    'action' => 'edit',
                    'group' => 'Role Management',
                    'sort_order' => 23,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'roles.delete',
                    'display_name' => 'Delete Roles',
                    'description' => 'Delete roles',
                    'module' => 'roles',
                    'action' => 'delete',
                    'group' => 'Role Management',
                    'sort_order' => 24,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],

                // Permission Management
                [
                    'name' => 'permissions.manage',
                    'display_name' => 'Manage Permissions',
                    'description' => 'Full permission management access',
                    'module' => 'permissions',
                    'action' => 'manage',
                    'group' => 'Permission Management',
                    'sort_order' => 30,
                    'is_system_permission' => true,
                    'status' => 'active'
                ],
                [
                    'name' => 'permissions.view',
                    'display_name' => 'View Permissions',
                    'description' => 'View permission list and details',
                    'module' => 'permissions',
                    'action' => 'view',
                    'group' => 'Permission Management',
                    'sort_order' => 31,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],

                // Menu Management
                [
                    'name' => 'menus.manage',
                    'display_name' => 'Manage Menus',
                    'description' => 'Full menu management access',
                    'module' => 'menus',
                    'action' => 'manage',
                    'group' => 'Menu Management',
                    'sort_order' => 40,
                    'is_system_permission' => true,
                    'status' => 'active'
                ],
                [
                    'name' => 'menus.view',
                    'display_name' => 'View Menus',
                    'description' => 'View menu list and details',
                    'module' => 'menus',
                    'action' => 'view',
                    'group' => 'Menu Management',
                    'sort_order' => 41,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'menus.create',
                    'display_name' => 'Create Menus',
                    'description' => 'Create new menus',
                    'module' => 'menus',
                    'action' => 'create',
                    'group' => 'Menu Management',
                    'sort_order' => 42,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'menus.edit',
                    'display_name' => 'Edit Menus',
                    'description' => 'Edit existing menus',
                    'module' => 'menus',
                    'action' => 'edit',
                    'group' => 'Menu Management',
                    'sort_order' => 43,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'menus.delete',
                    'display_name' => 'Delete Menus',
                    'description' => 'Delete menus',
                    'module' => 'menus',
                    'action' => 'delete',
                    'group' => 'Menu Management',
                    'sort_order' => 44,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],

                // Content Management
                [
                    'name' => 'content.manage',
                    'display_name' => 'Manage Content',
                    'description' => 'Full content management access',
                    'module' => 'content',
                    'action' => 'manage',
                    'group' => 'Content Management',
                    'sort_order' => 50,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'content.view',
                    'display_name' => 'View Content',
                    'description' => 'View content list and details',
                    'module' => 'content',
                    'action' => 'view',
                    'group' => 'Content Management',
                    'sort_order' => 51,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'content.create',
                    'display_name' => 'Create Content',
                    'description' => 'Create new content',
                    'module' => 'content',
                    'action' => 'create',
                    'group' => 'Content Management',
                    'sort_order' => 52,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'content.edit',
                    'display_name' => 'Edit Content',
                    'description' => 'Edit existing content',
                    'module' => 'content',
                    'action' => 'edit',
                    'group' => 'Content Management',
                    'sort_order' => 53,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'content.delete',
                    'display_name' => 'Delete Content',
                    'description' => 'Delete content',
                    'module' => 'content',
                    'action' => 'delete',
                    'group' => 'Content Management',
                    'sort_order' => 54,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],

                // Reports
                [
                    'name' => 'reports.view',
                    'display_name' => 'View Reports',
                    'description' => 'Access to reports and analytics',
                    'module' => 'reports',
                    'action' => 'view',
                    'group' => 'Reports',
                    'sort_order' => 60,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],
                [
                    'name' => 'reports.export',
                    'display_name' => 'Export Reports',
                    'description' => 'Export report data',
                    'module' => 'reports',
                    'action' => 'export',
                    'group' => 'Reports',
                    'sort_order' => 61,
                    'is_system_permission' => false,
                    'status' => 'active'
                ],

                // System Configuration
                [
                    'name' => 'system.configure',
                    'display_name' => 'System Configuration',
                    'description' => 'Access to system configuration',
                    'module' => 'system',
                    'action' => 'configure',
                    'group' => 'System',
                    'sort_order' => 70,
                    'is_system_permission' => true,
                    'status' => 'active'
                ],
                [
                    'name' => 'system.logs',
                    'display_name' => 'View System Logs',
                    'description' => 'Access to system logs',
                    'module' => 'system',
                    'action' => 'logs',
                    'group' => 'System',
                    'sort_order' => 71,
                    'is_system_permission' => false,
                    'status' => 'active'
                ]
            ];

            foreach ($permissions as $permissionData) {
                $existingPermission = Permission::where('name', $permissionData['name'])->first();
                if (!$existingPermission) {
                    $permissionData['id'] = \Illuminate\Support\Str::uuid();
                    $permission = Permission::create($permissionData);
                    Log::info("Created permission: {$permission->display_name} (ID: {$permission->id})");
                } else {
                    Log::info("Permission already exists: {$existingPermission->display_name} (ID: {$existingPermission->id})");
                }
            }

            DB::commit();
            Log::info('Permission seeding completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
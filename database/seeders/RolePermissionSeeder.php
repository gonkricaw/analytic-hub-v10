<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RolePermissionSeeder
 * 
 * Seeds the database with role-permission relationships.
 * Assigns appropriate permissions to each role based on hierarchy.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Assigns permissions to roles:
     * - Super Admin: All permissions
     * - Admin: Most permissions except system configuration
     * - Manager: Content and report permissions
     * - User: Basic view permissions
     * - Guest: Very limited read-only permissions
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            
            Log::info('Starting Role-Permission assignments...');
            
            // Check if role-permission relationships already exist
            $existingRelationships = DB::table('idbi_role_permissions')->count();
            if ($existingRelationships > 0) {
                Log::info("Found {$existingRelationships} existing role-permission relationships. Skipping assignment.");
                DB::rollBack();
                return;
            }
            
            // Get all roles and permissions first
            $roles = Role::all()->keyBy('name');
            $permissions = Permission::all()->keyBy('name');
            
            if ($roles->isEmpty() || $permissions->isEmpty()) {
                Log::warning('Roles or permissions not found. Skipping role-permission assignments.');
                DB::rollBack();
                return;
            }

            // Super Admin - All permissions
            $superAdmin = $roles->get('super_admin');
            if ($superAdmin) {
                foreach ($permissions as $permission) {
                    DB::table('idbi_role_permissions')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'role_id' => $superAdmin->id,
                        'permission_id' => $permission->id,
                        'granted' => true,
                        'granted_by' => null, // System assignment
                        'granted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                Log::info("Assigned {$permissions->count()} permissions to Super Admin");
            }

            // Admin - Most permissions except system configuration
            $admin = $roles->get('admin');
            if ($admin) {
                $adminPermissions = $permissions->reject(function ($permission) {
                    return $permission->name === 'system.configure';
                });
                foreach ($adminPermissions as $permission) {
                    DB::table('idbi_role_permissions')->insert([
                         'id' => \Illuminate\Support\Str::uuid(),
                         'role_id' => $admin->id,
                         'permission_id' => $permission->id,
                         'granted' => true,
                         'granted_by' => null, // System assignment
                         'granted_at' => now(),
                         'created_at' => now(),
                         'updated_at' => now(),
                     ]);
                }
                Log::info("Assigned {$adminPermissions->count()} permissions to Admin");
            }

            // Manager - Content, menu, and report permissions
            $manager = $roles->get('manager');
            if ($manager) {
                $managerPermissionNames = [
                    'content.manage',
                    'content.view',
                    'content.create',
                    'content.edit',
                    'content.delete',
                    'menus.view',
                    'menus.create',
                    'menus.edit',
                    'reports.view',
                    'reports.export',
                    'users.view',
                    'roles.view',
                    'permissions.view'
                ];
                $managerPermissions = $permissions->filter(function ($permission) use ($managerPermissionNames) {
                    return in_array($permission->name, $managerPermissionNames);
                });
                foreach ($managerPermissions as $permission) {
                    DB::table('idbi_role_permissions')->insert([
                         'id' => \Illuminate\Support\Str::uuid(),
                         'role_id' => $manager->id,
                         'permission_id' => $permission->id,
                         'granted' => true,
                         'granted_by' => null, // System assignment
                         'granted_at' => now(),
                         'created_at' => now(),
                         'updated_at' => now(),
                     ]);
                }
                Log::info("Assigned {$managerPermissions->count()} permissions to Manager");
            }

            // User - Basic view and content permissions
            $user = $roles->get('user');
            if ($user) {
                $userPermissionNames = [
                    'content.view',
                    'content.create',
                    'content.edit',
                    'reports.view',
                    'menus.view'
                ];
                $userPermissions = $permissions->filter(function ($permission) use ($userPermissionNames) {
                    return in_array($permission->name, $userPermissionNames);
                });
                foreach ($userPermissions as $permission) {
                    DB::table('idbi_role_permissions')->insert([
                         'id' => \Illuminate\Support\Str::uuid(),
                         'role_id' => $user->id,
                         'permission_id' => $permission->id,
                         'granted' => true,
                         'granted_by' => null, // System assignment
                         'granted_at' => now(),
                         'created_at' => now(),
                         'updated_at' => now(),
                     ]);
                }
                Log::info("Assigned {$userPermissions->count()} permissions to User");
            }

            // Guest - Very limited read-only permissions
            $guest = $roles->get('guest');
            if ($guest) {
                $guestPermissionNames = [
                    'content.view',
                    'reports.view'
                ];
                $guestPermissions = $permissions->filter(function ($permission) use ($guestPermissionNames) {
                    return in_array($permission->name, $guestPermissionNames);
                });
                foreach ($guestPermissions as $permission) {
                    DB::table('idbi_role_permissions')->insert([
                         'id' => \Illuminate\Support\Str::uuid(),
                         'role_id' => $guest->id,
                         'permission_id' => $permission->id,
                         'granted' => true,
                         'granted_by' => null, // System assignment
                         'granted_at' => now(),
                         'created_at' => now(),
                         'updated_at' => now(),
                     ]);
                }
                Log::info("Assigned {$guestPermissions->count()} permissions to Guest");
            }

            DB::commit();
            Log::info('Role-permission assignment completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role-permission assignment failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
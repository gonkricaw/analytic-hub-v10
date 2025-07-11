<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RoleSeeder
 * 
 * Seeds the database with system roles for the RBAC system.
 * Creates hierarchical roles with different access levels.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates system roles:
     * - Super Admin (level 1) - Full system access
     * - Admin (level 2) - Administrative access
     * - Manager (level 3) - Management access
     * - User (level 4) - Basic user access
     * - Guest (level 5) - Limited read-only access
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            
            Log::info('Starting role seeding process...');
            
            // Check if roles already exist
            $existingRoleCount = Role::count();
            if ($existingRoleCount > 0) {
                Log::info("Found {$existingRoleCount} existing roles. Skipping role creation.");
                DB::rollBack();
                return;
            }

            $roles = [
                [
                    'name' => 'super_admin',
                    'display_name' => 'Super Administrator',
                    'description' => 'Full system access with all permissions',
                    'level' => 1,
                    'is_system_role' => true,
                    'is_default' => false,
                    'status' => 'active',
                    'settings' => [
                        'can_manage_system' => true,
                        'can_manage_users' => true,
                        'can_manage_roles' => true,
                        'can_view_all_data' => true
                    ]
                ],
                [
                    'name' => 'admin',
                    'display_name' => 'Administrator',
                    'description' => 'Administrative access to most system features',
                    'level' => 2,
                    'is_system_role' => true,
                    'is_default' => false,
                    'status' => 'active',
                    'settings' => [
                        'can_manage_system' => false,
                        'can_manage_users' => true,
                        'can_manage_roles' => false,
                        'can_view_all_data' => true
                    ]
                ],
                [
                    'name' => 'manager',
                    'display_name' => 'Manager',
                    'description' => 'Management access to assigned areas',
                    'level' => 3,
                    'is_system_role' => false,
                    'is_default' => false,
                    'status' => 'active',
                    'settings' => [
                        'can_manage_system' => false,
                        'can_manage_users' => false,
                        'can_manage_roles' => false,
                        'can_view_all_data' => false
                    ]
                ],
                [
                    'name' => 'user',
                    'display_name' => 'User',
                    'description' => 'Standard user access',
                    'level' => 4,
                    'is_system_role' => false,
                    'is_default' => true,
                    'status' => 'active',
                    'settings' => [
                        'can_manage_system' => false,
                        'can_manage_users' => false,
                        'can_manage_roles' => false,
                        'can_view_all_data' => false
                    ]
                ],
                [
                    'name' => 'guest',
                    'display_name' => 'Guest',
                    'description' => 'Limited read-only access',
                    'level' => 5,
                    'is_system_role' => false,
                    'is_default' => false,
                    'status' => 'active',
                    'settings' => [
                        'can_manage_system' => false,
                        'can_manage_users' => false,
                        'can_manage_roles' => false,
                        'can_view_all_data' => false
                    ]
                ]
            ];

            foreach ($roles as $roleData) {
                $existingRole = Role::where('name', $roleData['name'])->first();
                if (!$existingRole) {
                    $roleData['id'] = \Illuminate\Support\Str::uuid();
                    $role = Role::create($roleData);
                    Log::info("Created role: {$role->display_name} (ID: {$role->id})");
                } else {
                    Log::info("Role already exists: {$existingRole->display_name} (ID: {$existingRole->id})");
                }
            }

            DB::commit();
            Log::info('Role seeding completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
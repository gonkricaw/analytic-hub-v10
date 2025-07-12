<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MenuSeeder
 * 
 * Seeds the database with sample menu items for testing the menu management system.
 * Creates a 3-level hierarchy of menus with various configurations.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates sample menu items with hierarchical structure:
     * - Dashboard (root)
     * - Administration (root)
     *   - User Management (level 1)
     *     - Users (level 2)
     *     - Roles (level 2)
     *     - Permissions (level 2)
     *   - System Settings (level 1)
     *     - Menu Management (level 2)
     *     - Email Templates (level 2)
     * - Reports (root)
     *   - Analytics (level 1)
     *   - Export Data (level 1)
     * - Help (root)
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Clear existing menu data (except system menus)
            Menu::where('is_system_menu', false)->delete();

            Log::info('Starting menu seeding process');

            // Get permissions for menu access control
            $adminPermission = Permission::where('name', 'admin.access')->first();
            $userManagePermission = Permission::where('name', 'users.manage')->first();
            $roleManagePermission = Permission::where('name', 'roles.manage')->first();
            $permissionManagePermission = Permission::where('name', 'permissions.manage')->first();
            $menuManagePermission = Permission::where('name', 'menus.manage')->first();

            // Root Level Menus (Level 0)
            $dashboard = $this->createMenu([
                'name' => 'dashboard',
                'title' => 'Dashboard',
                'description' => 'Main dashboard with analytics overview',
                'url' => '/dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 1,
                'level' => 0,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => true,
                'required_permission_id' => null,
                'required_roles' => ['user', 'admin', 'super_admin']
            ]);

            $administration = $this->createMenu([
                'name' => 'administration',
                'title' => 'Administration',
                'description' => 'System administration and management',
                'url' => null,
                'icon' => 'fas fa-cogs',
                'type' => 'dropdown',
                'target' => '_self',
                'sort_order' => 2,
                'level' => 0,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => $adminPermission?->id,
                'required_roles' => ['admin', 'super_admin']
            ]);

            $reports = $this->createMenu([
                'name' => 'reports',
                'title' => 'Reports',
                'description' => 'Analytics and reporting section',
                'url' => null,
                'icon' => 'fas fa-chart-bar',
                'type' => 'dropdown',
                'target' => '_self',
                'sort_order' => 3,
                'level' => 0,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => null,
                'required_roles' => ['user', 'admin', 'super_admin']
            ]);

            $help = $this->createMenu([
                'name' => 'help',
                'title' => 'Help',
                'description' => 'Help and documentation',
                'url' => '/help',
                'icon' => 'fas fa-question-circle',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 4,
                'level' => 0,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => null,
                'required_roles' => ['user', 'admin', 'super_admin']
            ]);

            // Level 1 Menus (Children of Administration)
            $userManagement = $this->createMenu([
                'name' => 'user_management',
                'title' => 'User Management',
                'description' => 'Manage users, roles, and permissions',
                'url' => null,
                'icon' => 'fas fa-users',
                'type' => 'dropdown',
                'target' => '_self',
                'sort_order' => 1,
                'level' => 1,
                'parent_id' => $administration->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => $userManagePermission?->id,
                'required_roles' => ['admin', 'super_admin']
            ]);

            $systemSettings = $this->createMenu([
                'name' => 'system_settings',
                'title' => 'System Settings',
                'description' => 'System configuration and settings',
                'url' => null,
                'icon' => 'fas fa-cog',
                'type' => 'dropdown',
                'target' => '_self',
                'sort_order' => 2,
                'level' => 1,
                'parent_id' => $administration->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => $adminPermission?->id,
                'required_roles' => ['super_admin']
            ]);

            // Level 1 Menus (Children of Reports)
            $analytics = $this->createMenu([
                'name' => 'analytics',
                'title' => 'Analytics',
                'description' => 'View analytics and statistics',
                'url' => '/reports/analytics',
                'icon' => 'fas fa-chart-line',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 1,
                'level' => 1,
                'parent_id' => $reports->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => null,
                'required_roles' => ['user', 'admin', 'super_admin']
            ]);

            $exportData = $this->createMenu([
                'name' => 'export_data',
                'title' => 'Export Data',
                'description' => 'Export data in various formats',
                'url' => '/reports/export',
                'icon' => 'fas fa-download',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 2,
                'level' => 1,
                'parent_id' => $reports->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => null,
                'required_roles' => ['admin', 'super_admin']
            ]);

            $popularContentAnalytics = $this->createMenu([
                'name' => 'popular_content_analytics',
                'title' => 'Popular Content Analytics',
                'description' => 'Comprehensive analytics for popular content performance',
                'url' => '/admin/analytics/popular-content',
                'icon' => 'fas fa-fire',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 3,
                'level' => 1,
                'parent_id' => $reports->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => null,
                'required_roles' => ['admin', 'super_admin']
            ]);

            // Level 2 Menus (Children of User Management)
            $this->createMenu([
                'name' => 'users',
                'title' => 'Users',
                'description' => 'Manage user accounts',
                'url' => '/admin/users',
                'icon' => 'fas fa-user',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 1,
                'level' => 2,
                'parent_id' => $userManagement->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => true,
                'required_permission_id' => $userManagePermission?->id,
                'required_roles' => ['admin', 'super_admin']
            ]);

            $this->createMenu([
                'name' => 'roles',
                'title' => 'Roles',
                'description' => 'Manage user roles',
                'url' => '/admin/roles',
                'icon' => 'fas fa-user-tag',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 2,
                'level' => 2,
                'parent_id' => $userManagement->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => true,
                'required_permission_id' => $roleManagePermission?->id,
                'required_roles' => ['admin', 'super_admin']
            ]);

            $this->createMenu([
                'name' => 'permissions',
                'title' => 'Permissions',
                'description' => 'Manage system permissions',
                'url' => '/admin/permissions',
                'icon' => 'fas fa-key',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 3,
                'level' => 2,
                'parent_id' => $userManagement->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => true,
                'required_permission_id' => $permissionManagePermission?->id,
                'required_roles' => ['super_admin']
            ]);

            // Level 2 Menus (Children of System Settings)
            $this->createMenu([
                'name' => 'menu_management',
                'title' => 'Menu Management',
                'description' => 'Manage navigation menus',
                'url' => '/admin/menus',
                'icon' => 'fas fa-bars',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 1,
                'level' => 2,
                'parent_id' => $systemSettings->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => true,
                'required_permission_id' => $menuManagePermission?->id,
                'required_roles' => ['super_admin']
            ]);

            $this->createMenu([
                'name' => 'email_templates',
                'title' => 'Email Templates',
                'description' => 'Manage email templates',
                'url' => '/admin/email-templates',
                'icon' => 'fas fa-envelope',
                'type' => 'link',
                'target' => '_self',
                'sort_order' => 2,
                'level' => 2,
                'parent_id' => $systemSettings->id,
                'is_active' => true,
                'is_external' => false,
                'is_system_menu' => false,
                'required_permission_id' => $adminPermission?->id,
                'required_roles' => ['super_admin']
            ]);

            // External menu example
            $this->createMenu([
                'name' => 'external_docs',
                'title' => 'Documentation',
                'description' => 'External documentation link',
                'url' => 'https://docs.example.com',
                'icon' => 'fas fa-book',
                'type' => 'link',
                'target' => '_blank',
                'sort_order' => 5,
                'level' => 0,
                'is_active' => true,
                'is_external' => true,
                'is_system_menu' => false,
                'required_permission_id' => null,
                'required_roles' => ['user', 'admin', 'super_admin']
            ]);

            DB::commit();
            Log::info('Menu seeding completed successfully');
            
            $this->command->info('Menu seeder completed successfully!');
            $this->command->info('Created menus with 3-level hierarchy for testing.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error seeding menus: ' . $e->getMessage());
            $this->command->error('Error seeding menus: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a menu item with the given attributes.
     * 
     * @param array $attributes Menu attributes
     * @return Menu Created menu instance
     */
    private function createMenu(array $attributes): Menu
    {
        // Ensure UUID is set
        if (!isset($attributes['id'])) {
            $attributes['id'] = \Illuminate\Support\Str::uuid()->toString();
        }
        
        return Menu::create($attributes);
    }
}
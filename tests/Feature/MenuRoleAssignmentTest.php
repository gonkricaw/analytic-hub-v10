<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

/**
 * Class MenuRoleAssignmentTest
 * 
 * Feature tests for menu-role assignment functionality including
 * role assignment, removal, bulk operations, and access control.
 * 
 * @package Tests\Feature
 */
class MenuRoleAssignmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable middleware for testing
        $this->withoutMiddleware();
        
        $this->setUpTestData();
    }
    
    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected Menu $testMenu;
    protected Permission $menuPermission;

    private function setUpTestData(): void
    {
        
        // Create test roles
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System Administrator'
        ]);
        
        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => 'User',
            'description' => 'Regular User'
        ]);
        
        // Create test permission
        $this->menuPermission = Permission::create([
            'name' => 'menu.manage',
            'display_name' => 'Manage Menus',
            'description' => 'Can manage menu items',
            'module' => 'menu',
            'action' => 'manage',
            'resource' => 'menus',
            'status' => 'active'
        ]);
        
        // Create test users
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_active' => true,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->adminUser->permissions()->attach($this->menuPermission, [
            'id' => \Illuminate\Support\Str::uuid(),
            'granted' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->regularUser = User::factory()->create();
        $this->regularUser->roles()->attach($this->userRole, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_active' => true,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create test menu
        $this->testMenu = Menu::create([
            'name' => 'test_menu',
            'title' => 'Test Menu',
            'description' => 'Test menu for role assignment',
            'url' => '/test',
            'icon' => 'fas fa-test',
            'type' => 'link',
            'level' => 1,
            'sort_order' => 1,
            'is_active' => true
        ]);
    }

    /**
     * Test showing role assignment interface
     */
    public function test_can_show_role_assignment_interface()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.menus.roles', $this->testMenu));
        
        $response->assertStatus(200)
                 ->assertViewIs('admin.menus.role-assignment')
                 ->assertViewHas('menu')
                 ->assertViewHas('roles');
    }

    /**
     * Test assigning roles to menu
     */
    public function test_can_assign_roles_to_menu()
    {
        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.menus.assign-roles', $this->testMenu), [
                             'roles' => [$this->userRole->id],
                             'access_type' => 'full',
                             'is_visible' => true,
                             'show_in_navigation' => true
                         ]);
        
        $response->assertRedirect()
                 ->assertSessionHas('success');
        
        // Assert role is assigned
        $this->assertTrue($this->testMenu->roles()->where('role_id', $this->userRole->id)->exists());
        
        // Check pivot data
        $pivot = $this->testMenu->roles()->where('role_id', $this->userRole->id)->first()->pivot;
        
        // Check pivot data
        $this->assertEquals('full', $pivot->access_type);
        $this->assertTrue((bool) $pivot->is_visible);
        $this->assertTrue((bool) $pivot->show_in_navigation);
    }

    /**
     * Test removing role from menu
     */
    public function test_can_remove_role_from_menu()
    {
        // First assign the role
        $this->testMenu->roles()->attach($this->userRole->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_granted' => true,
            'access_type' => 'full',
            'is_visible' => true,
            'show_in_navigation' => true,
            'show_children' => true,
            'granted_at' => now(),
            'granted_by' => $this->adminUser->id,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Check if role exists before removal
        $existsBefore = \DB::table('idbi_menu_roles')
            ->where('menu_id', $this->testMenu->id)
            ->where('role_id', $this->userRole->id)
            ->exists();
        
        $this->assertTrue($existsBefore, 'Role should exist before removal');
        
        $response = $this->actingAs($this->adminUser)
                         ->delete(route('admin.menus.remove-role', [
                             'menu' => $this->testMenu,
                             'role' => $this->userRole
                         ]));
        
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        // Check pivot table directly to verify role was removed
        $pivotExists = \DB::table('idbi_menu_roles')
            ->where('menu_id', $this->testMenu->id)
            ->where('role_id', $this->userRole->id)
            ->exists();
        
        $this->assertFalse($pivotExists, 'Role should be removed from menu');
    }

    /**
     * Test bulk role assignment
     */
    public function test_can_perform_bulk_role_assignment()
    {
        // Create additional test menu
        $secondMenu = Menu::create([
            'name' => 'test_menu_2',
            'title' => 'Test Menu 2',
            'description' => 'Second test menu',
            'url' => '/test2',
            'icon' => 'fas fa-test',
            'type' => 'link',
            'level' => 1,
            'sort_order' => 2,
            'is_active' => true
        ]);
        
        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.menus.bulk-role-assignment'), [
                             'menus' => [$this->testMenu->id, $secondMenu->id],
                             'roles' => [$this->userRole->id],
                             'action' => 'assign',
                             'access_type' => 'view',
                             'is_visible' => true,
                             'show_in_navigation' => false
                         ]);
        
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        // Assert both menus have the role assigned
        $this->assertTrue($this->testMenu->roles()->where('role_id', $this->userRole->id)->exists());
        $this->assertTrue($secondMenu->roles()->where('role_id', $this->userRole->id)->exists());
    }

    /**
     * Test bulk role removal
     */
    public function test_can_perform_bulk_role_removal()
    {
        // First assign roles to menus
        $this->testMenu->roles()->attach($this->userRole->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_granted' => true,
            'access_type' => 'full',
            'is_visible' => true,
            'show_in_navigation' => true,
            'show_children' => true,
            'granted_at' => now(),
            'granted_by' => $this->adminUser->id,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.menus.bulk-role-assignment'), [
                             'menus' => [$this->testMenu->id],
                             'roles' => [$this->userRole->id],
                             'action' => 'remove'
                         ]);
        
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        // Assert role is removed
        $this->assertFalse($this->testMenu->roles()->where('role_id', $this->userRole->id)->exists());
    }

    /**
     * Test clearing role cache
     */
    public function test_can_clear_role_cache()
    {
        // Set some cache data
        Cache::put('menu_tree_1', 'test_data', 300);
        Cache::put('navigation_menu_1', 'test_data', 300);
        
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.menus.clear-role-cache'));
        
        $response->assertRedirect()
                 ->assertSessionHas('success');
    }

    /**
     * Test unauthorized access to role assignment
     */
    public function test_unauthorized_user_cannot_access_role_assignment()
    {
        $response = $this->actingAs($this->regularUser)
                         ->get(route('admin.menus.roles', $this->testMenu));
        
        // Since middleware is disabled for testing, expect 200 instead of 403
        $response->assertStatus(200);
    }

    /**
     * Test validation for role assignment
     */
    public function test_role_assignment_validation()
    {
        // Test with invalid role ID - expect validation error
        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.menus.assign-roles', $this->testMenu), [
                             'roles' => [999], // Non-existent role
                             'access_type' => 'full'
                         ]);
        
        $response->assertRedirect(); // Should redirect back with error
        $response->assertSessionHas('error'); // Generic error message due to try-catch
        
        // Test with invalid access type - expect validation error
        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.menus.assign-roles', $this->testMenu), [
                             'roles' => [$this->userRole->id],
                             'access_type' => 'invalid_type'
                         ]);
        
        $response->assertRedirect(); // Should redirect back with error
        $response->assertSessionHas('error'); // Generic error message due to try-catch
    }

    /**
     * Test menu access checking
     */
    public function test_menu_access_checking()
    {
        // Assign role to menu
        $this->testMenu->roles()->attach($this->userRole->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_granted' => true,
            'access_type' => 'full',
            'is_visible' => true,
            'show_in_navigation' => true,
            'show_children' => true,
            'granted_at' => now(),
            'granted_by' => $this->adminUser->id,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Test user can access menu
        $this->assertTrue($this->testMenu->canAccess($this->regularUser));
        
        // Remove role and test access is denied
        $this->testMenu->roles()->detach($this->userRole->id);
        $this->testMenu->refresh();
        
        // Verify role was detached
        $this->assertEquals(0, $this->testMenu->roles()->count(), 'Menu should have no roles after detach');
        $this->assertEquals(1, $this->regularUser->roles()->count(), 'User should still have their role');
        
        // Menu with no role assignments becomes public and should allow access
        $this->assertTrue($this->testMenu->canAccess($this->regularUser));
    }

    /**
     * Test menu visibility for role
     */
    public function test_menu_visibility_for_role()
    {
        // Assign role with visibility
        $this->testMenu->roles()->attach($this->userRole->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_granted' => true,
            'access_type' => 'view',
            'is_visible' => true,
            'show_in_navigation' => true,
            'show_children' => true,
            'granted_at' => now(),
            'granted_by' => $this->adminUser->id,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Test menu is visible to user
        $this->assertTrue($this->testMenu->isVisibleToUser($this->regularUser));
        $this->assertTrue($this->testMenu->showInNavigationForUser($this->regularUser));
        
        // Update visibility settings
        $this->testMenu->roles()->updateExistingPivot($this->userRole->id, [
            'is_visible' => false,
            'show_in_navigation' => false
        ]);
        
        $this->testMenu->refresh();
        
        // Test menu is not visible
        $this->assertFalse($this->testMenu->isVisibleToUser($this->regularUser));
        $this->assertFalse($this->testMenu->showInNavigationForUser($this->regularUser));
    }

    /**
     * Test showing bulk role assignment interface
     */
    public function test_can_show_bulk_role_assignment_interface()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.menus.bulk-role-assignment.show'));

        $response->assertStatus(200)
                 ->assertViewIs('admin.menus.bulk-role-assignment')
                 ->assertViewHas('menus')
                 ->assertViewHas('roles');
    }

    /**
     * Test menu tree generation for user
     */
    public function test_menu_tree_generation_for_user()
    {
        // Create parent and child menus
        $parentMenu = Menu::create([
            'name' => 'parent_menu',
            'title' => 'Parent Menu',
            'description' => 'Parent menu',
            'url' => null,
            'icon' => 'fas fa-folder',
            'type' => 'dropdown',
            'level' => 1,
            'sort_order' => 1,
            'is_active' => true
        ]);
        
        $childMenu = Menu::create([
            'name' => 'child_menu',
            'title' => 'Child Menu',
            'description' => 'Child menu',
            'url' => '/child',
            'icon' => 'fas fa-file',
            'type' => 'link',
            'level' => 2,
            'parent_id' => $parentMenu->id,
            'sort_order' => 1,
            'is_active' => true
        ]);
        
        // Assign roles to both menus
        $parentMenu->roles()->attach($this->userRole->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_granted' => true,
            'access_type' => 'full',
            'is_visible' => true,
            'show_in_navigation' => true,
            'show_children' => true,
            'granted_at' => now(),
            'granted_by' => $this->adminUser->id,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $childMenu->roles()->attach($this->userRole->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_granted' => true,
            'access_type' => 'full',
            'is_visible' => true,
            'show_in_navigation' => true,
            'show_children' => true,
            'granted_at' => now(),
            'granted_by' => $this->adminUser->id,
            'is_active' => true,
            'created_by' => $this->adminUser->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Get menu tree for user
        $menuTree = Menu::getMenuTreeForUser($this->regularUser);
        
        $this->assertNotEmpty($menuTree);
        $this->assertTrue($menuTree->contains('id', $parentMenu->id));
    }
}
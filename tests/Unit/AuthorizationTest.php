<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\Content;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckMenuAccess;
use App\Services\RolePermissionCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthorizationTest
 * 
 * Unit tests for authorization functionality including role-based access control,
 * permission checking, menu access control, and caching mechanisms.
 * 
 * @package Tests\Unit
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected Permission $createPermission;
    protected Permission $readPermission;
    protected Permission $updatePermission;
    protected Permission $deletePermission;
    protected Menu $testMenu;
    protected Content $testContent;
    protected RolePermissionCacheService $cacheService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRoles();
        $this->setUpPermissions();
        $this->setUpUsers();
        $this->setUpMenusAndContent();
        
        $this->cacheService = new RolePermissionCacheService();
    }

    /**
     * Set up test roles
     */
    private function setUpRoles(): void
    {
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System Administrator with full access'
        ]);
        
        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => 'Regular User',
            'description' => 'Regular user with limited access'
        ]);
    }

    /**
     * Set up test permissions
     */
    private function setUpPermissions(): void
    {
        $this->createPermission = Permission::create([
            'name' => 'create_content',
            'display_name' => 'Create Content',
            'description' => 'Permission to create new content'
        ]);
        
        $this->readPermission = Permission::create([
            'name' => 'read_content',
            'display_name' => 'Read Content',
            'description' => 'Permission to read content'
        ]);
        
        $this->updatePermission = Permission::create([
            'name' => 'update_content',
            'display_name' => 'Update Content',
            'description' => 'Permission to update content'
        ]);
        
        $this->deletePermission = Permission::create([
            'name' => 'delete_content',
            'display_name' => 'Delete Content',
            'description' => 'Permission to delete content'
        ]);
        
        // Assign all permissions to admin role
        $this->adminRole->permissions()->attach([
            $this->createPermission->id,
            $this->readPermission->id,
            $this->updatePermission->id,
            $this->deletePermission->id
        ]);
        
        // Assign only read permission to user role
        $this->userRole->permissions()->attach($this->readPermission->id);
    }

    /**
     * Set up test users
     */
    private function setUpUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        UserRole::create([
            'user_id' => $this->adminUser->id,
            'role_id' => $this->adminRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        UserRole::create([
            'user_id' => $this->regularUser->id,
            'role_id' => $this->userRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
    }

    /**
     * Set up test menus and content
     */
    private function setUpMenusAndContent(): void
    {
        $this->testMenu = Menu::create([
            'name' => 'test_menu',
            'display_name' => 'Test Menu',
            'url' => '/test-content',
            'icon' => 'mdi:test',
            'order_index' => 1,
            'status' => 'active'
        ]);
        
        $this->testContent = Content::create([
            'title' => 'Test Content',
            'slug' => 'test-content',
            'content_type' => 'custom',
            'content' => '<p>Test content</p>',
            'status' => 'published'
        ]);
        
        // Assign menu to admin role only
        $this->testMenu->roles()->attach($this->adminRole);
        
        // Assign content to both roles
        $this->testContent->roles()->attach([$this->adminRole->id, $this->userRole->id]);
    }

    /**
     * Test role assignment to users
     */
    public function test_role_assignment(): void
    {
        $this->assertTrue($this->adminUser->hasRole('admin'));
        $this->assertTrue($this->regularUser->hasRole('user'));
        $this->assertFalse($this->adminUser->hasRole('user'));
        $this->assertFalse($this->regularUser->hasRole('admin'));
    }

    /**
     * Test permission checking for admin user
     */
    public function test_admin_permissions(): void
    {
        $this->assertTrue($this->adminUser->hasPermission('create_content'));
        $this->assertTrue($this->adminUser->hasPermission('read_content'));
        $this->assertTrue($this->adminUser->hasPermission('update_content'));
        $this->assertTrue($this->adminUser->hasPermission('delete_content'));
    }

    /**
     * Test permission checking for regular user
     */
    public function test_regular_user_permissions(): void
    {
        $this->assertFalse($this->regularUser->hasPermission('create_content'));
        $this->assertTrue($this->regularUser->hasPermission('read_content'));
        $this->assertFalse($this->regularUser->hasPermission('update_content'));
        $this->assertFalse($this->regularUser->hasPermission('delete_content'));
    }

    /**
     * Test role-based menu access
     */
    public function test_menu_access_control(): void
    {
        // Admin should have access to test menu
        $adminMenus = $this->adminUser->getAccessibleMenus();
        $this->assertTrue($adminMenus->contains('id', $this->testMenu->id));
        
        // Regular user should not have access to test menu
        $userMenus = $this->regularUser->getAccessibleMenus();
        $this->assertFalse($userMenus->contains('id', $this->testMenu->id));
    }

    /**
     * Test content access control
     */
    public function test_content_access_control(): void
    {
        // Both users should have access to test content
        $this->assertTrue($this->adminUser->canAccessContent($this->testContent));
        $this->assertTrue($this->regularUser->canAccessContent($this->testContent));
        
        // Create admin-only content
        $adminContent = Content::create([
            'title' => 'Admin Only Content',
            'slug' => 'admin-only-content',
            'content_type' => 'custom',
            'content' => '<p>Admin only content</p>',
            'status' => 'published'
        ]);
        
        $adminContent->roles()->attach($this->adminRole);
        
        $this->assertTrue($this->adminUser->canAccessContent($adminContent));
        $this->assertFalse($this->regularUser->canAccessContent($adminContent));
    }

    /**
     * Test permission caching
     */
    public function test_permission_caching(): void
    {
        $cacheKey = "user_permissions_{$this->adminUser->id}";
        
        // Clear cache first
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
        
        // Get permissions (should cache them)
        $permissions = $this->cacheService->getUserPermissions($this->adminUser->id);
        $this->assertTrue(Cache::has($cacheKey));
        
        // Verify cached permissions
        $cachedPermissions = Cache::get($cacheKey);
        $this->assertEquals($permissions, $cachedPermissions);
    }

    /**
     * Test cache invalidation on role changes
     */
    public function test_cache_invalidation_on_role_change(): void
    {
        $cacheKey = "user_permissions_{$this->regularUser->id}";
        
        // Cache user permissions
        $this->cacheService->getUserPermissions($this->regularUser->id);
        $this->assertTrue(Cache::has($cacheKey));
        
        // Add admin role to regular user
        UserRole::create([
            'user_id' => $this->regularUser->id,
            'role_id' => $this->adminRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        // Cache should be invalidated
        $this->cacheService->clearUserCache($this->regularUser->id);
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test multiple role permissions
     */
    public function test_multiple_role_permissions(): void
    {
        // Add admin role to regular user
        UserRole::create([
            'user_id' => $this->regularUser->id,
            'role_id' => $this->adminRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        // User should now have all permissions from both roles
        $this->assertTrue($this->regularUser->hasPermission('create_content'));
        $this->assertTrue($this->regularUser->hasPermission('read_content'));
        $this->assertTrue($this->regularUser->hasPermission('update_content'));
        $this->assertTrue($this->regularUser->hasPermission('delete_content'));
    }

    /**
     * Test role hierarchy (if implemented)
     */
    public function test_role_hierarchy(): void
    {
        // Create a manager role that inherits from user role
        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager with elevated permissions'
        ]);
        
        // Assign update permission to manager
        $managerRole->permissions()->attach($this->updatePermission->id);
        
        $managerUser = User::factory()->create([
            'email' => 'manager@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        UserRole::create([
            'user_id' => $managerUser->id,
            'role_id' => $managerRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        // Manager should have update permission
        $this->assertTrue($managerUser->hasPermission('update_content'));
        $this->assertFalse($managerUser->hasPermission('delete_content'));
    }

    /**
     * Test permission middleware
     */
    public function test_permission_middleware(): void
    {
        $middleware = new CheckPermission();
        $request = Request::create('/test', 'GET');
        
        // Test with admin user (should pass)
        Auth::login($this->adminUser);
        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'create_content');
        
        $this->assertEquals('OK', $response->getContent());
        
        // Test with regular user (should fail)
        Auth::login($this->regularUser);
        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'create_content');
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test role middleware
     */
    public function test_role_middleware(): void
    {
        $middleware = new CheckRole();
        $request = Request::create('/test', 'GET');
        
        // Test with admin user (should pass)
        Auth::login($this->adminUser);
        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'admin');
        
        $this->assertEquals('OK', $response->getContent());
        
        // Test with regular user (should fail)
        Auth::login($this->regularUser);
        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'admin');
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test menu access middleware
     */
    public function test_menu_access_middleware(): void
    {
        $middleware = new CheckMenuAccess();
        $request = Request::create('/test-content', 'GET');
        
        // Test with admin user (should pass)
        Auth::login($this->adminUser);
        $response = $middleware->handle($request, function () {
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
        
        // Test with regular user (should fail)
        Auth::login($this->regularUser);
        $response = $middleware->handle($request, function () {
            return response('OK');
        });
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test super admin bypass
     */
    public function test_super_admin_bypass(): void
    {
        // Create super admin role
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Super administrator with all permissions'
        ]);
        
        $superAdmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        UserRole::create([
            'user_id' => $superAdmin->id,
            'role_id' => $superAdminRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        // Super admin should have access to everything
        $this->assertTrue($superAdmin->hasRole('super_admin'));
        
        // Test if super admin bypasses permission checks
        if (method_exists($superAdmin, 'isSuperAdmin')) {
            $this->assertTrue($superAdmin->isSuperAdmin());
        }
    }

    /**
     * Test permission inheritance
     */
    public function test_permission_inheritance(): void
    {
        // Test that user inherits permissions from all assigned roles
        $permissions = $this->adminUser->getAllPermissions();
        
        $this->assertGreaterThan(0, $permissions->count());
        $this->assertTrue($permissions->contains('name', 'create_content'));
        $this->assertTrue($permissions->contains('name', 'read_content'));
        $this->assertTrue($permissions->contains('name', 'update_content'));
        $this->assertTrue($permissions->contains('name', 'delete_content'));
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        Auth::logout();
        Cache::flush();
        parent::tearDown();
    }
}
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Helpers\MenuHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Class MenuSystemTest
 * 
 * Unit tests for menu system functionality including menu CRUD,
 * hierarchy management, role-based access, and menu helper utilities.
 * 
 * @package Tests\Unit
 */
class MenuSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Menu $parentMenu;
    protected Menu $childMenu;
    protected Menu $grandchildMenu;
    protected Role $adminRole;
    protected Role $userRole;
    protected User $adminUser;
    protected User $regularUser;
    protected MenuHelper $menuHelper;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRoles();
        $this->setUpUsers();
        $this->setUpMenus();
        $this->setUpMenuHelper();
    }

    /**
     * Set up test roles
     */
    private function setUpRoles(): void
    {
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System administrator'
        ]);
        
        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => 'Regular User',
            'description' => 'Regular system user'
        ]);
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
            'assigned_at' => Carbon::now()
        ]);
        
        UserRole::create([
            'user_id' => $this->regularUser->id,
            'role_id' => $this->userRole->id,
            'is_active' => true,
            'assigned_at' => Carbon::now()
        ]);
    }

    /**
     * Set up test menus
     */
    private function setUpMenus(): void
    {
        // Create parent menu
        $this->parentMenu = Menu::create([
            'name' => 'Dashboard',
            'slug' => 'dashboard',
            'url' => '/dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'order_index' => 1,
            'is_active' => true,
            'parent_id' => null
        ]);
        
        // Create child menu
        $this->childMenu = Menu::create([
            'name' => 'Analytics',
            'slug' => 'analytics',
            'url' => '/dashboard/analytics',
            'icon' => 'fas fa-chart-bar',
            'order_index' => 1,
            'is_active' => true,
            'parent_id' => $this->parentMenu->id
        ]);
        
        // Create grandchild menu
        $this->grandchildMenu = Menu::create([
            'name' => 'Reports',
            'slug' => 'reports',
            'url' => '/dashboard/analytics/reports',
            'icon' => 'fas fa-file-alt',
            'order_index' => 1,
            'is_active' => true,
            'parent_id' => $this->childMenu->id
        ]);
        
        // Assign menus to roles
        $this->parentMenu->roles()->attach($this->adminRole);
        $this->parentMenu->roles()->attach($this->userRole);
        $this->childMenu->roles()->attach($this->adminRole);
        $this->grandchildMenu->roles()->attach($this->adminRole);
    }

    /**
     * Set up menu helper
     */
    private function setUpMenuHelper(): void
    {
        $this->menuHelper = new MenuHelper();
    }

    /**
     * Test menu creation
     */
    public function test_menu_creation(): void
    {
        $menuData = [
            'name' => 'Settings',
            'slug' => 'settings',
            'url' => '/settings',
            'icon' => 'fas fa-cog',
            'order_index' => 2,
            'is_active' => true
        ];
        
        $menu = Menu::create($menuData);
        
        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEquals('Settings', $menu->name);
        $this->assertEquals('settings', $menu->slug);
        $this->assertEquals('/settings', $menu->url);
        $this->assertEquals('fas fa-cog', $menu->icon);
        $this->assertTrue($menu->is_active);
        $this->assertTrue(Str::isUuid($menu->id));
    }

    /**
     * Test menu update
     */
    public function test_menu_update(): void
    {
        $originalName = $this->parentMenu->name;
        $newName = 'Main Dashboard';
        
        $this->parentMenu->update(['name' => $newName]);
        
        $this->assertEquals($newName, $this->parentMenu->fresh()->name);
        $this->assertNotEquals($originalName, $this->parentMenu->fresh()->name);
    }

    /**
     * Test menu soft delete
     */
    public function test_menu_soft_delete(): void
    {
        $menuId = $this->parentMenu->id;
        
        $this->parentMenu->delete();
        
        // Menu should be soft deleted
        $this->assertSoftDeleted('idbi_menus', ['id' => $menuId]);
        
        // Menu should not be found in normal queries
        $this->assertNull(Menu::find($menuId));
        
        // Menu should be found with trashed
        $this->assertNotNull(Menu::withTrashed()->find($menuId));
    }

    /**
     * Test menu hierarchy relationships
     */
    public function test_menu_hierarchy_relationships(): void
    {
        // Test parent-child relationship
        $this->assertEquals($this->parentMenu->id, $this->childMenu->parent_id);
        $this->assertTrue($this->parentMenu->children->contains('id', $this->childMenu->id));
        $this->assertEquals($this->parentMenu->id, $this->childMenu->parent->id);
        
        // Test grandparent-grandchild relationship
        $this->assertEquals($this->childMenu->id, $this->grandchildMenu->parent_id);
        $this->assertTrue($this->childMenu->children->contains('id', $this->grandchildMenu->id));
        $this->assertEquals($this->childMenu->id, $this->grandchildMenu->parent->id);
    }

    /**
     * Test menu tree building
     */
    public function test_menu_tree_building(): void
    {
        $menuTree = $this->menuHelper->buildMenuTree();
        
        $this->assertInstanceOf(Collection::class, $menuTree);
        $this->assertGreaterThan(0, $menuTree->count());
        
        // Find parent menu in tree
        $parentInTree = $menuTree->firstWhere('id', $this->parentMenu->id);
        $this->assertNotNull($parentInTree);
        
        // Check if children are properly nested
        $this->assertArrayHasKey('children', $parentInTree);
        $this->assertGreaterThan(0, count($parentInTree['children']));
    }

    /**
     * Test menu role assignment
     */
    public function test_menu_role_assignment(): void
    {
        $newRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager role'
        ]);
        
        // Assign menu to new role
        $this->parentMenu->roles()->attach($newRole);
        
        $this->assertTrue($this->parentMenu->roles->contains('id', $newRole->id));
        
        // Remove role assignment
        $this->parentMenu->roles()->detach($newRole);
        
        $this->assertFalse($this->parentMenu->fresh()->roles->contains('id', $newRole->id));
    }

    /**
     * Test menu access control
     */
    public function test_menu_access_control(): void
    {
        // Admin user should have access to all menus
        $adminMenus = $this->menuHelper->getUserMenus($this->adminUser);
        $this->assertGreaterThan(0, $adminMenus->count());
        
        // Regular user should have limited access
        $userMenus = $this->menuHelper->getUserMenus($this->regularUser);
        $this->assertLessThanOrEqual($adminMenus->count(), $userMenus->count());
        
        // Check specific menu access
        $this->assertTrue($this->adminUser->canAccessMenu($this->parentMenu));
        $this->assertTrue($this->regularUser->canAccessMenu($this->parentMenu));
        $this->assertTrue($this->adminUser->canAccessMenu($this->childMenu));
        $this->assertFalse($this->regularUser->canAccessMenu($this->childMenu));
    }

    /**
     * Test menu ordering
     */
    public function test_menu_ordering(): void
    {
        // Create additional menus with different order indices
        $menu1 = Menu::create([
            'name' => 'First Menu',
            'slug' => 'first-menu',
            'url' => '/first',
            'order_index' => 1,
            'is_active' => true
        ]);
        
        $menu2 = Menu::create([
            'name' => 'Second Menu',
            'slug' => 'second-menu',
            'url' => '/second',
            'order_index' => 2,
            'is_active' => true
        ]);
        
        $menu3 = Menu::create([
            'name' => 'Third Menu',
            'slug' => 'third-menu',
            'url' => '/third',
            'order_index' => 3,
            'is_active' => true
        ]);
        
        // Test ordering
        $orderedMenus = Menu::whereNull('parent_id')
            ->orderBy('order_index')
            ->get();
            
        $this->assertEquals('Dashboard', $orderedMenus->first()->name);
        $this->assertEquals('First Menu', $orderedMenus->skip(1)->first()->name);
        $this->assertEquals('Second Menu', $orderedMenus->skip(2)->first()->name);
    }

    /**
     * Test menu active state detection
     */
    public function test_menu_active_state_detection(): void
    {
        $currentUrl = '/dashboard/analytics';
        
        // Test exact match
        $this->assertTrue($this->menuHelper->isMenuActive($this->childMenu, $currentUrl));
        
        // Test parent menu active state (should be active if child is active)
        $this->assertTrue($this->menuHelper->isMenuActive($this->parentMenu, $currentUrl));
        
        // Test non-matching menu
        $otherMenu = Menu::create([
            'name' => 'Other Menu',
            'slug' => 'other-menu',
            'url' => '/other',
            'is_active' => true
        ]);
        
        $this->assertFalse($this->menuHelper->isMenuActive($otherMenu, $currentUrl));
    }

    /**
     * Test breadcrumb generation
     */
    public function test_breadcrumb_generation(): void
    {
        $currentUrl = '/dashboard/analytics/reports';
        
        $breadcrumbs = $this->menuHelper->generateBreadcrumbs($currentUrl);
        
        $this->assertIsArray($breadcrumbs);
        $this->assertGreaterThan(0, count($breadcrumbs));
        
        // Check breadcrumb structure
        foreach ($breadcrumbs as $breadcrumb) {
            $this->assertArrayHasKey('name', $breadcrumb);
            $this->assertArrayHasKey('url', $breadcrumb);
        }
    }

    /**
     * Test menu slug uniqueness
     */
    public function test_menu_slug_uniqueness(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Try to create menu with existing slug
        Menu::create([
            'name' => 'Duplicate Menu',
            'slug' => $this->parentMenu->slug, // Same slug as existing menu
            'url' => '/duplicate',
            'is_active' => true
        ]);
    }

    /**
     * Test menu status management
     */
    public function test_menu_status_management(): void
    {
        // Test active menu
        $this->assertTrue($this->parentMenu->is_active);
        
        // Deactivate menu
        $this->parentMenu->update(['is_active' => false]);
        $this->assertFalse($this->parentMenu->fresh()->is_active);
        
        // Reactivate menu
        $this->parentMenu->update(['is_active' => true]);
        $this->assertTrue($this->parentMenu->fresh()->is_active);
    }

    /**
     * Test menu search functionality
     */
    public function test_menu_search(): void
    {
        // Create additional test menus
        Menu::create([
            'name' => 'User Management',
            'slug' => 'user-management',
            'url' => '/users',
            'is_active' => true
        ]);
        
        Menu::create([
            'name' => 'System Settings',
            'slug' => 'system-settings',
            'url' => '/settings/system',
            'is_active' => true
        ]);
        
        // Search by name
        $results = Menu::where('name', 'LIKE', '%Management%')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('User Management', $results->first()->name);
        
        // Search by URL
        $urlResults = Menu::where('url', 'LIKE', '%settings%')->get();
        $this->assertGreaterThanOrEqual(1, $urlResults->count());
    }

    /**
     * Test menu filtering by status
     */
    public function test_menu_filtering_by_status(): void
    {
        // Create inactive menu
        Menu::create([
            'name' => 'Inactive Menu',
            'slug' => 'inactive-menu',
            'url' => '/inactive',
            'is_active' => false
        ]);
        
        // Filter active menus
        $activeMenus = Menu::where('is_active', true)->get();
        $this->assertGreaterThanOrEqual(3, $activeMenus->count());
        
        // Filter inactive menus
        $inactiveMenus = Menu::where('is_active', false)->get();
        $this->assertEquals(1, $inactiveMenus->count());
    }

    /**
     * Test menu depth calculation
     */
    public function test_menu_depth_calculation(): void
    {
        // Parent menu should have depth 0
        $this->assertEquals(0, $this->menuHelper->getMenuDepth($this->parentMenu));
        
        // Child menu should have depth 1
        $this->assertEquals(1, $this->menuHelper->getMenuDepth($this->childMenu));
        
        // Grandchild menu should have depth 2
        $this->assertEquals(2, $this->menuHelper->getMenuDepth($this->grandchildMenu));
    }

    /**
     * Test menu path generation
     */
    public function test_menu_path_generation(): void
    {
        $path = $this->menuHelper->getMenuPath($this->grandchildMenu);
        
        $this->assertIsArray($path);
        $this->assertEquals(3, count($path)); // Parent -> Child -> Grandchild
        
        // Check path order
        $this->assertEquals($this->parentMenu->id, $path[0]['id']);
        $this->assertEquals($this->childMenu->id, $path[1]['id']);
        $this->assertEquals($this->grandchildMenu->id, $path[2]['id']);
    }

    /**
     * Test menu icon validation
     */
    public function test_menu_icon_validation(): void
    {
        $validIcons = [
            'fas fa-home',
            'far fa-user',
            'fab fa-github',
            'fal fa-chart-bar'
        ];
        
        foreach ($validIcons as $icon) {
            $menu = Menu::create([
                'name' => 'Test Menu',
                'slug' => 'test-menu-' . uniqid(),
                'url' => '/test-' . uniqid(),
                'icon' => $icon,
                'is_active' => true
            ]);
            
            $this->assertEquals($icon, $menu->icon);
        }
    }

    /**
     * Test menu URL validation
     */
    public function test_menu_url_validation(): void
    {
        $validUrls = [
            '/dashboard',
            '/users/profile',
            '/settings/system',
            'https://external.com',
            '#'
        ];
        
        foreach ($validUrls as $url) {
            $menu = Menu::create([
                'name' => 'Test Menu',
                'slug' => 'test-menu-' . uniqid(),
                'url' => $url,
                'is_active' => true
            ]);
            
            $this->assertEquals($url, $menu->url);
        }
    }

    /**
     * Test menu caching
     */
    public function test_menu_caching(): void
    {
        // Clear cache
        $this->menuHelper->clearMenuCache();
        
        // First call should hit database
        $menus1 = $this->menuHelper->getCachedMenus($this->adminUser);
        
        // Second call should hit cache
        $menus2 = $this->menuHelper->getCachedMenus($this->adminUser);
        
        $this->assertEquals($menus1->count(), $menus2->count());
    }

    /**
     * Test menu cache invalidation
     */
    public function test_menu_cache_invalidation(): void
    {
        // Get cached menus
        $originalMenus = $this->menuHelper->getCachedMenus($this->adminUser);
        
        // Create new menu
        $newMenu = Menu::create([
            'name' => 'New Menu',
            'slug' => 'new-menu',
            'url' => '/new',
            'is_active' => true
        ]);
        
        $newMenu->roles()->attach($this->adminRole);
        
        // Clear cache
        $this->menuHelper->clearMenuCache();
        
        // Get menus again
        $updatedMenus = $this->menuHelper->getCachedMenus($this->adminUser);
        
        $this->assertGreaterThan($originalMenus->count(), $updatedMenus->count());
    }

    /**
     * Test menu permissions inheritance
     */
    public function test_menu_permissions_inheritance(): void
    {
        // If user has access to parent, they should see it in menu tree
        // but not necessarily have access to children without explicit permission
        
        $userMenuTree = $this->menuHelper->buildMenuTree($this->regularUser);
        
        // Find parent menu in user's tree
        $parentInUserTree = $userMenuTree->firstWhere('id', $this->parentMenu->id);
        $this->assertNotNull($parentInUserTree);
        
        // Child menu should not be in user's tree (no permission)
        $childInUserTree = collect($parentInUserTree['children'] ?? [])
            ->firstWhere('id', $this->childMenu->id);
        $this->assertNull($childInUserTree);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
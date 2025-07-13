<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\MenuHelper;
use App\Models\Menu;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Class MenuHelperTest
 * 
 * Unit tests for MenuHelper class functionality including active state detection,
 * breadcrumb generation, and menu tree operations.
 * 
 * @package Tests\Unit
 */
class MenuHelperTest extends TestCase
{
    use RefreshDatabase;

    protected Menu $testMenu;
    protected Menu $parentMenu;
    protected Menu $childMenu;
    protected User $testUser;
    protected Role $testRole;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test role
        $this->testRole = Role::create([
            'name' => 'test_role',
            'display_name' => 'Test Role',
            'description' => 'Test role for menu helper tests'
        ]);
        
        // Create test user
        $this->testUser = User::factory()->create();
        
        UserRole::create([
            'user_id' => $this->testUser->id,
            'role_id' => $this->testRole->id,
            'is_active' => true,
            'assigned_at' => Carbon::now()
        ]);
        
        // Create test menus
        $this->parentMenu = Menu::create([
            'name' => 'parent_menu',
            'title' => 'Parent Menu',
            'description' => 'Parent menu for testing',
            'url' => '/parent',
            'icon' => 'fas fa-folder',
            'type' => 'dropdown',
            'level' => 1,
            'sort_order' => 1,
            'is_active' => true
        ]);
        
        $this->childMenu = Menu::create([
            'name' => 'child_menu',
            'title' => 'Child Menu',
            'description' => 'Child menu for testing',
            'url' => '/parent/child',
            'icon' => 'fas fa-file',
            'type' => 'link',
            'level' => 2,
            'parent_id' => $this->parentMenu->id,
            'sort_order' => 1,
            'is_active' => true
        ]);
        
        $this->testMenu = Menu::create([
            'name' => 'test_menu',
            'title' => 'Test Menu',
            'description' => 'Test menu for helper tests',
            'url' => '/test',
            'route_name' => 'test.index',
            'icon' => 'fas fa-test',
            'type' => 'link',
            'level' => 1,
            'sort_order' => 2,
            'is_active' => true
        ]);
        
        // Assign roles to menus
        $this->parentMenu->roles()->attach($this->testRole->id, ['is_visible' => true]);
        $this->childMenu->roles()->attach($this->testRole->id, ['is_visible' => true]);
        $this->testMenu->roles()->attach($this->testRole->id, ['is_visible' => true]);
    }

    /**
     * Test menu active state detection by URL
     */
    public function test_menu_active_state_detection_by_url()
    {
        // Create mock request
        $request = Request::create('/test', 'GET');
        
        // Test exact URL match
        $this->assertTrue(MenuHelper::isMenuActive($this->testMenu, $request));
        
        // Test non-matching URL
        $request = Request::create('/other', 'GET');
        $this->assertFalse(MenuHelper::isMenuActive($this->testMenu, $request));
    }

    /**
     * Test menu active state detection by route name
     */
    public function test_menu_active_state_detection_by_route_name()
    {
        // Mock route
        Route::get('/test', function () {
            return 'test';
        })->name('test.index');
        
        $request = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            return Route::current();
        });
        
        $this->assertTrue(MenuHelper::isMenuActive($this->testMenu, $request));
    }

    /**
     * Test parent menu active state when child is active
     */
    public function test_parent_menu_active_when_child_active()
    {
        $request = Request::create('/parent/child', 'GET');
        
        // Load children relationship
        $this->parentMenu->load('children');
        
        // Parent should be active when child is active
        $this->assertTrue(MenuHelper::isMenuActive($this->parentMenu, $request));
    }

    /**
     * Test URL pattern matching
     */
    public function test_url_pattern_matching()
    {
        // Set URL pattern for menu
        $this->testMenu->update(['url_pattern' => '/test/*']);
        
        $request = Request::create('/test/sub-page', 'GET');
        
        $this->assertTrue(MenuHelper::matchesUrlPattern($this->testMenu, 'test/sub-page'));
        $this->assertFalse(MenuHelper::matchesUrlPattern($this->testMenu, 'other/page'));
    }

    /**
     * Test breadcrumb generation
     */
    public function test_breadcrumb_generation()
    {
        // Mock Cache to avoid Redis dependency in unit tests
        Cache::shouldReceive('remember')
             ->andReturn(collect([
                 [
                     'title' => 'Parent Menu',
                     'url' => '/parent',
                     'icon' => 'fas fa-folder',
                     'is_active' => false
                 ],
                 [
                     'title' => 'Child Menu',
                     'url' => '/parent/child',
                     'icon' => 'fas fa-file',
                     'is_active' => true
                 ]
             ]));
        
        $request = Request::create('/parent/child', 'GET');
        $breadcrumbs = MenuHelper::generateBreadcrumbs($request, $this->testUser->id);
        
        $this->assertInstanceOf('Illuminate\Support\Collection', $breadcrumbs);
    }

    /**
     * Test menu tree with active states
     */
    public function test_menu_tree_with_active_states()
    {
        // Mock Cache
        Cache::shouldReceive('remember')
             ->andReturn(collect([
                 [
                     'id' => $this->testMenu->id,
                     'name' => 'test_menu',
                     'title' => 'Test Menu',
                     'url' => '/test',
                     'is_active' => true,
                     'has_active_child' => false,
                     'children' => []
                 ]
             ]));
        
        $request = Request::create('/test', 'GET');
        $menuTree = MenuHelper::getMenuTreeWithActiveStates($this->testUser->id, $request);
        
        $this->assertInstanceOf('Illuminate\Support\Collection', $menuTree);
    }

    /**
     * Test navigation menu retrieval
     */
    public function test_navigation_menu_retrieval()
    {
        // Mock Cache
        Cache::shouldReceive('remember')
             ->andReturn(collect([
                 [
                     'id' => $this->testMenu->id,
                     'name' => 'test_menu',
                     'title' => 'Test Menu',
                     'url' => '/test',
                     'is_active' => false,
                     'children' => []
                 ]
             ]));
        
        $request = Request::create('/other', 'GET');
        $navigationMenu = MenuHelper::getNavigationMenu($this->testUser->id, $request);
        
        $this->assertInstanceOf('Illuminate\Support\Collection', $navigationMenu);
    }

    /**
     * Test menu cache clearing
     */
    public function test_menu_cache_clearing()
    {
        // Mock Cache
        Cache::shouldReceive('getRedis')
             ->andReturnSelf();
        Cache::shouldReceive('keys')
             ->andReturn(['key1', 'key2']);
        Cache::shouldReceive('del')
             ->with(['key1', 'key2'])
             ->andReturn(true);
        
        // Should not throw exception
        MenuHelper::clearMenuCache($this->testUser->id);
        
        $this->assertTrue(true); // Assert test completed without exception
    }

    /**
     * Test menu retrieval by identifier
     */
    public function test_menu_retrieval_by_identifier()
    {
        // Test by URL
        $menu = MenuHelper::getMenuByIdentifier('/test');
        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEquals($this->testMenu->id, $menu->id);
        
        // Test by route name
        $menu = MenuHelper::getMenuByIdentifier('test.index');
        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEquals($this->testMenu->id, $menu->id);
        
        // Test non-existent identifier
        $menu = MenuHelper::getMenuByIdentifier('/non-existent');
        $this->assertNull($menu);
    }

    /**
     * Test active state detection without request
     */
    public function test_active_state_detection_without_request()
    {
        // Should use global request() helper
        $this->assertIsBool(MenuHelper::isMenuActive($this->testMenu));
    }

    /**
     * Test breadcrumb generation without user ID
     */
    public function test_breadcrumb_generation_without_user_id()
    {
        // Mock Auth
        \Illuminate\Support\Facades\Auth::shouldReceive('check')
                                        ->andReturn(false);
        \Illuminate\Support\Facades\Auth::shouldReceive('id')
                                        ->andReturn(null);
        
        // Mock Cache
        Cache::shouldReceive('remember')
             ->andReturn(collect());
        
        $request = Request::create('/test', 'GET');
        $breadcrumbs = MenuHelper::generateBreadcrumbs($request);
        
        $this->assertInstanceOf('Illuminate\Support\Collection', $breadcrumbs);
    }

    /**
     * Test menu tree generation without user ID
     */
    public function test_menu_tree_generation_without_user_id()
    {
        // Mock Auth
        \Illuminate\Support\Facades\Auth::shouldReceive('check')
                                        ->andReturn(false);
        \Illuminate\Support\Facades\Auth::shouldReceive('id')
                                        ->andReturn(null);
        
        // Mock Cache
        Cache::shouldReceive('remember')
             ->andReturn(collect());
        
        $request = Request::create('/test', 'GET');
        $menuTree = MenuHelper::getMenuTreeWithActiveStates(null, $request);
        
        $this->assertInstanceOf('Illuminate\Support\Collection', $menuTree);
    }

    /**
     * Test URL pattern with multiple patterns
     */
    public function test_url_pattern_with_multiple_patterns()
    {
        // Set multiple URL patterns
        $this->testMenu->update(['url_pattern' => ['/test/*', '/admin/test/*']]);
        
        $this->assertTrue(MenuHelper::matchesUrlPattern($this->testMenu, 'test/page'));
        $this->assertTrue(MenuHelper::matchesUrlPattern($this->testMenu, 'admin/test/page'));
        $this->assertFalse(MenuHelper::matchesUrlPattern($this->testMenu, 'other/page'));
    }

    /**
     * Test menu active state with external URL
     */
    public function test_menu_active_state_with_external_url()
    {
        $externalMenu = Menu::create([
            'name' => 'external_menu',
            'title' => 'External Menu',
            'url' => 'https://example.com',
            'type' => 'link',
            'is_external' => true,
            'is_active' => true
        ]);
        
        $request = Request::create('https://example.com', 'GET');
        
        $this->assertTrue(MenuHelper::isMenuActive($externalMenu, $request));
    }
}
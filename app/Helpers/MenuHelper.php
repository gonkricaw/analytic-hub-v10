<?php

namespace App\Helpers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

/**
 * Class MenuHelper
 * 
 * Helper class for menu-related operations including active state detection,
 * breadcrumb generation, and menu tree building with role-based filtering.
 * 
 * @package App\Helpers
 */
class MenuHelper
{
    /**
     * Detect if a menu is active based on current request
     * 
     * @param Menu $menu
     * @param Request|null $request
     * @return bool
     */
    public static function isMenuActive(Menu $menu, Request $request = null): bool
    {
        if (!$request) {
            $request = request();
        }

        $currentUrl = $request->url();
        $currentPath = $request->path();
        $currentRoute = Route::currentRouteName();

        // Check exact URL match
        if ($menu->url && $menu->url === $currentUrl) {
            return true;
        }

        // Check path match (without domain)
        if ($menu->url) {
            $menuPath = parse_url($menu->url, PHP_URL_PATH);
            if ($menuPath && trim($menuPath, '/') === trim($currentPath, '/')) {
                return true;
            }
        }

        // Check route name match
        if ($menu->route_name && $menu->route_name === $currentRoute) {
            return true;
        }

        // Check if any child menu is active
        if ($menu->children && $menu->children->count() > 0) {
            foreach ($menu->children as $child) {
                if (self::isMenuActive($child, $request)) {
                    return true;
                }
            }
        }

        // Check pattern matching for dynamic routes
        if ($menu->url_pattern) {
            $pattern = str_replace('*', '.*', $menu->url_pattern);
            if (preg_match("/^{$pattern}$/", $currentPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate breadcrumb trail for current request
     * 
     * @param Request|null $request
     * @param string|null $userId
     * @return Collection
     */
    public static function generateBreadcrumbs(Request $request = null, string $userId = null): Collection
    {
        if (!$request) {
            $request = request();
        }

        if (!$userId && Auth::check()) {
            $userId = Auth::id();
        }

        $cacheKey = "breadcrumbs_{$userId}_" . md5($request->path());
        
        return Cache::remember($cacheKey, 300, function () use ($request, $userId) {
            $breadcrumbs = collect();
            
            // Get all active menus accessible to user
            $menus = self::getAccessibleMenus($userId);
            
            // Find the active menu
            $activeMenu = null;
            foreach ($menus as $menu) {
                if (self::isMenuActive($menu, $request)) {
                    $activeMenu = $menu;
                    break;
                }
            }

            if ($activeMenu) {
                // Build breadcrumb trail from root to active menu
                $trail = collect();
                $current = $activeMenu;
                
                while ($current) {
                    $trail->prepend($current);
                    $current = $current->parent;
                }
                
                // Convert to breadcrumb format
                foreach ($trail as $menu) {
                    $breadcrumbs->push([
                        'title' => $menu->title,
                        'url' => $menu->url,
                        'icon' => $menu->icon,
                        'is_active' => $menu->id === $activeMenu->id
                    ]);
                }
            }

            return $breadcrumbs;
        });
    }

    /**
     * Get menu tree with active states
     * 
     * @param string|null $userId
     * @param Request|null $request
     * @return Collection
     */
    public static function getMenuTreeWithActiveStates(string $userId = null, Request $request = null): Collection
    {
        if (!$request) {
            $request = request();
        }

        if (!$userId && Auth::check()) {
            $userId = Auth::id();
        }

        $cacheKey = "menu_tree_active_{$userId}_" . md5($request->path());
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $request) {
            $menus = self::getAccessibleMenus($userId);
            
            return $menus->map(function ($menu) use ($request) {
                return self::addActiveStateToMenu($menu, $request);
            });
        });
    }

    /**
     * Get accessible menus for a user
     * 
     * @param string|null $userId
     * @return Collection
     */
    private static function getAccessibleMenus(string $userId = null): Collection
    {
        if (!$userId) {
            return collect();
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return collect();
        }

        return Menu::getMenuTreeForUser($user);
    }

    /**
     * Add active state to menu and its children
     * 
     * @param Menu $menu
     * @param Request $request
     * @return array
     */
    private static function addActiveStateToMenu(Menu $menu, Request $request): array
    {
        $isActive = self::isMenuActive($menu, $request);
        $hasActiveChild = false;
        
        $children = [];
        if ($menu->children && $menu->children->count() > 0) {
            foreach ($menu->children as $child) {
                $childData = self::addActiveStateToMenu($child, $request);
                $children[] = $childData;
                
                if ($childData['is_active'] || $childData['has_active_child']) {
                    $hasActiveChild = true;
                }
            }
        }

        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'title' => $menu->title,
            'url' => $menu->url,
            'icon' => $menu->icon,
            'type' => $menu->type,
            'level' => $menu->level,
            'sort_order' => $menu->sort_order,
            'is_active' => $isActive,
            'has_active_child' => $hasActiveChild,
            'is_open' => $isActive || $hasActiveChild,
            'children' => $children
        ];
    }

    /**
     * Get navigation menu for sidebar
     * 
     * @param string|null $userId
     * @param Request|null $request
     * @return Collection
     */
    public static function getNavigationMenu(string $userId = null, Request $request = null): Collection
    {
        if (!$userId && Auth::check()) {
            $userId = Auth::id();
        }

        if (!$request) {
            $request = request();
        }

        $cacheKey = "navigation_menu_{$userId}_" . md5($request->path());
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $request) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return collect();
            }

            // Get navigation menus (show_in_navigation = true)
            $menus = Menu::getNavigationForUser($user);
            
            return $menus->map(function ($menu) use ($request) {
                return self::addActiveStateToMenu($menu, $request);
            });
        });
    }

    /**
     * Clear menu cache for a user
     * 
     * @param string|null $userId
     * @return void
     */
    public static function clearMenuCache(string $userId = null): void
    {
        if ($userId) {
            $patterns = [
                "breadcrumbs_{$userId}_*",
                "menu_tree_active_{$userId}_*",
                "navigation_menu_{$userId}_*"
            ];
            
            foreach ($patterns as $pattern) {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }
        }
    }

    /**
     * Get menu by URL or route name
     * 
     * @param string $identifier URL or route name
     * @return Menu|null
     */
    public static function getMenuByIdentifier(string $identifier): ?Menu
    {
        // Try to find by URL first
        $menu = Menu::where('url', $identifier)
                   ->where('is_active', true)
                   ->first();

        if (!$menu) {
            // Try to find by route name
            $menu = Menu::where('route_name', $identifier)
                       ->where('is_active', true)
                       ->first();
        }

        return $menu;
    }

    /**
     * Check if menu should be highlighted based on URL patterns
     * 
     * @param Menu $menu
     * @param string $currentPath
     * @return bool
     */
    public static function matchesUrlPattern(Menu $menu, string $currentPath): bool
    {
        if (!$menu->url_pattern) {
            return false;
        }

        $patterns = is_array($menu->url_pattern) ? $menu->url_pattern : [$menu->url_pattern];
        
        foreach ($patterns as $pattern) {
            $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            if (preg_match("/^{$regex}$/", $currentPath)) {
                return true;
            }
        }

        return false;
    }
}
<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Helpers\MenuHelper;
use App\Models\Menu;

/**
 * Class MenuComposer
 * 
 * View composer that provides menu data to views, including navigation menus,
 * breadcrumbs, and active states. Automatically injects menu-related data
 * into specified views with caching for performance optimization.
 * 
 * @package App\View\Composers
 */
class MenuComposer
{
    /**
     * Bind data to the view.
     * 
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        
        // Get navigation menu with active states
        $navigationMenu = $this->getNavigationMenu($userId);
        
        // Get breadcrumbs for current page
        $breadcrumbs = $this->getBreadcrumbs($userId);
        
        // Get current active menu
        $activeMenu = $this->getActiveMenu($userId);
        
        // Share data with the view
        $view->with([
            'navigationMenu' => $navigationMenu,
            'breadcrumbs' => $breadcrumbs,
            'activeMenu' => $activeMenu,
            'menuHelper' => new MenuHelper()
        ]);
    }

    /**
     * Get navigation menu for the current user
     * 
     * @param string|null $userId
     * @return \Illuminate\Support\Collection
     */
    private function getNavigationMenu(string $userId = null)
    {
        if (!$userId) {
            return collect();
        }

        return MenuHelper::getNavigationMenu($userId, request());
    }

    /**
     * Get breadcrumbs for the current page
     * 
     * @param string|null $userId
     * @return \Illuminate\Support\Collection
     */
    private function getBreadcrumbs(string $userId = null)
    {
        if (!$userId) {
            return collect();
        }

        return MenuHelper::generateBreadcrumbs(request(), $userId);
    }

    /**
     * Get the currently active menu
     * 
     * @param string|null $userId
     * @return Menu|null
     */
    private function getActiveMenu(string $userId = null): ?Menu
    {
        if (!$userId) {
            return null;
        }

        $cacheKey = "active_menu_{$userId}_" . md5(request()->path());
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return null;
            }

            // Get all accessible menus
            $menus = Menu::getMenuTreeForUser($user);
            
            // Find the active menu
            foreach ($menus as $menu) {
                if (MenuHelper::isMenuActive($menu, request())) {
                    return $menu;
                }
                
                // Check children recursively
                $activeChild = $this->findActiveMenuInChildren($menu->children);
                if ($activeChild) {
                    return $activeChild;
                }
            }

            return null;
        });
    }

    /**
     * Find active menu in children recursively
     * 
     * @param \Illuminate\Support\Collection $children
     * @return Menu|null
     */
    private function findActiveMenuInChildren($children): ?Menu
    {
        if (!$children || $children->isEmpty()) {
            return null;
        }

        foreach ($children as $child) {
            if (MenuHelper::isMenuActive($child, request())) {
                return $child;
            }
            
            // Check grandchildren
            $activeGrandchild = $this->findActiveMenuInChildren($child->children);
            if ($activeGrandchild) {
                return $activeGrandchild;
            }
        }

        return null;
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Menu;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Class CheckMenuAccess
 * 
 * Middleware to check if the authenticated user has access to a specific menu
 * based on the menu's required permissions and roles. This middleware provides
 * comprehensive menu access control with caching for performance optimization.
 * 
 * @package App\Http\Middleware
 */
class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param Closure $next
     * @param string|null $menuName The menu name to check access for
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $menuName = null)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return $this->handleUnauthorized($request, 'User not authenticated');
        }

        $user = Auth::user();

        // Check if user account is active
        if (!$user->isActive()) {
            return $this->handleForbidden($request, 'User account is not active');
        }

        // If no menu name provided, try to extract from route
        if (!$menuName) {
            $menuName = $this->extractMenuFromRoute($request);
        }

        // If still no menu name, allow access (no menu restriction)
        if (!$menuName) {
            return $next($request);
        }

        // Check menu access with caching
        $hasAccess = $this->checkMenuAccess($user, $menuName);

        if (!$hasAccess) {
            return $this->handleForbidden($request, "Access denied to menu: {$menuName}");
        }

        // Log successful access for audit
        Log::info('Menu access granted', [
            'user_id' => $user->id,
            'menu_name' => $menuName,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return $next($request);
    }

    /**
     * Check if user has access to a specific menu
     * 
     * @param $user
     * @param string $menuName
     * @return bool
     */
    private function checkMenuAccess($user, string $menuName): bool
    {
        // Create cache key for user menu access
        $cacheKey = "user_menu_access_{$user->id}_{$menuName}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $menuName) {
            // Find the menu by name
            $menu = Menu::where('name', $menuName)
                       ->where('is_active', true)
                       ->first();

            if (!$menu) {
                Log::warning('Menu not found or inactive', ['menu_name' => $menuName]);
                return false;
            }

            // Use the menu's canAccess method
            return $menu->canAccess($user);
        });
    }

    /**
     * Extract menu name from route
     * 
     * @param Request $request
     * @return string|null
     */
    private function extractMenuFromRoute(Request $request): ?string
    {
        $route = $request->route();
        
        if (!$route) {
            return null;
        }

        // Try to get menu name from route parameters
        if ($route->hasParameter('menu_name')) {
            return $route->parameter('menu_name');
        }

        // Try to extract from route name
        $routeName = $route->getName();
        if ($routeName) {
            // Convert route name to menu name (e.g., 'admin.users.index' -> 'users')
            $parts = explode('.', $routeName);
            if (count($parts) >= 2) {
                return $parts[1]; // Return the second part as menu name
            }
        }

        // Try to extract from URL path
        $path = trim($request->getPathInfo(), '/');
        $segments = explode('/', $path);
        
        // For admin routes, return the second segment
        if (count($segments) >= 2 && $segments[0] === 'admin') {
            return $segments[1];
        }

        return null;
    }

    /**
     * Handle unauthorized access
     * 
     * @param Request $request
     * @param string $message
     * @return ResponseAlias
     */
    private function handleUnauthorized(Request $request, string $message): ResponseAlias
    {
        Log::warning('Unauthorized menu access attempt', [
            'message' => $message,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message
            ], Response::HTTP_UNAUTHORIZED);
        }

        return redirect()->guest(route('login'))
                        ->with('error', 'Please log in to access this page.');
    }

    /**
     * Handle forbidden access
     * 
     * @param Request $request
     * @param string $message
     * @return ResponseAlias
     */
    private function handleForbidden(Request $request, string $message): ResponseAlias
    {
        Log::warning('Forbidden menu access attempt', [
            'message' => $message,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => $message
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->back()
                        ->with('error', 'You do not have permission to access this page.');
    }

    /**
     * Clear menu access cache for a user
     * 
     * @param string $userId
     * @param string|null $menuName
     * @return void
     */
    public static function clearCache(string $userId, string $menuName = null): void
    {
        if ($menuName) {
            Cache::forget("user_menu_access_{$userId}_{$menuName}");
        } else {
            // Clear all menu access cache for the user
            $pattern = "user_menu_access_{$userId}_*";
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }
}
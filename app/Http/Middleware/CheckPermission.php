<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Class CheckPermission
 * 
 * Middleware to check if the authenticated user has the required permission(s)
 * to access a specific route or resource. Supports multiple permission checking
 * with AND/OR logic and caching for performance optimization.
 * 
 * @package App\Http\Middleware
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param Closure $next
     * @param string $permission The required permission(s)
     * @param string $logic Logic for multiple permissions: 'and' (default) or 'or'
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission, string $logic = 'and')
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

        // Super admin bypass - super admins have all permissions
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Parse permissions (can be comma-separated)
        $permissions = array_map('trim', explode(',', $permission));
        
        // Check permissions based on logic
        $hasPermission = $this->checkUserPermissions($user, $permissions, $logic);

        if (!$hasPermission) {
            // Log permission denial
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'required_permissions' => $permissions,
                'logic' => $logic,
                'route' => $request->route()->getName(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $this->handleForbidden($request, 'Insufficient permissions');
        }

        return $next($request);
    }

    /**
     * Check if user has the required permissions.
     * 
     * @param \App\Models\User $user
     * @param array $permissions
     * @param string $logic
     * @return bool
     */
    private function checkUserPermissions($user, array $permissions, string $logic): bool
    {
        // Get user permissions with caching
        $userPermissions = $this->getUserPermissions($user);

        if ($logic === 'or') {
            // OR logic: user needs at least one of the permissions
            foreach ($permissions as $permission) {
                if ($this->hasPermission($userPermissions, $permission)) {
                    return true;
                }
            }
            return false;
        } else {
            // AND logic: user needs all permissions
            foreach ($permissions as $permission) {
                if (!$this->hasPermission($userPermissions, $permission)) {
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * Get user permissions with caching.
     * 
     * @param \App\Models\User $user
     * @return array
     */
    private function getUserPermissions($user): array
    {
        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user) { // 30 minutes cache
            $permissions = [];
            
            // Get permissions from all user roles
            foreach ($user->roles as $role) {
                if ($role->isActive()) {
                    // Use cached permissions if available
                    if ($role->permissions_cache && is_array($role->permissions_cache)) {
                        $permissions = array_merge($permissions, $role->permissions_cache);
                    } else {
                        // Fallback to database query
                        $rolePermissions = $role->permissions()
                                               ->where('status', 'active')
                                               ->pluck('name')
                                               ->toArray();
                        $permissions = array_merge($permissions, $rolePermissions);
                    }
                }
            }
            
            return array_unique($permissions);
        });
    }

    /**
     * Check if user has a specific permission (supports wildcards).
     * 
     * @param array $userPermissions
     * @param string $requiredPermission
     * @return bool
     */
    private function hasPermission(array $userPermissions, string $requiredPermission): bool
    {
        // Direct match
        if (in_array($requiredPermission, $userPermissions)) {
            return true;
        }

        // Wildcard matching
        foreach ($userPermissions as $permission) {
            if ($this->matchesWildcard($permission, $requiredPermission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a permission matches a wildcard pattern.
     * 
     * @param string $pattern Permission pattern (may contain wildcards)
     * @param string $permission Permission to check
     * @return bool
     */
    private function matchesWildcard(string $pattern, string $permission): bool
    {
        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace(['\*', '\?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';
        return preg_match($regex, $permission) === 1;
    }

    /**
     * Handle unauthorized access (401).
     * 
     * @param Request $request
     * @param string $message
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    private function handleUnauthorized(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message
            ], ResponseAlias::HTTP_UNAUTHORIZED);
        }

        return redirect()->route('login')
                        ->with('error', 'Please log in to access this page.');
    }

    /**
     * Handle forbidden access (403).
     * 
     * @param Request $request
     * @param string $message
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    private function handleForbidden(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => $message
            ], ResponseAlias::HTTP_FORBIDDEN);
        }

        // For web requests, show 403 error page or redirect to dashboard
        if ($request->route() && $request->route()->getName() !== 'dashboard') {
            return redirect()->route('dashboard')
                           ->with('error', 'You do not have permission to access that page.');
        }

        abort(ResponseAlias::HTTP_FORBIDDEN, $message);
    }

    /**
     * Clear user permissions cache.
     * 
     * @param int|string $userId
     * @return void
     */
    public static function clearUserPermissionsCache($userId): void
    {
        Cache::forget("user_permissions_{$userId}");
    }

    /**
     * Clear all user permissions cache.
     * 
     * @return void
     */
    public static function clearAllUserPermissionsCache(): void
    {
        Cache::tags(['user_permissions'])->flush();
    }
}
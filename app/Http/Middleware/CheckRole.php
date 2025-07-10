<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Class CheckRole
 * 
 * Middleware to check if the authenticated user has one of the required roles
 * to access a specific route or resource. Supports multiple role checking
 * with OR logic and role hierarchy consideration.
 * 
 * @package App\Http\Middleware
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request
     * @param Closure $next
     * @param string $roles The required role(s) - comma separated
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles)
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

        // Parse roles (can be comma-separated)
        $requiredRoles = array_map('trim', explode(',', $roles));
        
        // Check if user has any of the required roles
        $hasRole = $this->checkUserRoles($user, $requiredRoles);

        if (!$hasRole) {
            // Log role denial
            Log::warning('Role access denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'required_roles' => $requiredRoles,
                'route' => $request->route()->getName(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $this->handleForbidden($request, 'Insufficient role privileges');
        }

        return $next($request);
    }

    /**
     * Check if user has any of the required roles.
     * 
     * @param \App\Models\User $user
     * @param array $requiredRoles
     * @return bool
     */
    private function checkUserRoles($user, array $requiredRoles): bool
    {
        // Get user's active roles
        $userRoles = $user->roles()->where('status', 'active')->pluck('name')->toArray();

        // Super admin always has access
        if (in_array('super_admin', $userRoles)) {
            return true;
        }

        // Check if user has any of the required roles
        foreach ($requiredRoles as $requiredRole) {
            if (in_array($requiredRole, $userRoles)) {
                return true;
            }
        }

        // Check role hierarchy - if user has a higher level role
        return $this->checkRoleHierarchy($userRoles, $requiredRoles);
    }

    /**
     * Check role hierarchy - higher level roles can access lower level resources.
     * 
     * @param array $userRoles
     * @param array $requiredRoles
     * @return bool
     */
    private function checkRoleHierarchy(array $userRoles, array $requiredRoles): bool
    {
        // Get the minimum level from user's roles
        $userMinLevel = $this->getMinRoleLevel($userRoles);
        
        // Get the minimum level from required roles
        $requiredMinLevel = $this->getMinRoleLevel($requiredRoles);
        
        // If user has a role with lower or equal level number (higher hierarchy), allow access
        return $userMinLevel !== null && $requiredMinLevel !== null && $userMinLevel <= $requiredMinLevel;
    }

    /**
     * Get the minimum level (highest hierarchy) from a list of role names.
     * 
     * @param array $roleNames
     * @return int|null
     */
    private function getMinRoleLevel(array $roleNames): ?int
    {
        if (empty($roleNames)) {
            return null;
        }

        $roles = \App\Models\Role::whereIn('name', $roleNames)
                                 ->where('status', 'active')
                                 ->get();

        if ($roles->isEmpty()) {
            return null;
        }

        return $roles->min('level');
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
                           ->with('error', 'You do not have the required role to access that page.');
        }

        abort(ResponseAlias::HTTP_FORBIDDEN, $message);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ActivityLogging
 * 
 * Middleware that logs all user activities and requests:
 * - Logs all authenticated user requests
 * - Captures request/response details
 * - Tracks performance metrics
 * - Logs sensitive operations
 * - Provides audit trail
 * 
 * @package App\Http\Middleware
 */
class ActivityLogging
{
    /**
     * Routes that should not be logged (to avoid spam)
     * 
     * @var array
     */
    protected $skipRoutes = [
        'heartbeat',
        'health-check',
        'ping',
        'status'
    ];

    /**
     * Sensitive routes that require special logging
     * 
     * @var array
     */
    protected $sensitiveRoutes = [
        'login',
        'logout',
        'password.',
        'users.',
        'roles.',
        'permissions.',
        'admin.'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Skip logging for certain routes
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }
        
        // Process the request
        $response = $next($request);
        
        // Calculate response time
        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds
        
        // Log the activity
        $this->logActivity($request, $response, $responseTime);
        
        return $response;
    }

    /**
     * Determine if logging should be skipped for this request
     * 
     * @param Request $request
     * @return bool
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $route = $request->route();
        
        if (!$route) {
            return true;
        }
        
        $routeName = $route->getName();
        
        // Skip if route is in skip list
        foreach ($this->skipRoutes as $skipRoute) {
            if ($routeName === $skipRoute || str_starts_with($routeName, $skipRoute)) {
                return true;
            }
        }
        
        // Skip static assets
        if (str_contains($request->getPathInfo(), '.')) {
            $extension = pathinfo($request->getPathInfo(), PATHINFO_EXTENSION);
            $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
            
            if (in_array(strtolower($extension), $staticExtensions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Log the user activity
     * 
     * @param Request $request
     * @param Response $response
     * @param float $responseTime
     * @return void
     */
    private function logActivity(Request $request, Response $response, float $responseTime): void
    {
        $user = Auth::user();
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';
        
        // Determine if this is a sensitive operation
        $isSensitive = $this->isSensitiveRoute($routeName);
        
        // Determine severity based on response status and route
        $severity = $this->determineSeverity($response->getStatusCode(), $routeName, $isSensitive);
        
        // Prepare activity data
        $activityData = [
            'user_id' => $user ? $user->id : null,
            'subject_type' => $user ? \App\Models\User::class : null,
            'subject_id' => $user ? $user->id : null,
            'action' => $this->getActionFromRoute($routeName, $request->method()),
            'description' => $this->generateDescription($request, $response, $routeName),
            'properties' => [
                'route_name' => $routeName,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->getPathInfo(),
                'query_params' => $request->query->all(),
                'response_status' => $response->getStatusCode(),
                'response_time_ms' => $responseTime,
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'request_size' => strlen($request->getContent()),
                'response_size' => strlen($response->getContent()),
                'memory_usage' => memory_get_peak_usage(true),
                'timestamp' => Carbon::now()->toISOString()
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => $isSensitive,
            'severity' => $severity,
            'category' => $this->getCategoryFromRoute($routeName)
        ];
        
        // Add request body for sensitive operations (excluding passwords)
        if ($isSensitive && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $requestData = $request->all();
            
            // Remove sensitive fields
            $sensitiveFields = ['password', 'password_confirmation', 'current_password', 'new_password'];
            foreach ($sensitiveFields as $field) {
                if (isset($requestData[$field])) {
                    $requestData[$field] = '[REDACTED]';
                }
            }
            
            $activityData['properties']['request_data'] = $requestData;
        }
        
        // Log to activity log table
        try {
            \App\Models\ActivityLog::create($activityData);
        } catch (\Exception $e) {
            // Fallback to Laravel log if database logging fails
            Log::error('Failed to log activity to database', [
                'error' => $e->getMessage(),
                'activity_data' => $activityData
            ]);
        }
        
        // Also log to Laravel log for critical operations
        if ($severity === 'high' || $response->getStatusCode() >= 400) {
            Log::info('User Activity', [
                'user_id' => $user ? $user->id : 'guest',
                'action' => $activityData['action'],
                'route' => $routeName,
                'status' => $response->getStatusCode(),
                'response_time' => $responseTime,
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Check if route is sensitive
     * 
     * @param string $routeName
     * @return bool
     */
    private function isSensitiveRoute(string $routeName): bool
    {
        foreach ($this->sensitiveRoutes as $sensitiveRoute) {
            if ($routeName === $sensitiveRoute || str_starts_with($routeName, $sensitiveRoute)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine severity level
     * 
     * @param int $statusCode
     * @param string $routeName
     * @param bool $isSensitive
     * @return string
     */
    private function determineSeverity(int $statusCode, string $routeName, bool $isSensitive): string
    {
        // High severity for errors and sensitive operations
        if ($statusCode >= 500 || ($statusCode >= 400 && $isSensitive)) {
            return 'high';
        }
        
        // Medium severity for client errors and sensitive routes
        if ($statusCode >= 400 || $isSensitive) {
            return 'medium';
        }
        
        // Low severity for normal operations
        return 'low';
    }

    /**
     * Get action name from route and method
     * 
     * @param string $routeName
     * @param string $method
     * @return string
     */
    private function getActionFromRoute(string $routeName, string $method): string
    {
        // Extract meaningful action from route name
        $parts = explode('.', $routeName);
        $action = end($parts);
        
        // Map common actions
        $actionMap = [
            'index' => 'view_list',
            'show' => 'view_details',
            'create' => 'create_form',
            'store' => 'create_record',
            'edit' => 'edit_form',
            'update' => 'update_record',
            'destroy' => 'delete_record'
        ];
        
        return $actionMap[$action] ?? ($method . '_' . $action);
    }

    /**
     * Generate description for the activity
     * 
     * @param Request $request
     * @param Response $response
     * @param string $routeName
     * @return string
     */
    private function generateDescription(Request $request, Response $response, string $routeName): string
    {
        $method = $request->method();
        $status = $response->getStatusCode();
        $path = $request->getPathInfo();
        
        if ($status >= 400) {
            return "Failed {$method} request to {$path} (Status: {$status})";
        }
        
        return "Successful {$method} request to {$path} (Route: {$routeName})";
    }

    /**
     * Get category from route name
     * 
     * @param string $routeName
     * @return string
     */
    private function getCategoryFromRoute(string $routeName): string
    {
        $parts = explode('.', $routeName);
        $module = $parts[0] ?? 'general';
        
        // Map modules to categories
        $categoryMap = [
            'admin' => 'administration',
            'users' => 'user_management',
            'roles' => 'role_management',
            'permissions' => 'permission_management',
            'content' => 'content_management',
            'menu' => 'menu_management',
            'dashboard' => 'dashboard',
            'auth' => 'authentication',
            'password' => 'password_management',
            'terms' => 'terms_compliance'
        ];
        
        return $categoryMap[$module] ?? 'general';
    }
}
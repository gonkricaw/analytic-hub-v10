<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RateLimiting
 * 
 * Middleware that implements rate limiting to prevent abuse:
 * - Limits requests per IP address
 * - Limits requests per authenticated user
 * - Different limits for different route types
 * - Logs rate limit violations
 * - Provides graceful degradation
 * 
 * @package App\Http\Middleware
 */
class RateLimiting
{
    /**
     * Rate limit configurations
     * 
     * @var array
     */
    protected $rateLimits = [
        'default' => [
            'requests' => 60,
            'window' => 60, // seconds
            'by' => 'ip' // ip, user, or both
        ],
        'auth' => [
            'requests' => 5,
            'window' => 300, // 5 minutes
            'by' => 'ip'
        ],
        'api' => [
            'requests' => 100,
            'window' => 60,
            'by' => 'user'
        ],
        'admin' => [
            'requests' => 30,
            'window' => 60,
            'by' => 'user'
        ],
        'sensitive' => [
            'requests' => 10,
            'window' => 300,
            'by' => 'both'
        ]
    ];

    /**
     * Route patterns and their rate limit types
     * 
     * @var array
     */
    protected $routePatterns = [
        'login' => 'auth',
        'password.reset' => 'auth',
        'password.email' => 'auth',
        'register' => 'auth',
        'api.*' => 'api',
        'admin.*' => 'admin',
        'users.store' => 'sensitive',
        'users.update' => 'sensitive',
        'users.destroy' => 'sensitive',
        'roles.*' => 'sensitive',
        'permissions.*' => 'sensitive'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $limitType
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $limitType = null): Response
    {
        // Determine rate limit type
        $limitType = $limitType ?? $this->determineLimitType($request);
        
        // Get rate limit configuration
        $config = $this->rateLimits[$limitType] ?? $this->rateLimits['default'];
        
        // Check rate limits
        $rateLimitResult = $this->checkRateLimit($request, $config);
        
        if ($rateLimitResult['exceeded']) {
            return $this->handleRateLimitExceeded($request, $rateLimitResult, $config);
        }
        
        // Process request
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $rateLimitResult, $config);
        
        return $response;
    }

    /**
     * Determine the rate limit type for the request
     * 
     * @param Request $request
     * @return string
     */
    private function determineLimitType(Request $request): string
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        
        // Check route patterns
        foreach ($this->routePatterns as $pattern => $limitType) {
            if ($this->matchesPattern($routeName, $pattern)) {
                return $limitType;
            }
        }
        
        return 'default';
    }

    /**
     * Check if route name matches pattern
     * 
     * @param string $routeName
     * @param string $pattern
     * @return bool
     */
    private function matchesPattern(string $routeName, string $pattern): bool
    {
        if (str_ends_with($pattern, '*')) {
            return str_starts_with($routeName, rtrim($pattern, '*'));
        }
        
        return $routeName === $pattern;
    }

    /**
     * Check rate limit for the request
     * 
     * @param Request $request
     * @param array $config
     * @return array
     */
    private function checkRateLimit(Request $request, array $config): array
    {
        $keys = $this->generateCacheKeys($request, $config['by']);
        $window = $config['window'];
        $maxRequests = $config['requests'];
        
        $result = [
            'exceeded' => false,
            'remaining' => $maxRequests,
            'reset_time' => time() + $window,
            'total_requests' => $maxRequests
        ];
        
        foreach ($keys as $key) {
            $cacheKey = "rate_limit:{$key}";
            $requests = Cache::get($cacheKey, 0);
            
            if ($requests >= $maxRequests) {
                $result['exceeded'] = true;
                $result['remaining'] = 0;
                break;
            }
            
            // Increment counter
            Cache::put($cacheKey, $requests + 1, $window);
            $result['remaining'] = min($result['remaining'], $maxRequests - $requests - 1);
        }
        
        return $result;
    }

    /**
     * Generate cache keys based on limiting strategy
     * 
     * @param Request $request
     * @param string $by
     * @return array
     */
    private function generateCacheKeys(Request $request, string $by): array
    {
        $keys = [];
        $user = Auth::user();
        
        switch ($by) {
            case 'ip':
                $keys[] = 'ip:' . $request->ip();
                break;
                
            case 'user':
                if ($user) {
                    $keys[] = 'user:' . $user->id;
                } else {
                    $keys[] = 'ip:' . $request->ip(); // Fallback to IP for guests
                }
                break;
                
            case 'both':
                $keys[] = 'ip:' . $request->ip();
                if ($user) {
                    $keys[] = 'user:' . $user->id;
                }
                break;
        }
        
        return $keys;
    }

    /**
     * Handle rate limit exceeded
     * 
     * @param Request $request
     * @param array $rateLimitResult
     * @param array $config
     * @return Response
     */
    private function handleRateLimitExceeded(Request $request, array $rateLimitResult, array $config): Response
    {
        // Log rate limit violation
        $this->logRateLimitViolation($request, $config);
        
        // Prepare response data
        $retryAfter = $rateLimitResult['reset_time'] - time();
        $message = 'Too many requests. Please try again later.';
        
        // For API requests, return JSON
        if ($request->expectsJson() || str_starts_with($request->getPathInfo(), '/api/')) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $message,
                'retry_after' => $retryAfter,
                'limit' => $config['requests'],
                'window' => $config['window']
            ], 429)
            ->header('Retry-After', $retryAfter)
            ->header('X-RateLimit-Limit', $config['requests'])
            ->header('X-RateLimit-Remaining', 0)
            ->header('X-RateLimit-Reset', $rateLimitResult['reset_time']);
        }
        
        // For web requests, return view or redirect
        return response()->view('errors.429', [
            'message' => $message,
            'retry_after' => $retryAfter
        ], 429)
        ->header('Retry-After', $retryAfter);
    }

    /**
     * Add rate limit headers to response
     * 
     * @param Response $response
     * @param array $rateLimitResult
     * @param array $config
     * @return void
     */
    private function addRateLimitHeaders(Response $response, array $rateLimitResult, array $config): void
    {
        $response->headers->set('X-RateLimit-Limit', $config['requests']);
        $response->headers->set('X-RateLimit-Remaining', $rateLimitResult['remaining']);
        $response->headers->set('X-RateLimit-Reset', $rateLimitResult['reset_time']);
    }

    /**
     * Log rate limit violation
     * 
     * @param Request $request
     * @param array $config
     * @return void
     */
    private function logRateLimitViolation(Request $request, array $config): void
    {
        $user = Auth::user();
        $route = $request->route();
        $routeName = $route ? $route->getName() : 'unknown';
        
        // Log to activity log
        try {
            \App\Models\ActivityLog::create([
                'user_id' => $user ? $user->id : null,
                'subject_type' => $user ? \App\Models\User::class : null,
                'subject_id' => $user ? $user->id : null,
                'event' => 'rate_limit_exceeded', // Add required event field
                'action' => 'rate_limit_exceeded',
                'description' => "Rate limit exceeded for route: {$routeName}",
                'properties' => [
                    'route_name' => $routeName,
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'rate_limit_config' => $config,
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer'),
                    'timestamp' => Carbon::now()->toISOString()
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_sensitive' => true,
                'severity' => 'high',
                'category' => 'security'
            ]);
        } catch (\Exception $e) {
            // Fallback to Laravel log
            Log::warning('Rate limit exceeded', [
                'user_id' => $user ? $user->id : 'guest',
                'ip' => $request->ip(),
                'route' => $routeName,
                'config' => $config
            ]);
        }
        
        // Also log to Laravel log for monitoring
        Log::warning('Rate Limit Exceeded', [
            'user_id' => $user ? $user->id : 'guest',
            'ip_address' => $request->ip(),
            'route' => $routeName,
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'limit' => $config['requests'],
            'window' => $config['window']
        ]);
    }

    /**
     * Get current rate limit status for debugging
     * 
     * @param Request $request
     * @param string $limitType
     * @return array
     */
    public function getRateLimitStatus(Request $request, string $limitType = 'default'): array
    {
        $config = $this->rateLimits[$limitType] ?? $this->rateLimits['default'];
        $keys = $this->generateCacheKeys($request, $config['by']);
        
        $status = [];
        foreach ($keys as $key) {
            $cacheKey = "rate_limit:{$key}";
            $requests = Cache::get($cacheKey, 0);
            $status[$key] = [
                'requests_made' => $requests,
                'requests_remaining' => max(0, $config['requests'] - $requests),
                'limit' => $config['requests'],
                'window' => $config['window']
            ];
        }
        
        return $status;
    }
}
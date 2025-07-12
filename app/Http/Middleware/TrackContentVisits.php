<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Content;
use App\Services\ContentVisitTracker;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackContentVisits Middleware
 * 
 * Automatically tracks content visits for analytics and monitoring.
 * Integrates with ContentVisitTracker service to provide comprehensive
 * visit tracking and user behavior analysis.
 * 
 * Features:
 * - Automatic visit detection
 * - Content identification from routes
 * - Performance monitoring
 * - Error handling and logging
 * - Configurable tracking rules
 * 
 * @package App\Http\Middleware
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class TrackContentVisits
{
    /**
     * Content visit tracker service instance
     * 
     * @var ContentVisitTracker
     */
    protected $visitTracker;
    
    /**
     * Routes that should trigger content visit tracking
     * 
     * @var array
     */
    protected $trackableRoutes = [
        'content.show',
        'content.embed',
        'content.secure'
    ];
    
    /**
     * Constructor
     * 
     * @param ContentVisitTracker $visitTracker Visit tracking service
     */
    public function __construct(ContentVisitTracker $visitTracker)
    {
        $this->visitTracker = $visitTracker;
    }
    
    /**
     * Handle an incoming request.
     * 
     * Tracks content visits for applicable routes and content types.
     * Performs tracking after response to avoid impacting performance.
     * 
     * @param Request $request HTTP request instance
     * @param Closure $next Next middleware in chain
     * @return Response HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only track successful responses
        if ($response->getStatusCode() === 200) {
            $this->trackVisitIfApplicable($request, $response);
        }
        
        return $response;
    }
    
    /**
     * Track visit if the request is for trackable content.
     * 
     * Identifies content from route parameters and tracks the visit
     * using the ContentVisitTracker service.
     * 
     * @param Request $request HTTP request instance
     * @param Response $response HTTP response instance
     * @return void
     */
    protected function trackVisitIfApplicable(Request $request, Response $response): void
    {
        try {
            $route = $request->route();
            
            if (!$route || !$this->shouldTrackRoute($route->getName())) {
                return;
            }
            
            $content = $this->identifyContent($request, $route);
            
            if (!$content) {
                return;
            }
            
            // Determine access type based on route
            $accessType = $this->determineAccessType($route->getName());
            
            // Track the visit
            $this->visitTracker->trackVisit($content, $request, [
                'access_type' => $accessType,
                'route_name' => $route->getName(),
                'response_status' => $response->getStatusCode(),
                'response_size' => strlen($response->getContent()),
                'tracking_timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            // Log tracking errors but don't interrupt the response
            Log::warning('Content visit tracking failed', [
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if the route should be tracked.
     * 
     * Determines if the current route is configured for visit tracking.
     * 
     * @param string|null $routeName Route name to check
     * @return bool True if route should be tracked
     */
    protected function shouldTrackRoute(?string $routeName): bool
    {
        if (!$routeName) {
            return false;
        }
        
        return in_array($routeName, $this->trackableRoutes) ||
               str_starts_with($routeName, 'content.') ||
               str_contains($routeName, 'content');
    }
    
    /**
     * Identify content from request parameters.
     * 
     * Extracts content instance from route parameters using various
     * identification methods (slug, UUID, ID).
     * 
     * @param Request $request HTTP request instance
     * @param \Illuminate\Routing\Route $route Route instance
     * @return Content|null Content instance if found
     */
    protected function identifyContent(Request $request, $route): ?Content
    {
        $parameters = $route->parameters();
        
        // Try to get content from route model binding
        if (isset($parameters['content']) && $parameters['content'] instanceof Content) {
            return $parameters['content'];
        }
        
        // Try to find content by slug
        if ($slug = $parameters['slug'] ?? $request->get('slug')) {
            return Content::where('slug', $slug)
                ->where('status', 'published')
                ->first();
        }
        
        // Try to find content by UUID
        if ($uuid = $parameters['uuid'] ?? $request->get('uuid')) {
            return Content::where('uuid', $uuid)
                ->where('status', 'published')
                ->first();
        }
        
        // Try to find content by ID
        if ($id = $parameters['id'] ?? $request->get('id')) {
            return Content::where('id', $id)
                ->where('status', 'published')
                ->first();
        }
        
        // Try to extract from URL path
        return $this->identifyContentFromPath($request->path());
    }
    
    /**
     * Identify content from URL path.
     * 
     * Attempts to extract content identifier from URL path patterns.
     * 
     * @param string $path URL path
     * @return Content|null Content instance if found
     */
    protected function identifyContentFromPath(string $path): ?Content
    {
        // Pattern: /content/{slug}
        if (preg_match('/\/content\/([a-zA-Z0-9\-_]+)/', $path, $matches)) {
            return Content::where('slug', $matches[1])
                ->where('status', 'published')
                ->first();
        }
        
        // Pattern: /embed/{uuid}
        if (preg_match('/\/embed\/([a-f0-9\-]{36})/', $path, $matches)) {
            return Content::where('uuid', $matches[1])
                ->where('status', 'published')
                ->first();
        }
        
        return null;
    }
    
    /**
     * Determine access type based on route name.
     * 
     * Maps route names to appropriate access types for analytics.
     * 
     * @param string $routeName Route name
     * @return string Access type constant
     */
    protected function determineAccessType(string $routeName): string
    {
        $accessTypeMap = [
            'content.show' => 'view',
            'content.embed' => 'embed',
            'content.secure' => 'secure_view',
            'content.download' => 'download',
            'content.share' => 'share'
        ];
        
        return $accessTypeMap[$routeName] ?? 'view';
    }
    
    /**
     * Check if request is from a bot or crawler.
     * 
     * Identifies automated traffic to filter from analytics.
     * 
     * @param Request $request HTTP request instance
     * @return bool True if request is from a bot
     */
    protected function isBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper',
            'googlebot', 'bingbot', 'slurp', 'duckduckbot',
            'baiduspider', 'yandexbot', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'whatsapp', 'telegram'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if request should be excluded from tracking.
     * 
     * Applies business rules to determine if visit should be tracked.
     * 
     * @param Request $request HTTP request instance
     * @param Content $content Content being accessed
     * @return bool True if visit should be excluded
     */
    protected function shouldExcludeFromTracking(Request $request, Content $content): bool
    {
        // Exclude bot traffic
        if ($this->isBot($request)) {
            return true;
        }
        
        // Exclude admin users if configured
        $user = auth()->user();
        if ($user && $user->hasRole('admin') && config('analytics.exclude_admin_visits', false)) {
            return true;
        }
        
        // Exclude internal traffic
        if ($this->isInternalTraffic($request)) {
            return true;
        }
        
        // Exclude draft or unpublished content
        if ($content->status !== 'published') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if request is internal traffic.
     * 
     * Identifies requests from internal IP addresses or development environments.
     * 
     * @param Request $request HTTP request instance
     * @return bool True if request is internal
     */
    protected function isInternalTraffic(Request $request): bool
    {
        $ip = $request->ip();
        
        // Local development IPs
        $internalIPs = [
            '127.0.0.1',
            '::1',
            'localhost'
        ];
        
        // Private network ranges
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16'
        ];
        
        if (in_array($ip, $internalIPs)) {
            return true;
        }
        
        foreach ($privateRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP address is in a given range.
     * 
     * @param string $ip IP address to check
     * @param string $range CIDR range
     * @return bool True if IP is in range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
            !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HttpsEnforcement
 * 
 * Middleware that enforces HTTPS connections for security:
 * - Redirects HTTP requests to HTTPS in production
 * - Sets secure URL generation
 * - Handles proxy headers for load balancers
 * - Provides configuration for different environments
 * 
 * @package App\Http\Middleware
 */
class HttpsEnforcement
{
    /**
     * Handle an incoming request and enforce HTTPS if required.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if HTTPS enforcement is enabled
        if (!$this->shouldEnforceHttps()) {
            return $next($request);
        }

        // Handle proxy headers for load balancers
        $this->handleProxyHeaders($request);

        // Force HTTPS URL generation
        URL::forceScheme('https');

        // Redirect to HTTPS if not already secure
        if (!$request->isSecure() && !$this->isExemptRoute($request)) {
            return $this->redirectToHttps($request);
        }

        return $next($request);
    }

    /**
     * Determine if HTTPS should be enforced.
     *
     * @return bool
     */
    private function shouldEnforceHttps(): bool
    {
        // Check environment configuration
        if (config('app.force_https', false)) {
            return true;
        }

        // Enforce in production environment
        if (app()->environment('production')) {
            return true;
        }

        // Check if APP_URL is HTTPS
        if (str_starts_with(config('app.url'), 'https://')) {
            return true;
        }

        return false;
    }

    /**
     * Handle proxy headers for proper HTTPS detection behind load balancers.
     *
     * @param Request $request
     * @return void
     */
    private function handleProxyHeaders(Request $request): void
    {
        // Handle X-Forwarded-Proto header
        if ($request->hasHeader('X-Forwarded-Proto')) {
            $proto = $request->header('X-Forwarded-Proto');
            if ($proto === 'https') {
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
            }
        }

        // Handle X-Forwarded-SSL header
        if ($request->hasHeader('X-Forwarded-SSL')) {
            $ssl = $request->header('X-Forwarded-SSL');
            if ($ssl === 'on') {
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
            }
        }

        // Handle CloudFlare headers
        if ($request->hasHeader('CF-Visitor')) {
            $visitor = json_decode($request->header('CF-Visitor'), true);
            if (isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
            }
        }
    }

    /**
     * Check if the current route is exempt from HTTPS enforcement.
     *
     * @param Request $request
     * @return bool
     */
    private function isExemptRoute(Request $request): bool
    {
        $exemptRoutes = [
            'health-check',
            'status',
            'ping',
        ];

        $currentRoute = $request->route()?->getName();
        
        return in_array($currentRoute, $exemptRoutes, true);
    }

    /**
     * Redirect the request to HTTPS.
     *
     * @param Request $request
     * @return Response
     */
    private function redirectToHttps(Request $request): Response
    {
        $httpsUrl = $this->buildHttpsUrl($request);
        
        // Use 301 permanent redirect for SEO
        return redirect($httpsUrl, 301);
    }

    /**
     * Build the HTTPS URL for redirection.
     *
     * @param Request $request
     * @return string
     */
    private function buildHttpsUrl(Request $request): string
    {
        $host = $request->getHost();
        $port = $request->getPort();
        $path = $request->getRequestUri();
        
        // Build HTTPS URL
        $httpsUrl = 'https://' . $host;
        
        // Add port if not standard HTTPS port
        if ($port && $port !== 80 && $port !== 443) {
            $httpsUrl .= ':' . $port;
        }
        
        $httpsUrl .= $path;
        
        return $httpsUrl;
    }
}
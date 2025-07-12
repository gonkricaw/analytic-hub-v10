<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityHeaders
 * 
 * Middleware that adds comprehensive security headers to all responses:
 * - XSS Protection headers
 * - Content Security Policy (CSP)
 * - HSTS (HTTP Strict Transport Security)
 * - X-Frame-Options for clickjacking protection
 * - X-Content-Type-Options to prevent MIME sniffing
 * - Referrer Policy for privacy
 * - Feature Policy for browser features
 * 
 * @package App\Http\Middleware
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers to the response.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // XSS Protection - Enable XSS filtering and block page if attack detected
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Type Options - Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Frame Options - Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Referrer Policy - Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy - Comprehensive CSP for Analytics Hub
        $csp = $this->buildContentSecurityPolicy($request);
        $response->headers->set('Content-Security-Policy', $csp);

        // HTTP Strict Transport Security (HSTS) - Force HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Feature Policy / Permissions Policy - Control browser features
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        // Cross-Origin Embedder Policy - Control cross-origin embedding
        $response->headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none');

        // Cross-Origin Opener Policy - Control cross-origin window access
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // Cross-Origin Resource Policy - Control cross-origin resource access
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');

        // Remove server information for security
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Build Content Security Policy header value.
     *
     * @param Request $request
     * @return string
     */
    private function buildContentSecurityPolicy(Request $request): string
    {
        $appUrl = config('app.url');
        $isProduction = app()->environment('production');

        // Base CSP directives
        $directives = [
            // Default source - restrict to self
            "default-src 'self'",
            
            // Script sources - allow self, inline scripts for widgets, and CDNs
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
            "https://code.jquery.com " .
            "https://cdn.jsdelivr.net " .
            "https://cdnjs.cloudflare.com " .
            "https://stackpath.bootstrapcdn.com " .
            "https://unpkg.com " .
            "https://cdn.datatables.net " .
            "https://app.powerbi.com " .
            "https://msit.powerbi.com " .
            "https://public.tableau.com " .
            "https://online.tableau.com " .
            "https://datastudio.google.com " .
            "https://lookerstudio.google.com",
            
            // Style sources - allow self, inline styles, and CDNs
            "style-src 'self' 'unsafe-inline' " .
            "https://fonts.googleapis.com " .
            "https://cdn.jsdelivr.net " .
            "https://cdnjs.cloudflare.com " .
            "https://stackpath.bootstrapcdn.com " .
            "https://cdn.datatables.net " .
            "https://use.fontawesome.com",
            
            // Font sources
            "font-src 'self' " .
            "https://fonts.gstatic.com " .
            "https://cdn.jsdelivr.net " .
            "https://cdnjs.cloudflare.com " .
            "https://use.fontawesome.com " .
            "data:",
            
            // Image sources - allow self, data URLs, and analytics platforms
            "img-src 'self' data: blob: " .
            "https://app.powerbi.com " .
            "https://msit.powerbi.com " .
            "https://public.tableau.com " .
            "https://online.tableau.com " .
            "https://datastudio.google.com " .
            "https://lookerstudio.google.com " .
            "https://cdn.jsdelivr.net " .
            "https://cdnjs.cloudflare.com",
            
            // Frame sources - allow analytics platforms for embedded content
            "frame-src 'self' " .
            "https://app.powerbi.com " .
            "https://msit.powerbi.com " .
            "https://powerbi.microsoft.com " .
            "https://public.tableau.com " .
            "https://online.tableau.com " .
            "https://*.tableauusercontent.com " .
            "https://datastudio.google.com " .
            "https://lookerstudio.google.com",
            
            // Connect sources - allow AJAX requests to self and analytics APIs
            "connect-src 'self' " .
            "https://app.powerbi.com " .
            "https://api.powerbi.com " .
            "https://public.tableau.com " .
            "https://online.tableau.com " .
            "https://datastudio.google.com " .
            "https://lookerstudio.google.com",
            
            // Media sources
            "media-src 'self' data: blob:",
            
            // Object sources - restrict plugins
            "object-src 'none'",
            
            // Base URI - restrict base tag
            "base-uri 'self'",
            
            // Form action - restrict form submissions
            "form-action 'self'",
        ];

        // Add upgrade-insecure-requests in production
        if ($isProduction && $request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        // Add block-all-mixed-content in production
        if ($isProduction && $request->isSecure()) {
            $directives[] = 'block-all-mixed-content';
        }

        return implode('; ', $directives);
    }

    /**
     * Build Permissions Policy header value.
     *
     * @return string
     */
    private function buildPermissionsPolicy(): string
    {
        $policies = [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=(self)',
            'battery=()',
            'camera=()',
            'cross-origin-isolated=()',
            'display-capture=()',
            'document-domain=()',
            'encrypted-media=()',
            'execution-while-not-rendered=()',
            'execution-while-out-of-viewport=()',
            'fullscreen=(self)',
            'geolocation=()',
            'gyroscope=()',
            'keyboard-map=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'navigation-override=()',
            'payment=()',
            'picture-in-picture=()',
            'publickey-credentials-get=()',
            'screen-wake-lock=()',
            'sync-xhr=(self)',
            'usb=()',
            'web-share=()',
            'xr-spatial-tracking=()'
        ];

        return implode(', ', $policies);
    }
}
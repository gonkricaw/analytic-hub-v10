<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContentSecurityPolicy
 * 
 * Implements Content Security Policy (CSP) headers to prevent:
 * - Cross-Site Scripting (XSS) attacks
 * - Data injection attacks
 * - Clickjacking attacks
 * - Mixed content vulnerabilities
 * 
 * CSP provides fine-grained control over resource loading and execution
 * including scripts, styles, images, fonts, and other content types.
 * 
 * @package App\Http\Middleware
 */
class ContentSecurityPolicy
{
    /**
     * Default CSP directives for the application.
     *
     * @var array
     */
    private array $defaultDirectives = [
        'default-src' => ["'self'"],
        'script-src' => [
            "'self'",
            "'unsafe-inline'", // Required for Laravel Mix and some analytics
            "'unsafe-eval'",   // Required for some JavaScript frameworks
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://code.jquery.com',
            'https://stackpath.bootstrapcdn.com',
            'https://unpkg.com',
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://connect.facebook.net',
        ],
        'style-src' => [
            "'self'",
            "'unsafe-inline'", // Required for dynamic styles
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://stackpath.bootstrapcdn.com',
            'https://unpkg.com',
        ],
        'img-src' => [
            "'self'",
            'data:', // For base64 encoded images
            'blob:', // For dynamically generated images
            'https:',
            'http:', // Allow HTTP images in development
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
            'https://www.facebook.com',
        ],
        'font-src' => [
            "'self'",
            'data:', // For base64 encoded fonts
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://stackpath.bootstrapcdn.com',
        ],
        'connect-src' => [
            "'self'",
            'https://api.powerbi.com',
            'https://app.powerbi.com',
            'https://public.tableau.com',
            'https://online.tableau.com',
            'https://datastudio.google.com',
            'https://lookerstudio.google.com',
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
            'wss:', // WebSocket connections
            'ws:',  // WebSocket connections (development)
        ],
        'frame-src' => [
            "'self'",
            'https://app.powerbi.com',
            'https://public.tableau.com',
            'https://online.tableau.com',
            'https://datastudio.google.com',
            'https://lookerstudio.google.com',
            'https://www.youtube.com',
            'https://player.vimeo.com',
        ],
        'object-src' => ["'none'"], // Disable plugins
        'base-uri' => ["'self'"],   // Restrict base tag
        'form-action' => ["'self'"], // Restrict form submissions
        'frame-ancestors' => ["'none'"], // Prevent clickjacking
        'upgrade-insecure-requests' => [], // Upgrade HTTP to HTTPS
    ];

    /**
     * Environment-specific CSP overrides.
     *
     * @var array
     */
    private array $environmentOverrides = [
        'local' => [
            'script-src' => [
                "'self'",
                "'unsafe-inline'",
                "'unsafe-eval'",
                'http://localhost:*',
                'http://127.0.0.1:*',
                'ws://localhost:*',
                'ws://127.0.0.1:*',
            ],
            'connect-src' => [
                "'self'",
                'http://localhost:*',
                'http://127.0.0.1:*',
                'ws://localhost:*',
                'ws://127.0.0.1:*',
            ],
            'img-src' => [
                "'self'",
                'data:',
                'blob:',
                'http:',
                'https:',
            ],
            'frame-ancestors' => ["'self'"], // Allow framing in development
        ],
        'testing' => [
            'script-src' => [
                "'self'",
                "'unsafe-inline'",
                "'unsafe-eval'",
            ],
            'frame-ancestors' => ["'self'"],
        ],
    ];

    /**
     * Route-specific CSP overrides.
     *
     * @var array
     */
    private array $routeOverrides = [
        'content.iframe' => [
            'frame-ancestors' => [
                "'self'",
                'https://app.powerbi.com',
                'https://public.tableau.com',
                'https://datastudio.google.com',
            ],
        ],
        'analytics.*' => [
            'script-src' => [
                "'self'",
                "'unsafe-inline'",
                "'unsafe-eval'",
                'https://www.googletagmanager.com',
                'https://www.google-analytics.com',
                'https://connect.facebook.net',
            ],
            'connect-src' => [
                "'self'",
                'https://www.google-analytics.com',
                'https://www.googletagmanager.com',
                'https://www.facebook.com',
            ],
        ],
        'embed.*' => [
            'frame-src' => [
                "'self'",
                'https://*',
            ],
            'frame-ancestors' => [
                "'self'",
                'https://*',
            ],
        ],
    ];

    /**
     * Handle an incoming request and apply CSP headers.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Skip CSP for non-HTML responses
        if (!$this->shouldApplyCSP($request, $response)) {
            return $response;
        }
        
        // Build CSP directives
        $directives = $this->buildCSPDirectives($request);
        
        // Generate CSP header value
        $cspHeader = $this->generateCSPHeader($directives);
        
        // Apply CSP headers
        $response->headers->set('Content-Security-Policy', $cspHeader);
        
        // Add report-only header for testing (if enabled)
        if (Config::get('app.csp_report_only', false)) {
            $response->headers->set('Content-Security-Policy-Report-Only', $cspHeader);
        }
        
        return $response;
    }

    /**
     * Determine if CSP should be applied to this request/response.
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    private function shouldApplyCSP(Request $request, Response $response): bool
    {
        // Skip for API requests
        if ($request->is('api/*')) {
            return false;
        }
        
        // Skip for AJAX requests that don't return HTML
        if ($request->ajax() && !str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return false;
        }
        
        // Skip for file downloads
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/') && !str_contains($contentType, 'json')) {
            return false;
        }
        
        // Skip for redirects
        if ($response->isRedirection()) {
            return false;
        }
        
        return true;
    }

    /**
     * Build CSP directives for the current request.
     *
     * @param Request $request
     * @return array
     */
    private function buildCSPDirectives(Request $request): array
    {
        // Start with default directives
        $directives = $this->defaultDirectives;
        
        // Apply environment-specific overrides
        $environment = Config::get('app.env', 'production');
        if (isset($this->environmentOverrides[$environment])) {
            $directives = $this->mergeDirectives($directives, $this->environmentOverrides[$environment]);
        }
        
        // Apply route-specific overrides
        $routeName = $request->route()?->getName();
        if ($routeName) {
            foreach ($this->routeOverrides as $pattern => $overrides) {
                if (fnmatch($pattern, $routeName)) {
                    $directives = $this->mergeDirectives($directives, $overrides);
                }
            }
        }
        
        // Apply configuration overrides
        $configOverrides = Config::get('security.csp_overrides', []);
        if (!empty($configOverrides)) {
            $directives = $this->mergeDirectives($directives, $configOverrides);
        }
        
        // Add nonce for inline scripts if needed
        if ($this->shouldUseNonce($request)) {
            $nonce = $this->generateNonce();
            $request->attributes->set('csp_nonce', $nonce);
            
            // Add nonce to script-src
            if (isset($directives['script-src'])) {
                $directives['script-src'][] = "'nonce-{$nonce}'";
            }
        }
        
        return $directives;
    }

    /**
     * Merge CSP directives, combining arrays for each directive.
     *
     * @param array $base
     * @param array $overrides
     * @return array
     */
    private function mergeDirectives(array $base, array $overrides): array
    {
        foreach ($overrides as $directive => $sources) {
            if (isset($base[$directive])) {
                // Merge and remove duplicates
                $base[$directive] = array_unique(array_merge($base[$directive], $sources));
            } else {
                $base[$directive] = $sources;
            }
        }
        
        return $base;
    }

    /**
     * Generate CSP header string from directives array.
     *
     * @param array $directives
     * @return string
     */
    private function generateCSPHeader(array $directives): string
    {
        $headerParts = [];
        
        foreach ($directives as $directive => $sources) {
            if (empty($sources)) {
                // Directive without sources (e.g., upgrade-insecure-requests)
                $headerParts[] = $directive;
            } else {
                // Directive with sources
                $sourcesString = implode(' ', $sources);
                $headerParts[] = "{$directive} {$sourcesString}";
            }
        }
        
        return implode('; ', $headerParts);
    }

    /**
     * Determine if nonce should be used for this request.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldUseNonce(Request $request): bool
    {
        // Use nonce for admin pages and sensitive operations
        $routeName = $request->route()?->getName();
        
        $nonceRoutes = [
            'admin.*',
            'users.*',
            'roles.*',
            'permissions.*',
            'system.*',
            'content.create',
            'content.edit',
        ];
        
        if ($routeName) {
            foreach ($nonceRoutes as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Generate a cryptographically secure nonce.
     *
     * @return string
     */
    private function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Get the current CSP nonce from the request.
     *
     * @param Request $request
     * @return string|null
     */
    public static function getNonce(Request $request): ?string
    {
        return $request->attributes->get('csp_nonce');
    }

    /**
     * Generate a CSP-compliant script tag with nonce.
     *
     * @param string $script
     * @param Request $request
     * @return string
     */
    public static function scriptWithNonce(string $script, Request $request): string
    {
        $nonce = self::getNonce($request);
        
        if ($nonce) {
            return "<script nonce=\"{$nonce}\">{$script}</script>";
        }
        
        return "<script>{$script}</script>";
    }

    /**
     * Generate a CSP-compliant style tag with nonce.
     *
     * @param string $style
     * @param Request $request
     * @return string
     */
    public static function styleWithNonce(string $style, Request $request): string
    {
        $nonce = self::getNonce($request);
        
        if ($nonce) {
            return "<style nonce=\"{$nonce}\">{$style}</style>";
        }
        
        return "<style>{$style}</style>";
    }

    /**
     * Validate and sanitize CSP directive sources.
     *
     * @param array $sources
     * @return array
     */
    private function sanitizeSources(array $sources): array
    {
        $sanitized = [];
        
        foreach ($sources as $source) {
            // Remove any potentially dangerous characters
            $source = preg_replace('/[^\w\-.:*\/\'\"\s]/', '', $source);
            
            // Validate source format
            if ($this->isValidCSPSource($source)) {
                $sanitized[] = $source;
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate CSP source format.
     *
     * @param string $source
     * @return bool
     */
    private function isValidCSPSource(string $source): bool
    {
        // Allow keywords
        if (in_array($source, ["'self'", "'unsafe-inline'", "'unsafe-eval'", "'none'", "'strict-dynamic'"])) {
            return true;
        }
        
        // Allow data: and blob: schemes
        if (in_array($source, ['data:', 'blob:', 'https:', 'http:', 'ws:', 'wss:'])) {
            return true;
        }
        
        // Allow nonce sources
        if (preg_match('/^\s*\'nonce-[A-Za-z0-9+\/=]+\'\s*$/', $source)) {
            return true;
        }
        
        // Allow hash sources
        if (preg_match('/^\s*\'(sha256|sha384|sha512)-[A-Za-z0-9+\/=]+\'\s*$/', $source)) {
            return true;
        }
        
        // Allow valid URLs
        if (filter_var($source, FILTER_VALIDATE_URL) || preg_match('/^https?:\/\/[\w\-.]+(:\d+)?(\/.*)?$/', $source)) {
            return true;
        }
        
        // Allow wildcard domains
        if (preg_match('/^\*\.[\w\-.]+(:\d+)?$/', $source)) {
            return true;
        }
        
        return false;
    }
}
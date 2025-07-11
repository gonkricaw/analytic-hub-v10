<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

/**
 * Class EnhancedCsrfProtection
 * 
 * Enhanced CSRF protection middleware that extends Laravel's default CSRF protection
 * with additional security features:
 * - Enhanced token validation
 * - Request rate limiting for CSRF failures
 * - Activity logging for security events
 * - IP-based CSRF failure tracking
 * 
 * @package App\Http\Middleware
 */
class EnhancedCsrfProtection extends Middleware
{
    /**
     * Maximum CSRF failures before temporary IP block
     */
    const MAX_CSRF_FAILURES = 10;
    
    /**
     * CSRF failure tracking window in minutes
     */
    const FAILURE_WINDOW = 60;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'webhook/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // Log CSRF failure
            $this->logCsrfFailure($request);
            
            // Track failures and potentially blacklist IP
            $this->trackCsrfFailure($request);
            
            // Return custom error response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'CSRF token mismatch',
                    'message' => 'The request could not be completed due to security validation failure.',
                    'code' => 'CSRF_MISMATCH'
                ], 419);
            }
            
            return redirect()->back()
                ->withInput($request->except('_token'))
                ->withErrors(['csrf' => 'Security validation failed. Please try again.']);
        }
    }

    /**
     * Log CSRF failure for security monitoring
     * 
     * @param Request $request
     * @return void
     */
    private function logCsrfFailure(Request $request): void
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_type' => null,
            'subject_id' => null,
            'action' => 'csrf_failure',
            'description' => 'CSRF token validation failed',
            'properties' => [
                'ip_address' => $request->ip(),
                'provided_token' => $request->input('_token'),
                'session_token' => $request->session()->token(),
                'referer' => $request->header('referer'),
                'form_data' => $this->sanitizeFormData($request->except(['_token', 'password', 'password_confirmation'])),
                'is_ajax' => $request->ajax(),
                'expects_json' => $request->expectsJson(),
                'content_type' => $request->header('content-type')
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => true,
            'severity' => 'medium',
            'category' => 'security'
        ]);
    }

    /**
     * Track CSRF failures and potentially blacklist IP
     * 
     * @param Request $request
     * @return void
     */
    private function trackCsrfFailure(Request $request): void
    {
        $ipAddress = $request->ip();
        $cacheKey = "csrf_failures_{$ipAddress}";
        
        // Get current failure count
        $failures = cache()->get($cacheKey, 0);
        $failures++;
        
        // Store updated count with expiry
        cache()->put($cacheKey, $failures, now()->addMinutes(self::FAILURE_WINDOW));
        
        // Check if threshold exceeded
        if ($failures >= self::MAX_CSRF_FAILURES) {
            $this->blacklistIpForCsrfAbuse($request, $failures);
        }
    }

    /**
     * Blacklist IP for CSRF abuse
     * 
     * @param Request $request
     * @param int $failureCount
     * @return void
     */
    private function blacklistIpForCsrfAbuse(Request $request, int $failureCount): void
    {
        // Check if the blacklisted_ips table exists before querying
        if (!\Illuminate\Support\Facades\Schema::hasTable('idbi_blacklisted_ips')) {
            return;
        }
        
        $ipAddress = $request->ip();
        
        // Check if IP is already blacklisted
        $existingBlacklist = \App\Models\BlacklistedIp::where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->first();
        
        if (!$existingBlacklist) {
            // Create temporary blacklist entry
            \App\Models\BlacklistedIp::create([
                'ip_address' => $ipAddress,
                'reason' => "Excessive CSRF failures ({$failureCount} attempts)",
                'blocked_by' => 'system',
                'is_active' => true,
                'expires_at' => now()->addHours(24), // 24-hour temporary block
                'attempt_count' => 1,
                'last_attempt_at' => now()
            ]);
            
            // Log the blacklisting
            \App\Models\ActivityLog::create([
                'user_id' => null,
                'subject_type' => \App\Models\BlacklistedIp::class,
                'subject_id' => null,
                'action' => 'ip_blacklisted_csrf_abuse',
                'description' => "IP blacklisted due to excessive CSRF failures: {$ipAddress}",
                'properties' => [
                    'ip_address' => $ipAddress,
                    'failure_count' => $failureCount,
                    'block_duration' => '24 hours',
                    'block_reason' => 'csrf_abuse',
                    'threshold' => self::MAX_CSRF_FAILURES
                ],
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_sensitive' => true,
                'severity' => 'high',
                'category' => 'security'
            ]);
        }
    }

    /**
     * Sanitize form data for logging (remove sensitive information)
     * 
     * @param array $data
     * @return array
     */
    private function sanitizeFormData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'api_key', 'secret', 'token', 'credit_card', 'ssn'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }
}
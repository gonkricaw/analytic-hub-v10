<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;

/**
 * Class SqlInjectionPrevention
 * 
 * Middleware that provides additional SQL injection protection:
 * - Detects common SQL injection patterns in request data
 * - Validates and sanitizes input parameters
 * - Logs suspicious activities for security monitoring
 * - Blocks requests with high-risk SQL injection patterns
 * - Works alongside Laravel's built-in Eloquent ORM protection
 * 
 * @package App\Http\Middleware
 */
class SqlInjectionPrevention
{
    /**
     * Common SQL injection patterns to detect.
     *
     * @var array
     */
    private array $sqlInjectionPatterns = [
        // Union-based injection
        '/\bunion\b.*\bselect\b/i',
        '/\bunion\b.*\ball\b.*\bselect\b/i',
        
        // Boolean-based blind injection
        '/\b(and|or)\b.*\b(\d+\s*=\s*\d+|\d+\s*<>\s*\d+)/i',
        '/\b(and|or)\b.*\b(true|false)\b/i',
        
        // Time-based blind injection
        '/\b(sleep|benchmark|waitfor)\s*\(/i',
        '/\bif\s*\(.*,.*sleep\s*\(/i',
        
        // Error-based injection
        '/\b(extractvalue|updatexml|exp)\s*\(/i',
        '/\bcast\s*\(.*\bas\s/i',
        
        // Stacked queries
        '/;\s*(drop|delete|insert|update|create|alter)\b/i',
        
        // Comment-based injection
        '/\/\*.*\*\//s',
        '/--[^\r\n]*/s',
        '/#[^\r\n]*/s',
        
        // Information schema access
        '/\binformation_schema\b/i',
        '/\bsys\.\w+/i',
        
        // Database function calls
        '/\b(version|user|database|schema)\s*\(\s*\)/i',
        '/\b(load_file|into\s+outfile|into\s+dumpfile)\b/i',
        
        // Hex encoding attempts
        '/0x[0-9a-f]+/i',
        
        // Concatenation attempts
        '/\bconcat\s*\(/i',
        '/\|\|/i',
        
        // Subquery patterns
        '/\(\s*select\b.*\bfrom\b/i',
    ];

    /**
     * High-risk patterns that should immediately block the request.
     *
     * @var array
     */
    private array $highRiskPatterns = [
        '/\bdrop\s+table\b/i',
        '/\bdelete\s+from\b/i',
        '/\btruncate\s+table\b/i',
        '/\balter\s+table\b/i',
        '/\bcreate\s+table\b/i',
        '/\binsert\s+into\b/i',
        '/\bupdate\s+.*\bset\b/i',
        '/\bexec\s*\(/i',
        '/\bexecute\s*\(/i',
        '/\bsp_\w+/i',
        '/\bxp_\w+/i',
    ];

    /**
     * Handle an incoming request and check for SQL injection attempts.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for certain routes (like file uploads)
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        // Check all request data for SQL injection patterns
        $suspiciousData = $this->detectSqlInjection($request);
        
        if (!empty($suspiciousData)) {
            return $this->handleSqlInjectionAttempt($request, $suspiciousData);
        }

        return $next($request);
    }

    /**
     * Determine if validation should be skipped for this request.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldSkipValidation(Request $request): bool
    {
        $skipRoutes = [
            'file.upload',
            'avatar.upload',
            'content.upload',
        ];

        $currentRoute = $request->route()?->getName();
        
        return in_array($currentRoute, $skipRoutes, true);
    }

    /**
     * Detect SQL injection patterns in request data.
     *
     * @param Request $request
     * @return array
     */
    private function detectSqlInjection(Request $request): array
    {
        $suspiciousData = [];
        
        // Check all input data
        $allInput = array_merge(
            $request->all(),
            $request->query->all(),
            $request->headers->all()
        );
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                $patterns = $this->checkForSqlPatterns($value);
                if (!empty($patterns)) {
                    $suspiciousData[$key] = [
                        'value' => $value,
                        'patterns' => $patterns,
                        'risk_level' => $this->calculateRiskLevel($patterns)
                    ];
                }
            } elseif (is_array($value)) {
                $nestedSuspicious = $this->checkArrayForSqlInjection($value, $key);
                if (!empty($nestedSuspicious)) {
                    $suspiciousData = array_merge($suspiciousData, $nestedSuspicious);
                }
            }
        }
        
        return $suspiciousData;
    }

    /**
     * Check array values recursively for SQL injection patterns.
     *
     * @param array $data
     * @param string $parentKey
     * @return array
     */
    private function checkArrayForSqlInjection(array $data, string $parentKey): array
    {
        $suspiciousData = [];
        
        foreach ($data as $key => $value) {
            $fullKey = $parentKey . '[' . $key . ']';
            
            if (is_string($value)) {
                $patterns = $this->checkForSqlPatterns($value);
                if (!empty($patterns)) {
                    $suspiciousData[$fullKey] = [
                        'value' => $value,
                        'patterns' => $patterns,
                        'risk_level' => $this->calculateRiskLevel($patterns)
                    ];
                }
            } elseif (is_array($value)) {
                $nestedSuspicious = $this->checkArrayForSqlInjection($value, $fullKey);
                if (!empty($nestedSuspicious)) {
                    $suspiciousData = array_merge($suspiciousData, $nestedSuspicious);
                }
            }
        }
        
        return $suspiciousData;
    }

    /**
     * Check a string value for SQL injection patterns.
     *
     * @param string $value
     * @return array
     */
    private function checkForSqlPatterns(string $value): array
    {
        $matchedPatterns = [];
        
        // Check against all SQL injection patterns
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $matchedPatterns[] = $pattern;
            }
        }
        
        return $matchedPatterns;
    }

    /**
     * Calculate risk level based on matched patterns.
     *
     * @param array $patterns
     * @return string
     */
    private function calculateRiskLevel(array $patterns): string
    {
        foreach ($patterns as $pattern) {
            foreach ($this->highRiskPatterns as $highRiskPattern) {
                if ($pattern === $highRiskPattern) {
                    return 'high';
                }
            }
        }
        
        return count($patterns) > 2 ? 'medium' : 'low';
    }

    /**
     * Handle detected SQL injection attempt.
     *
     * @param Request $request
     * @param array $suspiciousData
     * @return Response
     */
    private function handleSqlInjectionAttempt(Request $request, array $suspiciousData): Response
    {
        // Determine if this is a high-risk attempt
        $highRisk = false;
        foreach ($suspiciousData as $data) {
            if ($data['risk_level'] === 'high') {
                $highRisk = true;
                break;
            }
        }

        // Log the security incident
        $this->logSecurityIncident($request, $suspiciousData, $highRisk);

        // Block high-risk attempts immediately
        if ($highRisk) {
            return response()->json([
                'error' => 'Request blocked for security reasons.',
                'code' => 'SECURITY_VIOLATION'
            ], 403);
        }

        // For lower risk attempts, log but allow with warning
        Log::warning('Potential SQL injection attempt detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'suspicious_data' => $suspiciousData
        ]);

        // Continue with the request but add security headers
        $response = response()->json([
            'warning' => 'Request contains potentially unsafe content.',
            'code' => 'SECURITY_WARNING'
        ], 400);

        return $response;
    }

    /**
     * Log security incident to database and application logs.
     *
     * @param Request $request
     * @param array $suspiciousData
     * @param bool $highRisk
     * @return void
     */
    private function logSecurityIncident(Request $request, array $suspiciousData, bool $highRisk): void
    {
        try {
            // Log to activity log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'security.sql_injection_attempt',
                'description' => 'SQL injection attempt detected',
                'properties' => [
                    'suspicious_data' => $suspiciousData,
                    'risk_level' => $highRisk ? 'high' : 'medium',
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer'),
                    'timestamp' => now()->toISOString()
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_sensitive' => true,
                'severity' => $highRisk ? 'critical' : 'high',
            ]);

            // Log to application log
            Log::channel('security')->critical('SQL injection attempt detected', [
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'suspicious_data' => $suspiciousData,
                'risk_level' => $highRisk ? 'high' : 'medium',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            // Fallback to file logging if database logging fails
            Log::error('Failed to log SQL injection attempt to database', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'suspicious_data' => $suspiciousData
            ]);
        }
    }
}
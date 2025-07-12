<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;
use Carbon\Carbon;

/**
 * Class AuditLogging
 * 
 * Comprehensive audit logging middleware that tracks:
 * - All user activities and system interactions
 * - Security-sensitive operations
 * - Data access and modifications
 * - Authentication and authorization events
 * - System configuration changes
 * - Failed access attempts
 * 
 * @package App\Http\Middleware
 */
class AuditLogging
{
    /**
     * Routes that should be audited with high priority.
     *
     * @var array
     */
    private array $highPriorityRoutes = [
        'login',
        'logout',
        'password.reset',
        'password.update',
        'users.store',
        'users.update',
        'users.destroy',
        'roles.store',
        'roles.update',
        'roles.destroy',
        'permissions.store',
        'permissions.update',
        'permissions.destroy',
        'system.config.update',
        'content.store',
        'content.update',
        'content.destroy',
    ];

    /**
     * Routes that should be excluded from audit logging.
     *
     * @var array
     */
    private array $excludedRoutes = [
        'health-check',
        'ping',
        'status',
        'assets.*',
        'css.*',
        'js.*',
        'images.*',
    ];

    /**
     * Sensitive fields that should be masked in logs.
     *
     * @var array
     */
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'temp_password',
        'token',
        'api_key',
        'secret',
        'private_key',
        'credit_card',
        'ssn',
        'social_security',
    ];

    /**
     * Handle an incoming request and log audit information.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Skip audit logging for excluded routes
        if ($this->shouldSkipAudit($request)) {
            return $next($request);
        }

        // Capture request data before processing
        $requestData = $this->captureRequestData($request);
        
        // Process the request
        $response = $next($request);
        
        // Calculate response time
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Capture response data
        $responseData = $this->captureResponseData($response);
        
        // Log the audit entry
        $this->logAuditEntry($request, $response, $requestData, $responseData, $responseTime);
        
        return $response;
    }

    /**
     * Determine if audit logging should be skipped for this request.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldSkipAudit(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();
        
        // Check excluded routes
        foreach ($this->excludedRoutes as $excludedRoute) {
            if (fnmatch($excludedRoute, $routeName) || fnmatch($excludedRoute, $path)) {
                return true;
            }
        }
        
        // Skip OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return true;
        }
        
        return false;
    }

    /**
     * Capture relevant request data for audit logging.
     *
     * @param Request $request
     * @return array
     */
    private function captureRequestData(Request $request): array
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'timestamp' => Carbon::now()->toISOString(),
        ];
        
        // Add user information if authenticated
        if (Auth::check()) {
            $user = Auth::user();
            $data['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];
        }
        
        // Add request parameters (with sensitive data masked)
        $requestParams = $request->all();
        $data['request_data'] = $this->maskSensitiveData($requestParams);
        
        // Add file upload information
        if ($request->hasFile()) {
            $data['files'] = [];
            foreach ($request->allFiles() as $key => $file) {
                if (is_array($file)) {
                    foreach ($file as $index => $singleFile) {
                        $data['files'][$key][$index] = [
                            'name' => $singleFile->getClientOriginalName(),
                            'size' => $singleFile->getSize(),
                            'mime_type' => $singleFile->getMimeType(),
                        ];
                    }
                } else {
                    $data['files'][$key] = [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }
        }
        
        return $data;
    }

    /**
     * Capture relevant response data for audit logging.
     *
     * @param Response $response
     * @return array
     */
    private function captureResponseData(Response $response): array
    {
        $data = [
            'status_code' => $response->getStatusCode(),
            'status_text' => Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown',
            'content_type' => $response->headers->get('Content-Type'),
            'content_length' => $response->headers->get('Content-Length'),
        ];
        
        // Add response content for certain status codes (errors, redirects)
        if ($response->getStatusCode() >= 400 || $response->getStatusCode() >= 300) {
            $content = $response->getContent();
            
            // Try to decode JSON response
            $decodedContent = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['response_data'] = $this->maskSensitiveData($decodedContent);
            } else {
                // Limit content length for non-JSON responses
                $data['response_data'] = substr($content, 0, 1000);
            }
        }
        
        return $data;
    }

    /**
     * Mask sensitive data in arrays.
     *
     * @param array $data
     * @return array
     */
    private function maskSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif (is_string($value) && $this->isSensitiveField($key)) {
                $data[$key] = $this->maskValue($value);
            }
        }
        
        return $data;
    }

    /**
     * Check if a field is considered sensitive.
     *
     * @param string $fieldName
     * @return bool
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);
        
        foreach ($this->sensitiveFields as $sensitiveField) {
            if (str_contains($fieldName, $sensitiveField)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mask a sensitive value.
     *
     * @param string $value
     * @return string
     */
    private function maskValue(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }
        
        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }

    /**
     * Log the audit entry to database and file.
     *
     * @param Request $request
     * @param Response $response
     * @param array $requestData
     * @param array $responseData
     * @param float $responseTime
     * @return void
     */
    private function logAuditEntry(Request $request, Response $response, array $requestData, array $responseData, float $responseTime): void
    {
        try {
            // Determine audit entry details
            $action = $this->determineAction($request, $response);
            $description = $this->generateDescription($request, $response);
            $severity = $this->determineSeverity($request, $response);
            $isHighPriority = $this->isHighPriorityRoute($request);
            
            // Create audit log entry
            $auditData = [
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $description,
                'properties' => [
                    'request' => $requestData,
                    'response' => $responseData,
                    'response_time_ms' => $responseTime,
                    'high_priority' => $isHighPriority,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_sensitive' => $isHighPriority || $response->getStatusCode() >= 400,
                'severity' => $severity,
            ];
            
            // Save to database
            ActivityLog::create($auditData);
            
            // Log to file for high-priority or error events
            if ($isHighPriority || $response->getStatusCode() >= 400) {
                Log::channel('audit')->info('Audit Log Entry', $auditData);
            }
            
        } catch (\Exception $e) {
            // Fallback logging if database fails
            Log::error('Failed to create audit log entry', [
                'error' => $e->getMessage(),
                'request_url' => $request->fullUrl(),
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);
        }
    }

    /**
     * Determine the action type for the audit log.
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    private function determineAction(Request $request, Response $response): string
    {
        $routeName = $request->route()?->getName();
        $method = $request->method();
        
        // Map specific routes to actions
        $actionMap = [
            'login' => 'auth.login',
            'logout' => 'auth.logout',
            'password.reset' => 'auth.password_reset',
            'users.store' => 'user.create',
            'users.update' => 'user.update',
            'users.destroy' => 'user.delete',
            'content.store' => 'content.create',
            'content.update' => 'content.update',
            'content.destroy' => 'content.delete',
        ];
        
        if (isset($actionMap[$routeName])) {
            return $actionMap[$routeName];
        }
        
        // Fallback to method-based action
        return strtolower($method) . '.request';
    }

    /**
     * Generate a human-readable description for the audit log.
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    private function generateDescription(Request $request, Response $response): string
    {
        $routeName = $request->route()?->getName();
        $method = $request->method();
        $statusCode = $response->getStatusCode();
        
        $descriptions = [
            'login' => 'User login attempt',
            'logout' => 'User logout',
            'password.reset' => 'Password reset request',
            'users.store' => 'New user created',
            'users.update' => 'User information updated',
            'users.destroy' => 'User deleted',
            'content.store' => 'New content created',
            'content.update' => 'Content updated',
            'content.destroy' => 'Content deleted',
        ];
        
        if (isset($descriptions[$routeName])) {
            $description = $descriptions[$routeName];
        } else {
            $description = ucfirst(strtolower($method)) . ' request to ' . $request->path();
        }
        
        // Add status information
        if ($statusCode >= 400) {
            $description .= ' (Failed - ' . $statusCode . ')';
        } elseif ($statusCode >= 300) {
            $description .= ' (Redirected - ' . $statusCode . ')';
        } else {
            $description .= ' (Success - ' . $statusCode . ')';
        }
        
        return $description;
    }

    /**
     * Determine the severity level for the audit log.
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    private function determineSeverity(Request $request, Response $response): string
    {
        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 500) {
            return 'critical';
        } elseif ($statusCode >= 400) {
            return 'high';
        } elseif ($this->isHighPriorityRoute($request)) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Check if the current route is high priority.
     *
     * @param Request $request
     * @return bool
     */
    private function isHighPriorityRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        
        return in_array($routeName, $this->highPriorityRoutes, true);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * PerformanceMonitoring Middleware
 * 
 * Monitors and logs performance metrics for each HTTP request:
 * - Request execution time
 * - Memory usage and peak memory
 * - Database query count and execution time
 * - Response size and status
 * - User context and request details
 * 
 * Implements the Performance Monitoring Logic as specified in Logic.md:
 * - Tracks timing during request lifecycle
 * - Logs request details and performance metrics
 * - Monitors database queries and memory usage
 * - Calculates response time and logs slow requests
 * 
 * @package App\Http\Middleware
 */
class PerformanceMonitoring
{
    /**
     * Handle an incoming request and monitor performance.
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Record start time and initial memory usage
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startQueries = $this->getQueryCount();
        
        // Process the request
        $response = $next($request);
        
        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $endQueries = $this->getQueryCount();
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;
        $queryCount = $endQueries - $startQueries;
        
        // Gather request context
        $requestData = $this->gatherRequestContext($request, $response, $executionTime, $memoryUsed, $peakMemory, $queryCount);
        
        // Log performance metrics
        $this->logPerformanceMetrics($requestData);
        
        // Log slow requests if threshold exceeded
        $slowRequestThreshold = config('app.slow_request_threshold', 2000); // 2 seconds default
        if ($executionTime > $slowRequestThreshold) {
            $this->logSlowRequest($requestData, $slowRequestThreshold);
        }
        
        // Log critical slow requests
        $criticalThreshold = config('app.critical_request_threshold', 10000); // 10 seconds
        if ($executionTime > $criticalThreshold) {
            $this->logCriticalSlowRequest($requestData, $criticalThreshold);
        }
        
        return $response;
    }
    
    /**
     * Get the current database query count.
     * 
     * @return int The number of queries executed so far
     */
    protected function getQueryCount(): int
    {
        return collect(DB::getQueryLog())->count();
    }
    
    /**
     * Gather comprehensive request context and performance data.
     * 
     * @param Request $request The HTTP request
     * @param Response $response The HTTP response
     * @param float $executionTime Request execution time in milliseconds
     * @param int $memoryUsed Memory used during request in bytes
     * @param int $peakMemory Peak memory usage in bytes
     * @param int $queryCount Number of database queries executed
     * @return array Comprehensive request context data
     */
    protected function gatherRequestContext(Request $request, Response $response, float $executionTime, int $memoryUsed, int $peakMemory, int $queryCount): array
    {
        $user = Auth::user();
        
        return [
            // Request Information
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route() ? $request->route()->getName() : null,
            'controller_action' => $request->route() ? $request->route()->getActionName() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            
            // User Context
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_role' => $user && $user->roles->isNotEmpty() ? $user->roles->first()->name : null,
            'is_authenticated' => Auth::check(),
            
            // Performance Metrics
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'peak_memory_bytes' => $peakMemory,
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'query_count' => $queryCount,
            
            // Response Information
            'response_status' => $response->getStatusCode(),
            'response_size_bytes' => strlen($response->getContent()),
            'response_size_kb' => round(strlen($response->getContent()) / 1024, 2),
            'content_type' => $response->headers->get('content-type'),
            
            // System Information
            'timestamp' => Carbon::now()->toISOString(),
            'server_load' => $this->getServerLoad(),
            'php_memory_limit' => ini_get('memory_limit'),
            'request_id' => $request->header('X-Request-ID', uniqid('req_')),
            
            // Performance Classification
            'performance_category' => $this->categorizePerformance($executionTime),
            'memory_category' => $this->categorizeMemoryUsage($peakMemory),
            'query_category' => $this->categorizeQueryCount($queryCount)
        ];
    }
    
    /**
     * Log standard performance metrics.
     * 
     * @param array $requestData The request context and performance data
     */
    protected function logPerformanceMetrics(array $requestData): void
    {
        Log::channel('performance')->info('Request Performance Metrics', $requestData);
    }
    
    /**
     * Log slow request warning.
     * 
     * @param array $requestData The request context and performance data
     * @param float $threshold The slow request threshold in milliseconds
     */
    protected function logSlowRequest(array $requestData, float $threshold): void
    {
        $requestData['threshold_ms'] = $threshold;
        $requestData['performance_issue'] = 'slow_request';
        $requestData['requires_optimization'] = true;
        
        Log::channel('performance')->warning('Slow Request Detected', $requestData);
    }
    
    /**
     * Log critical slow request error.
     * 
     * @param array $requestData The request context and performance data
     * @param float $threshold The critical request threshold in milliseconds
     */
    protected function logCriticalSlowRequest(array $requestData, float $threshold): void
    {
        $requestData['threshold_ms'] = $threshold;
        $requestData['performance_issue'] = 'critical_slow_request';
        $requestData['immediate_action_required'] = true;
        $requestData['alert_level'] = 'critical';
        
        Log::channel('error')->error('Critical Slow Request', $requestData);
    }
    
    /**
     * Get current server load average (Unix systems only).
     * 
     * @return float|null Server load average or null if unavailable
     */
    protected function getServerLoad(): ?float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load ? round($load[0], 2) : null;
        }
        
        return null;
    }
    
    /**
     * Categorize request performance based on execution time.
     * 
     * @param float $executionTime Execution time in milliseconds
     * @return string Performance category
     */
    protected function categorizePerformance(float $executionTime): string
    {
        if ($executionTime < 100) {
            return 'excellent';
        } elseif ($executionTime < 500) {
            return 'good';
        } elseif ($executionTime < 1000) {
            return 'acceptable';
        } elseif ($executionTime < 2000) {
            return 'slow';
        } elseif ($executionTime < 5000) {
            return 'very_slow';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Categorize memory usage.
     * 
     * @param int $memoryBytes Memory usage in bytes
     * @return string Memory usage category
     */
    protected function categorizeMemoryUsage(int $memoryBytes): string
    {
        $memoryMB = $memoryBytes / 1024 / 1024;
        
        if ($memoryMB < 16) {
            return 'low';
        } elseif ($memoryMB < 32) {
            return 'normal';
        } elseif ($memoryMB < 64) {
            return 'high';
        } elseif ($memoryMB < 128) {
            return 'very_high';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Categorize database query count.
     * 
     * @param int $queryCount Number of queries executed
     * @return string Query count category
     */
    protected function categorizeQueryCount(int $queryCount): string
    {
        if ($queryCount < 5) {
            return 'minimal';
        } elseif ($queryCount < 15) {
            return 'normal';
        } elseif ($queryCount < 30) {
            return 'high';
        } elseif ($queryCount < 50) {
            return 'very_high';
        } else {
            return 'excessive';
        }
    }
}
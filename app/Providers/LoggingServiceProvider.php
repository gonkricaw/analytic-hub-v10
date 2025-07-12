<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * LoggingServiceProvider
 * 
 * Provides comprehensive logging services for the Analytics Hub application:
 * - Database query logging with performance tracking
 * - Slow query detection and alerting
 * - Performance monitoring and metrics
 * - Error logging and categorization
 * - System health monitoring
 * 
 * @package App\Providers
 */
class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     * 
     * Sets up database query logging, performance monitoring,
     * and other logging services when the application boots.
     */
    public function boot(): void
    {
        // Enable database query logging if configured
        if (config('app.log_queries', false)) {
            $this->enableQueryLogging();
        }

        // Set up performance monitoring
        $this->enablePerformanceMonitoring();

        // Set up error logging enhancements
        $this->enhanceErrorLogging();
    }

    /**
     * Enable comprehensive database query logging.
     * 
     * Logs all database queries with execution time, bindings,
     * and performance metrics. Identifies slow queries for optimization.
     */
    protected function enableQueryLogging(): void
    {
        DB::listen(function (QueryExecuted $query) {
            $executionTime = $query->time;
            $sql = $query->sql;
            $bindings = $query->bindings;
            $connection = $query->connectionName;
            
            // Replace bindings in SQL for better readability
            $formattedSql = $this->formatSqlWithBindings($sql, $bindings);
            
            // Log basic query information
            Log::channel('query')->info('Database Query Executed', [
                'sql' => $formattedSql,
                'execution_time_ms' => $executionTime,
                'connection' => $connection,
                'bindings_count' => count($bindings),
                'timestamp' => Carbon::now()->toISOString(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ]);
            
            // Log slow queries separately for performance analysis
            $slowQueryThreshold = config('app.slow_query_threshold', 1000); // 1 second default
            if ($executionTime > $slowQueryThreshold) {
                Log::channel('performance')->warning('Slow Query Detected', [
                    'sql' => $formattedSql,
                    'execution_time_ms' => $executionTime,
                    'threshold_ms' => $slowQueryThreshold,
                    'connection' => $connection,
                    'performance_impact' => 'high',
                    'requires_optimization' => true,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }
            
            // Log extremely slow queries as errors
            $criticalThreshold = config('app.critical_query_threshold', 5000); // 5 seconds
            if ($executionTime > $criticalThreshold) {
                Log::channel('error')->error('Critical Slow Query', [
                    'sql' => $formattedSql,
                    'execution_time_ms' => $executionTime,
                    'threshold_ms' => $criticalThreshold,
                    'connection' => $connection,
                    'performance_impact' => 'critical',
                    'immediate_action_required' => true,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }
        });
    }

    /**
     * Enable performance monitoring for requests.
     * 
     * Tracks request execution time, memory usage, and other
     * performance metrics for system optimization.
     */
    protected function enablePerformanceMonitoring(): void
    {
        // This will be handled by middleware, but we can set up
        // additional performance logging here if needed
        
        // Log memory usage periodically
        if (config('app.log_memory_usage', false)) {
            register_shutdown_function(function () {
                $memoryUsage = memory_get_usage(true);
                $peakMemory = memory_get_peak_usage(true);
                
                Log::channel('performance')->info('Request Memory Usage', [
                    'memory_usage_bytes' => $memoryUsage,
                    'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                    'peak_memory_bytes' => $peakMemory,
                    'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            });
        }
    }

    /**
     * Enhance error logging with additional context.
     * 
     * Adds request context, user information, and system state
     * to error logs for better debugging and monitoring.
     */
    protected function enhanceErrorLogging(): void
    {
        // Set up custom error handler for enhanced logging
        set_error_handler(function ($severity, $message, $file, $line) {
            // Only log errors that are being reported
            if (!(error_reporting() & $severity)) {
                return false;
            }
            
            $errorTypes = [
                E_ERROR => 'ERROR',
                E_WARNING => 'WARNING',
                E_PARSE => 'PARSE',
                E_NOTICE => 'NOTICE',
                E_CORE_ERROR => 'CORE_ERROR',
                E_CORE_WARNING => 'CORE_WARNING',
                E_COMPILE_ERROR => 'COMPILE_ERROR',
                E_COMPILE_WARNING => 'COMPILE_WARNING',
                E_USER_ERROR => 'USER_ERROR',
                E_USER_WARNING => 'USER_WARNING',
                E_USER_NOTICE => 'USER_NOTICE',
                E_STRICT => 'STRICT',
                E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
                E_DEPRECATED => 'DEPRECATED',
                E_USER_DEPRECATED => 'USER_DEPRECATED'
            ];
            
            $errorType = $errorTypes[$severity] ?? 'UNKNOWN';
            
            Log::channel('error')->error('PHP Error Detected', [
                'error_type' => $errorType,
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'timestamp' => Carbon::now()->toISOString(),
                'memory_usage' => memory_get_usage(true),
                'request_uri' => request()->getRequestUri() ?? 'N/A',
                'user_agent' => request()->userAgent() ?? 'N/A'
            ]);
            
            // Don't prevent the default error handler from running
            return false;
        });
    }

    /**
     * Format SQL query with bindings for better readability.
     * 
     * @param string $sql The SQL query
     * @param array $bindings The query bindings
     * @return string Formatted SQL with bindings replaced
     */
    protected function formatSqlWithBindings(string $sql, array $bindings): string
    {
        if (empty($bindings)) {
            return $sql;
        }
        
        $formattedSql = $sql;
        
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'$binding'" : $binding;
            $formattedSql = preg_replace('/\?/', $value, $formattedSql, 1);
        }
        
        return $formattedSql;
    }
}
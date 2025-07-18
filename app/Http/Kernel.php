<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Class Kernel
 * 
 * HTTP Kernel that defines middleware groups and route middleware
 * for the application's request handling pipeline.
 * 
 * @package App\Http
 */
class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\CheckBlacklistedIp::class,
        \App\Http\Middleware\HttpsEnforcement::class,
        \App\Http\Middleware\SecurityHeaders::class,
        \App\Http\Middleware\SqlInjectionPrevention::class,
        \App\Http\Middleware\ContentSecurityPolicy::class,
        \App\Http\Middleware\AuditLogging::class,
        \App\Http\Middleware\PerformanceMonitoring::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\EnhancedCsrfProtection::class, // Custom enhanced CSRF protection
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        
        // Custom Authentication Middleware
        'auth.user' => \App\Http\Middleware\AuthenticateUser::class,
        'check.status' => \App\Http\Middleware\CheckUserStatus::class,
        'blacklist.check' => \App\Http\Middleware\CheckBlacklistedIp::class,
        'csrf.enhanced' => \App\Http\Middleware\EnhancedCsrfProtection::class,
        
        // Role & Permission Middleware
        'role' => \App\Http\Middleware\CheckRole::class,
        'permission' => \App\Http\Middleware\CheckPermission::class,
        'menu.access' => \App\Http\Middleware\CheckMenuAccess::class,
        
        // Additional Security & Compliance Middleware
        'terms.check' => \App\Http\Middleware\CheckTermsAcceptance::class,
        'password.expiry' => \App\Http\Middleware\CheckPasswordExpiry::class,
        'activity.log' => \App\Http\Middleware\ActivityLogging::class,
        'rate.limit' => \App\Http\Middleware\RateLimiting::class,
        
        // Analytics & Tracking Middleware
        'track.visits' => \App\Http\Middleware\TrackContentVisits::class,
        'email.tracking' => \App\Http\Middleware\EmailTrackingMiddleware::class,
        
        // Security Middleware
        'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        'https.enforcement' => \App\Http\Middleware\HttpsEnforcement::class,
        'sql.injection.prevention' => \App\Http\Middleware\SqlInjectionPrevention::class,
        'audit.logging' => \App\Http\Middleware\AuditLogging::class,
        'content.security.policy' => \App\Http\Middleware\ContentSecurityPolicy::class,
        
        // Performance & Monitoring Middleware
        'performance.monitoring' => \App\Http\Middleware\PerformanceMonitoring::class,
    ];
}
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware aliases
        $middleware->alias([
            'auth.user' => \App\Http\Middleware\AuthenticateUser::class,
            'check.status' => \App\Http\Middleware\CheckUserStatus::class,
            'blacklist.check' => \App\Http\Middleware\CheckBlacklistedIp::class,
            'csrf.enhanced' => \App\Http\Middleware\EnhancedCsrfProtection::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'menu.access' => \App\Http\Middleware\CheckMenuAccess::class,
            'terms.check' => \App\Http\Middleware\CheckTermsAcceptance::class,
            'password.expiry' => \App\Http\Middleware\CheckPasswordExpiry::class,
            'activity.log' => \App\Http\Middleware\ActivityLogging::class,
            'rate.limit' => \App\Http\Middleware\RateLimiting::class,
            'track.visits' => \App\Http\Middleware\TrackContentVisits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Content;
use App\Models\SystemConfig;
use App\Observers\UserObserver;
use App\Observers\RoleObserver;
use App\Observers\PermissionObserver;
use App\Observers\ContentObserver;
use App\Observers\SystemConfigObserver;
use App\View\Composers\MenuComposer;

/**
 * Class AppServiceProvider
 * 
 * Main application service provider for registering services and observers.
 * Handles model observers registration for activity logging and audit trail.
 * 
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * 
     * Registers model observers for activity logging and audit trail.
     * These observers track model events and log them to ActivityLog model.
     * Also registers view composers for menu data injection.
     */
    public function boot(): void
    {
        // Register model observers for activity logging
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        Content::observe(ContentObserver::class);
        SystemConfig::observe(SystemConfigObserver::class);
        
        // Register view composers
        View::composer([
            'layouts.admin',
            'admin.*',
            'layouts.app'
        ], MenuComposer::class);
    }
}

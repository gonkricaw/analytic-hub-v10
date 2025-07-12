<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

/**
 * Class BroadcastServiceProvider
 * 
 * Service provider that handles broadcasting authentication
 * and channel authorization for real-time features.
 * 
 * This provider is responsible for:
 * - Registering broadcast authentication routes
 * - Defining channel authorization logic
 * - Setting up private channel access control
 * 
 * @package App\Providers
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered,
     * meaning you have access to all other services that have been registered.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register broadcast authentication routes
        // This creates the /broadcasting/auth endpoint for Laravel Echo
        Broadcast::routes();

        // Load channel authorization definitions
        require base_path('routes/channels.php');
    }
}
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register app-wide services or bindings here if needed
    }

    /**
     * Bootstrap any application services.
     */

public function boot()
{
    // Force HTTPS in production with Render-specific session fixes
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
        
        // Render-specific session configuration
        config([
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'none', // Critical for Render
            'session.domain' => null, // Let Render handle domain
            'session.driver' => 'database', // Persistent across containers
        ]);
    }
}
}

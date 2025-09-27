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
    // Force HTTPS in production
    //if ($this->app->environment('production')) {
    //    URL::forceScheme('https');
    //    
        // Ensure session cookies work with HTTPS
    //    config([
    //        'session.secure' => true,
    //        'session.http_only' => true,
    //        'session.same_site' => 'lax'
    //    ]);
    //}
}
}

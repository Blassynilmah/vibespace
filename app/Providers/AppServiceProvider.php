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
    // Temporarily disable HTTPS forcing for local development
     if ($this->app->environment('production')) {
         URL::forceScheme('https');
     }
}
}

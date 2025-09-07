<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        // Additional boot logic can go here if needed
    }
}

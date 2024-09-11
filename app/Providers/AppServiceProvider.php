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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
    protected function mapApiRoutes()
{
    Route::prefix('api')  // This ensures /api prefix is added to all routes in api.php
         ->middleware('api')  // API-specific middleware
         ->namespace($this->namespace)
         ->group(base_path('routes/api.php'));  // Loads routes from api.php
}

}


<?php

namespace ItDelmax\AuthCache\Providers;

use Illuminate\Support\ServiceProvider;
use ItDelmax\AuthCache\Services\TokenCacheService;

class AuthCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind TokenCacheService
        $this->app->singleton(TokenCacheService::class, function ($app) {
            return new TokenCacheService();
        });

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/auth-cache.php',
            'auth-cache'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register AuthServiceProvider
        $this->app->register(\ItDelmax\AuthCache\Providers\AuthServiceProvider::class);

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/auth-cache.php' => config_path('auth-cache.php'),
        ], 'auth-cache-config');

        // Load migrations if needed
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        // Load commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add console commands here if needed
            ]);
        }
    }
}
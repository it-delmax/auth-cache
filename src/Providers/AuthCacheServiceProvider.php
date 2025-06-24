<?php

namespace ItDelmax\AuthCache\Providers;

use Illuminate\Support\ServiceProvider;
use ItDelmax\AuthCache\Services\TokenCacheService;
use ItDelmax\AuthCache\Passwords\DelmaxPasswordBrokerManager;
use ItDelmax\AuthCache\Providers\AuthServiceProvider;
use ItDelmax\AuthCache\Providers\CacheEloquentUserProvider;
use Illuminate\Support\Facades\Auth;

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

    $this->app->singleton('auth.password', function ($app) {
      return new DelmaxPasswordBrokerManager($app);
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
    $this->app->register(AuthServiceProvider::class);

    Auth::provider('cache-eloquent', function ($app, array $config) {
      return new CacheEloquentUserProvider(
        $app['hash'],
        $config['model'],
        $app[TokenCacheService::class]
      );
    });

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

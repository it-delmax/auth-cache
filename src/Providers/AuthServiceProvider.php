<?php

namespace ItDelmax\AuthCache\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use ItDelmax\AuthCache\Models\User;
use ItDelmax\AuthCache\Passwords\CustomPasswordBrokerManager;
use ItDelmax\AuthCache\Services\TokenCacheService;
use ItDelmax\AuthCache\Providers\CacheEloquentUserProvider;

class AuthServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->singleton('auth.password', function ($app) {
      return new CustomPasswordBrokerManager($app);
    });
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // Register custom user provider with caching
    Auth::provider('cache-eloquent', function ($app, array $config) {
      return new CacheEloquentUserProvider(
        $app['hash'],
        $config['model'],
        $app[TokenCacheService::class]
      );
    });
  }
}

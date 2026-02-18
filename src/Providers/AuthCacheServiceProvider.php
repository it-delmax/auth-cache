<?php

namespace ItDelmax\AuthCache\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use ItDelmax\AuthCache\Services\TokenCacheService;
use ItDelmax\AuthCache\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Log;
use ItDelmax\AuthCache\Console\Commands\AuthCacheConfiguration;
use ItDelmax\AuthCache\Console\Commands\ClearAuthCache;
use ItDelmax\AuthCache\Console\Commands\InvalidateUserAuthCache;
use ItDelmax\AuthCache\Console\Commands\WarmAuthCache;
use ItDelmax\AuthCache\Console\Commands\WarmUserAuthCache;

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
    $this->app->register(AuthServiceProvider::class);

    // view namespace
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'auth-cache');


    $this->publishes([
      __DIR__ . '/../resources/views' => resource_path('views/vendor/auth-cache'),
    ], 'auth-cache-views');


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
        WarmAuthCache::class,
        ClearAuthCache::class,
        AuthCacheConfiguration::class,
        WarmUserAuthCache::class,
        InvalidateUserAuthCache::class
      ]);
      // ⏱️ Hook scheduling
      $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
        $schedule->job(new \ItDelmax\AuthCache\Jobs\InvalidateExpiredTokensJob())
          ->hourly()
          ->name('auth-cache:invalidate-expired-tokens')
          ->onOneServer();

        $schedule->job(new \ItDelmax\AuthCache\Jobs\WarmAllActiveTokensJob())
          ->everySixHours()
          ->name('auth-cache:warm-all-active-tokens')
          ->onOneServer();

        $schedule->job(new \ItDelmax\AuthCache\Jobs\WarmAllActiveUsersJob())
          ->everySixHours()
          ->name('auth-cache:warm-all-active-users')
          ->onOneServer();

        $schedule->call(function () {
          Log::info('🛠️ Daily auth-cache maintenance completed');
        })->dailyAt('03:00')
          ->name('auth-cache:daily-maintenance')
          ->onOneServer();
      });
    }
  }
}

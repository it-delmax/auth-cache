<?php

namespace ItDelmax\AuthCache\Console\Commands;

use Illuminate\Console\Command;
use ItDelmax\AuthCache\Services\TokenCacheService;

class WarmAuthCache extends Command
{
  protected $signature = 'auth-cache:warm
                            {--users : Warm all users cache}
                            {--api-users : Warm only users with API access}
                            {--tokens : Warm all active tokens}
                            {--all : Warm everything}';

  protected $description = 'Warm authentication cache';

  public function handle(TokenCacheService $cacheService)
  {
    $this->info('Starting cache warm-up...');

    if ($this->option('all') || $this->option('users')) {
      $this->info('Warming users cache...');
      $result = $cacheService->warmAllActiveUsers();
      $this->info("✓ Cached {$result['users']} users, {$result['failed']} failed");
    }

    if ($this->option('api-users')) {
      $this->info('Warming API users cache...');
      $result = $cacheService->warmApiUsers();
      $this->info("✓ Cached {$result['users']} API users, {$result['failed']} failed");
    }

    if ($this->option('all') || $this->option('tokens')) {
      $this->info('Warming tokens cache...');
      $count = $cacheService->warmAllActiveTokens();
      $this->info("✓ Cached {$count} tokens");
    }

    $this->info('Cache warm-up completed!');
  }
}

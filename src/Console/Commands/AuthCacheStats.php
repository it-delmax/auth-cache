<?php

namespace ItDelmax\AuthCache\Console\Commands;

use Illuminate\Console\Command;
use ItDelmax\AuthCache\Services\TokenCacheService;

class AuthCacheStats extends Command
{
  protected $signature = 'auth-cache:stats';
  protected $description = 'Show auth cache statistics';

  public function handle(TokenCacheService $cacheService)
  {
    $stats = $cacheService->getCacheStats();

    $this->table(
      ['Setting', 'Value'],
      [
        ['User TTL', $stats['user_ttl'] . ' seconds'],
        ['Token TTL', $stats['token_ttl'] . ' seconds'],
        ['API Access TTL', $stats['api_access_ttl'] . ' seconds'],
        ['API Slug TTL', $stats['api_slug_ttl'] . ' seconds'],
      ]
    );
  }
}

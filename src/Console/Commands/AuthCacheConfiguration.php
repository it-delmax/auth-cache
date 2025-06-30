<?php

namespace ItDelmax\AuthCache\Console\Commands;

use Illuminate\Console\Command;
use ItDelmax\AuthCache\Services\TokenCacheService;

class AuthCacheConfiguration extends Command
{
  protected $signature = 'auth-cache:config';
  protected $description = 'Show auth cache statistics';

  public function handle(): int
  {
    $cacheService = app(TokenCacheService::class);
    $stats = $cacheService->getCacheConfiguration();

    $this->info('📊 Cache Configuration:');
    $this->table(['Setting', 'Value'], [
      ['User TTL', $stats['user_ttl'] . ' seconds (' . round($stats['user_ttl'] / 3600, 2) . 'h)'],
      ['Token TTL', $stats['token_ttl'] . ' seconds (' . round($stats['token_ttl'] / 3600, 2) . 'h)'],
      ['API Access TTL', $stats['api_access_ttl'] . ' seconds (' . round($stats['api_access_ttl'] / 60, 2) . 'm)'],
      ['API Slug TTL', $stats['api_slug_ttl'] . ' seconds (' . round($stats['api_slug_ttl'] / 3600, 2) . 'h)'],
    ]);

    return self::SUCCESS;
  }
}

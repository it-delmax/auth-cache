<?php

namespace ItDelmax\AuthCache\Console\Commands;

use Illuminate\Console\Command;
use ItDelmax\AuthCache\Services\TokenCacheService;

class WarmUserAuthCache extends Command
{
  protected $signature = 'auth-cache:warm-user {userId : ID korisnika}';
  protected $description = 'Warm cache for specific user';

  public function handle(): int
  {
    $userId = (int) $this->argument('userId');
    $cacheService = app(TokenCacheService::class);

    if ($cacheService->warmUserCache($userId)) {
      $this->info("✅ User cache warmed for user: {$userId}");
      return self::SUCCESS;
    }

    $this->error("❌ Failed to warm cache for user: {$userId}");
    return self::FAILURE;
  }
}

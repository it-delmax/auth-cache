<?php

namespace ItDelmax\AuthCache\Console\Commands;

use Illuminate\Console\Command;
use ItDelmax\AuthCache\Services\TokenCacheService;

class InvalidateUserCache extends Command
{
  protected $signature = 'auth-cache:invalidate-user {userId : ID korisnika}';
  protected $description = 'Invalidate all cache for specific user';

  public function handle(): int
  {
    $userId = (int) $this->argument('userId');
    $cacheService = app(TokenCacheService::class);

    $cacheService->invalidateAllUserData($userId);
    $this->info("🧹 All cache invalidated for user: {$userId}");

    return self::SUCCESS;
  }
}

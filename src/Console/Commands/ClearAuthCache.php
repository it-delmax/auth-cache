<?php

namespace ItDelmax\AuthCache\Console\Commands;

use Illuminate\Console\Command;
use ItDelmax\AuthCache\Services\TokenCacheService;

class ClearAuthCache extends Command
{
  protected $signature = 'auth-cache:clear
                            {--user= : Clear specific user cache}
                            {--expired : Clear only expired tokens}';

  public function handle(TokenCacheService $cacheService)
  {
    if ($userId = $this->option('user')) {
      $cacheService->invalidateAllUserData($userId);
      $this->info("Cleared cache for user {$userId}");
    }

    if ($this->option('expired')) {
      $count = $cacheService->invalidateExpiredTokens();
      $this->info("Cleared {$count} expired tokens");
    }
  }
}

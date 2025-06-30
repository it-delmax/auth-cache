<?php

namespace ItDelmax\AuthCache\Jobs;

use ItDelmax\AuthCache\Services\TokenCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WarmAllActiveUsersJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $timeout = 600; // 10 minuta
  public $tries = 2;

  public function __construct()
  {
    $this->onQueue(config('auth-cache.queue', 'cache'));
  }

  public function handle(TokenCacheService $cacheService): void
  {
    try {
      $startTime = microtime(true);
      $result = $cacheService->warmAllActiveUsers();
      $duration = round((microtime(true) - $startTime) * 1000, 2);

      Log::info("🔥 All active users cache warmed", [
        'users_warmed' => $result['users'] ?? 0,
        'duration_ms' => $duration,
        'job' => self::class
      ]);
    } catch (\Exception $e) {
      Log::error("❌ Failed to warm all active users", [
        'error' => $e->getMessage(),
        'users_warmed' => $result['users'] ?? 0,
        'users_failed' => $result['failed'] ?? 0,
        'duration_ms' => isset($duration) ? $duration : null,
        'job' => self::class
      ]);
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("💥 WarmAllActiveUsersJob failed permanently", [
      'error' => $exception->getMessage(),
      'job' => self::class
    ]);
  }
}

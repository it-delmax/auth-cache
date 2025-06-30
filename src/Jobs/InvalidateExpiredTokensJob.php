<?php

namespace ItDelmax\AuthCache\Jobs;

use ItDelmax\AuthCache\Services\TokenCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvalidateExpiredTokensJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $timeout = 300; // 5 minuta
  public $tries = 2;

  public function __construct()
  {
    $this->onQueue(config('auth-cache.queue', 'cache'));
  }

  public function handle(TokenCacheService $cacheService): void
  {
    try {
      $startTime = microtime(true);
      $count = $cacheService->invalidateExpiredTokens();
      $duration = round((microtime(true) - $startTime) * 1000, 2);

      Log::info("🧹 Expired tokens cache invalidated", [
        'tokens_invalidated' => $count,
        'duration_ms' => $duration,
        'job' => self::class
      ]);
    } catch (\Exception $e) {
      Log::error("❌ Failed to invalidate expired tokens", [
        'error' => $e->getMessage(),
        'job' => self::class
      ]);
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("💥 InvalidateExpiredTokensJob failed permanently", [
      'error' => $exception->getMessage(),
      'job' => self::class
    ]);
  }
}

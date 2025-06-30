<?php

namespace ItDelmax\AuthCache\Jobs;

use ItDelmax\AuthCache\Services\TokenCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WarmUserCacheJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $timeout = 300; // 5 minuta
  public $tries = 3;

  protected int $userId;

  public function __construct(int $userId)
  {
    $this->userId = $userId;
    $this->onQueue(config('auth-cache.queue', 'cache'));
  }

  public function handle(TokenCacheService $cacheService): void
  {
    try {
      $success = $cacheService->warmUserCache($this->userId);

      if ($success) {
        Log::info("✅ User cache warmed successfully", [
          'user_id' => $this->userId,
          'job' => self::class
        ]);
      } else {
        Log::warning("⚠️ Failed to warm user cache - user not found", [
          'user_id' => $this->userId,
          'job' => self::class
        ]);
      }
    } catch (\Exception $e) {
      Log::error("❌ User cache warming failed", [
        'user_id' => $this->userId,
        'error' => $e->getMessage(),
        'job' => self::class
      ]);
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("💥 WarmUserCacheJob failed permanently", [
      'user_id' => $this->userId,
      'error' => $exception->getMessage(),
      'job' => self::class
    ]);
  }
}

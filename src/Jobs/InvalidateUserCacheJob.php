<?php

namespace ItDelmax\AuthCache\Jobs;

use ItDelmax\AuthCache\Services\TokenCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvalidateUserCacheJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $timeout = 120; // 2 minuta
  public $tries = 3;

  protected int $userId;
  protected bool $invalidateAllUserData;

  public function __construct(int $userId, bool $invalidateAllUserData = false)
  {
    $this->userId = $userId;
    $this->invalidateAllUserData = $invalidateAllUserData;
    $this->onQueue(config('auth-cache.queue', 'cache'));
  }

  public function handle(TokenCacheService $cacheService): void
  {
    try {
      if ($this->invalidateAllUserData) {
        $cacheService->invalidateAllUserData($this->userId);

        Log::info("🧹 All user data cache invalidated", [
          'user_id' => $this->userId,
          'job' => self::class
        ]);
      } else {
        $cacheService->invalidateUser($this->userId);
        $cacheService->invalidateUserAccess($this->userId);

        Log::info("🧹 User cache invalidated", [
          'user_id' => $this->userId,
          'job' => self::class
        ]);
      }
    } catch (\Exception $e) {
      Log::error("❌ User cache invalidation failed", [
        'user_id' => $this->userId,
        'invalidate_all' => $this->invalidateAllUserData,
        'error' => $e->getMessage(),
        'job' => self::class
      ]);
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("💥 InvalidateUserCacheJob failed permanently", [
      'user_id' => $this->userId,
      'invalidate_all' => $this->invalidateAllUserData,
      'error' => $exception->getMessage(),
      'job' => self::class
    ]);
  }
}

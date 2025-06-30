<?php

namespace ItDelmax\AuthCache\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use ItDelmax\AuthCache\Models\DmxApi;
use ItDelmax\AuthCache\Models\DmxApiUser;
use ItDelmax\AuthCache\Models\PersonalAccessToken;
use ItDelmax\AuthCache\Models\User;

class TokenCacheService
{
  protected CacheRepository $cache;

  protected $userTTL;
  protected $tokenTTL;
  protected $apiAccessTTL;
  protected $apiSlugTTL;

  public function __construct()
  {
    $this->cache = Cache::store('auth_cache');

    $this->userTTL = config('auth-cache.ttl.user', 6 * 60 * 60);
    $this->tokenTTL = config('auth-cache.ttl.token', 12 * 60 * 60);
    $this->apiAccessTTL = config('auth-cache.ttl.api_access', 24 * 60 * 60);
    $this->apiSlugTTL = config('auth-cache.ttl.api_slug', 24 * 60 * 60);
  }

  public function cacheToken(PersonalAccessToken $token): void
  {
    $prefix = config('auth-cache.prefixes.token', 'token:');
    $this->cache->put("{$prefix}{$token->token}", [
      'user_id' => $token->tokenable_id,
      'token_id' => $token->id,
      'abilities' => $token->abilities,
      'expires_at' => $token->expires_at,
    ], $this->tokenTTL);
  }

  public function getCachedToken(string $tokenHash): ?array
  {
    $prefix = config('auth-cache.prefixes.token', 'token:');
    return $this->cache->get("{$prefix}{$tokenHash}");
  }

  public function invalidateToken(string $tokenHash): void
  {
    $prefix = config('auth-cache.prefixes.token', 'token:');
    $this->cache->forget("{$prefix}{$tokenHash}");
  }

  public function cacheUserApiAccess(int $userId): void
  {
    $access = DmxApiUser::where('USER_ID', $userId)->active()->notExpired()->get();

    $prefix = config('auth-cache.prefixes.api_access', 'api_access:');
    $this->cache->put("{$prefix}{$userId}", $access, $this->apiAccessTTL);
  }

  public function getUserApiAccess(int $userId)
  {
    $prefix = config('auth-cache.prefixes.api_access', 'api_access:');
    return $this->cache->get("{$prefix}{$userId}");
  }

  public function invalidateUserAccess(int $userId): void
  {
    $prefix = config('auth-cache.prefixes.api_access', 'api_access:');
    $this->cache->forget("{$prefix}{$userId}");
  }

  public function getApiBySlug(string $slug)
  {
    $prefix = config('auth-cache.prefixes.api_slug', 'api_slug:');
    return $this->cache->remember("{$prefix}{$slug}", $this->apiSlugTTL, function () use ($slug) {
      return DmxApi::active()->bySlug($slug)->first();
    });
  }

  public function invalidateApiBySlug(string $slug): void
  {
    $prefix = config('auth-cache.prefixes.api_slug', 'api_slug:');
    $this->cache->forget("{$prefix}{$slug}");
  }

  public function cacheUser(User $user): void
  {
    $prefix = config('auth-cache.prefixes.user', 'user:');
    $this->cache->put("{$prefix}{$user->user_id}", $user, $this->userTTL);
  }

  public function getCachedUser(int $userId): ?User
  {
    $prefix = config('auth-cache.prefixes.user', 'user:');
    return $this->cache->get("{$prefix}{$userId}");
  }

  public function invalidateUser(int $userId): void
  {
    $prefix = config('auth-cache.prefixes.user', 'user:');
    $this->cache->forget("{$prefix}{$userId}");
  }

  public function warmUserCache(int $userId): bool
  {
    try {
      $user = User::find($userId);
      if ($user) {
        $this->cacheUser($user);
        $this->cacheUserApiAccess($userId);
        return true;
      }
      return false;
    } catch (\Exception $e) {
      Log::error("Failed to warm user cache for user {$userId}: " . $e->getMessage());
      return false;
    }
  }

  public function warmTokenCache(string $tokenHash): bool
  {
    try {
      $token = PersonalAccessToken::where('token', hash('sha256', $tokenHash))->first();
      if ($token) {
        $this->cacheToken($token);
        return true;
      }
      return false;
    } catch (\Exception $e) {
      Log::error("Failed to warm token cache for token {$tokenHash}: " . $e->getMessage());
      return false;
    }
  }

  public function warmAllActiveTokens(): int
  {
    $count = 0;
    try {
      PersonalAccessToken::whereNull('expires_at')
        ->orWhere('expires_at', '>', now())
        ->chunk(100, function ($tokens) use (&$count) {
          foreach ($tokens as $token) {
            $this->cacheToken($token);
            $count++;
          }
        });
    } catch (\Exception $e) {
      Log::error("Failed to warm all active tokens: " . $e->getMessage());
    }
    return $count;
  }

  public function invalidateAllUserData(int $userId): void
  {
    $this->invalidateUser($userId);
    $this->invalidateUserAccess($userId);

    try {
      PersonalAccessToken::where('tokenable_id', $userId)
        ->whereNull('expires_at')
        ->orWhere('expires_at', '>', now())
        ->get()
        ->each(function ($token) {
          $this->invalidateToken($token->token);
        });
    } catch (\Exception $e) {
      Log::error("Failed to invalidate user tokens for user {$userId}: " . $e->getMessage());
    }
  }

  public function invalidateExpiredTokens(): int
  {
    $count = 0;
    try {
      $expiredTokens = PersonalAccessToken::where('expires_at', '<', now())->get();

      foreach ($expiredTokens as $token) {
        $this->invalidateToken($token->token);
        $count++;
      }
    } catch (\Exception $e) {
      Log::error("Failed to invalidate expired tokens: " . $e->getMessage());
    }
    return $count;
  }

  public function warmAllActiveUsers(): array
  {
    $count = 0;
    $failed = 0;

    try {
      User::chunk(500, function ($users) use (&$count, &$failed) {
        foreach ($users as $user) {
          try {
            $this->cacheUser($user);
            $this->cacheUserApiAccess($user->user_id);
            $count++;
          } catch (\Exception $e) {
            $failed++;
            Log::error("Failed to warm cache for user {$user->user_id}: " . $e->getMessage());
          }
        }
      });

      Log::info("User cache warm-up completed: {$count} users cached, {$failed} failed");
    } catch (\Exception $e) {
      Log::error("Failed to warm all users cache: " . $e->getMessage());
    }

    return [
      'users' => $count,
      'failed' => $failed,
    ];
  }

  /**
   * Warms the cache for all active API users.
   * This method retrieves all active users with API access, caches their data,
   * @return array
   */
  public function warmApiUsers(): array
  {
    $count = 0;
    $failed = 0;

    try {
      $userIds = DmxApiUser::active()
        ->notExpired()
        ->distinct()
        ->pluck('USER_ID');

      User::whereIn('user_id', $userIds)
        ->chunk(500, function ($users) use (&$count, &$failed) {
          foreach ($users as $user) {
            try {
              $this->cacheUser($user);
              $this->cacheUserApiAccess($user->user_id);
              $count++;
            } catch (\Exception $e) {
              $failed++;
              Log::error("Failed to warm cache for user {$user->user_id}: " . $e->getMessage());
            }
          }
        });

      Log::info("API users cache warm-up completed: {$count} users cached, {$failed} failed");
    } catch (\Exception $e) {
      Log::error("Failed to warm API users cache: " . $e->getMessage());
    }

    return [
      'users' => $count,
      'failed' => $failed,
    ];
  }

  public function getCacheConfiguration(): array
  {
    return [
      'user_ttl' => $this->userTTL,
      'token_ttl' => $this->tokenTTL,
      'api_access_ttl' => $this->apiAccessTTL,
      'api_slug_ttl' => $this->apiSlugTTL,
    ];
  }

  public function flushAllCache(): void
  {
    try {
      Log::info("Cache flush requested - implementing pattern-based clearing would be needed");
    } catch (\Exception $e) {
      Log::error("Failed to flush cache: " . $e->getMessage());
    }
  }
}

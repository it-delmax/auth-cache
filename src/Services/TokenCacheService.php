<?php

namespace ItDelmax\AuthCache\Services;

use ItDelmax\AuthCache\Models\EtgApi;
use Illuminate\Support\Facades\Cache;
use ItDelmax\AuthCache\Models\PersonalAccessToken;
use ItDelmax\AuthCache\Models\EtgApiUser;
use ItDelmax\AuthCache\Models\User;

class TokenCacheService
{
    protected $userTTL = 6 * 60 * 60; // 6 sati
    protected $tokenTTL = 12 * 60 * 60; // 12 sati
    protected $apiAccessTTL = 24 * 60 * 60; // 24 sata (retko se menja!)
    protected $apiSlugTTL = 24 * 60 * 60; // 24 sata (skoro nikad se ne menja)

    /**
     * Cache keys prefixes
     */
    // const USER_KEY_PREFIX = 'sanctum:user:';
    // const USER_EMAIL_KEY_PREFIX = 'sanctum:user:email:';
    // const TOKEN_KEY_PREFIX = 'sanctum:token:';
    // const USER_TOKENS_KEY_PREFIX = 'sanctum:user:tokens:';

    public function cacheToken(PersonalAccessToken $token): void
    {
        Cache::put("token:{$token->token}", [
            'user_id' => $token->tokenable_id,
            'token_id' => $token->id,
            'abilities' => $token->abilities,
            'expires_at' => $token->expires_at,
        ], $this->tokenTTL);
    }

    public function getCachedToken(string $tokenHash): ?array
    {
        return Cache::get("token:{$tokenHash}");
    }

    public function invalidateToken(string $tokenHash): void
    {
        Cache::forget("token:{$tokenHash}");
    }

    public function cacheUserApiAccess(int $userId): void
    {
        $access = EtgApiUser::where('USER_ID', $userId)->active()->notExpired()->get();

        Cache::put("api_access:{$userId}", $access, $this->apiAccessTTL);
    }

    public function getUserApiAccess(int $userId)
    {
        return Cache::get("api_access:{$userId}");
    }

    public function invalidateUserAccess(int $userId): void
    {
        Cache::forget("api_access:{$userId}");
    }

    public function getApiBySlug(string $slug)
    {
        return Cache::remember("api_slug:{$slug}", $this->apiSlugTTL, function () use ($slug) {
            return EtgApi::active()->bySlug($slug)->first();
        });
    }

    public function invalidateApiBySlug(string $slug): void
    {
        Cache::forget("api_slug:{$slug}");
    }

    public function cacheUser(User $user): void
    {
        if ($user) {
            Cache::put("user:{$user->user_id}", $user, $this->userTTL);
        }
    }

    public function getCachedUser(int $userId): ?User
    {
        return Cache::get("user:{$userId}");
    }

    public function invalidateUser(int $userId): void
    {
        Cache::forget("user:{$userId}");
    }

    // ==================== CACHE WARMING METHODS ====================

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
            \Log::error("Failed to warm user cache for user {$userId}: " . $e->getMessage());
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
            \Log::error("Failed to warm token cache for token {$tokenHash}: " . $e->getMessage());
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
            \Log::error("Failed to warm all active tokens: " . $e->getMessage());
        }
        return $count;
    }

    // ==================== BULK INVALIDATION METHODS ====================

    public function invalidateAllUserData(int $userId): void
    {
        $this->invalidateUser($userId);
        $this->invalidateUserAccess($userId);
        
        // Invalidate all user tokens
        try {
            PersonalAccessToken::where('tokenable_id', $userId)
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now())
                ->get()
                ->each(function ($token) {
                    $this->invalidateToken($token->token);
                });
        } catch (\Exception $e) {
            \Log::error("Failed to invalidate user tokens for user {$userId}: " . $e->getMessage());
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
            \Log::error("Failed to invalidate expired tokens: " . $e->getMessage());
        }
        return $count;
    }

    // ==================== CACHE STATISTICS ====================

    public function getCacheStats(): array
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
            // Ne možemo koristiti Cache::flush() jer bi obrisao sve
            // Umesto toga, moramo brisati po pattern-u
            \Log::info("Cache flush requested - implementing pattern-based clearing would be needed");
        } catch (\Exception $e) {
            \Log::error("Failed to flush cache: " . $e->getMessage());
        }
    }
}

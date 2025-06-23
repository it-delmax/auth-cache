<?php

namespace ItDelmax\AuthCache\Services;

use ItDelmax\AuthCache\Models\EtgApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ItDelmax\AuthCache\Models\PersonalAccessToken;
use ItDelmax\AuthCache\Models\EtgApiUser;
use ItDelmax\AuthCache\Models\User;

class TokenCacheService
{
    protected $userTTL;
    protected $tokenTTL;
    protected $apiAccessTTL;
    protected $apiSlugTTL;

    public function __construct()
    {
        $this->userTTL = config('auth-cache.ttl.user', 6 * 60 * 60);
        $this->tokenTTL = config('auth-cache.ttl.token', 12 * 60 * 60);
        $this->apiAccessTTL = config('auth-cache.ttl.api_access', 24 * 60 * 60);
        $this->apiSlugTTL = config('auth-cache.ttl.api_slug', 24 * 60 * 60);
    }

    /**
     * Cache keys prefixes
     */
    // const USER_KEY_PREFIX = 'sanctum:user:';
    // const USER_EMAIL_KEY_PREFIX = 'sanctum:user:email:';
    // const TOKEN_KEY_PREFIX = 'sanctum:token:';
    // const USER_TOKENS_KEY_PREFIX = 'sanctum:user:tokens:';

    public function cacheToken(PersonalAccessToken $token): void
    {
        $prefix = config('auth-cache.prefixes.token', 'token:');
        Cache::put("{$prefix}{$token->token}", [
            'user_id' => $token->tokenable_id,
            'token_id' => $token->id,
            'abilities' => $token->abilities,
            'expires_at' => $token->expires_at,
        ], $this->tokenTTL);
    }

    public function getCachedToken(string $tokenHash): ?array
    {
        $prefix = config('auth-cache.prefixes.token', 'token:');
        return Cache::get("{$prefix}{$tokenHash}");
    }

    public function invalidateToken(string $tokenHash): void
    {
        $prefix = config('auth-cache.prefixes.token', 'token:');
        Cache::forget("{$prefix}{$tokenHash}");
    }

    public function cacheUserApiAccess(int $userId): void
    {
        $access = EtgApiUser::where('USER_ID', $userId)->active()->notExpired()->get();

        $prefix = config('auth-cache.prefixes.api_access', 'api_access:');
        Cache::put("{$prefix}{$userId}", $access, $this->apiAccessTTL);
    }

    public function getUserApiAccess(int $userId)
    {
        $prefix = config('auth-cache.prefixes.api_access', 'api_access:');
        return Cache::get("{$prefix}{$userId}");
    }

    public function invalidateUserAccess(int $userId): void
    {
        $prefix = config('auth-cache.prefixes.api_access', 'api_access:');
        Cache::forget("{$prefix}{$userId}");
    }

    public function getApiBySlug(string $slug)
    {
        $prefix = config('auth-cache.prefixes.api_slug', 'api_slug:');
        return Cache::remember("{$prefix}{$slug}", $this->apiSlugTTL, function () use ($slug) {
            return EtgApi::active()->bySlug($slug)->first();
        });
    }

    public function invalidateApiBySlug(string $slug): void
    {
        $prefix = config('auth-cache.prefixes.api_slug', 'api_slug:');
        Cache::forget("{$prefix}{$slug}");
    }

    public function cacheUser(User $user): void
    {
        if ($user) {
            $prefix = config('auth-cache.prefixes.user', 'user:');
            Cache::put("{$prefix}{$user->user_id}", $user, $this->userTTL);
        }
    }

    public function getCachedUser(int $userId): ?User
    {
        $prefix = config('auth-cache.prefixes.user', 'user:');
        return Cache::get("{$prefix}{$userId}");
    }

    public function invalidateUser(int $userId): void
    {
        $prefix = config('auth-cache.prefixes.user', 'user:');
        Cache::forget("{$prefix}{$userId}");
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
            Log::info("Cache flush requested - implementing pattern-based clearing would be needed");
        } catch (\Exception $e) {
            Log::error("Failed to flush cache: " . $e->getMessage());
        }
    }
}

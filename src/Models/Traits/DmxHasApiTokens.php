<?php

namespace ItDelmax\AuthCache\Models\Traits;

use Laravel\Sanctum\DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Str;
use ItDelmax\AuthCache\Models\DmxApiUser;

trait DmxHasApiTokens
{
  public function createDmxToken(int|string $apiId, string $tokenName, ?\DateTimeInterface $expiresAt = null): NewAccessToken
  {
    $abilities = $this->getAbilitiesForApi($apiId);

    /** @var NewAccessToken $token */
    $token = $this->createToken($tokenName, $abilities, $expiresAt);
    $cache = app(new \ItDelmax\AuthCache\Services\TokenCacheService());
    $cache->cacheToken($token);

    return $token;
  }

  public function getAbilitiesForApi(int|string $apiId): array
  {
    if (is_numeric($apiId)) {
      $apiId = (int) $apiId;

      $apiUser = DmxApiUser::query()
        ->where('USER_ID', $this->getKey())
        ->where('API_ID', $apiId)
        ->where('IS_ACTIVE', true)
        ->where(function ($q) {
          $q->whereNull('EXPIRES_AT')->orWhere('EXPIRES_AT', '>', now());
        })
        ->whereHas('api', fn($q) => $q->where('IS_ACTIVE', true))
        ->first();
    } elseif (is_string($apiId)) {
      $apiSlug = Str::of($apiId)->trim()->lower();

      $apiUser = DmxApiUser::query()
        ->where('USER_ID', $this->getKey())
        ->where('IS_ACTIVE', true)
        ->where(function ($q) {
          $q->whereNull('EXPIRES_AT')->orWhere('EXPIRES_AT', '>', now());
        })
        ->whereHas('api', fn($q) => $q->where('SLUG', $apiSlug)->where('IS_ACTIVE', true))
        ->with('api')
        ->get()
        ->firstWhere(fn($u) => Str::lower(optional($u->api)->SLUG) === $apiSlug);
    } else {
      throw new \InvalidArgumentException('API ID must be a string or an integer.');
    }

    return $apiUser ? ($apiUser->ABILITIES ?? ['*']) : [];
  }
}

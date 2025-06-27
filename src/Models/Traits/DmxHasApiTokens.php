<?php

namespace ItDelmax\AuthCache\Models\Traits;

use Laravel\Sanctum\DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Str;
use ItDelmax\AuthCache\Models\DmxApiUser;

trait DmxHasApiTokens
{
  public function createDmxToken($apiId, string $tokenName, ?\DateTimeInterface $expiresAt = null): NewAccessToken
  {
    $apiUser = DmxApiUser::query()
      ->where('USER_ID', $this->getKey())
      ->where('API_ID', $apiId)
      ->where('IS_ACTIVE', true)
      ->where(function ($q) {
        $q->whereNull('EXPIRES_AT')->orWhere('EXPIRES_AT', '>', now());
      })->first();

    $abilities = $apiUser->ABILITIES ?? ['*']; // default to all abilities


    /** @var NewAccessToken $token */
    $token = $this->createToken($tokenName, $abilities, $expiresAt);
    $cache = app(new \ItDelmax\AuthCache\Services\TokenCacheService());
    $cache->cacheToken($token);

    return $token;
  }
}

<?php

namespace ItDelmax\AuthCache\Providers;

use ItDelmax\AuthCache\Services\TokenCacheService;

class CacheEloquentUserProvider extends \Illuminate\Auth\EloquentUserProvider
{
  protected TokenCacheService $cacheService;

  public function __construct($hasher, $model, TokenCacheService $cacheService)
  {
    parent::__construct($hasher, $model);
    $this->cacheService = $cacheService;
  }

  /**
   * Retrieve a user by their unique identifier with caching.
   */
  public function retrieveById($identifier)
  {
    // Try cache first
    $user = $this->cacheService->getCachedUser($identifier);

    if ($user) {
      return $user;
    }

    // Fallback to database
    $user = $this->createModel()->newQuery()->find($identifier);

    if ($user) {
      $this->cacheService->cacheUser($user);
    }

    return $user;
  }

  /**
   * Retrieve a user by their unique identifier and "remember me" token.
   */
  public function retrieveByToken($identifier, $token)
  {
    // For remember tokens, we should check database directly
    return parent::retrieveByToken($identifier, $token);
  }

  /**
   * Retrieve a user by the given credentials.
   */
  public function retrieveByCredentials(array $credentials)
  {
    if (empty($credentials) || (count($credentials) === 1 && str_contains($this->firstCredentialKey($credentials), 'password'))) {
      return null;
    }

    // For credential-based lookup, use database directly
    $query = $this->newModelQuery();

    foreach ($credentials as $key => $value) {
      if (str_contains($key, 'password')) {
        continue;
      }

      if (is_array($value) || $value instanceof \Illuminate\Contracts\Support\Arrayable) {
        $query->whereIn($key, $value);
      } else {
        $query->where($key, $value);
      }
    }

    $user = $query->first();

    if ($user) {
      $this->cacheService->cacheUser($user);
    }

    return $user;
  }

  /**
   * Get the first key from the credential array.
   */
  protected function firstCredentialKey(array $credentials)
  {
    foreach ($credentials as $key => $value) {
      return $key;
    }

    return null;
  }
}

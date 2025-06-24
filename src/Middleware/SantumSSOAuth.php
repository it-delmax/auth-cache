<?php

// ==========================================
// MIDDLEWARE ZA API/PORTAL
// ==========================================

namespace ItDelmax\AuthCache\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use ItDelmax\AuthCache\Services\TokenCacheService;

class SanctumSSOAuth
{
  protected $cacheService;

  public function __construct(TokenCacheService $cacheService)
  {
    $this->cacheService = $cacheService;
  }

  public function handle(Request $request, Closure $next)
  {
    $token = $request->bearerToken();

    if (!$token) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Brza provera - da li je Sanctum token
    if (!str_contains($token, '|')) {
      return response()->json(['message' => 'Invalid token format'], 401);
    }

    // Koristi postojeću cache logiku
    [$id, $token] = explode('|', $token, 2);
    $hashedToken = hash('sha256', $token);

    // Check cache first
    $cachedToken = $this->cacheService->getCachedToken($hashedToken);

    if ($cachedToken) {
      $this->authenticateFromCache($cachedToken, $request);
      return $next($request);
    }

    // Fallback to database
    $accessToken = PersonalAccessToken::find($id);

    if (!$accessToken || !hash_equals($accessToken->token, $hashedToken)) {
      return response()->json(['message' => 'Invalid token'], 401);
    }

    // Cache for next time
    $this->cacheService->cacheToken($accessToken);

    // Authenticate
    $user = $accessToken->tokenable;
    auth()->login($user);
    $request->setUserResolver(fn() => $user);

    return $next($request);
  }

  protected function authenticateFromCache($cachedToken, Request $request)
  {
    $user = $this->cacheService->getCachedUser($cachedToken['user_id']);

    if (!$user) {
      $user = \ItDelmax\AuthCache\Models\User::with(['partner', 'apiAccess'])
        ->find($cachedToken['user_id']);
      $this->cacheService->cacheUser($user);
    }

    auth()->login($user);
    $request->setUserResolver(fn() => $user);
  }
}

<?php

namespace ItDelmax\AuthCache\Middleware;

use Closure;
use Illuminate\Http\Request;
use ItDelmax\AuthCache\Services\TokenCacheService;
use ItDelmax\AuthCache\Models\User;
use Illuminate\Support\Facades\Auth;
use ItDelmax\AuthCache\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class CachedSanctumAuth
{
  public function handle(Request $request, Closure $next)
  {
    $token = $request->bearerToken();
    if (!$token) {
      return response()->json(['message' => 'Unauthorized (missing token)'], 401);
    }

    $parts = explode('|', $token, 2);
    if (count($parts) !== 2) {
      return response()->json(['message' => 'Invalid token format'], 401);
    }

    $plainTextToken = $parts[1];
    $tokenHash = hash('sha256', $plainTextToken);

    $cache = app(TokenCacheService::class);

    $cachedToken = $cache->getCachedToken($tokenHash);
    if ($cachedToken) {
      $user = $cache->getCachedUser($cachedToken['user_id']);
      if (!$user) {
        $user = User::with('partner')->find($cachedToken['user_id']);
        if (!$user) {
          return response()->json(['message' => 'User not found'], 401);
        }
        $cache->cacheUser($user);
      }

      Auth::setUser($user);
      $request->setUserResolver(fn() => $user);
      return $next($request);
    }

    $pat = PersonalAccessToken::where('token', $tokenHash)->first();
    if ($pat) {
      $cache->cacheToken($pat);

      // 🧠 Bitno: eager-load partner
      $user = User::with('partner')->find($pat->tokenable_id);
      if (!$user) {
        return response()->json(['message' => 'User not found'], 401);
      }

      $cache->cacheUser($user);

      // ✅ Garantujemo da je ovo isti model koji će koristiti ceo request
      Auth::setUser($user);
      $request->setUserResolver(fn() => $user);

      Log::debug('✅ Keširani korisnik postavljen sa partner relacijom: ' . $user->partner?->NAZIV);

      return $next($request);
    }
    return response()->json(['message' => 'Unauthorized'], 401);
  }
}

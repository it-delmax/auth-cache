<?php

namespace ItDelmax\AuthCache\Middleware;

use ItDelmax\AuthCache\Services\TokenCacheService;
use Closure;
use Illuminate\Http\Request;

use ItDelmax\AuthCache\Models\EtgApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckCachedEtgApiAccess
{
    private TokenCacheService $apiAccessService;

    public function __construct(TokenCacheService $apiAccessService)
    {
        $this->apiAccessService = $apiAccessService;
    }

    public function handle(Request $request, Closure $next, string $slug)
    {
        $user = Auth::user(); // već postavljen preko Auth::setUser()

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $api = $this->apiAccessService->getApiBySlug($slug);

        if (!$api) {
            return response()->json(['message' => 'API not found'], 404);
        }

        $this->apiAccessService->cacheUser($user);

        $accessList = $this->apiAccessService->getUserApiAccess($user->user_id);

        if ($accessList && $accessList->contains('API_ID', $api->ID)) {
            return $next($request);
        }

        if (!$accessList) {
            $this->apiAccessService->cacheUserApiAccess($user->user_id);

            $accessList = $this->apiAccessService->getUserApiAccess($user->user_id);

            if ($accessList && $accessList->contains('API_ID', $api->ID)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}

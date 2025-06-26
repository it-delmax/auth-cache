<?php

namespace ItDelmax\AuthCache\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use ItDelmax\AuthCache\Models\PersonalAccessToken;
use ItDelmax\AuthCache\Models\DmxApi;
use ItDelmax\AuthCache\Models\DmxApiUser;
use ItDelmax\AuthCache\Services\TokenCacheService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TokenManagementController extends Controller
{
    protected TokenCacheService $cacheService;

    public function __construct(TokenCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function getUserTokenInfo(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tokens = $user->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json([
            'user_id' => $user->user_id,
            'tokens_count' => $tokens->count(),
            'tokens' => $tokens,
            'api_access' => $user->getAccessibleApis()
        ]);
    }

    public function createToken(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'sometimes|array',
            'expires_at' => 'sometimes|date|after:now'
        ]);

        $user = Auth::user();
        $token = $user->createToken(
            $request->name,
            $request->abilities ?? ['*'],
            $request->expires_at ? now()->parse($request->expires_at) : null
        );

        // Cache the new token
        $this->cacheService->cacheToken($token->accessToken);

        Log::info("✅ New token created", [
            'user_id' => $user->user_id,
            'token_name' => $request->name,
            'abilities' => $request->abilities ?? ['*']
        ]);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
            'name' => $request->name,
            'abilities' => $request->abilities ?? ['*'],
            'expires_at' => $token->accessToken->expires_at
        ], 201);
    }

    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        $user = Auth::user();
        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 404);
        }

        // Invalidate from cache
        $this->cacheService->invalidateToken($token->token);

        // Delete from database
        $token->delete();

        Log::info("🗑️ Token revoked", [
            'user_id' => $user->user_id,
            'token_id' => $tokenId
        ]);

        return response()->json(['message' => 'Token revoked successfully']);
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tokens = $user->tokens()->get();

        foreach ($tokens as $token) {
            $this->cacheService->invalidateToken($token->token);
        }

        $count = $user->tokens()->delete();

        Log::info("🗑️ All tokens revoked", [
            'user_id' => $user->user_id,
            'revoked_count' => $count
        ]);

        return response()->json([
            'message' => "All {$count} tokens revoked successfully",
            'revoked_count' => $count
        ]);
    }

    public function listTokens(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tokens = $user->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json([
            'tokens' => $tokens,
            'total_count' => $tokens->count()
        ]);
    }

    public function requestApiAccess(Request $request): JsonResponse
    {
        $request->validate([
            'api_slug' => 'required|string|exists:DMX_API,SLUG',
            'reason' => 'required|string|max:500'
        ]);

        $user = Auth::user();
        $api = DmxApi::where('SLUG', $request->api_slug)->where('IS_ACTIVE', 1)->first();

        if (!$api) {
            return response()->json(['error' => 'API not found or inactive'], 404);
        }

        // Check if access already exists
        $existingAccess = DmxApiUser::where('USER_ID', $user->user_id)
            ->where('API_ID', $api->API_ID)
            ->where('IS_ACTIVE', 1)
            ->first();

        if ($existingAccess) {
            return response()->json(['error' => 'Access already granted'], 409);
        }

        Log::info("📝 API access requested", [
            'user_id' => $user->user_id,
            'api_slug' => $request->api_slug,
            'reason' => $request->reason
        ]);

        return response()->json([
            'message' => 'API access request submitted successfully',
            'api_slug' => $request->api_slug,
            'status' => 'pending_approval'
        ]);
    }

    public function getAvailableApis(Request $request): JsonResponse
    {
        $user = Auth::user();
        $availableApis = DmxApi::where('IS_ACTIVE', 1)->get(['API_ID', 'NAME', 'SLUG', 'DESCRIPTION']);
        $userApis = $user->getAccessibleApis()->pluck('API_ID')->toArray();

        $apis = $availableApis->map(function ($api) use ($userApis) {
            return [
                'api_id' => $api->API_ID,
                'name' => $api->NAME,
                'slug' => $api->SLUG,
                'description' => $api->DESCRIPTION,
                'has_access' => in_array($api->API_ID, $userApis)
            ];
        });

        return response()->json([
            'available_apis' => $apis,
            'user_accessible_count' => count($userApis)
        ]);
    }
}

<?php

namespace ItDelmax\AuthCache\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use ItDelmax\AuthCache\Models\DmxApi;
use ItDelmax\AuthCache\Models\DmxApiUser;
use ItDelmax\AuthCache\Models\User;
use ItDelmax\AuthCache\Services\TokenCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApiAdminController extends Controller
{
    protected TokenCacheService $cacheService;

    public function __construct(TokenCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function getApiStats(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_apis' => DmxApi::count(),
                'active_apis' => DmxApi::where('IS_ACTIVE', 1)->count(),
                'total_users' => User::count(),
                'users_with_api_access' => DmxApiUser::distinct('USER_ID')->count(),
                'active_api_grants' => DmxApiUser::where('IS_ACTIVE', 1)->count(),
                'expired_grants' => DmxApiUser::where('EXPIRES_AT', '<', now())->count(),
                'cache_stats' => $this->cacheService->getCacheStats()
            ];

            // API usage breakdown
            $apiUsage = DmxApi::withCount(['apiUsers' => function ($query) {
                $query->where('IS_ACTIVE', 1);
            }])->get(['API_ID', 'NAME', 'SLUG']);

            $stats['api_usage'] = $apiUsage->map(function ($api) {
                return [
                    'api_id' => $api->API_ID,
                    'name' => $api->NAME,
                    'slug' => $api->SLUG,
                    'active_users' => $api->api_users_count
                ];
            });

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error("Failed to get API stats: " . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve stats'], 500);
        }
    }

    public function grantAccess(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:USERS_VIEW,user_id',
            'api_slug' => 'required|string|exists:DMX_API,SLUG',
            'expires_at' => 'nullable|date|after:now',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            $user = User::find($request->user_id);
            $api = DmxApi::where('SLUG', $request->api_slug)->where('IS_ACTIVE', 1)->first();

            if (!$api) {
                return response()->json(['error' => 'API not found or inactive'], 404);
            }

            // Check if access already exists
            $existingAccess = DmxApiUser::where('USER_ID', $request->user_id)
                ->where('API_ID', $api->API_ID)
                ->where('IS_ACTIVE', 1)
                ->first();

            if ($existingAccess) {
                return response()->json(['error' => 'User already has access to this API'], 409);
            }

            // Grant access
            $access = DmxApiUser::create([
                'USER_ID' => $request->user_id,
                'API_ID' => $api->API_ID,
                'IS_ACTIVE' => 1,
                'EXPIRES_AT' => $request->expires_at,
                'GRANTED_AT' => now(),
                'GRANTED_BY' => auth()->id(),
                'NOTE' => $request->note
            ]);

            // Invalidate user's cache to force refresh
            $this->cacheService->invalidateUserAccess($request->user_id);

            Log::info("✅ API access granted", [
                'user_id' => $request->user_id,
                'api_slug' => $request->api_slug,
                'granted_by' => auth()->id(),
                'expires_at' => $request->expires_at
            ]);

            return response()->json([
                'message' => 'API access granted successfully',
                'access_id' => $access->API_USER_ID,
                'user_id' => $request->user_id,
                'api_slug' => $request->api_slug,
                'expires_at' => $request->expires_at
            ], 201);
        } catch (\Exception $e) {
            Log::error("Failed to grant API access: " . $e->getMessage());
            return response()->json(['error' => 'Failed to grant access'], 500);
        }
    }

    public function revokeAccess(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'api_slug' => 'required|string',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $api = DmxApi::where('SLUG', $request->api_slug)->first();
            if (!$api) {
                return response()->json(['error' => 'API not found'], 404);
            }

            $access = DmxApiUser::where('USER_ID', $request->user_id)
                ->where('API_ID', $api->API_ID)
                ->where('IS_ACTIVE', 1)
                ->first();

            if (!$access) {
                return response()->json(['error' => 'Access not found or already revoked'], 404);
            }

            // Revoke access
            $access->update([
                'IS_ACTIVE' => 0,
                'REVOKED_AT' => now(),
                'REVOKED_BY' => auth()->id(),
                'REVOKE_REASON' => $request->reason
            ]);

            // Invalidate user's cache
            $this->cacheService->invalidateUserAccess($request->user_id);

            Log::info("🚫 API access revoked", [
                'user_id' => $request->user_id,
                'api_slug' => $request->api_slug,
                'revoked_by' => auth()->id(),
                'reason' => $request->reason
            ]);

            return response()->json([
                'message' => 'API access revoked successfully',
                'user_id' => $request->user_id,
                'api_slug' => $request->api_slug
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to revoke API access: " . $e->getMessage());
            return response()->json(['error' => 'Failed to revoke access'], 500);
        }
    }

    public function getExpiringAccess(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365'
        ]);

        $days = $request->get('days', 30);
        $expiringDate = now()->addDays($days);

        try {
            $expiringAccess = DmxApiUser::with(['user', 'api'])
                ->where('IS_ACTIVE', 1)
                ->where('EXPIRES_AT', '<=', $expiringDate)
                ->where('EXPIRES_AT', '>', now())
                ->orderBy('EXPIRES_AT', 'asc')
                ->get();

            $result = $expiringAccess->map(function ($access) {
                return [
                    'access_id' => $access->API_USER_ID,
                    'user_id' => $access->USER_ID,
                    'user_name' => $access->user?->name,
                    'user_email' => $access->user?->email,
                    'api_name' => $access->api?->NAME,
                    'api_slug' => $access->api?->SLUG,
                    'expires_at' => $access->EXPIRES_AT,
                    'days_until_expiry' => now()->diffInDays($access->EXPIRES_AT)
                ];
            });

            return response()->json([
                'expiring_access' => $result,
                'total_count' => $result->count(),
                'days_filter' => $days
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get expiring access: " . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve expiring access'], 500);
        }
    }

    public function bulkExtendAccess(Request $request): JsonResponse
    {
        $request->validate([
            'access_ids' => 'required|array|min:1',
            'access_ids.*' => 'integer|exists:DMX_API_USERS,API_USER_ID',
            'extend_days' => 'required|integer|min:1|max:365',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            $updated = 0;
            $userIds = [];

            DB::transaction(function () use ($request, &$updated, &$userIds) {
                foreach ($request->access_ids as $accessId) {
                    $access = DmxApiUser::find($accessId);
                    if ($access && $access->IS_ACTIVE) {
                        $newExpiryDate = ($access->EXPIRES_AT ?
                            now()->parse($access->EXPIRES_AT) : now())
                            ->addDays($request->extend_days);

                        $access->update([
                            'EXPIRES_AT' => $newExpiryDate,
                            'EXTENDED_AT' => now(),
                            'EXTENDED_BY' => auth()->id(),
                            'EXTEND_NOTE' => $request->note
                        ]);

                        $userIds[] = $access->USER_ID;
                        $updated++;
                    }
                }
            });

            // Invalidate cache for affected users
            foreach (array_unique($userIds) as $userId) {
                $this->cacheService->invalidateUserAccess($userId);
            }

            Log::info("📅 Bulk access extension completed", [
                'extended_count' => $updated,
                'extend_days' => $request->extend_days,
                'extended_by' => auth()->id()
            ]);

            return response()->json([
                'message' => "Successfully extended {$updated} access grants",
                'extended_count' => $updated,
                'extend_days' => $request->extend_days
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to bulk extend access: " . $e->getMessage());
            return response()->json(['error' => 'Failed to extend access'], 500);
        }
    }
}

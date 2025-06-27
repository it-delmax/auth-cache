<?php

namespace ItDelmax\AuthCache\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use ItDelmax\AuthCache\Models\Branch;
use ItDelmax\AuthCache\Models\DmxApi;
use Laravel\Sanctum\DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ItDelmax\AuthCache\Models\DmxApiUser;

trait DmxApiAccess
{

  public function apiAccess(): HasMany
  {
    return $this->hasMany(DmxApiUser::class, 'USER_ID', 'user_id')
      ->where('IS_ACTIVE', 1)
      ->where(function ($query) {
        $query->whereNull('EXPIRES_AT')
          ->orWhere('EXPIRES_AT', '>', now());
      });
  }

  public function activeApiAccess(): HasMany
  {
    return $this->apiAccess(); // reuse
  }

  public function hasApiAccess($apiSlug): bool
  {
    return $this->activeApiAccess()
      ->whereHas('api', fn($q) => $q->where('SLUG', $apiSlug)->where('IS_ACTIVE', 1))
      ->exists();
  }

  public function getAccessibleApis()
  {
    return DmxApi::whereHas('apiUsers', function ($query) {
      $query->where('USER_ID', $this->user_id)
        ->where('IS_ACTIVE', 1)
        ->where(function ($q) {
          $q->whereNull('EXPIRES_AT')
            ->orWhere('EXPIRES_AT', '>', now());
        });
    })->where('IS_ACTIVE', 1)->get();
  }



  public function hasBranchAccess($branchId): bool
  {
    return $this->branchAccess()
      ->where('BRANCH_ID', $branchId)
      ->exists();
  }

  public function branchAccess(): HasMany
  {
    return $this->hasMany(Branch::class, 'USER_ID', 'user_id')
      ->where('IS_ACTIVE', 1)
      ->where(function ($query) {
        $query->whereNull('EXPIRES_AT')
          ->orWhere('EXPIRES_AT', '>', now());
      });
  }
}

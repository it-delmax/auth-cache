<?php

namespace ItDelmax\AuthCache\Models\Traits;


use Illuminate\Support\Facades\Cache;

trait CachesRelationships
{
    public function getCachedRelation(string $relation, int $ttlMinutes = 360): mixed
    {
        $cacheKey = $this->getRelationCacheKey($relation);

        return Cache::remember($cacheKey, now()->addMinutes($ttlMinutes), function () use ($relation) {
            return $this->loadMissing($relation)->getRelation($relation);
        });
    }

    public function forgetCachedRelation(string $relation): void
    {
        Cache::forget($this->getRelationCacheKey($relation));
    }

    protected function getRelationCacheKey(string $relation): string
    {
        $cacheKey = "relation:{$this->getTable()}:{$this->getKey()}:{$relation}";
        logger()->debug("🧠 Cache key for relation {$relation}: {$cacheKey}");
        return $cacheKey;
    }
}

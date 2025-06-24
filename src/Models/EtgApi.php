<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtgApi extends Model
{
  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';
  protected $table = 'ETG_APIS';
  protected $primaryKey = 'ID';
  protected $sequence = 'SEQ_ETG_APIS';

  protected $fillable = [
    'NAME',
    'SLUG',
    'DESCRIPTION',
    'API_VERSION',
    'BASE_URL',
    'DOCS_URL',
    'GUARD_NAME',
    'IS_ACTIVE',
    'TTL',
    'RATE_LIMIT_PER_MINUTE',
    'REQUIRES_APPROVAL'
  ];

  protected $casts = [
    'IS_ACTIVE' => 'boolean',
    'REQUIRES_APPROVAL' => 'boolean',
    'RATE_LIMIT_PER_MINUTE' => 'integer'
  ];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
    $this->table = config('auth_cache.tables.api_users') ?: parent::getTable();
  }

  public function apiUsers(): HasMany
  {
    return $this->hasMany(EtgApiUser::class, 'API_ID', 'ID');
  }

  public function activeApiUsers(): HasMany
  {
    return $this->hasMany(EtgApiUser::class, 'API_ID', 'ID')
      ->where('IS_ACTIVE', 1)
      ->where(function ($query) {
        $query->whereNull('EXPIRES_AT')
          ->orWhere('EXPIRES_AT', '>', now());
      });
  }

  public function scopeActive($query)
  {
    return $query->where('IS_ACTIVE', 1);
  }

  public function scopeBySlug($query, $slug)
  {
    return $query->where('SLUG', $slug);
  }

  public function scopeRequiresApproval($query)
  {
    return $query->where('REQUIRES_APPROVAL', 1);
  }

  public function getFullUrlAttribute(): string
  {
    return rtrim($this->BASE_URL, '/') . '/' . $this->API_VERSION;
  }

  public function getUserCountAttribute(): int
  {
    return $this->activeApiUsers()->count();
  }
}

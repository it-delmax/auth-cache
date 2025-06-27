<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use ItDelmax\AuthCache\Models\DmxApi;
use ItDelmax\AuthCache\Models\Traits\RecordsUserActivity;
use ItDelmax\AuthCache\Models\User;

class DmxApiUser extends Model
{
  use RecordsUserActivity;

  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';
  protected $table = 'DMX_API_USERS';
  protected $primaryKey = 'ID';
  protected $sequence = 'SEQ_DMX_API_USERS';

  protected $fillable = [
    'API_ID',
    'ABILITIES',
    'USER_ID',
    'IS_ACTIVE',
    'APPROVED_AT',
    'APPROVED_BY',
    'EXPIRES_AT',
    'RATE_LIMIT_PER_MINUTE',

  ];

  protected $casts = [
    'IS_ACTIVE' => 'boolean',
    'ABILITIES' => 'array',
    'APPROVED_BY' => 'integer',
    'APPROVED_AT' => 'datetime',
    'EXPIRES_AT' => 'datetime',
    'CREATED_AT' => 'datetime',
    'UPDATED_AT' => 'datetime',
    'RATE_LIMIT_PER_MINUTE' => 'integer'
  ];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
    $this->table = config('auth_cache.tables.api_users') ?: parent::getTable();
  }

  public function api(): BelongsTo
  {
    return $this->belongsTo(DmxApi::class, 'API_ID', 'ID');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'USER_ID', 'user_id');
  }

  public function approver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'APPROVED_BY', 'user_id');
  }

  public function scopeActive($query)
  {
    return $query->where('IS_ACTIVE', 1);
  }

  public function scopeNotExpired($query)
  {
    return $query->where(function ($q) {
      $q->whereNull('EXPIRES_AT')
        ->orWhere('EXPIRES_AT', '>', now());
    });
  }

  public function scopeExpired($query)
  {
    return $query->whereNotNull('EXPIRES_AT')
      ->where('EXPIRES_AT', '<=', now());
  }

  public function scopeForApi($query, $apiId)
  {
    return $query->where('API_ID', $apiId);
  }

  public function scopeForUser($query, $userId)
  {
    return $query->where('USER_ID', $userId);
  }

  public function hasAccess(): bool
  {
    return $this->IS_ACTIVE &&
      (!$this->EXPIRES_AT || $this->EXPIRES_AT->isFuture());
  }

  public function isExpired(): bool
  {
    return $this->EXPIRES_AT && $this->EXPIRES_AT->isPast();
  }

  public function getDaysUntilExpiryAttribute(): ?int
  {
    if (!$this->EXPIRES_AT) {
      return null;
    }

    return now()->diffInDays($this->EXPIRES_AT, false);
  }

  public function isApproved()
  {
    return $this->IS_ACTIVE && $this->APPROVED_AT && (!$this->EXPIRES_AT || $this->EXPIRES_AT->isFuture());
  }

  public function effectiveAbilities()
  {
    return $this->ABILITIES ?: $this->api->DEFAULT_ABILITIES;
  }

  public function toogleRevoked()
  {
    $this->IS_ACTIVE = $this->IS_ACTIVE == 1 ? 0 : 1;
    return $this->save();
  }

  public function markAsApproved()
  {
    $this->APPROVED_AT = now();
    $this->APPROVED_BY = Auth::id();
    return $this->save();
  }
}

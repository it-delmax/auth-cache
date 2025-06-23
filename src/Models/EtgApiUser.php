<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ItDelmax\AuthCache\Models\EtgApi;
use ItDelmax\AuthCache\Models\User;

class EtgApiUser extends Model
{
  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';
  protected $table = 'ETG_API_USERS';
  protected $primaryKey = 'ID';
  protected $sequence = 'SEQ_ETG_API_USERS';

  protected $fillable = [
    'API_ID',
    'USER_ID',
    'IS_ACTIVE',
    'APPROVED_AT',
    'APPROVED_BY',
    'EXPIRES_AT'
  ];

  protected $casts = [
    'IS_ACTIVE' => 'boolean',
    'APPROVED_AT' => 'datetime',
    'EXPIRES_AT' => 'datetime'
  ];

  public function api(): BelongsTo
  {
    return $this->belongsTo(EtgApi::class, 'API_ID', 'ID');
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
}
